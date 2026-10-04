<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DashboardController extends MY_Controller {

	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_employees';

	protected ?string $requiredRole = 'admin';

	//Renders the employee dashboard page.
	public function index()
	{
		$this->render('pages/employees/dashboard', ['title' => 'Dashboard']);
	}
}
