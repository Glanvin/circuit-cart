<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class RepairJobController extends MY_Controller {

	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_employees';

	protected array $allowedRoles = ['admin', 'manager', 'technician'];

	private const STATUSES = ['diagnosis', 'estimate_pending', 'approved', 'in_progress', 'ready_for_pickup', 'completed', 'cancelled'];

	//Lists repair jobs with filters.
	public function index()
	{
		$status = strtolower((string) $this->input->get('status', true));
		$status = in_array($status, self::STATUSES) ? $status : '';
		$q = trim((string) $this->input->get('q', true));
		$sort = strtolower((string) $this->input->get('sort', true));
		$sort = in_array($sort, ['newest', 'oldest', 'reference'], true) ? $sort : 'newest';
		$tech_filter = (int) $this->input->get('technician', true);

		$filters = [];
		if ($status) $filters['status'] = $status;
		if ($q) $filters['q'] = $q;
		if ($tech_filter) $filters['technician_id'] = $tech_filter;

		$jobs = [];
		$technicians = [];

		try {
			$jobs = $this->RepairJobModel->getJobs($filters, $sort);
			$technicians = $this->assignableTechnicians();
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/employees/repair_jobs', [
			'title' => 'Repair Jobs',
			'jobs' => $jobs,
			'status' => $status,
			'q' => $q,
			'sort' => $sort,
			'technicians' => $technicians,
			'tech_filter' => $tech_filter,
		]);
	}

	//Shows a single repair job with full details and actions.
	public function view(int $job_id)
	{
		try {
			$job = $this->RepairJobModel->getJobById($job_id);
			$parts = $this->PartsModel->getAllParts(); //For parts selector
			$technicians = $this->assignableTechnicians();
			$payments = $this->RepairJobModel->getPaymentsForJob($job_id);
			$totals = $this->RepairJobModel->computeTotals($job);
			$payment_methods = $this->SalesModel->getActivePaymentMethods();
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			redirect('repair/jobs', 'refresh');
			return;
		}

		$this->render('pages/employees/repair_job_detail', [
			'title' => 'Job ' . $job['job_reference'],
			'job' => $job,
			'parts' => $parts,
			'technicians' => $technicians,
			'payments' => $payments,
			'totals' => $totals,
			'payment_methods' => $payment_methods,
			'statuses' => self::STATUSES,
		]);
	}

	//Assigns the job to a technician, or clears the assignment when none is sent.
	public function assign(int $job_id)
	{
		$technician_id = (int) $this->input->post('technician_id', true);

		try {
			$job = $this->RepairJobModel->assignTechnician($job_id, $technician_id ?: NULL);

			if ($job['technician_id']) {
				$employee = $this->EmployeeModel->getEmployeeById($job['technician_id']);
				$name = trim(($employee['firstname'] ?? '') . ' ' . ($employee['lastname'] ?? ''));
				$this->toastify->success('Job assigned to ' . ($name !== '' ? $name : 'technician #' . $job['technician_id']) . '.');
			} else {
				$this->toastify->success('Technician unassigned.');
			}
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('repair/jobs/view/' . $job_id, 'refresh');
	}

	//Active technicians and admins that a repair job may be assigned to.
	private function assignableTechnicians(): array
	{
		return array_values(array_filter(
			$this->EmployeeModel->getAllEmployees(),
			fn($e) => in_array($e['role'], ['technician', 'admin'], TRUE) && (int) $e['is_active'] === 1
		));
	}

	//Updates job status.
	public function status(int $job_id)
	{
		$status = strtolower((string) $this->input->post('status', true));
		$note = (string) $this->input->post('note', true);

		if (!in_array($status, self::STATUSES)) {
			$this->toastify->error('Invalid status.');
			redirect('repair/jobs/view/' . $job_id, 'refresh');
			return;
		}

		try {
			$this->RepairJobModel->updateStatus($job_id, $status, $note, (int) $this->session->userdata('employee_id'));
			$this->toastify->success('Status updated to ' . $status . '.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('repair/jobs/view/' . $job_id, 'refresh');
	}

	//Sets diagnosis and estimate.
	public function diagnose(int $job_id)
	{
		$diagnosis = (string) $this->input->post('diagnosis', true);
		$estimate = $this->input->post('estimate_total');

		if (trim($diagnosis) === '') {
			$this->toastify->error('Diagnosis is required.');
			redirect('repair/jobs/view/' . $job_id, 'refresh');
			return;
		}

		$estimate_total = $estimate !== '' ? (float) $estimate : NULL;

		try {
			$this->RepairJobModel->setDiagnosis($job_id, $diagnosis, $estimate_total);

			if ($estimate_total !== NULL && $estimate_total > 0) {
				//Email the customer approve/decline links for the fresh estimate token.
				$job = $this->RepairJobModel->getJobById($job_id);
				$booking = $this->BookingModel->getBookingById($job['booking_id']);

				send_repair_estimate_email(
					$booking['username'],
					$job,
					site_url('repair/estimate/' . $job['estimate_token'])
				);

				$this->toastify->success('Diagnosis and estimate saved. Estimate emailed to the customer.');
			} else {
				$this->toastify->success('Diagnosis saved (no estimate).');
			}
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('repair/jobs/view/' . $job_id, 'refresh');
	}

	//Approves estimate (technician confirms customer approved).
	public function approveEstimate(int $job_id)
	{
		try {
			$this->RepairJobModel->approveEstimate($job_id);
			$this->toastify->success('Estimate approved. Job status set to approved.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('repair/jobs/view/' . $job_id, 'refresh');
	}

	//Adds parts used to the job.
	public function addParts(int $job_id)
	{
		$parts = $this->input->post('parts', true); //array of part_id, qty

		if (!is_array($parts) || empty($parts)) {
			$this->toastify->error('No parts selected.');
			redirect('repair/jobs/view/' . $job_id, 'refresh');
			return;
		}

		$cleanParts = [];
		foreach ($parts as $p) {
			$part_id = (int) ($p['part_id'] ?? 0);
			$qty = (int) ($p['qty'] ?? 0);
			if ($part_id > 0 && $qty > 0) {
				//Get selling price
				$part = $this->PartsModel->getPartById($part_id);
				$cleanParts[] = [
					'part_id' => $part_id,
					'qty' => $qty,
					'price' => (float) $part['selling_price'],
				];
			}
		}

		if (empty($cleanParts)) {
			$this->toastify->error('Invalid parts selection.');
			redirect('repair/jobs/view/' . $job_id, 'refresh');
			return;
		}

		try {
			$this->RepairJobModel->addPartsUsed($job_id, $cleanParts, (int) $this->session->userdata('employee_id'));
			$this->toastify->success('Parts added to repair job.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('repair/jobs/view/' . $job_id, 'refresh');
	}

	//Adds labor items.
	public function addLabor(int $job_id)
	{
		$items = $this->input->post('labor', true); //array of description, hours, rate

		if (!is_array($items) || empty($items)) {
			$this->toastify->error('No labor items provided.');
			redirect('repair/jobs/view/' . $job_id, 'refresh');
			return;
		}

		$cleanItems = [];
		foreach ($items as $item) {
			$desc = trim((string) ($item['description'] ?? ''));
			$hours = (float) ($item['hours'] ?? 0);
			$rate = (float) ($item['rate'] ?? 0);
			if ($desc !== '' && $hours > 0 && $rate >= 0) {
				$cleanItems[] = [
					'description' => $desc,
					'hours' => $hours,
					'rate' => $rate,
					'total' => $hours * $rate,
				];
			}
		}

		if (empty($cleanItems)) {
			$this->toastify->error('Invalid labor items.');
			redirect('repair/jobs/view/' . $job_id, 'refresh');
			return;
		}

		try {
			$this->RepairJobModel->addLaborItems($job_id, $cleanItems);
			$this->toastify->success('Labor items added.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('repair/jobs/view/' . $job_id, 'refresh');
	}

	//Adds technician note.
	public function addNote(int $job_id)
	{
		$note = trim((string) $this->input->post('note', true));

		if ($note === '') {
			$this->toastify->error('Note cannot be empty.');
			redirect('repair/jobs/view/' . $job_id, 'refresh');
			return;
		}

		try {
			$this->RepairJobModel->addNote($job_id, $note);
			$this->toastify->success('Note added.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('repair/jobs/view/' . $job_id, 'refresh');
	}

	//Marks job as ready for pickup.
	public function ready(int $job_id)
	{
		try {
			$this->RepairJobModel->readyForPickup($job_id);
			$this->toastify->success('Job marked as ready for pickup.');

			//Notify customer
			$job = $this->RepairJobModel->getJobById($job_id);
			$booking = $this->BookingModel->getBookingById($job['booking_id']);
			send_booking_notification(
				$booking['username'],
				'Device Ready for Pickup - Circuit Cart',
				'Your repair is complete and your device is ready for pickup. Please visit our shop during business hours.',
				['Job Reference' => $job['job_reference'], 'Status' => 'Ready for Pickup']
			);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('repair/jobs/view/' . $job_id, 'refresh');
	}

	/*
	 * Marks a job picked up/completed. The device may only leave once its
	 * recorded charge is settled. Admin/manager may release it on account by
	 * explicitly ticking the override, which is recorded in the job history.
	 */
	public function pickup(int $job_id)
	{
		try {
			$job = $this->RepairJobModel->getJobById($job_id);
			$totals = $this->RepairJobModel->computeTotals($job);

			$on_account = (bool) $this->input->post('on_account');
			$role = (string) $this->session->userdata('employee_role');

			if ($totals['balance'] > 0.001) {
				if (!$on_account || !in_array($role, ['admin', 'manager'], TRUE)) {
					throw new AppException('D22', ['balance' => number_format($totals['balance'], 2)]);
				}

				$this->RepairJobModel->addNote(
					$job_id,
					'Released on account with outstanding balance of ₱' . number_format($totals['balance'], 2)
						. ' approved by ' . $role
				);
			}

			$this->RepairJobModel->markPickedUp($job_id);
			$this->toastify->success('Job marked as picked up.');

			//Notify customer
			$job = $this->RepairJobModel->getJobById($job_id);
			$booking = $this->BookingModel->getBookingById($job['booking_id']);
			send_booking_notification(
				$booking['username'],
				'Repair Completed - Circuit Cart',
				'Your device has been picked up. Thank you for choosing Circuit Cart!',
				['Job Reference' => $job['job_reference'], 'Status' => 'Completed']
			);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('repair/jobs/view/' . $job_id, 'refresh');
	}

	//Records a deposit or final payment against a repair job.
	public function recordPayment(int $job_id)
	{
		$amount = $this->input->post('amount', true);
		$kind = (string) $this->input->post('kind', true);
		$method_id = (int) $this->input->post('method_id', true);
		$reference = (string) $this->input->post('reference', true);

		if ($amount === NULL || $amount === '' || (float) $amount <= 0) {
			$this->toastify->error('Enter a payment amount greater than zero.');
			redirect('repair/jobs/view/' . $job_id, 'refresh');
			return;
		}

		try {
			$this->RepairJobModel->recordPayment($job_id, [
				'amount' => (float) $amount,
				'kind' => $kind === 'deposit' ? 'deposit' : 'payment',
				'method_id' => $method_id,
				'reference' => $reference,
			], (int) $this->session->userdata('employee_id'));

			$this->toastify->success('Payment recorded.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('repair/jobs/view/' . $job_id, 'refresh');
	}
}