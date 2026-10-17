<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PosController extends MY_Controller {

	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_cashier';

	protected array $allowedRoles = ['cashier', 'manager', 'admin'];

	private const ACTIVE_SALE_STATUSES = ['completed', 'processing', 'shipped', 'delivered'];

	private const REFUND_PIN_MAX_ATTEMPTS = 5;

	private const REFUND_PIN_LOCK_SECONDS = 300;

	public function index()
	{
		$parts = [];
		$payment_methods = [];
		$tax_rates = [];

		try {
			$parts = $this->PartsModel->getAllParts();
			$payment_methods = $this->SalesModel->getActivePaymentMethods();
			$tax_rates = $this->SalesModel->getStoreTaxRates();
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/pos/pos', [
			'title' => 'Point of Sale',
			'parts' => $parts,
			'payment_methods' => $payment_methods,
			'tax_rates' => $tax_rates,
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

	//Returns recent store sales, or a single store sale by ?receipt=, for the
	//refund panel. Employee-only JSON endpoint.
	public function lookup()
	{
		$this->output->set_content_type('application/json');

		$receipt = trim((string) $this->input->get('receipt', true));

		try {
			if ($receipt !== '') {
				$sale = $this->SalesModel->getSaleByReceipt($receipt);

				if ($sale['origin'] !== 'store') {
					throw new AppException('D07');
				}

				$sales = [$sale];
			} else {
				$sales = $this->SalesModel->getRecentStoreSales(25);
			}

			$payload = [];

			foreach ($sales as $sale) {
				$payload[] = $this->salePayload($sale);
			}

			$this->output->set_output(json_encode([
				'ok' => TRUE,
				'sales' => $payload,
			]));
		}catch(AppException $e) {
			$this->output->set_output(json_encode([
				'ok' => false, 'error' => $e->toToast(),
			]));
		}
	}

	//Refunds or voids a store sale after a manager's PIN authorizes the action.
	public function refund()
	{
		$this->output->set_content_type('application/json');

		$sale_id = (int) $this->input->post('sale_id', true);
		$mode = strtolower((string) $this->input->post('mode', true)) === 'void' ? 'void' : 'refund';
		$reason = trim((string) $this->input->post('reason', true));
		$restock = $this->input->post('restock', true) !== '0';

		if ($sale_id <= 0) {
			$this->output->set_output(json_encode([
				'ok' => false, 'error' => 'Select a sale to reverse.',
			]));
			return;
		}

		$lockedUntil = (int) $this->session->userdata('refund_pin_locked_until');
		if ($lockedUntil > time()) {
			$this->output->set_status_header(429);
			$this->output->set_output(json_encode([
				'ok' => false,
				'error' => 'Too many incorrect PIN attempts. Try again in a few minutes.',
			]));
			return;
		}

		try {
			$approverPin = (string) $this->input->post('approver_pin', TRUE);
			$approverId = preg_match('/^\d{6}$/', $approverPin) === 1
				&& $this->EmployeeModel->verifyRefundApproverPin($approverPin);

			if (!$approverId) {
				$attempts = (int) $this->session->userdata('refund_pin_attempts') + 1;
				if ($attempts >= self::REFUND_PIN_MAX_ATTEMPTS) {
					$this->session->set_userdata('refund_pin_attempts', 0);
					$this->session->set_userdata('refund_pin_locked_until', time() + self::REFUND_PIN_LOCK_SECONDS);
					$this->output->set_status_header(429);
					$error = 'Too many incorrect PIN attempts. Try again in a few minutes.';
				} else {
					$this->session->set_userdata('refund_pin_attempts', $attempts);
					$this->output->set_status_header(403);
				$error = 'A valid manager or admin PIN is required to process this refund.';
				}

				$this->output->set_output(json_encode(['ok' => false, 'error' => $error]));
				return;
			}

			$this->session->unset_userdata(['refund_pin_attempts', 'refund_pin_locked_until']);
			$sale = $this->SalesModel->getSaleById($sale_id);

			$reversed = $this->SalesModel->refundSale(
				$sale_id,
				(int) $this->session->userdata('employee_id'),
				$reason,
				$restock,
				$mode,
				(int) $approverId
			);

			$this->output->set_output(json_encode([
				'ok' => TRUE,
				'sale' => $this->salePayload($reversed),
			]));
		}catch(AppException $e) {
			$this->output->set_output(json_encode([
				'ok' => false, 'error' => $e->toToast(),
			]));
		}
	}

	//Narrows a sale to the fields the refund panel displays.
	private function salePayload(array $sale): array
	{
		$items = [];

		foreach ($sale['items'] ?? [] as $line) {
			$items[] = [
				'part_id' => (int) $line['part_id'],
				'name' => $line['name'],
				'qty' => (int) $line['qty'],
				'price' => (float) $line['price'],
			];
		}

		return [
			'id' => (int) $sale['id'],
			'receipt_no' => $sale['receipt_no'],
			'origin' => $sale['origin'],
			'status' => $sale['status'],
			'sold_by' => $sale['sold_by'],
			'total' => (float) $sale['total'],
			'created_at' => $sale['created_at'],
			'refund_amount' => isset($sale['refund_amount']) ? (float) $sale['refund_amount'] : NULL,
			'refund_reason' => $sale['refund_reason'] ?? NULL,
			'refunded_at' => $sale['refunded_at'] ?? NULL,
			'items' => $items,
			'reversible' => $sale['origin'] === 'store' && in_array($sale['status'], self::ACTIVE_SALE_STATUSES, TRUE),
		];
	}
}
