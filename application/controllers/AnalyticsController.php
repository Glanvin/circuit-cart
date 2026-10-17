<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AnalyticsController extends MY_Controller {

	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_employees';

	protected array $allowedRoles = ['manager', 'admin'];

	private const ORDER_STATUSES = ['processing', 'shipped', 'delivered', 'cancelled'];

	//Selectable chart/table windows (days).
	private const RANGE_OPTIONS = [7, 14, 30, 90];

	private const DEFAULT_DAYS = 30;

	//Orders table sort options (getOnlineSales), newest first is the default.
	private const ORDER_SORTS = ['newest', 'oldest', 'total_high', 'total_low'];

	public function index()
	{
		$days = $this->requestedDays();

		try {
			$data = $this->chartData($days);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			$data = $this->emptyChartData($days);
		}

		$data['selected_days'] = $days;
		$data['chart_payload'] = $this->chartPayload($data);

		$this->render('pages/employees/analytics', $data);
	}

	//JSON feed used by the range selector to redraw the charts without a reload.
	public function data()
	{
		$days = $this->requestedDays();

		try {
			$data = $this->chartData($days);
		} catch (AppException $e) {
			$data = $this->emptyChartData($days);
		}

		$data['selected_days'] = $days;

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($this->chartPayload($data)));
	}

	//Fulfilment panel for online orders ("delivered" is what counts the sale).
	public function orders()
	{
		$status = strtolower((string) $this->input->get('status', true));
		$sort = strtolower((string) $this->input->get('sort', true));
		$q = trim((string) $this->input->get('q', true));

		if ($status !== '' && !in_array($status, self::ORDER_STATUSES, true)) {
			$status = '';
		}

		if (!in_array($sort, self::ORDER_SORTS, true)) {
			$sort = 'newest';
		}

		$orders = [];

		try {
			$orders = $this->SalesModel->getOnlineSales($status !== '' ? $status : NULL, $sort, $q);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/employees/orders', [
			'title' => 'Orders',
			'orders' => $orders,
			'status' => $status,
			'sort' => $sort,
			'q' => $q,
		]);
	}

	//Moves a single online order along the fulfilment statuses.
	public function orderStatus(int $order_id, string $status)
	{
		if (!in_array($status, self::ORDER_STATUSES, true)) {
			$this->toastify->error('Invalid order status.');
			redirect('employee/orders', 'refresh');
			return;
		}

		try {
			$order = $this->SalesModel->getSaleById($order_id);

			if ($order['origin'] !== 'online') {
				$this->toastify->error('Only online orders can be fulfilled.');
				redirect('employee/orders', 'refresh');
				return;
			}

			if ($status === 'cancelled') {
				//Cancelling restocks the order's lines through the model.
				$this->SalesModel->cancelOrder(
					$order_id,
					(int) $this->session->userdata('employee_id'),
					'Cancelled by staff',
					['processing', 'shipped']
				);
			} else {
				if (in_array($order['status'], ['cancelled', 'refunded', 'voided'], true)) {
					throw new AppException('D06');
				}

				$this->SalesModel->updateStatus($order_id, $status);
			}

			$this->toastify->success('Order marked as ' . $status . '.');

			try {
				$updated = $this->SalesModel->getSaleById($order_id);
				send_order_status_email($updated, $status);
			} catch (Exception $e) {
				// Email failures must not block fulfilment.
			}
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('employee/orders', 'refresh');
	}
	//Validates the ?days= selector against the allowed options.
	private function requestedDays(): int
	{
		$days = (int) $this->input->get('days', true);

		return in_array($days, self::RANGE_OPTIONS, true) ? $days : self::DEFAULT_DAYS;
	}

	//Aggregates everything the manager charts and detail tables need for the
	//selected window.
	private function chartData(int $days): array
	{
		$from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
		$labels = [];

		for ($i = $days - 1; $i >= 0; $i--) {
			$labels[] = date('Y-m-d', strtotime('-' . $i . ' days'));
		}

		//Sales: store always counts, online only when delivered.
		$store = $this->SalesModel->getDayTotals($from, 'store');
		$online = $this->SalesModel->getDayTotals($from, 'online', 'delivered');

		$storeSeries = [];
		$onlineSeries = [];

		foreach ($labels as $day) {
			$storeSeries[] = $store[$day] ?? 0;
			$onlineSeries[] = $online[$day] ?? 0;
		}

		//User registrations.
		$registrations = $this->UserModel->getUserRegistrationsSince($from);

		$registrationSeries = [];

		foreach ($labels as $day) {
			$registrationSeries[] = $registrations[$day] ?? 0;
		}

		//Attendance records per day.
		$attendance = $this->EmployeeModel->attendanceTotalsSince($from);
		$attendanceSeries = [];

		foreach ($labels as $day) {
			$attendanceSeries[] = $attendance[$day] ?? 0;
		}

		//Inventory: active parts, lowest stock gets its own bar.
		$parts = $this->PartsModel->getAllParts();

		usort($parts, function ($a, $b) {
			return (int) $a['stock_quantity'] <=> (int) $b['stock_quantity'];
		});

		$lowStockParts = array_slice($parts, 0, 10);
		$lowStockLabels = [];
		$lowStockSeries = [];

		foreach ($lowStockParts as $part) {
			$lowStockLabels[] = $part['part_name'];
			$lowStockSeries[] = (int) $part['stock_quantity'];
		}

		$lowStockCount = 0;

		foreach ($parts as $part) {
			if ((int) $part['stock_quantity'] <= 5) {
				$lowStockCount++;
			}
		}

		$totalEmployees = 0;
		$activeEmployees = 0;

		try {
			foreach ($this->EmployeeModel->getAllEmployees() as $employee) {
				$totalEmployees++;

				if ((int) $employee['is_active'] === 1) {
					$activeEmployees++;
				}
			}
		} catch (AppException $e) {
			//Charts still render with zeros.
		}

		return [
			'title' => 'Analytics',
			'labels' => $labels,
			'storeSales' => $storeSeries,
			'onlineSales' => $onlineSeries,
			'registrations' => $registrationSeries,
			'attendance' => $attendanceSeries,
			'lowStockParts' => $lowStockParts,
			'lowStockLabels' => $lowStockLabels,
			'lowStock' => $lowStockSeries,
			'totalSales' => array_sum($storeSeries) + array_sum($onlineSeries),
			'storeTotal' => array_sum($storeSeries),
			'onlineTotal' => array_sum($onlineSeries),
			'usersTotal' => $this->UserModel->countUsers(),
			'lowStockCount' => $lowStockCount,
			'totalEmployees' => $totalEmployees,
			'activeEmployees' => $activeEmployees,
		];
	}

	//Narrows the aggregate down to the fields the charts and AJAX tables need.
	private function chartPayload(array $data): array
	{
		$parts = [];

		foreach ($data['lowStockParts'] as $part) {
			$parts[] = [
				'part_id' => $part['part_id'],
				'sku' => $part['sku'],
				'part_name' => $part['part_name'],
				'stock_quantity' => (int) $part['stock_quantity'],
			];
		}

		return [
			'labels' => $data['labels'],
			'storeSales' => $data['storeSales'],
			'onlineSales' => $data['onlineSales'],
			'registrations' => $data['registrations'],
			'attendance' => $data['attendance'],
			'lowStockLabels' => $data['lowStockLabels'],
			'lowStock' => $data['lowStock'],
			'lowStockParts' => $parts,
			'selected_days' => (int) $data['selected_days'],
			'totals' => [
				'sales' => $data['totalSales'],
				'store' => $data['storeTotal'],
				'online' => $data['onlineTotal'],
				'users' => $data['usersTotal'],
				'lowStock' => $data['lowStockCount'],
				'activeEmployees' => $data['activeEmployees'],
				'totalEmployees' => $data['totalEmployees'],
			],
		];
	}

	private function emptyChartData(int $days): array
	{
		$labels = [];

		for ($i = $days - 1; $i >= 0; $i--) {
			$labels[] = date('Y-m-d', strtotime('-' . $i . ' days'));
		}

		return [
			'title' => 'Analytics',
			'labels' => $labels,
			'storeSales' => array_fill(0, $days, 0),
			'onlineSales' => array_fill(0, $days, 0),
			'registrations' => array_fill(0, $days, 0),
			'attendance' => array_fill(0, $days, 0),
			'lowStockParts' => [],
			'lowStockLabels' => [],
			'lowStock' => [],
			'totalSales' => 0,
			'storeTotal' => 0,
			'onlineTotal' => 0,
			'usersTotal' => 0,
			'lowStockCount' => 0,
			'totalEmployees' => 0,
			'activeEmployees' => 0,
		];
	}
}