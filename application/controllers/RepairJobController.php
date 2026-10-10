<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class RepairJobController extends MY_Controller {

	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_employees';

	protected array $allowedRoles = ['admin', 'technician'];

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
			$technicians = $this->EmployeeModel->getAllEmployees();
			$technicians = array_filter($technicians, fn($e) => in_array($e['role'], ['technician', 'admin']) && (int) $e['is_active'] === 1);
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
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			redirect('repair/jobs', 'refresh');
			return;
		}

		$this->render('pages/employees/repair_job_detail', [
			'title' => 'Job ' . $job['job_reference'],
			'job' => $job,
			'parts' => $parts,
			'statuses' => self::STATUSES,
		]);
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
			$this->RepairJobModel->updateStatus($job_id, $status, $note);
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
			$msg = $estimate_total ? 'Diagnosis and estimate saved.' : 'Diagnosis saved (no estimate).';
			$this->toastify->success($msg);
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
			$this->RepairJobModel->addPartsUsed($job_id, $cleanParts);
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

	//Marks job as picked up/completed.
	public function pickup(int $job_id)
	{
		try {
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
}