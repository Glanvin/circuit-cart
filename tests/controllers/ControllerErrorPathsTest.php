<?php

use PHPUnit\Framework\TestCase;

class ControllerErrorPathsTest extends TestCase {

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

		$this->session->set_userdata('username', 'alice');
	}

	private function pageData(string $view): ?array {
		foreach ($this->loader->viewCalls as $call) {
			if ($call['view'] === $view) {
				return $call['data'];
			}
		}
		$this->fail("View '{$view}' was never loaded.");
	}

	// ----------------------------------------------------------------
	// BrowseController
	// ----------------------------------------------------------------

	public function testBrowseRendersActiveParts() {
		$this->db->rows = [
			['part_id' => 1, 'part_name' => 'Screen'],
			['part_id' => 2, 'part_name' => 'Battery'],
		];

		$c = make_my_controller(BrowseController::class, $this->session, $this->toastify, $this->db, $this->loader);
		$c->index();

		$data = $this->pageData('pages/browse');
		$this->assertCount(2, $data['parts']);
		$this->assertSame('Screen', $data['parts'][0]['part_name']);
	}

	public function testBrowseRendersEmptyPartsOnQueryFailureWithToast() {
		$this->db->failQuery = TRUE;

		$c = make_my_controller(BrowseController::class, $this->session, $this->toastify, $this->db, $this->loader);
		$c->index();

		$data = $this->pageData('pages/browse');
		$this->assertSame([], $data['parts']);
		$this->assertSame('We are currently experiencing technical difficulties.', $this->session->flashdata('toast_error'));
	}

	// ----------------------------------------------------------------
	// StockController
	// ----------------------------------------------------------------

	public function testStockRequiresAdmin() {
		redirect_stop(TRUE);
		$this->session->set_userdata('employee_id', 6);
		$this->session->set_userdata('employee_name', 'Casey Cashier');
		$this->session->set_userdata('employee_role', 'cashier');

		try {
			make_my_controller(StockController::class, $this->session, $this->toastify, $this->db, $this->loader);
			$this->fail('Expected RedirectException');
		} catch (RedirectException $e) {
		}

		$this->assertSame('pos', redirect_target());
	}

	public function testStockRendersPartsForAdmin() {
		$this->session->set_userdata('employee_id', 1);
		$this->session->set_userdata('employee_name', 'Alice Admin');
		$this->session->set_userdata('employee_role', 'admin');
		$this->db->rows = [['part_id' => 1, 'sku' => 'ABC-1', 'stock_quantity' => 5]];

		$c = make_my_controller(StockController::class, $this->session, $this->toastify, $this->db, $this->loader);
		$c->index();

		$data = $this->pageData('pages/inventory/stock_table');
		$this->assertCount(1, $data['parts']);
		$this->assertSame('ABC-1', $data['parts'][0]['sku']);
	}

	public function testStockRendersEmptyPartsOnQueryFailureWithToast() {
		$this->session->set_userdata('employee_id', 1);
		$this->session->set_userdata('employee_name', 'Alice Admin');
		$this->session->set_userdata('employee_role', 'admin');
		$this->db->failQuery = TRUE;

		$c = make_my_controller(StockController::class, $this->session, $this->toastify, $this->db, $this->loader);
		$c->index();

		$data = $this->pageData('pages/inventory/stock_table');
		$this->assertSame([], $data['parts']);
		$this->assertNotEmpty($this->session->flashdata('toast_error'));
	}

	public function testStockStubMethodsAreCallableNoops() {
		$this->session->set_userdata('employee_id', 1);
		$this->session->set_userdata('employee_name', 'Alice Admin');
		$this->session->set_userdata('employee_role', 'admin');
		$c = make_my_controller(StockController::class, $this->session, $this->toastify, $this->db, $this->loader);

		$c->addStock();
		$c->newSKU();

		$this->assertTrue(TRUE);
	}
}
