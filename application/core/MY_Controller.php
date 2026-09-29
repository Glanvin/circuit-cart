<?php
defined('BASEPATH') OR exit('No direct script access allowed');

abstract class MY_Controller extends CI_Controller {

	//Which navbar this controller pages use.
	protected string $navbar = 'templates/navbar';

	//Role required to access this controller, or NULL for any signed-in user.
	protected ?string $requiredRole = NULL;

	//Alternative to $requiredRole: any role in this list may access the page.
	protected array $allowedRoles = [];

	//Whether a valid session is required to access this controller. Public pages
	//(browse, cart, checkout, home) set this to FALSE so guests can shop.
	protected bool $requireLogin = TRUE;

	//Whether this controller pages belong to the employee portal. Employee
	//controllers keep their session data under the employee_* keys so a
	//customer session and an employee session never collide.
	protected bool $employeePortal = FALSE;

	/*
	 * Validates the session and role requirements on every request, redirecting
	 * to the appropriate page when access is denied.
	 */
	public function __construct()
	{
		parent::__construct();
		try {
			if ($this->requireLogin) {
				check_session($this->employeePortal ? 'employee' : 'customer');
			}

			$role = $this->session->userdata($this->employeePortal ? 'employee_role' : 'role');

			if (!empty($this->allowedRoles)) {
				if (!in_array($role, $this->allowedRoles, true)) {
					throw new AppException('P03');
				}
			} elseif ($this->requiredRole && $role !== $this->requiredRole) {
				throw new AppException('P03');
			}
		}catch(AppException $e) {
			$this->toastify->error($e->toToast());

			//P03 = signed-in user lacking the required role, otherwise invalid session.
			if ($this->employeePortal) {
				if ($e->getErrorCode() === 'P03') {
					$role = (string) $this->session->userdata('employee_role');
					$homes = $this->config->item('role_home');
					redirect(is_array($homes) && isset($homes[$role]) ? $homes[$role] : 'employee/attendance', 'refresh');
				}
				redirect('employee/signin', 'refresh');
			}
			redirect($e->getErrorCode() === 'P03' ? 'home' : 'auth', 'refresh');
		}
	}

	//Renders a page wrapped in the shared template shell.
	protected function render(string $pageView, array $data = []): void
	{
		$data['title'] ??= '';
		$data['enable_footer'] ??= TRUE;
		//Lets the shared footer pick the correct sign-out target for this portal.
		$data['portal'] = $this->employeePortal ? 'employee' : 'customer';

		$this->load->view('templates/header', $data);
		$this->load->view($this->navbar);
		$this->load->view($pageView, $data);
		if ($data['enable_footer']) {
			$this->load->view('templates/footer', $data);
		}
	}
}
