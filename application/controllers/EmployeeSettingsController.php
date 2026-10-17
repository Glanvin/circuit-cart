<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EmployeeSettingsController extends MY_Controller {

	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_employees';

	//Employees manage their own profile, email (with verification) and password.
	public function index()
	{
		$employee = $this->currentEmployee();
		$refundPinConfigured = !empty($employee['refund_pin_hash']);
		unset($employee['refund_pin_hash']);

		$this->render('pages/employees/settings', [
			'title' => 'Settings',
			'employee' => $employee,
			'refund_pin_configured' => $refundPinConfigured,
		]);
	}

	//Lets a manager or admin set or replace their own refund PIN after
	//confirming their account password. The PIN is stored as a one-way hash.
	public function updateRefundPin()
	{
		$employee = $this->currentEmployee();
		if (!in_array($employee['role'] ?? '', ['manager', 'admin'], TRUE)) {
			$this->toastify->error('Only managers and admins can configure a refund PIN.');
			redirect('employee/settings', 'refresh');
			return;
		}

		$currentPassword = (string) $this->input->post('current_password', TRUE);
		$pin = (string) $this->input->post('refund_pin', TRUE);
		$confirmation = (string) $this->input->post('confirm_refund_pin', TRUE);

		if (!password_verify($currentPassword, $employee['password'])) {
			$this->toastify->error('Current password is incorrect.');
		} elseif (!preg_match('/^\d{6}$/', $pin)) {
			$this->toastify->error('Refund PIN must be exactly 6 digits.');
		} elseif (!hash_equals($pin, $confirmation)) {
			$this->toastify->error('Refund PIN confirmation does not match.');
		} else {
			$this->EmployeeModel->updateEmployee($employee['employee_id'], [
				'refund_pin_hash' => password_hash($pin, PASSWORD_DEFAULT),
			]);
			$this->toastify->success('Refund PIN updated.');
		}

		redirect('employee/settings', 'refresh');
	}

	/*
	 * Validates and saves the editable employee fields, handling email
	 * uniqueness before redirecting back to the settings page.
	 */
	public function updateProfile()
	{
		$employee = $this->currentEmployee();
		$fields = ['firstname', 'middlename', 'lastname', 'contactnumber', 'email'];

		$rules = $this->config->item('employee_profile');
		$this->form_validation->set_rules($rules);

		if ($this->form_validation->run() === FALSE) {
			foreach ($fields as $field) {
				if (($value = $this->input->post($field, TRUE)) !== NULL) {
					$employee[$field] = $value;
				}
			}

			$this->toastify->error('Please correct the highlighted fields');
			$this->render('pages/employees/settings', [
				'title' => 'Settings',
				'employee' => $employee,
			]);
			return;
		}

		$data = [];
		foreach ($fields as $field) {
			if (($value = $this->input->post($field, TRUE)) !== NULL) {
				$data[$field] = trim($value);
			}
		}

		foreach (['firstname', 'middlename', 'lastname'] as $name) {
			if (isset($data[$name])) {
				$data[$name] = ucfirst($data[$name]);
			}
		}

		if (isset($data['email'])) {
			$data['email'] = strtolower($data['email']);

			if ($data['email'] !== $employee['email']) {
				$existing = $this->EmployeeModel->findEmployeeByEmail($data['email']);

				if (!empty($existing) && (int) $existing['employee_id'] !== (int) $employee['employee_id']) {
					foreach ($fields as $field) {
						if (($value = $this->input->post($field, TRUE)) !== NULL) {
							$employee[$field] = $value;
						}
					}

					$this->toastify->error('That email is already in use.');
					$this->render('pages/employees/settings', [
						'title' => 'Settings',
						'employee' => $employee,
					]);
					return;
				}

				$data['verified_email'] = 0;
				$this->session->set_userdata('employee_email', $data['email']);
				$this->toastify->info('Email changed. Please verify your new email address.');
			}
		}

		$this->EmployeeModel->updateEmployee($employee['employee_id'], $data);

		$name = trim(($data['firstname'] ?? $employee['firstname']) . ' ' . ($data['lastname'] ?? $employee['lastname']));
		$this->session->set_userdata('employee_name', $name);
		$this->toastify->success('Profile updated.');
		redirect('employee/settings', 'refresh');
	}

	/*
	 * Validates the current and new passwords, then saves the newly hashed
	 * password and redirects back to the settings page.
	 */
	public function updatePassword()
	{
		$employee = $this->currentEmployee();

		$rules = $this->config->item('employee_password');
		$this->form_validation->set_rules($rules);

		if ($this->form_validation->run() === FALSE) {
			$this->toastify->error('Please correct the highlighted fields');
			$this->render('pages/employees/settings', [
				'title' => 'Settings',
				'employee' => $employee,
			]);
			return;
		}

		$password = (string) $this->input->post('new_password', TRUE);

		$this->EmployeeModel->updateEmployee($employee['employee_id'], [
			'password' => password_hash($password, PASSWORD_DEFAULT),
		]);

		$this->toastify->success('Password updated.');
		redirect('employee/settings', 'refresh');
	}

	/*
	 * Sends a verification code to the session employee email and validates the
	 * submitted OTP, marking the employee account as verified before redirecting
	 * to the employee settings page.
	 */
	public function verify()
	{
		$data = [
			'title' => 'Verify Email',
			'enable_footer' => FALSE,
		];

		if ($this->input->post('otp')) {
			if (verify_otp((string) $this->input->post('otp', TRUE))) {
				$employee = $this->currentEmployee();
				$this->EmployeeModel->updateEmployee($employee['employee_id'], ['verified_email' => 1]);

				$this->toastify->success('Your email has been verified.');
				redirect('employee/settings', 'refresh');
			}

			$this->toastify->error('Invalid or expired verification code. Please try again.');
		} else {
			$this->ensureEmailInSession();

			$email = (string) $this->session->userdata('employee_email');
			if ($email === '' || !store_otp($email)) {
				$this->toastify->error('Unable to send the verification code. Please try again.');
			}
		}

		$this->load->view('templates/header', $data);
		$this->load->view('auth/employee_verify_email', $data);
	}

	//Form-validation callback that checks the submitted current password.
	public function check_current_employee_password($password)
	{
		$employee = $this->currentEmployee();

		if (!empty($employee) && password_verify((string) $this->input->post('current_password', TRUE), $employee['password'])) {
			return TRUE;
		}

		$this->form_validation->set_message('check_current_employee_password', 'Current password is incorrect.');
		return FALSE;
	}

	//Returns the signed-in employee record for the session employee ID.
	private function currentEmployee(): array
	{
		return $this->EmployeeModel->findByEmployeeId((string) $this->session->userdata('employee_id'));
	}

	//Ensures the current employee's email is loaded into the session.
	private function ensureEmailInSession(): void
	{
		if (!empty($this->session->userdata('employee_email'))) {
			return;
		}

		$employee = $this->currentEmployee();

		if (!empty($employee['email'])) {
			$this->session->set_userdata('employee_email', $employee['email']);
		}
	}
}
