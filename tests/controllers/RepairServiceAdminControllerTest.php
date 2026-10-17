<?php

use PHPUnit\Framework\TestCase;

class RepairServiceAdminControllerTest extends TestCase {

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

	private function controller(string $role = 'admin'): RepairServiceAdminController {
		$this->session->set_userdata('employee_id', 1);
		$this->session->set_userdata('employee_role', $role);

		return make_my_controller(RepairServiceAdminController::class, $this->session, $this->toastify, $this->db, $this->loader);
	}

	public function testIndexRendersServiceTable() {
		$this->db->rows = [['id' => 1, 'name' => 'Diagnostics', 'is_active' => 1]];

		$c = $this->controller();
		$c->index();

		$this->assertContains('pages/employees/service_table', array_column($this->loader->viewCalls, 'view'));
	}

	public function testManagerCanReachList() {
		$c = $this->controller('manager');
		$c->index();

		$this->assertContains('pages/employees/service_table', array_column($this->loader->viewCalls, 'view'));
	}

	public function testCashierIsRejected() {
		redirect_stop(TRUE);
		$this->session->set_userdata('employee_id', 6);
		$this->session->set_userdata('employee_role', 'cashier');

		try {
			make_my_controller(RepairServiceAdminController::class, $this->session, $this->toastify, $this->db, $this->loader);
			$this->fail('Expected RedirectException');
		} catch (RedirectException $e) {
		}

		$this->assertSame('pos', redirect_target());
	}

	public function testToggleDeactivatesActiveService() {
		$this->db->rows = [['id' => 1, 'name' => 'Diagnostics', 'is_active' => 1]];

		$c = $this->controller();
		$c->toggle(1);

		$this->assertSame('tbl_repair_services', $this->db->updateCalls[0]['table']);
		$this->assertSame(0, $this->db->updateCalls[0]['data']['is_active']);
		$this->assertSame('Service deactivated.', $this->session->flashdata('toast_success'));
		$this->assertSame('employee/services', redirect_target());
	}

	public function testToggleActivatesInactiveService() {
		$this->db->rows = [['id' => 1, 'name' => 'Diagnostics', 'is_active' => 0]];

		$c = $this->controller();
		$c->toggle(1);

		$this->assertSame(1, $this->db->updateCalls[0]['data']['is_active']);
		$this->assertSame('Service activated.', $this->session->flashdata('toast_success'));
	}

	public function testNewCreatesService() {
		$this->db->insertId = 9;

		$c = $this->controller();
		$c->input->postData = [
			'create_service' => 1,
			'name' => 'Charging Port Repair',
			'description' => 'Fixes charging',
			'base_price' => '600',
			'sort_order' => '4',
		];
		$c->new();

		$this->assertSame('tbl_repair_services', $this->db->lastInsertTable);
		$this->assertSame('Charging Port Repair', $this->db->lastInsertData['name']);
		$this->assertSame('Repair service created.', $this->session->flashdata('toast_success'));
		$this->assertSame('employee/services', redirect_target());
	}

	public function testNewRejectsEmptyName() {
		$c = $this->controller();
		$c->input->postData = ['create_service' => 1, 'name' => ''];
		$c->new();

		$this->assertNull($this->db->lastInsertTable);
		$this->assertNotNull($this->session->flashdata('toast_error'));
	}

	public function testEditRejectsMissingService() {
		$this->db->rows = [];

		$c = $this->controller();
		$c->edit(404);

		$this->assertNotNull($this->session->flashdata('toast_error'));
		$this->assertSame('employee/services', redirect_target());
	}
}
