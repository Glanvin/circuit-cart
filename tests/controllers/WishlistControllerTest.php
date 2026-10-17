<?php

use PHPUnit\Framework\TestCase;

class WishlistControllerTest extends TestCase {

	private $session;
	private $toastify;
	private $db;
	private $loader;

	protected function setUp(): void {
		redirect_stop(FALSE);
		$GLOBALS['redirect_target'] = NULL;

		$this->session  = new FakeSession();
		$this->session->set_userdata('username', 'alice');
		$this->toastify = new FakeToastify($this->session);
		$this->db       = new FakeDb();
		$this->loader   = new FakeLoader();
	}

	private function controller(): WishlistController {
		return make_my_controller(WishlistController::class, $this->session, $this->toastify, $this->db, $this->loader);
	}

	private function activePart(array $overrides = []): array {
		return array_merge(['part_id' => 5, 'is_active' => 1], $overrides);
	}

	public function testIndexRendersWishlistPage() {
		$this->db->rows = [['part_id' => 5, 'part_name' => 'Battery']];

		$c = $this->controller();
		$c->index();

		$this->assertContains('pages/wishlist', array_column($this->loader->viewCalls, 'view'));
	}

	public function testToggleAddsNewProduct() {
		$this->db->rows = [$this->activePart()];
		$this->db->countAllResults = 0;

		$c = $this->controller();
		$c->toggle(5);

		$this->assertSame('tbl_wishlist', $this->db->lastInsertTable);
		$this->assertSame('Saved to your wishlist.', $this->session->flashdata('toast_success'));
		$this->assertSame('browse', redirect_target());
	}

	public function testToggleRemovesSavedProduct() {
		$this->db->rows = [$this->activePart()];
		$this->db->countAllResults = 1;
		$this->db->affectedRows = 1;

		$c = $this->controller();
		$c->toggle(5);

		$this->assertSame('tbl_wishlist', $this->db->deleteCalls[0]['table']);
		$this->assertSame('Removed from your wishlist.', $this->session->flashdata('toast_success'));
	}

	public function testToggleRejectsInactiveProduct() {
		$this->db->rows = [$this->activePart(['is_active' => 0])];

		$c = $this->controller();
		$c->toggle(5);

		$this->assertNull($this->db->lastInsertTable);
		$this->assertNotNull($this->session->flashdata('toast_error'));
	}

	public function testToggleRejectsMissingProduct() {
		$this->db->rows = [];

		$c = $this->controller();
		$c->toggle(5);

		$this->assertNull($this->db->lastInsertTable);
		$this->assertNotNull($this->session->flashdata('toast_error'));
	}

	public function testToggleReturnsToRefererWhenLocal() {
		$this->db->rows = [$this->activePart()];
		$this->db->countAllResults = 0;

		$c = $this->controller();
		$c->input->serverData['HTTP_REFERER'] = 'http://localhost/browse?category=Screen';
		$c->toggle(5);

		$this->assertSame('http://localhost/browse?category=Screen', redirect_target());
	}

	public function testToggleFallsBackToBrowseWhenRefererIsForeign() {
		$this->db->rows = [$this->activePart()];
		$this->db->countAllResults = 0;

		$c = $this->controller();
		$c->input->serverData['HTTP_REFERER'] = 'https://evil.example.com/phish';
		$c->toggle(5);

		$this->assertSame('browse', redirect_target());
	}

	public function testRemoveDeletesAndRedirectsToWishlist() {
		$this->db->affectedRows = 1;

		$c = $this->controller();
		$c->remove(5);

		$this->assertSame('tbl_wishlist', $this->db->deleteCalls[0]['table']);
		$this->assertSame('Removed from your wishlist.', $this->session->flashdata('toast_success'));
		$this->assertSame('wishlist', redirect_target());
	}
}
