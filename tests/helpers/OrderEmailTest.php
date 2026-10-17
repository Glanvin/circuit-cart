<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/env_helper.php';
require_once APPPATH . 'helpers/my_helper.php';

class OrderEmailTest extends TestCase {

	private $email;
	private $loader;

	protected function setUp(): void {
		$ci = new CI_Controller();
		$ci->load  = $this->loader = new FakeLoader();
		$ci->email = $this->email  = new FakeEmail();
	}

	private function sale(string $status = 'processing'): array {
		return [
			'receipt_no' => 'CC-20260101-0001',
			'customer_email' => 'buyer@example.com',
			'customer_address' => '123 Main St',
			'total' => 199.00,
			'status' => $status,
			'items' => [],
		];
	}

	public function testOrderConfirmationEmailsTheCustomer() {
		send_order_confirmation_email($this->sale());

		$this->assertSame('buyer@example.com', $this->email->to);
		$this->assertStringContainsString('Order Confirmed', $this->email->subject);
		$this->assertTrue($this->email->sent);
	}

	public function testOrderStatusEmailsTheCustomer() {
		send_order_status_email($this->sale('shipped'), 'shipped');

		$this->assertSame('buyer@example.com', $this->email->to);
		$this->assertStringContainsString('Order Update', $this->email->subject);
		$this->assertTrue($this->email->sent);
	}

	public function testOrderEmailsSkipWhenNoRecipient() {
		send_order_confirmation_email(['receipt_no' => 'X', 'customer_email' => '']);

		$this->assertFalse($this->email->sent);
	}

	public function testOrderEmailsSwallowFailures() {
		$this->email->sendResult = FALSE;

		// Should not throw
		send_order_confirmation_email($this->sale());
		send_order_status_email($this->sale('delivered'), 'delivered');

		$this->assertTrue(TRUE);
	}
}
