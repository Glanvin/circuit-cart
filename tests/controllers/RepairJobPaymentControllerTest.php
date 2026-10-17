<?php

use PHPUnit\Framework\TestCase;

class RepairJobPaymentControllerTest extends TestCase {

	private $session;
	private $toastify;
	private $db;
	private $loader;

	protected function setUp(): void {
		redirect_stop(FALSE);
		$GLOBALS['redirect_target'] = NULL;

		$this->session  = new FakeSession();
		$this->toastify = new FakeToastify($this->session);
		$this->db       = new FakeDb();
		$this->loader   = new FakeLoader();
	}

	private function jobRow(array $overrides = []): array {
		return array_merge([
			'id' => 3,
			'job_reference' => 'RJ-20260101-ABCDE',
			'booking_id' => 9,
			'username' => 'alice',
			'device' => 'iPhone 12',
			'issue' => 'Cracked screen',
			'status' => 'ready_for_pickup',
			'status_history' => json_encode([]),
			'parts_used' => json_encode([['part_id' => 1, 'qty' => 1, 'price' => 500.00]]),
			'labor_items' => json_encode([]),
			'technician_notes' => '',
		], $overrides);
	}

	private function controller(string $role = 'technician'): RepairJobController {
		$this->session->set_userdata('employee_id', 2);
		$this->session->set_userdata('employee_role', $role);

		$c = make_my_controller(RepairJobController::class, $this->session, $this->toastify, $this->db, $this->loader);
		$c->UserModel->db = $this->db;
		$c->config->items['error_codes'] = error_codes();
		return $c;
	}

	public function testRecordPaymentRejectsNonPositiveAmount() {
		$this->db->rows = [$this->jobRow(['status' => 'approved'])];

		$c = $this->controller();
		$c->input->postData = ['amount' => '0', 'kind' => 'payment', 'method_id' => 2];
		$c->recordPayment(3);

		$this->assertNull($this->db->lastInsertTable);
		$this->assertNotNull($this->session->flashdata('toast_error'));
		$this->assertSame('repair/jobs/view/3', redirect_target());
	}

	public function testRecordPaymentStoresDeposit() {
		$this->db->rows = [$this->jobRow(['status' => 'approved'])];
		$this->db->countAllResults = 1;

		$c = $this->controller();
		$c->input->postData = [
			'amount' => '250',
			'kind' => 'deposit',
			'method_id' => 2,
			'reference' => 'GC-9',
		];
		$c->recordPayment(3);

		$this->assertSame('tbl_repair_payments', $this->db->lastInsertTable);
		$this->assertSame(250.00, $this->db->lastInsertData['amount']);
		$this->assertSame('deposit', $this->db->lastInsertData['kind']);
		$this->assertSame(2, $this->db->lastInsertData['recorded_by']);
		$this->assertSame('Payment recorded.', $this->session->flashdata('toast_success'));
	}

	public function testPickupBlockedWhenBalanceOutstanding() {
		$this->db->rows = [$this->jobRow()];

		$c = $this->controller('technician');
		$c->input->postData = [];
		$c->pickup(3);

		$this->assertEmpty($this->db->updateCalls);
		$this->assertStringContainsString('outstanding balance', $this->session->flashdata('toast_error'));
	}

	public function testPickupOnAccountOverrideCompletesJob() {
		$this->db->rows = [$this->jobRow()];

		$c = $this->controller('admin');
		$c->input->postData = ['on_account' => '1'];
		$c->pickup(3);

		$statusUpdates = array_values(array_filter(
			$this->db->updateCalls,
			fn($call) => $call['table'] === 'tbl_repair_jobs' && isset($call['data']['status'])
		));
		$this->assertNotEmpty($statusUpdates);
		$this->assertSame('completed', $statusUpdates[array_key_last($statusUpdates)]['data']['status']);
		$this->assertSame('Job marked as picked up.', $this->session->flashdata('toast_success'));
	}

	public function testPickupWithoutBalanceCompletesJob() {
		$this->db->rows = [$this->jobRow([
			'parts_used' => json_encode([]),
			'labor_items' => json_encode([]),
		])];

		$c = $this->controller('technician');
		$c->input->postData = [];
		$c->pickup(3);

		$statusUpdates = array_values(array_filter(
			$this->db->updateCalls,
			fn($call) => $call['table'] === 'tbl_repair_jobs' && isset($call['data']['status'])
		));
		$this->assertNotEmpty($statusUpdates);
		$this->assertSame('completed', $statusUpdates[array_key_last($statusUpdates)]['data']['status']);
	}
}
