<?php

use PHPUnit\Framework\TestCase;

class ReviewAdminControllerTest extends TestCase {

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

	private function controller(): ReviewAdminController {
		$this->session->set_userdata('employee_id', 1);
		$this->session->set_userdata('employee_role', 'admin');

		return make_my_controller(ReviewAdminController::class, $this->session, $this->toastify, $this->db, $this->loader);
	}

	public function testIndexRendersReviewTable() {
		$this->db->rows = [['id' => 1, 'part_id' => 5, 'rating' => 5, 'status' => 'published']];

		$c = $this->controller();
		$c->index();

		$this->assertContains('pages/employees/review_table', array_column($this->loader->viewCalls, 'view'));
	}

	public function testManagerCanReachList() {
		$this->session->set_userdata('employee_id', 2);
		$this->session->set_userdata('employee_role', 'manager');

		$c = make_my_controller(ReviewAdminController::class, $this->session, $this->toastify, $this->db, $this->loader);
		$c->index();

		$this->assertContains('pages/employees/review_table', array_column($this->loader->viewCalls, 'view'));
	}

	public function testCashierIsRejected() {
		redirect_stop(TRUE);
		$this->session->set_userdata('employee_id', 6);
		$this->session->set_userdata('employee_role', 'cashier');

		try {
			make_my_controller(ReviewAdminController::class, $this->session, $this->toastify, $this->db, $this->loader);
			$this->fail('Expected RedirectException');
		} catch (RedirectException $e) {
		}

		$this->assertSame('pos', redirect_target());
	}

	public function testStatusHidesReview() {
		$c = $this->controller();
		$c->status(1, 'hidden');

		$this->assertSame('tbl_product_reviews', $this->db->updateCalls[0]['table']);
		$this->assertSame(['status' => 'hidden'], $this->db->updateCalls[0]['data']);
		$this->assertSame('Review hidden from the storefront.', $this->session->flashdata('toast_success'));
		$this->assertSame('employee/reviews', redirect_target());
	}

	public function testStatusRejectsBadValue() {
		$c = $this->controller();
		$c->status(1, 'bogus');

		$this->assertNotNull($this->session->flashdata('toast_error'));
		$this->assertSame('employee/reviews', redirect_target());
	}

	public function testDeleteRemovesReview() {
		$this->db->affectedRows = 1;

		$c = $this->controller();
		$c->delete(3);

		$this->assertSame('tbl_product_reviews', $this->db->deleteCalls[0]['table']);
		$this->assertSame('Review deleted.', $this->session->flashdata('toast_success'));
		$this->assertSame('employee/reviews', redirect_target());
	}
}
