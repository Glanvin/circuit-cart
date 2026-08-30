<?php
defined('BASEPATH') OR exit('No direct script access allowed');

abstract class MY_Controller extends CI_Controller {

	//Which navbar this controller pages use.
	protected string $navbar = 'templates/navbar';

	//Role required to access this controller, or NULL for any signed-in user.
	protected ?string $requiredRole = NULL;

	//Validates the user session on every request.
	public function __construct()
	{
		parent::__construct();
		try {
			check_session();

			if ($this->requiredRole && $this->session->userdata('role') !== $this->requiredRole) {
				throw new AppException('P03');
			}
		}catch(AppException $e) {
			$this->toastify->error($e->toToast());

			//P03 = signed-in user lacking the required role, otherwise invalid session.
			redirect($e->getErrorCode() === 'P03' ? 'home' : 'auth', 'refresh');
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
