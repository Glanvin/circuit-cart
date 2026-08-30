<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DashboardController extends MY_Controller {

	protected string $navbar = 'templates/navbar_employees';

	protected ?string $requiredRole = 'admin';

	public function index()
	{
		$this->render('pages/employees/dashboard', ['title' => 'Dashboard']);
	}
}
