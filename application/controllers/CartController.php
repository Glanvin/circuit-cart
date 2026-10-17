<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CartController extends MY_Controller {

	protected bool $requireLogin = FALSE;

	//Renders the shopping cart page with the session cart lines.
	public function index()
	{
		$this->render('pages/cart', [
			'title' => 'Cart',
			'cart_items' => cart_items(),
			'promo_code' => $this->session->userdata('promo_code'),
		]);
	}

	/*
	 * Adds a part to the session cart, bumping the quantity when it is already
	 * present, then redirects back to the browse page.
	 */
	public function add(int $part_id)
	{
		try {
			$part = $this->PartsModel->getPartById($part_id);
			if ((int) $part['is_active'] !== 1 || (int) $part['stock_quantity'] < 1) {
				$this->toastify->error('This product is currently unavailable.');
				redirect('browse', 'refresh');
				return;
			}

			$cart = cart_items();

			foreach ($cart as &$line) {
				if ((int) $line['part_id'] === $part_id) {
					if ((int) $line['quantity'] >= (int) $part['stock_quantity']) {
						$this->toastify->error('The cart quantity already matches available stock.');
						redirect('browse', 'refresh');
						return;
					}
					$line['quantity'] += 1;
					$this->session->set_userdata('cart_items', $cart);
					$this->toastify->success('Added to cart.');
					redirect('browse', 'refresh');
					return;
				}
			}
			unset($line);

			$cart[] = [
				'part_id' => $part['part_id'],
				'name' => $part['part_name'],
				'category' => $part['category'],
				'price' => $part['selling_price'],
				'image_path' => $part['image_path'] ?? NULL,
				'quantity' => 1,
			];

			$this->session->set_userdata('cart_items', $cart);
			$this->toastify->success('Added to cart.');
		} catch (Exception $e) {
			$this->toastify->error('We are currently experiencing technical difficulties.');
		}

		redirect('browse', 'refresh');
	}

	//Prepares a single part for direct checkout, independent of the session cart.
	public function buyNow(int $part_id)
	{
		try {
			$part = $this->PartsModel->getPartById($part_id);
			if ((int) $part['is_active'] !== 1 || (int) $part['stock_quantity'] < 1) {
				$this->toastify->error('This product is currently unavailable.');
				redirect('browse', 'refresh');
				return;
			}

			$this->session->set_userdata('buy_now_item', [
				'part_id' => $part['part_id'],
				'name' => $part['part_name'],
				'category' => $part['category'],
				'price' => $part['selling_price'],
				'image_path' => $part['image_path'] ?? NULL,
				'quantity' => 1,
			]);

			redirect('checkout', 'refresh');
		} catch (Exception $e) {
			$this->toastify->error('We are currently experiencing technical difficulties.');
			redirect('browse', 'refresh');
		}
	}

	/*
	 * Adjusts a line's quantity by +1/-1 and returns JSON with the new totals
	 * so the page can update without a reload. The last unit drops the line.
	 */
	public function update(int $part_id)
	{
		$this->output->set_content_type('application/json');

		$action = $this->input->post('action');

		if (!in_array($action, ['inc', 'dec'], true)) {
			$this->output->set_output(json_encode([
				'ok' => false,
				'error' => 'Invalid action.',
			]));
			return;
		}

		$cart = cart_items();

		foreach ($cart as $i => $line) {
			if ((int) $line['part_id'] !== $part_id) {
				continue;
			}

			// Cap quantity to available stock on increment
			if ($action === 'inc') {
				try {
					$part = $this->PartsModel->getPartById($part_id);
					if ((int) $part['is_active'] === 0 || (int) $part['stock_quantity'] < 1) {
						$this->output->set_output(json_encode([
							'ok' => false,
							'error' => 'This product is currently unavailable.',
						]));
						return;
					}
					if ((int) $line['quantity'] >= (int) $part['stock_quantity']) {
						$this->output->set_output(json_encode([
							'ok' => false,
							'error' => 'The cart quantity already matches available stock.',
						]));
						return;
					}
					$cart[$i]['quantity'] += 1;
				} catch (Exception $e) {
					$this->output->set_output(json_encode([
						'ok' => false,
						'error' => 'We are currently experiencing technical difficulties.',
					]));
					return;
				}
			} else {
				$cart[$i]['quantity'] -= 1;
			}

			$quantity = (int) $cart[$i]['quantity'];
			$removed = false;

			if ($quantity <= 0) {
				unset($cart[$i]);
				$removed = true;
				$quantity = 0;
			}

			$cart = array_values($cart);
			$count = 0;

			foreach ($cart as $l) {
				$count += (int) $l['quantity'];
			}

			$this->session->set_userdata('cart_items', $cart);

			$this->output->set_output(json_encode([
				'ok' => true,
				'quantity' => $quantity,
				'removed' => $removed,
				'count' => $count,
			]));
			return;
		}

		$this->output->set_output(json_encode([
			'ok' => false,
			'error' => 'Item not in cart.',
		]));
	}

	//Validates a promo code against the current cart subtotal and, when
	//valid, stores it in the session for checkout to apply. Per-user limits
	//are re-checked at checkout once the buyer's email is known.
	public function applyPromo()
	{
		$code = (string) $this->input->post('promo_code', true);

		if (trim($code) === '') {
			$this->toastify->error('Enter a promo code.');
			redirect('cart', 'refresh');
			return;
		}

		$cart = cart_items();
		$subtotal = 0.0;

		foreach ($cart as $item) {
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

		redirect('cart', 'refresh');
	}

	//Clears any applied promo code.
	public function removePromo()
	{
		$this->session->unset_userdata('promo_code');
		$this->toastify->success('Promo code removed.');
		redirect('cart', 'refresh');
	}

	/*
	 * Removes a line from the session cart and returns JSON so the page can
	 * update the totals and badge without a reload.
	 */
	public function remove(int $part_id)
	{
		$this->output->set_content_type('application/json');

		$cart = cart_items();
		$removed = false;

		foreach ($cart as $i => $line) {
			if ((int) $line['part_id'] !== $part_id) {
				continue;
			}

			unset($cart[$i]);
			$removed = true;
			break;
		}

		if (!$removed) {
			$this->output->set_output(json_encode([
				'ok' => false,
				'error' => 'Item not in cart.',
			]));
			return;
		}

		$cart = array_values($cart);
		$count = 0;

		foreach ($cart as $line) {
			$count += (int) $line['quantity'];
		}

		$this->session->set_userdata('cart_items', $cart);

		$this->output->set_output(json_encode([
			'ok' => true,
			'count' => $count,
		]));
	}
}
