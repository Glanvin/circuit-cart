<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ManagementController extends MY_Controller {

	protected string $navbar = 'templates/navbar_employees';

	protected ?string $requiredRole = 'admin';

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
		]);
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

	private function createEmployee(): void
	{
		$rules = $this->config->item('employee_create');
		$this->form_validation->set_rules($rules);

		if ($this->form_validation->run() === FALSE) {
			$this->toastify->error('Please correct the highlighted fields');
			return;
		}

		$role = (string) $this->input->post('role', true);

		if (!in_array($role, $this->config->item('employee_roles'), true)) {
			$this->toastify->error('Invalid employee role.');
			return;
		}

		try {
			$this->EmployeeModel->createEmployee([
				'username' => strtolower($this->input->post('username', true)),
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

			$this->toastify->success('Employee created with a 6-digit ID.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}
	}
}