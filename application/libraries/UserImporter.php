<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserImporter {

    //Caps how many rows a single spreadsheet may contribute. A 5 MB .xlsx can
    //decompress into an unbounded number of rows, so the size limit on upload
    //is not by itself a bound on the insert count.
    public const MAX_ROWS = 2000;

    //bcrypt ignores anything past 72 bytes, so longer passphrases are rejected
    //rather than silently truncated into something the user never typed.
    private const MAX_PASSWORD_LENGTH = 72;

    //Contact numbers are 10-11 digits. The signup form rule asks for exactly
    //10 (form_rules.php), but the seeded demo account stores 11, so both are
    //accepted here to match the data actually in the table.
    private const PHONE_PATTERN = '/^\d{10,11}$/';

    //Mirrors form_rules.php: only these two values are ever stored in
    //tbl_users.role, and manage_users.php treats 'admin' specially.
    private const ALLOWED_ROLES = ['user', 'admin'];

    //Column name => whether the sheet must supply it. The key order is also
    //the column order of the downloadable sample sheet.
    private const COLUMNS = [
        'username' => TRUE,
        'firstname' => TRUE,
        'middlename' => FALSE,
        'lastname' => TRUE,
        'email' => TRUE,
        'contactnumber' => TRUE,
        'password' => TRUE,
        'address' => FALSE,
        'role' => FALSE,
    ];

    //Shortest bcrypt hash: $2y$10$ plus 22 salt and 31 hash characters.
    private const BCRYPT_MIN_LENGTH = 60;

    /*
     * Reads a spreadsheet and returns ['users' => [...], 'errors' => [...]].
     * Each user carries the spreadsheet line number it came from; each error is
     * a ['row' => int, 'message' => string] pair. A malformed row is collected
     * rather than thrown, so one bad line never costs the rest of the batch.
     */
    public function parse(string $path): array
    {
        $sheet = $this->sheet($path);
        $highest = (int) $sheet->getHighestDataRow();

        //+1 for the header line, which does not count towards the cap.
        if ($highest > self::MAX_ROWS + 1) {
            throw new AppException('U04', ['max' => self::MAX_ROWS]);
        }

        $rows = $sheet->toArray();
        $header = $this->normaliseHeader($rows[0] ?? []);
        $indexes = $this->columnIndexes($header);

        $users = [];
        $errors = [];
        $seenUsernames = [];
        $seenEmails = [];

        for ($line = 2; $line <= $highest; $line++) {
            $cells = $rows[$line - 1] ?? NULL;

            if ($cells === NULL || $this->isBlankRow($cells)) {
                continue;
            }

            $built = $this->buildUser($cells, $indexes);

            if ($built['errors'] !== []) {
                $errors[] = ['row' => $line, 'message' => implode(' ', $built['errors'])];
                continue;
            }

            $user = $built['user'];

            //A later row repeating an earlier one in the same file would only
            //be caught as a duplicate once the first was already inserted.
            if (isset($seenUsernames[$user['username']])) {
                $errors[] = [
                    'row' => $line,
                    'message' => 'Username "' . $user['username'] . '" already appears on row ' . $seenUsernames[$user['username']] . '.',
                ];
                continue;
            }

            if (isset($seenEmails[$user['email']])) {
                $errors[] = [
                    'row' => $line,
                    'message' => 'Email "' . $user['email'] . '" already appears on row ' . $seenEmails[$user['email']] . '.',
                ];
                continue;
            }

            $seenUsernames[$user['username']] = $line;
            $seenEmails[$user['email']] = $line;

            $user['row'] = $line;
            $users[] = $user;
        }

        if ($users === [] && $errors === []) {
            throw new AppException('U03');
        }

        return ['users' => $users, 'errors' => $errors];
    }

    /*
     * Builds the downloadable sample sheet: the exact headers the importer
     * expects, in order, followed by two example rows.
     */
    public function template(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Users');

        $sheet->fromArray(array_keys(self::COLUMNS), NULL, 'A1');
        $sheet->fromArray([
            ['juan.delacruz', 'Juan', 'Reyes', 'Dela Cruz', 'juan.delacruz@example.com', '09170000001', 'password123', '019 Corrales Ext, Cagayan de Oro City', 'user'],
            ['maria.santos', 'Maria', '', 'Santos', 'maria.santos@example.com', '09170000002', 'password123', '', 'user'],
        ], NULL, 'A2');

        $last = $sheet->getHighestColumn();
        $headerStyle = $sheet->getStyle('A1:' . $last . '1');

        $headerStyle->getFont()->setBold(TRUE);
        $headerStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF0DCFF0');

        for ($i = 1; $i <= count(self::COLUMNS); $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(TRUE);
        }

        return $spreadsheet;
    }

    //Writes the sample sheet as an .xlsx to $path and returns that path.
    public function writeTemplate(string $path): string
    {
        $writer = new Xlsx($this->template());
        $writer->save($path);

        return $path;
    }

    /*
     * Loads the workbook through the read-only path, which skips style and
     * formula handling and keeps wide sheets cheap. A corrupt or unsupported
     * file surfaces as U01 instead of an uncaught reader exception.
     */
    private function sheet(string $path): Worksheet
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(TRUE);

            $spreadsheet = $reader->load($path);
        } catch (Throwable $e) {
            throw new AppException('U01', [], $e);
        }

        return $spreadsheet->getActiveSheet();
    }

    //Lowercases and trims the header row into column-position => column-name.
    //Worksheet::toArray() is numeric-indexed, so positions line up with the
    //per-row cell arrays used later.
    private function normaliseHeader(array $cells): array
    {
        $header = [];

        foreach ($cells as $letter => $value) {
            $name = strtolower(trim((string) $value));

            if ($name !== '') {
                $header[$letter] = $name;
            }
        }

        return $header;
    }

    //Resolves the column letters for the columns we understand and reports
    //which required ones the sheet is missing.
    private function columnIndexes(array $header): array
    {
        $indexes = [];
        $missing = [];

        foreach (self::COLUMNS as $name => $required) {
            $letter = array_search($name, $header, TRUE);

            if ($letter === FALSE) {
                if ($required) {
                    $missing[] = $name;
                }

                continue;
            }

            $indexes[$name] = $letter;
        }

        if ($missing !== []) {
            throw new AppException('U02', ['columns' => implode(', ', $missing)]);
        }

        return $indexes;
    }

    //True when every cell on the line is blank, so trailing padding rows in
    //the sheet are ignored rather than reported as missing required fields.
    private function isBlankRow(array $cells): bool
    {
        foreach ($cells as $value) {
            if (trim((string) $this->stringify($value)) !== '') {
                return FALSE;
            }
        }

        return TRUE;
    }

    //Read-only sheets hand back plain scalars, but a formula or rich-text cell
    //can still yield an object that must not reach the validators.
    private function stringify($value): string
    {
        if ($value === NULL || is_array($value) || is_object($value)) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        return (string) $value;
    }

    private function cell(array $cells, array $indexes, string $column): string
    {
        if (!isset($indexes[$column])) {
            return '';
        }

        return trim($this->stringify($cells[$indexes[$column]] ?? NULL));
    }

    /*
     * Validates and normalises one spreadsheet row into an insertable
     * tbl_users row. Returns ['user' => array|NULL, 'errors' => string[]].
     */
    private function buildUser(array $cells, array $indexes): array
    {
        $errors = [];

        $username = strtolower($this->cell($cells, $indexes, 'username'));
        $firstname = $this->cell($cells, $indexes, 'firstname');
        $middlename = $this->cell($cells, $indexes, 'middlename');
        $lastname = $this->cell($cells, $indexes, 'lastname');
        $email = strtolower($this->cell($cells, $indexes, 'email'));
        $contactnumber = $this->cell($cells, $indexes, 'contactnumber');
        $password = $this->cell($cells, $indexes, 'password');
        $address = $this->cell($cells, $indexes, 'address');
        $role = strtolower($this->cell($cells, $indexes, 'role'));

        if ($username === '') {
            $errors[] = 'Username is required.';
        } elseif (strlen($username) < 3 || strlen($username) > 20) {
            $errors[] = 'Username must be 3 to 20 characters long.';
        } elseif (!preg_match('/^[a-zA-Z0-9_@.\-]+$/', $username)) {
            $errors[] = 'Username may only contain letters, numbers, and _ @ . - characters.';
        }

        foreach (['First name' => $firstname, 'Last name' => $lastname, 'Middle name' => $middlename] as $label => $value) {
            if ($value === '') {
                if ($label !== 'Middle name') {
                    $errors[] = $label . ' is required.';
                }

                continue;
            }

            if (strlen($value) > 100 || !preg_match('/^[a-zA-Z -]+$/', $value)) {
                $errors[] = $label . ' may only contain letters, spaces, and hyphens (max 100 characters).';
            }
        }

        if ($email === '') {
            $errors[] = 'Email is required.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) {
            $errors[] = 'Email "' . $email . '" is not a valid address.';
        } elseif (strlen($email) > 100) {
            //tbl_users.email is VARCHAR(100), not the 255 used elsewhere.
            $errors[] = 'Email must be 100 characters or fewer.';
        }

        if ($contactnumber === '') {
            $errors[] = 'Contact number is required.';
        } elseif (!preg_match(self::PHONE_PATTERN, $contactnumber)) {
            $errors[] = 'Contact number must be 10 to 11 digits (got "' . $contactnumber . '").';
        }

        $hash = '';

        if ($password === '') {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        } elseif (strlen($password) > self::MAX_PASSWORD_LENGTH) {
            $errors[] = 'Password must be ' . self::MAX_PASSWORD_LENGTH . ' characters or fewer.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            if ($hash === FALSE || strlen($hash) < self::BCRYPT_MIN_LENGTH) {
                $errors[] = 'Password could not be hashed.';
                $hash = '';
            }
        }

        if ($address !== '' && strlen($address) > 100) {
            $errors[] = 'Address must be 100 characters or fewer.';
        }

        if ($role === '') {
            $role = 'user';
        } elseif (!in_array($role, self::ALLOWED_ROLES, TRUE)) {
            $errors[] = 'Role must be either "user" or "admin".';
        }

        if ($errors !== []) {
            return ['user' => NULL, 'errors' => $errors];
        }

        return [
            'user' => [
                'username' => $username,
                'firstname' => ucfirst($firstname),
                'middlename' => $middlename !== '' ? ucfirst($middlename) : NULL,
                'lastname' => ucfirst($lastname),
                'email' => $email,
                'contactnumber' => $contactnumber,
                'address' => $address !== '' ? $address : NULL,
                'password' => $hash,
                'role' => $role,
            ],
            'errors' => [],
        ];
    }
}