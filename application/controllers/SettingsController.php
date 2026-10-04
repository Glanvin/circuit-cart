<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SettingsController extends MY_Controller {

	protected string $navbar = 'templates/navbar_settings';

	//Renders the settings page with the current user's profile.
	public function index()
	{
		$user = $this->UserModel->findUserByUsername($this->session->userdata('username'));

		$this->render('pages/settings', [
			'title' => 'Settings',
			'user' => $user,
		]);
	}

	//Persists the selected theme in the session and returns a JSON acknowledgement.
	public function theme()
	{
		$theme = $this->input->post('theme', true);

		if (!in_array($theme, ['light', 'dark', 'system'], true)) {
			$this->output->set_status_header(400);
			return;
		}

		$this->session->set_userdata('theme', $theme);

		$this->output->set_content_type('application/json')->set_output(json_encode(['success' => true]));
	}

	/*
	 * Validates and saves the editable profile or address fields, handling
	 * email uniqueness before redirecting back to the settings page.
	 */
	public function updateProfile()
	{
		$user = $this->UserModel->findUserByUsername($this->session->userdata('username'));

		$isProfile = (bool) $this->input->post('save_profile');
		$fields = $isProfile
			? ['firstname', 'middlename', 'lastname', 'contactnumber', 'email']
			: ['address'];

		$rules = $this->config->item($isProfile ? 'user_profile' : 'user_address');
		$this->form_validation->set_rules($rules);

		if ($this->form_validation->run() === FALSE) {
			foreach ($fields as $field) {
				if (($value = $this->input->post($field, TRUE)) !== NULL) {
					$user[$field] = $value;
				}
			}

			$this->toastify->error('Please correct the highlighted fields');
			$this->render('pages/settings', [
				'title' => 'Settings',
				'user' => $user,
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

			if ($data['email'] !== $user['email']) {
				$existing = $this->UserModel->findUserByEmail($data['email']);

				if (!empty($existing) && $existing['id'] !== $user['id']) {
					foreach ($fields as $field) {
						if (($value = $this->input->post($field, TRUE)) !== NULL) {
							$user[$field] = $value;
						}
					}

					$this->toastify->error('That email is already in use.');
					$this->render('pages/settings', [
						'title' => 'Settings',
						'user' => $user,
					]);
					return;
				}

				$data['verified_email'] = 0;
				$this->session->set_userdata('email', $data['email']);
				$this->toastify->info('Email changed. Please verify your new email address.');
			}
		}

		$this->UserModel->updateUser($user['id'], $data);
		$this->toastify->success('Profile updated.');
		redirect('settings', 'refresh');
	}

	/*
	 * Validates the current and new passwords, then saves the newly hashed
	 * password and redirects back to the settings page.
	 */
	public function updatePassword()
	{
		$user = $this->UserModel->findUserByUsername($this->session->userdata('username'));

		$rules = $this->config->item('user_password');
		$this->form_validation->set_rules($rules);

		if ($this->form_validation->run() === FALSE) {
			$this->toastify->error('Please correct the highlighted fields');
			$this->render('pages/settings', [
				'title' => 'Settings',
				'user' => $user,
			]);
			return;
		}

		$password = (string) $this->input->post('new_password', TRUE);

		$this->UserModel->updateUser($user['id'], [
			'password' => password_hash($password, PASSWORD_DEFAULT),
		]);

		$this->toastify->success('Password updated.');
		redirect('settings', 'refresh');
	}

	//Form-validation callback that checks the submitted current password.
	public function check_current_password($password)
	{
		$user = $this->UserModel->findUserByUsername($this->session->userdata('username'));

		if (!empty($user) && password_verify((string) $this->input->post('current_password', TRUE), $user['password'])) {
			return TRUE;
		}

		$this->form_validation->set_message('check_current_password', 'Current password is incorrect.');
		return FALSE;
	}
}
