<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class HomeController extends MY_Controller {

	public function index()
	{
		$this->render('pages/home', ['title' => 'Home']);
	}
}
