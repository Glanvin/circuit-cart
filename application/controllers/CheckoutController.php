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

		$promoCode = $buyNow !== NULL ? '' : (string) $this->session->userdata('promo_code');
		$customerEmail = $this->session->userdata('email');

		try {
			$quote = $this->SalesModel->quoteOnlineOrder($items, $promoCode, $customerEmail);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			if ($promoCode !== '') {
				$this->session->unset_userdata('promo_code');
			}
			redirect('cart', 'refresh');
			return;
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
			'checkout_items' => $quote['items'],
			'source' => $source,
			'subtotal' => $quote['subtotal'],
			'discount' => $quote['discount'],
			'promo_code' => $quote['promo_code'],
			'shipping' => $quote['shipping'],
			'tax' => $quote['tax'],
			'total' => $quote['total'],
			'is_guest' => !$this->session->userdata('username'),
			'delivery_address' => $address,
			'buy_now' => $buyNow !== NULL,
		]);
	}

	//Validates a promo code against the current cart subtotal and stores it in
	//the session so the checkout summary can apply it. Buy Now purchases are a
	//standalone line and do not accept promo codes.
	public function applyPromo()
	{
		if ($this->session->userdata('buy_now_item') !== NULL) {
			$this->toastify->error('Promo codes cannot be used with Buy Now.');
			redirect('checkout', 'refresh');
			return;
		}

		$code = (string) $this->input->post('promo_code', true);

		if (trim($code) === '') {
			$this->toastify->error('Enter a promo code.');
			redirect('checkout', 'refresh');
			return;
		}

		$subtotal = 0.0;

		foreach (cart_items() as $item) {
			$subtotal += (float) $item['price'] * (int) $item['quantity'];
		}

		try {
			$promo = $this->PromoModel->validatePromoCode($code, round($subtotal, 2), $this->session->userdata('email'));
			$this->session->set_userdata('promo_code', $promo['code']);
			$this->toastify->success('Promo code ' . $promo['code'] . ' applied.');
		} catch (AppException $e) {
			$this->session->unset_userdata('promo_code');
			$this->toastify->error($e->toToast());
		}

		redirect('checkout', 'refresh');
	}

	//Clears any applied promo code while staying on checkout.
	public function removePromo()
	{
		$this->session->unset_userdata('promo_code');
		$this->toastify->success('Promo code removed.');
		redirect('checkout', 'refresh');
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
		$paymentCode = (string) $this->input->post('payment_code', true);
		if (!in_array($paymentCode, ['cod', 'gcash', 'gotyme', 'card'], true)) {
			$this->toastify->error('Choose a valid payment method.');
			redirect('checkout', 'refresh');
			return;
		}
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
			$rules = $this->config->item('checkout_user');
			$this->form_validation->set_rules($rules);

			if ($this->form_validation->run() === FALSE) {
				$this->toastify->error('Please provide a valid delivery address.');
				redirect('checkout', 'refresh');
				return;
			}

			if (trim($address) === '') {
				try {
					$user = $this->UserModel->findUserByUsername($this->session->userdata('username'));
					$address = trim((string) ($user['address'] ?? ''));
				} catch (Exception $e) {
					$address = '';
				}
			}

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

		$promoCode = $buyNow !== NULL ? '' : (string) $this->session->userdata('promo_code');

		try {
			$sale = $this->SalesModel->recordSale([
				'origin' => 'online',
				'sold_by' => $soldBy,
				'customer_email' => $email,
				'customer_address' => $address,
				'payment_code' => $paymentCode,
				'promo_code' => $promoCode,
				'items' => $lines,
			]);

			$this->session->unset_userdata('buy_now_item');
			$this->session->unset_userdata('promo_code');
			$this->session->set_userdata('cart_items', []);
			$this->session->set_flashdata('last_order', $sale);

			send_order_confirmation_email($sale);

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
