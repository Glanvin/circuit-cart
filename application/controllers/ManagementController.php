<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ManagementController extends MY_Controller {

	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_employees';

	protected ?string $requiredRole = 'admin';

	//Flashdata keys carrying the per-row import outcome back to the modal.
	private const IMPORT_RESULT = 'import_result';

	private const IMPORT_ERRORS = 'import_errors';

	public function index()
	{
		redirect('employee/manage/users', 'refresh');
	}

	//Customer users table: admin may delete a user record.
	public function users()
	{
		if ($this->input->post('delete_user')) {
			$user_id = (int) $this->input->post('user_id', true);

			try {
				$user = $this->UserModel->findUserById($user_id);

				if ($user['username'] === (string) $this->session->userdata('username')) {
					$this->toastify->error('You cannot delete your own account.');
				} else {
					$this->UserModel->deleteUser($user_id);
					$this->toastify->success('User "' . $user['username'] . '" deleted.');
				}
			} catch (AppException $e) {
				$this->toastify->error($e->toToast());
			}

			redirect('employee/manage/users', 'refresh');
		}

		$users = [];

		try {
			$users = $this->UserModel->getAllUsers();
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/employees/manage_users', [
			'title' => 'Manage Users',
			'users' => $users,
			'employee_roles' => $this->config->item('employee_roles'),
			'import_columns' => $this->importColumnReference(),
		]);
	}

	/*
	 * Creates customer accounts from an uploaded spreadsheet. Rows that clash
	 * with an existing account, or that fail validation, are skipped and
	 * reported back per row instead of failing the whole file.
	 */
	public function importUsers()
	{
		if ($this->input->post('import_users')) {
			$this->processImport();
			redirect('employee/manage/users', 'refresh');
		}

		redirect('employee/manage/users', 'refresh');
	}

	//Streams the sample spreadsheet documenting the expected columns.
	public function userTemplate()
	{
		$path = tempnam(sys_get_temp_dir(), 'ci3_import_');

		try {
			(new Xlsx($this->importer()->template()))->save($path);
			$contents = file_get_contents($path);
		} finally {
			if (is_file($path)) {
				unlink($path);
			}
		}

		$this->output
			->set_content_type('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
			->set_header('Content-Disposition: attachment; filename="circuit-cart-user-import-sample.xlsx"')
			->set_header('Cache-Control: max-age=0')
			->set_header('Pragma: public')
			->set_header('Expires: 0')
			->set_output($contents);
	}

	/*
	 * Runs the whole upload: validate the file, read it, drop the rows that
	 * collide with existing accounts, then insert the survivors in one
	 * transaction. The uploaded file is always removed, and the per-row
	 * outcome is flashed so the modal can render it after the redirect.
	 */
	private function processImport(): void
	{
		$errors = [];

		try {
			$path = $this->storeImportUpload($errors);

			if ($path === NULL) {
				$this->session->set_flashdata(self::IMPORT_RESULT, [
					'imported' => 0,
					'skipped' => 0,
					'fatal' => $errors[0]['message'] ?? 'The file could not be uploaded.',
				]);

				return;
			}

			try {
				$parsed = $this->importer()->parse($path);
			} finally {
				//The file has served its purpose either way; never leave the
				//admin's spreadsheet sitting in a web-reachable directory.
				if (is_file($path)) {
					unlink($path);
				}
			}

			$errors = array_merge($parsed['errors'], $this->collisions($parsed['users']));
			$rows = $this->withoutCollisions($parsed['users'], $errors);

			$imported = $this->insertUsers($rows);
		} catch (AppException $e) {
			$this->session->set_flashdata(self::IMPORT_RESULT, [
				'imported' => 0,
				'skipped' => 0,
				'fatal' => $e->getMessage(),
			]);

			return;
		}

		//A single row can break more than one rule, so count distinct rows
		//rather than every reason when reporting how many were skipped.
		$skipped = count(array_unique(array_column($errors, 'row')));

		if ($imported > 0 && $skipped > 0) {
			$this->toastify->success('Imported ' . $imported . ' user(s), skipped ' . $skipped . '.');
		} elseif ($imported > 0) {
			$this->toastify->success('Imported ' . $imported . ' user(s).');
		} else {
			$codes = $this->config->item('error_codes');
			$this->toastify->error($codes['U05'] ?? 'No users were imported.');
		}

		$this->session->set_flashdata(self::IMPORT_RESULT, [
			'imported' => $imported,
			'skipped' => $skipped,
			'fatal' => '',
		]);
		$this->session->set_flashdata(self::IMPORT_ERRORS, $errors);
	}

	/*
	 * Saves the posted spreadsheet into the configured upload folder and
	 * returns its path, or NULL after pushing the reason onto $errors.
	 */
	private function storeImportUpload(array &$errors): ?string
	{
		if (empty($_FILES['user_file']) || ($_FILES['user_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
			$errors[] = ['row' => 0, 'message' => 'Choose an .xlsx, .xls or .csv file to upload.'];

			return NULL;
		}

		$this->config->load('upload');

		$config = $this->config->item('user_import', 'upload');

		$config['max_size'] = (int) $config['max_size'];
		$config['is_image'] = (bool) $config['is_image'];
		$config['encrypt_name'] = (bool) $config['encrypt_name'];
		$config['overwrite'] = (bool) $config['overwrite'];

		if (!is_dir($config['upload_path'])) {
			mkdir($config['upload_path'], 0777, TRUE);
		}

		//Guarantee write access for the web server user even if folder
		//permissions were reset after creation.
		chmod($config['upload_path'], 0777);

		$this->load->library('upload', $config);

		if (!$this->upload->do_upload('user_file')) {
			$errors[] = ['row' => 0, 'message' => strip_tags($this->upload->display_errors('', ' '))];

			return NULL;
		}

		return $config['upload_path'] . $this->upload->data('file_name');
	}

	/*
	 * Looks up which usernames and emails from the sheet already exist, so a
	 * collision is skipped instead of silently creating a duplicate account.
	 */
	private function collisions(array $users): array
	{
		if ($users === []) {
			return [];
		}

		$usernames = array_column($users, 'username');
		$emails = array_column($users, 'email');

		$existingUsernames = array_map('strtolower', $this->UserModel->getExistingUsernames($usernames));
		$existingEmails = array_map('strtolower', $this->UserModel->getExistingEmails($emails));

		$errors = [];

		foreach ($users as $user) {
			if (in_array(strtolower($user['username']), $existingUsernames, TRUE)) {
				$errors[] = [
					'row' => $user['row'],
					'message' => 'Username "' . $user['username'] . '" already has an account.',
				];
			}

			if (in_array(strtolower($user['email']), $existingEmails, TRUE)) {
				$errors[] = [
					'row' => $user['row'],
					'message' => 'Email "' . $user['email'] . '" already has an account.',
				];
			}
		}

		return $errors;
	}

	//Drops the rows a collision check flagged, leaving the rows to insert.
	private function withoutCollisions(array $users, array $errors): array
	{
		$rejected = array_fill_keys(array_column($errors, 'row'), TRUE);

		return array_values(array_filter($users, static function($user) use ($rejected) {
			return !isset($rejected[$user['row']]);
		}));
	}

	/*
	 * Inserts the prepared rows in a single transaction so a mid-batch
	 * database failure cannot leave a partial import behind. Returns the
	 * number of users created.
	 */
	private function insertUsers(array $users): int
	{
		if ($users === []) {
			return 0;
		}

		$this->db->trans_start();

		foreach ($users as $user) {
			//'row' is bookkeeping for the error report, not a column.
			unset($user['row']);

			if (!$this->UserModel->registerUser($user)) {
				$this->db->trans_rollback();

				throw new AppException('Q03');
			}
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			throw new AppException('Q03');
		}

		return count($users);
	}

	//Column name => required flag, for documenting the sheet layout in the modal.
	private function importColumnReference(): array
	{
		return [
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
	}

	/*
	 * Loads the spreadsheet importer on demand, so PhpSpreadsheet is only
	 * pulled in for the requests that actually read or write a workbook.
	 */
	private function importer(): UserImporter
	{
		// CI_Loader runs ucfirst() on the library name, so 'UserImporter' maps to
		// libraries/UserImporter.php; the explicit alias keeps the property name
		// readable instead of the default lowercase 'userimporter'.
		$this->load->library('UserImporter', NULL, 'user_importer');

		return $this->user_importer;
	}

	//Employees table: create, set role, reset password, activate/deactivate.
	public function employees()
	{
		if ($this->input->post('employee_action')) {
			$this->handleEmployeeAction();
			redirect('employee/manage/employees', 'refresh');
		}

		$employees = [];

		try {
			$employees = $this->EmployeeModel->getAllEmployees();
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/employees/manage_employees', [
			'title' => 'Manage Employees',
			'employees' => $employees,
			'employee_roles' => $this->config->item('employee_roles'),
		]);
	}

	//Standalone add-employee page (no modal, no username field).
	public function newEmployee()
	{
		if ($this->input->post('employee_action')) {
			if ($this->createEmployee() > 0) {
				redirect('employee/manage/employees', 'refresh');
			}
		}

		$this->render('pages/employees/new_employee', [
			'title' => 'Add Employee',
			'employee_roles' => $this->config->item('employee_roles'),
		]);
	}

	private function handleEmployeeAction(): void
	{
		$action = (string) $this->input->post('employee_action', true);
		$id = (int) $this->input->post('employee_id', true);

		try {
			switch ($action) {
				case 'create':
					$this->createEmployee();
					return;

				case 'role':
					$role = (string) $this->input->post('role', true);

					if (!in_array($role, $this->config->item('employee_roles'), true)) {
						$this->toastify->error('Invalid employee role.');
						return;
					}

					$this->EmployeeModel->getEmployeeById($id);
					$this->EmployeeModel->updateEmployee($id, ['role' => $role]);
					$this->toastify->success('Employee role updated.');
					return;

				case 'password':
					$password = (string) $this->input->post('new_password', true);

					if (strlen($password) < 6) {
						$this->toastify->error('Password must be at least 6 characters.');
						return;
					}

					$this->EmployeeModel->getEmployeeById($id);
					$this->EmployeeModel->updateEmployee($id, ['password' => password_hash($password, PASSWORD_DEFAULT)]);
					$this->toastify->success('Password reset.');
					return;

				case 'deactivate':
					$this->EmployeeModel->getEmployeeById($id);
					$this->EmployeeModel->updateEmployee($id, ['is_active' => 0]);
					$this->toastify->success('Employee deactivated.');
					return;

				case 'activate':
					$this->EmployeeModel->getEmployeeById($id);
					$this->EmployeeModel->updateEmployee($id, ['is_active' => 1]);
					$this->toastify->success('Employee activated.');
					return;

				default:
					$this->toastify->error('Unknown action.');
			}
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}
	}

	private function createEmployee(): int
	{
		$rules = $this->config->item('employee_create');
		$this->form_validation->set_rules($rules);

		if ($this->form_validation->run() === FALSE) {
			$this->toastify->error('Please correct the highlighted fields');
			return 0;
		}

		$role = (string) $this->input->post('role', true);

		if (!in_array($role, $this->config->item('employee_roles'), true)) {
			$this->toastify->error('Invalid employee role.');
			return 0;
		}

		try {
			$employee_id = $this->EmployeeModel->createEmployee([
				'firstname' => ucfirst($this->input->post('firstname', true)),
				'middlename' => ucfirst($this->input->post('middlename', true)),
				'lastname' => ucfirst($this->input->post('lastname', true)),
				'email' => strtolower($this->input->post('email', true)),
				'contactnumber' => $this->input->post('contactnumber', true),
				'password' => password_hash($this->input->post('password', true), PASSWORD_DEFAULT),
				'role' => $role,
				'attendance_history' => NULL,
				'is_active' => 1,
			]);

			$this->toastify->success('Employee created with ID ' . sprintf('%06d', $employee_id) . '.');
			return (int) $employee_id;
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			return 0;
		}
	}
}