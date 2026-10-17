<?php

use PHPUnit\Framework\TestCase;

class CartControllerTest extends TestCase {

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

	private function controller(): CartController {
		return make_my_controller(CartController::class, $this->session, $this->toastify, $this->db, $this->loader);
	}

	private function json(CartController $c): array {
		return json_decode($c->output->output, true);
	}

	public function testUpdateCapsQuantityAtAvailableStock() {
		$this->db->rows = [['part_id' => 1, 'is_active' => 1, 'stock_quantity' => 2]];
		$this->session->set_userdata('cart_items', [
			['part_id' => 1, 'quantity' => 2, 'price' => 100.0],
		]);

		$c = $this->controller();
		$c->input->postData = ['action' => 'inc'];
		$c->update(1);

		$json = $this->json($c);
		$this->assertFalse($json['ok']);
		$this->assertSame('The cart quantity already matches available stock.', $json['error']);

		//The line is left untouched.
		$cart = $this->session->userdata('cart_items');
		$this->assertSame(2, (int) $cart[0]['quantity']);
	}

	public function testUpdateRejectsUnavailableProduct() {
		$this->db->rows = [['part_id' => 1, 'is_active' => 0, 'stock_quantity' => 0]];
		$this->session->set_userdata('cart_items', [
			['part_id' => 1, 'quantity' => 1, 'price' => 100.0],
		]);

		$c = $this->controller();
		$c->input->postData = ['action' => 'inc'];
		$c->update(1);

		$json = $this->json($c);
		$this->assertFalse($json['ok']);
		$this->assertSame('This product is currently unavailable.', $json['error']);
	}

	public function testUpdateIncrementsWhenUnderStock() {
		$this->db->rows = [['part_id' => 1, 'is_active' => 1, 'stock_quantity' => 3]];
		$this->session->set_userdata('cart_items', [
			['part_id' => 1, 'quantity' => 1, 'price' => 100.0],
		]);

		$c = $this->controller();
		$c->input->postData = ['action' => 'inc'];
		$c->update(1);

		$json = $this->json($c);
		$this->assertTrue($json['ok']);
		$this->assertSame(2, $json['quantity']);
		$this->assertSame(2, $json['count']);
	}

	public function testUpdateDecrementRemovesLineAtZero() {
		$this->session->set_userdata('cart_items', [
			['part_id' => 1, 'quantity' => 1, 'price' => 100.0],
		]);

		$c = $this->controller();
		$c->input->postData = ['action' => 'dec'];
		$c->update(1);

		$json = $this->json($c);
		$this->assertTrue($json['ok']);
		$this->assertTrue($json['removed']);
		$this->assertSame(0, $json['count']);
	}

	public function testUpdateRejectsInvalidAction() {
		$c = $this->controller();
		$c->input->postData = ['action' => 'explode'];
		$c->update(1);

		$json = $this->json($c);
		$this->assertFalse($json['ok']);
		$this->assertSame('Invalid action.', $json['error']);
	}

	public function testUpdateReportsMissingLine() {
		$this->session->set_userdata('cart_items', [
			['part_id' => 2, 'quantity' => 1, 'price' => 100.0],
		]);

		$c = $this->controller();
		$c->input->postData = ['action' => 'dec'];
		$c->update(1);

		$json = $this->json($c);
		$this->assertFalse($json['ok']);
		$this->assertSame('Item not in cart.', $json['error']);
	}
}
