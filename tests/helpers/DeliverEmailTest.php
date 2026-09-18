<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/env_helper.php';
require_once APPPATH . 'helpers/env_helper.php';
require_once APPPATH . 'helpers/my_helper.php';

class DeliverEmailTest extends TestCase {

	private $email;
	private $loader;

	protected function setUp(): void {
		// Point the CI singleton at a bare controller carrying the shared
		// fakes so deliver_email() renders through FakeLoader and dispatches
		// through FakeEmail.
		$ci = new CI_Controller();
		$ci->load  = $this->loader = new FakeLoader();
		$ci->email = $this->email  = new FakeEmail();
	}

	private function sharedInstance(): CI_Controller {
		return CI_Controller::get_instance();
	}

	public function testDeliversRenderedViewToRecipientWithGivenSubjectAndFrom() {
		$ok = deliver_email(
			'emails/welcome_email',
			['subject' => 'Welcome to Circuit Cart'],
			'new@example.com',
			'Welcome to Circuit Cart',
			'no-reply@circuitcart.test'
		);

		$this->assertTrue($ok);
		$this->assertSame('new@example.com', $this->email->to);
		$this->assertSame('Welcome to Circuit Cart', $this->email->subject);
		$this->assertSame(['no-reply@circuitcart.test', 'Circuit Cart'], $this->email->from);
		$this->assertNotSame('', $this->email->message);
		$this->assertTrue($this->email->sent);
		$this->assertSame(1, $this->email->sendCalls);
	}

	public function testDefaultsFromToSmtpUserWhenNotProvided() {
		deliver_email('emails/welcome_email', [], 'a@example.com', 'Subject');

		$this->assertSame([app_env('SMTP_USER', ''), 'Circuit Cart'], $this->email->from);
	}

	public function testReturnsFalseWhenSendFailsWithoutThrowing() {
		$this->email->sendResult = FALSE;

		$ok = deliver_email('emails/welcome_email', [], 'a@example.com', 'Subject');

		$this->assertFalse($ok);
		$this->assertFalse($this->email->sent);
	}
}
