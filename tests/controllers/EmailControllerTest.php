<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/env_helper.php';
require_once APPPATH . 'controllers/EmailController.php';

class EmailControllerTest extends TestCase {

	private $controller;
	private $session;
	private $toastify;
	private $loader;
	private $email;

	protected function setUp(): void {
		redirect_stop(FALSE);
		$GLOBALS['redirect_target'] = NULL;

		$this->session  = new FakeSession();
		$this->toastify = new FakeToastify($this->session);
		$this->loader   = new FakeLoader();
		$this->email    = new FakeEmail();

		$this->session->set_userdata('username', 'alice');
		$this->session->set_userdata('email', 'alice@example.com');

		$this->controller = new EmailController();
		$this->controller->session  = $this->session;
		$this->controller->toastify = $this->toastify;
		$this->controller->load     = $this->loader;
		$this->controller->email    = $this->email;
	}

	private function views(): array {
		return array_column($this->loader->viewCalls, 'view');
	}

	public function testIndexRendersWelcomeEmailPreview() {
		$this->controller->index();

		$this->assertContains('emails/welcome_email', $this->views());
	}

	public function testSendWelcomeSendsToSessionEmailAndRedirectsHome() {
		$this->email->sendResult = TRUE;

		$this->controller->sendWelcome();

		$this->assertContains('emails/welcome_email', $this->views());
		$this->assertTrue($this->email->sent);
		$this->assertSame('alice@example.com', $this->email->to);
		$this->assertSame('Welcome to Circuit Cart', $this->email->subject);
		$this->assertSame('home', redirect_target());
	}

	public function testSendWelcomeFailurePrintsDebuggerWithoutRedirect() {
		$this->email->sendResult = FALSE;

		ob_start();
		$this->controller->sendWelcome();
		$output = ob_get_clean();

		$this->assertStringContainsString('SMTP Error Details:', $output);
		$this->assertFalse($this->email->sent);
		$this->assertNull(redirect_target());
	}

	public function testSendOTPUsesOtpSubjectAndRendersVerifyPage() {
		$this->email->sendResult = TRUE;

		$this->controller->sendOTP();

		$this->assertContains('emails/opt_email', $this->views());
		$this->assertTrue($this->email->sent);
		$this->assertSame('alice@example.com', $this->email->to);
		$this->assertSame('Verify Your Email - Circuit Cart', $this->email->subject);
		$this->assertContains('auth/verify_email', $this->views());
		$this->assertStringContainsString('A verification code has been sent to your email.', $this->session->flashdata('toast_success'));
	}

	public function testSendOTPFailureShowsErrorToastAndVerifyPage() {
		$this->email->sendResult = FALSE;

		$this->controller->sendOTP();

		$this->assertContains('auth/verify_email', $this->views());
		$this->assertFalse($this->email->sent);
		$this->assertStringContainsString('Unable to send the verification code', $this->session->flashdata('toast_error'));
	}

	public function testSendOTPFetchesEmailFromDatabaseWhenSessionLacksIt() {
		$this->session->unset_userdata('email');

		$db = new FakeDb();
		$db->rows = [
			['username' => 'alice', 'email' => 'alice@example.com'],
		];
		$this->controller->db = $db;
		$this->controller->UserModel = new UserModel();

		$this->controller->sendOTP();

		$this->assertSame('alice@example.com', $this->email->to);
		$this->assertSame('alice@example.com', $this->session->userdata('email'));
	}
}