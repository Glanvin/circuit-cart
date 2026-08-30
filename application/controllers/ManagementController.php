<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ManagementController extends MY_Controller {

	protected string $navbar = 'templates/navbar_employees';

	public function index()
	{
		$this->render('pages/manage', ['title' => 'Manage']);
	}
}
