<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CheckoutController extends MY_Controller {

	public function index()
	{
		//A Buy Now purchase is a standalone line, independent of the session cart.
		$buyNow = $this->session->userdata('buy_now_item');

		if ($buyNow !== NULL) {
			$items = is_array($buyNow) ? [$buyNow] : [];
			$source = 'Buy Now';
		} else {
			$items = cart_items();
			$source = 'Cart';
		}

		if (empty($items)) {
			redirect('browse', 'refresh');
		}

		$this->render('pages/checkout', [
			'title' => 'Checkout',
			'checkout_items' => $items,
			'source' => $source,
		]);
	}
}