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
		$this->session->set_userdata('username', 'admin1');
		$this->session->set_userdata('role', 'admin');

		$c = make_my_controller(DashboardController::class, $this->session, $this->toastify, new FakeDb(), $this->loader);
		$c->index();

		$this->assertContains('pages/employees/dashboard', $this->views());
	}

	public function testDashboardRejectsNonAdmin() {
		redirect_stop(TRUE);
		$this->session->set_userdata('username', 'staff1');
		$this->session->set_userdata('role', 'staff');

		try {
			make_my_controller(DashboardController::class, $this->session, $this->toastify, new FakeDb(), $this->loader);
			$this->fail('Expected RedirectException');
		} catch (RedirectException $e) {
		}

		$this->assertSame('home', redirect_target());
	}

	public function testManagementRequiresAdminAndRendersManagePage() {
		$this->session->set_userdata('username', 'admin1');
		$this->session->set_userdata('role', 'admin');

		$c = make_my_controller(ManagementController::class, $this->session, $this->toastify, new FakeDb(), $this->loader);
		$c->index();

		$this->assertContains('pages/manage', $this->views());
	}

	public function testManagementRejectsNonAdmin() {
		redirect_stop(TRUE);
		$this->session->set_userdata('username', 'staff1');
		$this->session->set_userdata('role', 'staff');

		try {
			make_my_controller(ManagementController::class, $this->session, $this->toastify, new FakeDb(), $this->loader);
			$this->fail('Expected RedirectException');
		} catch (RedirectException $e) {
		}

		$this->assertSame('home', redirect_target());
	}
}
