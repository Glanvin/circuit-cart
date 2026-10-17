<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class UserImporterTest extends TestCase {

	private UserImporter $importer;
	private array $tempFiles = [];

	protected function setUp(): void {
		$this->importer = new UserImporter();
	}

	protected function tearDown(): void {
		foreach ($this->tempFiles as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}
		$this->tempFiles = [];
	}

	//Writes a workbook with the given rows (first row is the header) and
	//returns the .xlsx path, tracked for cleanup.
	private function writeSheet(array $rows): string {
		$spreadsheet = new Spreadsheet();
		$spreadsheet->getActiveSheet()->fromArray($rows, NULL, 'A1');

		$path = tempnam(sys_get_temp_dir(), 'cc_import_') . '.xlsx';
		(new Xlsx($spreadsheet))->save($path);

		$this->tempFiles[] = $path;
		return $path;
	}

	private function header(): array {
		return ['username', 'firstname', 'middlename', 'lastname', 'email', 'contactnumber', 'password', 'address', 'role'];
	}

	public function testTemplateUsesExpectedHeaderOrder() {
		$sheet = $this->importer->template();

		$this->assertSame('Users', $sheet->getActiveSheet()->getTitle());
		$this->assertSame($this->header(), $sheet->getActiveSheet()->rangeToArray('A1:I1')[0]);
	}

	public function testParseReturnsNormalisedUsers() {
		$path = $this->writeSheet([
			$this->header(),
			['JUAN.Delacruz', 'juan', '', 'dela cruz', 'JUAN@Example.COM', '09170000001', 'password123', 'Somewhere', 'admin'],
		]);

		$result = $this->importer->parse($path);

		$this->assertCount(1, $result['users']);
		$this->assertSame([], $result['errors']);

		$user = $result['users'][0];
		$this->assertSame('juan.delacruz', $user['username']);
		$this->assertSame('Juan', $user['firstname']);
		$this->assertSame('Dela cruz', $user['lastname']);
		$this->assertNull($user['middlename']);
		$this->assertSame('juan@example.com', $user['email']);
		$this->assertSame('admin', $user['role']);
		$this->assertSame(2, $user['row']);
		$this->assertTrue(password_verify('password123', $user['password']));
	}

	public function testRoleDefaultsToUserWhenBlank() {
		$path = $this->writeSheet([
			$this->header(),
			['maria.santos', 'Maria', '', 'Santos', 'maria@example.com', '09170000002', 'password123', '', ''],
		]);

		$result = $this->importer->parse($path);

		$this->assertSame('user', $result['users'][0]['role']);
	}

	public function testInvalidRowsAreCollectedAndValidRowsKept() {
		$path = $this->writeSheet([
			$this->header(),
			['good.user', 'Good', '', 'User', 'good@example.com', '09170000003', 'password123', '', 'user'],
			['bad.email', 'Bad', '', 'Email', 'not-an-email', '09170000004', 'password123', '', 'user'],
			['short.pw', 'Short', '', 'Pass', 'short@example.com', '09170000005', '123', '', 'user'],
			['bad.role', 'Bad', '', 'Role', 'role@example.com', '09170000006', 'password123', '', 'superuser'],
			['bad.phone', 'Bad', '', 'Phone', 'phone@example.com', 'abc', 'password123', '', 'user'],
		]);

		$result = $this->importer->parse($path);

		$this->assertCount(1, $result['users']);
		$this->assertSame('good.user', $result['users'][0]['username']);
		$this->assertCount(4, $result['errors']);

		$rows = array_column($result['errors'], 'row');
		$this->assertSame([3, 4, 5, 6], $rows);
	}

	public function testDuplicateRowsWithinFileAreReported() {
		$path = $this->writeSheet([
			$this->header(),
			['dupe', 'First', '', 'One', 'first@example.com', '09170000010', 'password123', '', 'user'],
			['dupe', 'Second', '', 'Two', 'second@example.com', '09170000011', 'password123', '', 'user'],
			['other', 'Third', '', 'Three', 'first@example.com', '09170000012', 'password123', '', 'user'],
		]);

		$result = $this->importer->parse($path);

		$this->assertCount(1, $result['users']);
		$this->assertCount(2, $result['errors']);
		$this->assertStringContainsString('Username "dupe"', $result['errors'][0]['message']);
		$this->assertStringContainsString('Email "first@example.com"', $result['errors'][1]['message']);
	}

	public function testMissingRequiredColumnThrowsU02() {
		$rows = $this->header();
		unset($rows[5]); // drop contactnumber
		$path = $this->writeSheet([
			array_values($rows),
			['someone', 'Some', '', 'One', 'some@example.com', 'password123', '', 'user'],
		]);

		try {
			$this->importer->parse($path);
			$this->fail('Expected AppException U02');
		} catch (AppException $e) {
			$this->assertSame('U02', $e->getErrorCode());
		}
	}

	public function testEmptySheetThrowsU03() {
		$path = $this->writeSheet([$this->header()]);

		try {
			$this->importer->parse($path);
			$this->fail('Expected AppException U03');
		} catch (AppException $e) {
			$this->assertSame('U03', $e->getErrorCode());
		}
	}

	public function testTooManyRowsThrowsU04() {
		$rows = [$this->header()];

		for ($i = 0; $i < UserImporter::MAX_ROWS + 1; $i++) {
			$rows[] = ['user' . $i, 'First', '', 'Last', 'user' . $i . '@example.com', '09170000099', 'password123', '', 'user'];
		}

		$path = $this->writeSheet($rows);

		try {
			$this->importer->parse($path);
			$this->fail('Expected AppException U04');
		} catch (AppException $e) {
			$this->assertSame('U04', $e->getErrorCode());
		}
	}

	public function testBlankTrailingRowsAreIgnored() {
		$path = $this->writeSheet([
			$this->header(),
			['only.row', 'Only', '', 'Row', 'only@example.com', '09170000020', 'password123', '', 'user'],
			['', '', '', '', '', '', '', '', ''],
		]);

		$result = $this->importer->parse($path);

		$this->assertCount(1, $result['users']);
		$this->assertSame([], $result['errors']);
	}
}
