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

			$cart = cart_items();

			foreach ($cart as &$line) {
				if ((int) $line['part_id'] === $part_id) {
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

			$this->session->set_userdata('buy_now_item', [
				'part_id' => $part['part_id'],
				'name' => $part['part_name'],
				'category' => $part['category'],
				'price' => $part['selling_price'],
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

			$cart[$i]['quantity'] += ($action === 'inc') ? 1 : -1;

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
