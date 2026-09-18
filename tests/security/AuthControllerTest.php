<?php

use PHPUnit\Framework\TestCase;

class AuthControllerTest extends TestCase {

	private $controller;
	private $session;
	private $toastify;
	private $input;
	private $config;
	private $validation;
	private $db;
	private $loader;

	protected function setUp(): void {
		redirect_stop(FALSE);
		$GLOBALS['redirect_target'] = NULL;

		$controller = new AuthController();

		$controller->session       = $this->session    = new FakeSession();
		$controller->toastify      = $this->toastify   = new FakeToastify($this->session);
		$controller->input         = $this->input      = new FakeInput();
		$controller->config        = $this->config     = new FakeConfig();
		$controller->form_validation = $this->validation = new FakeValidation();
		$controller->db            = $this->db         = new FakeDb();
		$controller->load          = $this->loader     = new FakeLoader();
		$controller->email         = $this->email     = new FakeEmail();
		$controller->UserModel     = new UserModel();

		$this->config->items['user_signin'] = [
			['field' => 'username', 'rules' => 'required'],
			['field' => 'password', 'rules' => 'required'],
		];
		$this->config->items['user_signup'] = [
			['field' => 'username', 'rules' => 'required'],
		];

		$this->controller = $controller;
	}

	// ----------------------------------------------------------------
	// index()
	// ----------------------------------------------------------------

	public function testIndexRedirectsAdminToDashboard() {
		$this->session->set_userdata('username', 'admin1');
		$this->session->set_userdata('role', 'admin');

		$this->controller->index();

		$this->assertSame('employee/dashboard', redirect_target());
	}

	public function testIndexRedirectsNonAdminToHome() {
		$this->session->set_userdata('username', 'staff1');
		$this->session->set_userdata('role', 'staff');

		$this->controller->index();

		$this->assertSame('home', redirect_target());
	}

	public function testIndexWithoutValidSessionJustShowsSigninView() {
		// No username in session -> signin view renders with no session error toast.
		$this->controller->index();

		$this->assertNull($this->session->flashdata('toast_error'));
		$views = array_column($this->loader->viewCalls, 'view');
		$this->assertContains('auth/sign_in', $views);
	}

	// ----------------------------------------------------------------
	// signout()
	// ----------------------------------------------------------------

	public function testSignoutClearsUserdataAndRedirectsToSignin() {
		$this->session->set_userdata('username', 'alice');
		$this->session->set_userdata('role', 'admin');

		$this->controller->signout();

		$this->assertNull($this->session->userdata('username'));
		$this->assertNull($this->session->userdata('role'));
		$this->assertSame('You have been signed out.', $this->session->flashdata('toast_success'));
		$this->assertSame('auth/signin', redirect_target());
	}

	// ----------------------------------------------------------------
	// signin()
	// ----------------------------------------------------------------

	public function testSigninSuccessRedirectsAdminToDashboard() {
		$this->input->postData = ['signin' => '1', 'username' => 'admin1', 'password' => 'secret'];
		$this->validation->runResult = TRUE;
		$this->db->rows = [
			['username' => 'admin1', 'password' => password_hash('secret', PASSWORD_DEFAULT), 'role' => 'admin', 'email' => 'admin1@example.com'],
		];

		$this->controller->signin();

		$this->assertSame('admin1', $this->session->userdata('username'));
		$this->assertSame('admin', $this->session->userdata('role'));
		$this->assertSame('admin1@example.com', $this->session->userdata('email'));
		$this->assertSame('employee/dashboard', redirect_target());
	}

	public function testSigninSuccessRedirectsStaffToHome() {
		$this->input->postData = ['signin' => '1', 'username' => 'staff1', 'password' => 'secret'];
		$this->validation->runResult = TRUE;
		$this->db->rows = [
			['username' => 'staff1', 'password' => password_hash('secret', PASSWORD_DEFAULT), 'role' => 'staff', 'email' => 'staff1@example.com'],
		];

		$this->controller->signin();

		$this->assertSame('home', redirect_target());
		$this->assertSame('staff1@example.com', $this->session->userdata('email'));
	}

	public function testSigninUnknownUserShowsError() {
		$this->input->postData = ['signin' => '1', 'username' => 'ghost', 'password' => 'secret'];
		$this->validation->runResult = TRUE;
		$this->db->rows = [];

		$this->controller->signin();

		$this->assertSame('User does not exist', $this->session->flashdata('toast_error'));
		$this->assertNull(redirect_target());
	}

	public function testSigninWrongPasswordShowsError() {
		$this->input->postData = ['signin' => '1', 'username' => 'alice', 'password' => 'wrong'];
		$this->validation->runResult = TRUE;
		$this->db->rows = [['username' => 'alice', 'password' => 'secret', 'role' => 'admin']];

		$this->controller->signin();

		$this->assertSame('Invalid credentials passed', $this->session->flashdata('toast_error'));
	}

	public function testSigninValidationFailureShowsFieldError() {
		$this->input->postData = ['signin' => '1', 'username' => 'al', 'password' => 'x'];
		$this->validation->runResult = FALSE;

		$this->controller->signin();

		$this->assertSame('Please correct the highlighted fields', $this->session->flashdata('toast_error'));
		$views = array_column($this->loader->viewCalls, 'view');
		$this->assertContains('auth/sign_in', $views);
	}

	public function testSigninWhenNoPostOnlyShowsEmptyForm() {
		$this->input->postData = [];

		$this->controller->signin();

		$this->assertNull($this->session->flashdata('toast_error'));
		$views = array_column($this->loader->viewCalls, 'view');
		$this->assertContains('auth/sign_in', $views);
	}

	// ----------------------------------------------------------------
	// signup()
	// ----------------------------------------------------------------

	public function testSignupSuccessRedirectsToSignin() {
		$this->input->postData = [
			'register' => '1',
			'username' => 'Alice',
			'firstname' => 'Alice',
			'middlename' => 'M',
			'lastname' => 'Smith',
			'contactnumber' => '09171234567',
			'password' => 'secret1',
			'email' => 'Alice@Example.com',
		];
		$this->validation->runResult = TRUE;
		$this->db->failQuery = FALSE;

		$this->controller->signup();

		$this->assertSame('Registration successul', $this->session->flashdata('toast_success'));
		$this->assertSame('auth/signin', redirect_target());
		$this->assertSame('tbl_users', $this->db->lastInsertTable);
		// Username/email must be lowercased before insert.
		$this->assertSame('alice', $this->db->lastInsertData['username']);
		$this->assertSame('alice@example.com', $this->db->lastInsertData['email']);
	}

	public function testSignupDbFailureRedirectsWithError() {
		$this->input->postData = [
			'register' => '1',
			'username' => 'Bob',
			'firstname' => 'Bob',
			'middlename' => '',
			'lastname' => 'Jones',
			'contactnumber' => '09171234567',
			'password' => 'secret1',
			'email' => 'bob@example.com',
		];
		$this->validation->runResult = TRUE;
		$this->db->failQuery = TRUE;

		$this->controller->signup();

		$this->assertSame('Registration unsuccessful', $this->session->flashdata('toast_error'));
		$this->assertSame('auth/signup', redirect_target());
	}

	public function testSignupValidationFailureShowsFieldError() {
		$this->input->postData = ['register' => '1', 'username' => 'x'];
		$this->validation->runResult = FALSE;

		$this->controller->signup();

		$this->assertSame('Please correct the highlighted fields', $this->session->flashdata('toast_error'));
		$views = array_column($this->loader->viewCalls, 'view');
		$this->assertContains('auth/sign_up', $views);
	}

	public function testSignupWhenNoPostShowsEmptyForm() {
		$this->input->postData = [];

		$this->controller->signup();

		$this->assertNull($this->session->flashdata('toast_error'));
		$views = array_column($this->loader->viewCalls, 'view');
		$this->assertContains('auth/sign_up', $views);
	}
}
