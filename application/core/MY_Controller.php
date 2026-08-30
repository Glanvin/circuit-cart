<?php
defined('BASEPATH') OR exit('No direct script access allowed');

abstract class MY_Controller extends CI_Controller {

	//Which navbar this controller pages use.
	protected string $navbar = 'templates/navbar';

	//Validates the user session on every request.
	public function __construct()
	{
		parent::__construct();
		try {
			check_session();
		}catch(Exception $e) {
			$this->toastify->error("{$e->getMessage()} [{$e->getCode()}]");
			redirect('auth','refresh');	
		}
	}

	//Renders a page wrapped in the shared template shell.
	protected function render(string $pageView, array $data = []): void
	{
		$data['title'] ??= '';
		$data['enable_footer'] ??= TRUE;

		$this->load->view('templates/header', $data);
		$this->load->view($this->navbar);
		$this->load->view($pageView, $data);
		if ($data['enable_footer']) {
			$this->load->view('templates/footer', $data);
		}
	}
}
