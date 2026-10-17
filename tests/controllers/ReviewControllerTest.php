<?php

use PHPUnit\Framework\TestCase;

class ReviewControllerTest extends TestCase {

	private $session;
	private $toastify;
	private $db;
	private $loader;

	protected function setUp(): void {
		redirect_stop(FALSE);
		$GLOBALS['redirect_target'] = NULL;

		$this->session  = new FakeSession();
		$this->session->set_userdata('username', 'alice');
		$this->session->set_userdata('email', 'alice@example.com');
		$this->toastify = new FakeToastify($this->session);
		$this->db       = new FakeDb();
		$this->loader   = new FakeLoader();
	}

	private function controller(): ReviewController {
		return make_my_controller(ReviewController::class, $this->session, $this->toastify, $this->db, $this->loader);
	}

	private function part(array $overrides = []): array {
		return array_merge(['part_id' => 5, 'is_active' => 1], $overrides);
	}

	public function testCreateRejectsMissingProduct() {
		$this->db->rows = [];

		$c = $this->controller();
		$c->create(5);

		$this->assertNotNull($this->session->flashdata('toast_error'));
		$this->assertSame('browse', redirect_target());
	}

	public function testCreateRejectsInactiveProduct() {
		$this->db->rows = [$this->part(['is_active' => 0])];

		$c = $this->controller();
		$c->create(5);

		$this->assertNotNull($this->session->flashdata('toast_error'));
		$this->assertSame('browse', redirect_target());
	}

	public function testCreateRejectsInvalidForm() {
		$this->db->rows = [$this->part()];

		$c = $this->controller();
		$c->form_validation->runResult = FALSE;
		$c->create(5);

		$this->assertNotNull($this->session->flashdata('toast_error'));
		$this->assertSame('browse/5', redirect_target());
	}

	public function testCreateStoresValidReview() {
		$this->db->rows = [$this->part(['id' => 11])];

		$c = $this->controller();
		$c->input->postData = [
			'rating' => '4',
			'title' => 'Solid replacement',
			'body' => 'Fit perfectly and held a charge all day.',
		];
		$c->create(5);

		$this->assertSame('tbl_product_reviews', $this->db->updateCalls[0]['table']);
		$this->assertSame('Thanks! Your review has been posted.', $this->session->flashdata('toast_success'));
		$this->assertSame('browse/5', redirect_target());
	}
}
