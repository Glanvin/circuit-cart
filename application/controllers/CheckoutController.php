<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CheckoutController extends MY_Controller {

	protected bool $requireLogin = FALSE;

	//Renders the checkout summary. Guests must supply a delivery email and
	//address; signed-in users get the address pre-filled from their account.
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

		$subtotal = 0;

		foreach ($items as $item) {
			$subtotal += (float) $item['price'] * (int) $item['quantity'];
		}

		$address = '';

		if ($this->session->userdata('username')) {
			try {
				$user = $this->UserModel->findUserByUsername($this->session->userdata('username'));
				$address = $user['address'] ?? '';
			} catch (Exception $e) {
				//A missing account should not block checkout.
			}
		}

		$this->render('pages/checkout', [
			'title' => 'Checkout',
			'checkout_items' => $items,
			'source' => $source,
			'subtotal' => $subtotal,
			'shipping' => 10.00,
			'tax' => 20.00,
			'is_guest' => !$this->session->userdata('username'),
			'delivery_address' => $address,
		]);
	}

	//Places an online order: validates delivery info for guests, records the
	//sale (origin=online, status=processing), decrements stock, and clears
	//the cart/buy-now line.
	public function placeOrder()
	{
		$buyNow = $this->session->userdata('buy_now_item');

		if ($buyNow !== NULL) {
			$items = is_array($buyNow) ? [$buyNow] : [];
		} else {
			$items = cart_items();
		}

		if (empty($items)) {
			redirect('browse', 'refresh');
		}

		$address = (string) $this->input->post('delivery_address', true);
		$isGuest = !$this->session->userdata('username');

		if ($isGuest) {
			$rules = $this->config->item('checkout_guest');
			$this->form_validation->set_rules($rules);

			if ($this->form_validation->run() === FALSE) {
				$this->toastify->error('Please provide a delivery email and address.');
				redirect('checkout', 'refresh');
				return;
			}

			$email = (string) $this->input->post('guest_email', true);
			$soldBy = NULL;
		} else {
			if (trim($address) === '') {
				$this->toastify->error('Please provide a delivery address.');
				redirect('checkout', 'refresh');
				return;
			}

			$email = $this->session->userdata('email');
			$soldBy = $this->session->userdata('username');
		}

		$lines = [];

		foreach ($items as $item) {
			$lines[] = [
				'part_id' => (int) $item['part_id'],
				'qty' => (int) $item['quantity'],
			];
		}

		try {
			$sale = $this->SalesModel->recordSale([
				'origin' => 'online',
				'sold_by' => $soldBy,
				'customer_email' => $email,
				'customer_address' => $address,
				'items' => $lines,
			]);

			$this->session->unset_userdata('buy_now_item');
			$this->session->set_userdata('cart_items', []);
			$this->session->set_flashdata('last_order', $sale);

			redirect('checkout/success', 'refresh');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			redirect('checkout', 'refresh');
		}
	}

	//Confirmation page after a successful online order.
	public function success()
	{
		$sale = $this->session->flashdata('last_order');

		if (!is_array($sale)) {
			redirect('home', 'refresh');
		}

		$sale['items'] = json_decode($sale['items'] ?? '[]', true);

		$this->render('pages/checkout_success', [
			'title' => 'Order Confirmed',
			'sale' => $sale,
		]);
	}
}