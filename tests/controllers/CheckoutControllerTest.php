<?php

use PHPUnit\Framework\TestCase;

class CheckoutControllerTest extends TestCase {

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

	private function controller(): CheckoutController {
		return make_my_controller(CheckoutController::class, $this->session, $this->toastify, $this->db, $this->loader);
	}

	private function promo(array $overrides = []): array {
		return array_merge([
			'id' => 1,
			'code' => 'WELCOME10',
			'discount_type' => 'percent',
			'discount_value' => '10.00',
			'min_subtotal' => '0.00',
			'max_total_uses' => NULL,
			'used_count' => 0,
			'per_user_limit' => NULL,
			'starts_at' => NULL,
			'expires_at' => NULL,
			'is_active' => 1,
		], $overrides);
	}

	public function testApplyPromoRejectsEmptyCode() {
		$c = $this->controller();
		$c->input->postData = ['promo_code' => '   '];
		$c->applyPromo();

		$this->assertSame('checkout', redirect_target());
		$this->assertSame('Enter a promo code.', $this->session->flashdata('toast_error'));
	}

	public function testApplyPromoRejectsWhenBuyNowIsActive() {
		$this->session->set_userdata('buy_now_item', ['part_id' => 1, 'quantity' => 1]);
		$this->session->set_userdata('cart_items', [
			['part_id' => 1, 'quantity' => 1, 'price' => 100.0],
		]);

		$c = $this->controller();
		$c->input->postData = ['promo_code' => 'WELCOME10'];
		$c->applyPromo();

		$this->assertSame('checkout', redirect_target());
		$this->assertSame('Promo codes cannot be used with Buy Now.', $this->session->flashdata('toast_error'));
		$this->assertNull($this->session->userdata('promo_code'));
	}

	public function testApplyPromoStoresValidCodeInSession() {
		$this->db->rows = [$this->promo()];
		$this->session->set_userdata('cart_items', [
			['part_id' => 1, 'quantity' => 2, 'price' => 100.0],
		]);

		$c = $this->controller();
		$c->input->postData = ['promo_code' => 'welcome10'];
		$c->applyPromo();

		$this->assertSame('WELCOME10', $this->session->userdata('promo_code'));
		$this->assertSame('Promo code WELCOME10 applied.', $this->session->flashdata('toast_success'));
		$this->assertSame('checkout', redirect_target());
	}

	public function testApplyPromoClearsInvalidCode() {
		$this->db->rows = [$this->promo(['is_active' => 0])];
		$this->session->set_userdata('promo_code', 'WELCOME10');
		$this->session->set_userdata('cart_items', [
			['part_id' => 1, 'quantity' => 1, 'price' => 100.0],
		]);

		$c = $this->controller();
		$c->input->postData = ['promo_code' => 'WELCOME10'];
		$c->applyPromo();

		$this->assertNull($this->session->userdata('promo_code'));
		$this->assertNotNull($this->session->flashdata('toast_error'));
		$this->assertSame('checkout', redirect_target());
	}

	public function testRemovePromoClearsSession() {
		$this->session->set_userdata('promo_code', 'WELCOME10');

		$c = $this->controller();
		$c->removePromo();

		$this->assertNull($this->session->userdata('promo_code'));
		$this->assertSame('Promo code removed.', $this->session->flashdata('toast_success'));
		$this->assertSame('checkout', redirect_target());
	}
}
