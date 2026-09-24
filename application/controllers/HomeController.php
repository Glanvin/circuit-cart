<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class HomeController extends MY_Controller {

	protected bool $requireLogin = FALSE;

	//Renders the home page.
	public function index()
	{
		$this->render('pages/home', ['title' => 'Home']);
	}
}
