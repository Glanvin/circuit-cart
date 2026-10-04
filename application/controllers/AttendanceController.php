<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AttendanceController extends MY_Controller {

	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_employees';

	protected array $allowedRoles = ['cashier', 'manager', 'technician', 'admin', 'developer', 'logistics'];

	//Roles that may view and clock in/out attendance for other employees.
	private function canManageOthers(): bool
	{
		return in_array($this->session->userdata('employee_role'), ['manager', 'admin'], true);
	}

	//Resolves which employee attendance to show: anyone for manager/admin, self otherwise.
	public function index(?int $target_id = NULL)
	{
		$records = [];
		$today = null;

		try {
			$target_id = $this->resolveTarget($target_id);
			$records = $this->EmployeeModel->getAttendance($target_id);

			foreach ($records as $record) {
				if (($record['date'] ?? '') === date('Y-m-d')) {
					$today = $record;
					break;
				}
			}

			$target = $this->EmployeeModel->getEmployeeById($target_id);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			redirect('employee/attendance', 'refresh');
			return;
		}

		$data = [
			'title' => 'Attendance',
			'records' => array_reverse($records),
			'today' => $today,
			'target' => $target,
			'can_manage' => $this->canManageOthers(),
			'employees' => [],
		];

		if ($data['can_manage']) {
			$data['employees'] = $this->EmployeeModel->getAllEmployees();
		}

		$this->render('pages/employees/attendance', $data);
	}

	//Clock in for today, returns the created record as JSON.
	public function timeIn()
	{
		$this->output->set_content_type('application/json');

		try {
			$target_id = $this->resolveTarget((int) $this->input->post('employee_id', true));

			$result = $this->EmployeeModel->timeIn($target_id);

			if ($result['ok']) {
				$this->output->set_output(json_encode(['ok' => true, 'record' => $result['record']]));
			} else {
				$this->output->set_output(json_encode(['ok' => false, 'error' => $result['message']]));
			}
		} catch (AppException $e) {
			$this->output->set_output(json_encode(['ok' => false, 'error' => $e->toToast()]));
		}
	}

	//Clock out, filling the time_out of today's open record.
	public function timeOut()
	{
		$this->output->set_content_type('application/json');

		try {
			$target_id = $this->resolveTarget((int) $this->input->post('employee_id', true));

			$result = $this->EmployeeModel->timeOut($target_id);

			if ($result['ok']) {
				$this->output->set_output(json_encode(['ok' => true, 'record' => $result['record']]));
			} else {
				$this->output->set_output(json_encode(['ok' => false, 'error' => $result['message']]));
			}
		} catch (AppException $e) {
			$this->output->set_output(json_encode(['ok' => false, 'error' => $e->toToast()]));
		}
	}

	//Only manager/admin may act on someone else; everyone else is always forced
	//back to their own employee_id.
	private function resolveTarget(?int $target_id): int
	{
		$self = (int) $this->session->userdata('employee_id');

		if ($target_id === NULL || $target_id <= 0 || !$this->canManageOthers()) {
			return $self;
		}

		//Ensure the target actually exists before acting on it.
		$this->EmployeeModel->getEmployeeById($target_id);

		return $target_id;
	}
}