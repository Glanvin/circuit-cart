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
}
