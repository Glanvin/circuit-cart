<?php

use PHPUnit\Framework\TestCase;

class ControllerPagesTest extends TestCase {

	private $session;
	private $toastify;
	private $loader;

	protected function setUp(): void {
		redirect_stop(FALSE);
		$GLOBALS['redirect_target'] = NULL;

		$this->session  = new FakeSession();
		$this->toastify = new FakeToastify($this->session);
		$this->loader   = new FakeLoader();
	}

	private function views(): array {
		return array_column($this->loader->viewCalls, 'view');
	}

	public function testHomeRendersHomePage() {
		$this->session->set_userdata('username', 'alice');

		$c = make_my_controller(HomeController::class, $this->session, $this->toastify, new FakeDb(), $this->loader);
		$c->index();

		$this->assertContains('pages/home', $this->views());
	}

	public function testDashboardRequiresAdminAndRendersDashboard() {
		$this->session->set_userdata('employee_id', 1);
		$this->session->set_userdata('employee_name', 'Alice Admin');
		$this->session->set_userdata('employee_role', 'admin');

		$c = make_my_controller(DashboardController::class, $this->session, $this->toastify, new FakeDb(), $this->loader);
		$c->index();

		$this->assertContains('pages/employees/dashboard', $this->views());
	}

	public function testDashboardRejectsNonAdmin() {
		redirect_stop(TRUE);
		$this->session->set_userdata('employee_id', 6);
		$this->session->set_userdata('employee_name', 'Casey Cashier');
		$this->session->set_userdata('employee_role', 'cashier');

		try {
			make_my_controller(DashboardController::class, $this->session, $this->toastify, new FakeDb(), $this->loader);
			$this->fail('Expected RedirectException');
		} catch (RedirectException $e) {
		}

		$this->assertSame('pos', redirect_target());
	}

	public function testManagementRequiresAdminAndRendersManagePage() {
		$this->session->set_userdata('employee_id', 1);
		$this->session->set_userdata('employee_name', 'Alice Admin');
		$this->session->set_userdata('employee_role', 'admin');

		$c = make_my_controller(ManagementController::class, $this->session, $this->toastify, new FakeDb(), $this->loader);
		$c->users();

		$this->assertContains('pages/employees/manage_users', $this->views());
	}

	public function testManagementRejectsNonAdmin() {
		redirect_stop(TRUE);
		$this->session->set_userdata('employee_id', 6);
		$this->session->set_userdata('employee_name', 'Casey Cashier');
		$this->session->set_userdata('employee_role', 'cashier');

		try {
			make_my_controller(ManagementController::class, $this->session, $this->toastify, new FakeDb(), $this->loader);
			$this->fail('Expected RedirectException');
		} catch (RedirectException $e) {
		}

		$this->assertSame('pos', redirect_target());
	}

	public function testPromoAdminRequiresManagerAndRendersList() {
		$this->session->set_userdata('employee_id', 2);
		$this->session->set_userdata('employee_name', 'Morgan Manager');
		$this->session->set_userdata('employee_role', 'manager');

		$c = make_my_controller(PromoAdminController::class, $this->session, $this->toastify, new FakeDb(), $this->loader);
		$c->index();

		$this->assertContains('pages/employees/promo_table', $this->views());
	}

	public function testPromoAdminRejectsCashier() {
		redirect_stop(TRUE);
		$this->session->set_userdata('employee_id', 6);
		$this->session->set_userdata('employee_name', 'Casey Cashier');
		$this->session->set_userdata('employee_role', 'cashier');

		try {
			make_my_controller(PromoAdminController::class, $this->session, $this->toastify, new FakeDb(), $this->loader);
			$this->fail('Expected RedirectException');
		} catch (RedirectException $e) {
		}

		$this->assertSame('pos', redirect_target());
	}

	public function testPromoAdminNewRendersForm() {
		$this->session->set_userdata('employee_id', 1);
		$this->session->set_userdata('employee_name', 'Alice Admin');
		$this->session->set_userdata('employee_role', 'admin');

		$c = make_my_controller(PromoAdminController::class, $this->session, $this->toastify, new FakeDb(), $this->loader);
		$c->new();

		$this->assertContains('pages/employees/promo_form', $this->views());
		$last = end($this->loader->viewCalls);
		$this->assertTrue($last['data']['is_new']);
	}

	public function testBrowsePaginatesAndPassesPaginationData() {
		$this->session->set_userdata('username', 'alice');
		$db = new FakeDb();
		$db->rows = [['part_id' => 1, 'part_name' => 'Screen']];
		$db->countAllResults = 25;

		$c = make_my_controller(BrowseController::class, $this->session, $this->toastify, $db, $this->loader);
		$c->index();

		$this->assertSame([12, 0], $db->limitCalls[0]);

		$data = $this->views();
		$browse = NULL;
		foreach ($this->loader->viewCalls as $call) {
			if ($call['view'] === 'pages/browse') {
				$browse = $call['data'];
			}
		}
		$this->assertNotNull($browse);
		$this->assertSame(25, $browse['total_results']);
		$this->assertSame(3, $browse['pagination']['total_pages']);
		$this->assertSame(1, $browse['pagination']['current']);
	}

	public function testProductDetailRendersGalleryAndRelated() {
		$this->session->set_userdata('username', 'alice');
		$db = new FakeDb();
		$db->rows = [[
			'part_id' => 5,
			'part_name' => 'Screen',
			'sku' => 'SCR-APL-IP11',
			'category' => 'Screen',
			'device_type' => 'iPhone',
			'selling_price' => '100.00',
			'stock_quantity' => 3,
			'image_path' => 'storage/products/screen.webp',
			'is_active' => 1,
			'compatibility' => '[]',
			'technical_specs' => '{}',
		]];

		$c = make_my_controller(BrowseController::class, $this->session, $this->toastify, $db, $this->loader);
		$c->product(5);

		$detail = NULL;
		foreach ($this->loader->viewCalls as $call) {
			if ($call['view'] === 'pages/product_detail') {
				$detail = $call['data'];
			}
		}

		$this->assertNotNull($detail);
		$this->assertSame(5, $detail['part']['part_id']);
		$this->assertArrayHasKey('gallery', $detail);
		$this->assertArrayHasKey('related', $detail);
	}
}
