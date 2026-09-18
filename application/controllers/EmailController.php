<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EmailController extends CI_Controller {

	public function index()
	{
		$data = [
			'subject'  => 'Welcome to Circuit Cart',
			'greeting' => 'Dear User,',
			'message'  => 'Welcome to Circuit Cart',
		];

		$this->load->view('emails/welcome_email', $data);
	}

	public function sendWelcome()
	{
		$data = [
			'subject'  => 'Welcome to Circuit Cart',
			'greeting' => "Dear {$this->session->userdata('username')},",
			'message'  => 'Welcome to Circuit Cart',
		];

		if ($this->_deliver('emails/welcome_email', $data, $data['subject'])) {
			$this->toastify->success('Welcome email sent.');
			redirect('home', 'refresh');
		}

		echo "SMTP Error Details:<br>";
		echo $this->email->print_debugger();
	}

	public function sendOTP()
	{
		$otpData = [
			'subject'  => 'Verify Your Email - Circuit Cart',
			'greeting' => "Dear {$this->session->userdata('username')},",
			'message'  => "We're thrilled to have you as part of the Circuit Cart community. To finish securing your account, please verify your email address using the code we sent you.",
		];

		if ($this->_deliver('emails/opt_email', $otpData, $otpData['subject'])) {
			$this->toastify->success('A verification code has been sent to your email.');
		} else {
			$this->toastify->error('Unable to send the verification code. Please try again.');
		}

		$this->_showVerifyPage();
	}

	private function _deliver(string $view, array $data, string $subject): bool
	{
		$this->ensureEmailInSession();

		return deliver_email($view, $data, (string) $this->session->userdata('email'), $subject);
	}

	private function ensureEmailInSession(): void
	{
		if (!empty($this->session->userdata('email'))) {
			return;
		}

		$username = $this->session->userdata('username');
		if (empty($username)) {
			return;
		}

		$user = $this->UserModel->findUserByUsername($username);

		if (!empty($user['email'])) {
			$this->session->set_userdata('email', $user['email']);
		}
	}

	private function _showVerifyPage(array $data = []): void
	{
		$data = array_merge([
			'title'         => 'Verify Email',
			'enable_footer' => FALSE,
		], $data);

		$this->load->view('templates/header', $data);
		$this->load->view('auth/verify_email', $data);
	}
}