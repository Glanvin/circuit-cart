<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EmployeeBugController extends MY_Controller {

	protected string $navbar = 'templates/navbar_employees';

	protected array $allowedRoles = ['admin', 'staff'];

	private const STATUSES = ['new', 'in_progress', 'fixed'];

	/*
	 * Lists bug reports, filtered by status and search query, for the staff.
	 */
	public function index()
	{
		$bugs = [];
		$status = strtolower((string) $this->input->get('status', true));

		if ($status !== '' && !in_array($status, self::STATUSES, true)) {
			$status = '';
		}

		$filter = in_array($status, self::STATUSES, true) ? $status : 'new';
		$q = trim((string) $this->input->get('q', true));

		try {
			$bugs = $this->BugReportModel->getBugReports($filter, $q);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/employees/bugs', [
			'title' => 'Bugs',
			'bugs' => $bugs,
			'status' => $filter,
			'q' => $q,
		]);
	}

	//Labels a bug report as new, in progress, or fixed.
	public function status(int $bug_id, string $status)
	{
		if (!in_array($status, self::STATUSES, true)) {
			$this->toastify->error('Invalid bug report status.');
			redirect('storage/bugs', 'refresh');
			return;
		}

		try {
			$bug = $this->BugReportModel->getBugReportById($bug_id);

			$this->BugReportModel->updateStatus($bug_id, $status);

			$this->toastify->success('Bug report marked as ' . str_replace('_', ' ', $status) . '.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('storage/bugs', 'refresh');
	}
}