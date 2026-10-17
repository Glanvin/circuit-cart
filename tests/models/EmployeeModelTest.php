<?php

use PHPUnit\Framework\TestCase;

class EmployeeModelTest extends TestCase {

	private FakeDb $db;
	private EmployeeModel $model;

	protected function setUp(): void {
		$ci = new CI_Controller();
		$this->db = new FakeDb();
		$ci->db = $this->db;
		$this->model = new EmployeeModel();
	}

	public function testRefundPinVerificationUsesStoredHashAndManagerAdminScope(): void {
		$this->db->rows = [
			['employee_id' => 3, 'refund_pin_hash' => password_hash('583104', PASSWORD_DEFAULT)],
		];

		$this->assertSame(3, $this->model->verifyRefundApproverPin('583104'));
		$this->assertContains(['role IN', ['manager', 'admin']], $this->db->whereCalls);
		$this->assertContains(['is_active', 1], $this->db->whereCalls);
	}

	public function testRefundPinVerificationRejectsWrongPin(): void {
		$this->db->rows = [['employee_id' => 3, 'refund_pin_hash' => password_hash('583104', PASSWORD_DEFAULT)]];

		$this->assertNull($this->model->verifyRefundApproverPin('583105'));
	}
}
