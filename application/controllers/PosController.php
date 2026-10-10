<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PosController extends MY_Controller {

	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_cashier';

	protected array $allowedRoles = ['cashier', 'admin'];

	public function index()
	{
		$parts = [];
		$payment_methods = [];

		try {
			$parts = $this->PartsModel->getAllParts();
			$payment_methods = $this->SalesModel->getActivePaymentMethods();
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/pos/pos', [
			'title' => 'Point of Sale',
			'parts' => $parts,
			'payment_methods' => $payment_methods,
		]);
	}

	//Completes a store sale. The client submits item lines [{part_id, qty}]
	//and payment_methods [{method_id, code, amount, reference}]; prices and totals are computed server-side.
	public function complete()
	{
		$this->output->set_content_type('application/json');

		$raw = $this->input->post('items', true);
		$payment_methods_raw = $this->input->post('payment_methods', true);

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

		$payment_methods = [];
		if ($payment_methods_raw !== NULL && is_string($payment_methods_raw)) {
			$payment_methods = json_decode($payment_methods_raw, true) ?? [];
		}

		try {
			$sale = $this->SalesModel->recordSale([
				'origin' => 'store',
				'sold_by' => (string) $this->session->userdata('employee_name'),
				'employee_id' => (int) $this->session->userdata('employee_id'),
				'items' => $items,
				'payment_methods' => $payment_methods,
			]);

			//Calculate change (cash overpayment)
			$cash_paid = 0.0;
			foreach ($sale['payment_methods'] as $pm) {
				if ($pm['code'] === 'cash') {
					$cash_paid += (float) $pm['amount'];
				}
			}
			$change = round($cash_paid - $sale['total'], 2);

			$this->output->set_output(json_encode([
				'ok' => true,
				'receipt' => $sale['receipt_no'],
				'subtotal' => $sale['subtotal'],
				'tax_total' => $sale['tax_total'],
				'total' => $sale['total'],
				'payment_methods' => $sale['payment_methods'],
				'change' => max(0, $change),
			]));
		}catch(AppException $e) {
			$this->output->set_output(json_encode([
				'ok' => false, 'error' => $e->toToast(),
			]));
		}
	}
}