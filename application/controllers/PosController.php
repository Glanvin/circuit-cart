<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PosController extends MY_Controller {

	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_cashier';

	protected array $allowedRoles = ['cashier', 'admin'];

	public function index()
	{
		$parts = [];

		try {
			$parts = $this->PartsModel->getAllParts();
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/pos/pos', [
			'title' => 'Point of Sale',
			'parts' => $parts,
		]);
	}

	//Completes a store sale. The client submits item lines [{part_id, qty}]
	//plus the cash tendered; prices and totals are computed server-side.
	public function complete()
	{
		$this->output->set_content_type('application/json');

		$tendered = (float) $this->input->post('tendered', true);
		$raw = $this->input->post('items', true);

		if ($raw === NULL || !is_string($raw)) {
			$this->output->set_output(json_encode([
				'ok' => false, 'error' => 'No items were submitted.',
			]));
			return;
		}

		$items = json_decode($raw, true);

		if (!is_array($items) || empty($items)) {
			$this->output->set_output(json_encode([
				'ok' => false, 'error' => 'The sale cart is empty.',
			]));
			return;
		}

		try {
			$sale = $this->SalesModel->recordSale([
				'origin' => 'store',
				'sold_by' => (string) $this->session->userdata('employee_name'),
				'items' => $items,
				'tendered' => $tendered,
			]);

			$this->output->set_output(json_encode([
				'ok' => true,
				'receipt' => $sale['receipt_no'],
				'subtotal' => $sale['subtotal'],
				'total' => $sale['total'],
				'tendered' => $tendered,
				'change' => round($tendered - $sale['total'], 2),
			]));
		}catch(AppException $e) {
			$this->output->set_output(json_encode([
				'ok' => false, 'error' => $e->toToast(),
			]));
		}
	}
}