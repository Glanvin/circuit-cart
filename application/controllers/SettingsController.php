<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SettingsController extends MY_Controller {

	protected string $navbar = 'templates/navbar_settings';

	public function index()
	{
		$user = $this->UserModel->findUserByUsername($this->session->userdata('username'));

		$this->render('pages/settings', [
			'title' => 'Settings',
			'user'  => $user,
		]);
	}

	public function theme()
	{
		$theme = $this->input->post('theme', true);

		if (!in_array($theme, ['light', 'dark', 'system'], true)) {
			$this->output->set_status_header(400);
			return;
		}

		$this->session->set_userdata('theme', $theme);

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(['success' => true]));
	}
}
