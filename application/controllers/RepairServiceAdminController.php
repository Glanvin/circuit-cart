<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Staff management of the repair service catalog. Services are an optional
 * customer-facing list used on the booking form; base_price is a display hint
 * only and is never treated as a charge.
 */
class RepairServiceAdminController extends MY_Controller {

	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_employees';

	protected array $allowedRoles = ['admin', 'manager'];

	//Lists every repair service.
	public function index()
	{
		$services = [];

		try {
			$services = $this->RepairServiceModel->getAllServices();
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/employees/service_table', [
			'title' => 'Repair Services',
			'services' => $services,
		]);
	}

	//Renders the create form and stores a new service on submit.
	public function new()
	{
		if ($this->input->post('create_service')) {
			$rules = $this->config->item('service_create');
			$this->form_validation->set_rules($rules);

			if ($this->form_validation->run() === TRUE) {
				try {
					$this->RepairServiceModel->createService($this->serviceDataFromPost());
					$this->toastify->success('Repair service created.');
					redirect('employee/services', 'refresh');
					return;
				} catch (AppException $e) {
					$this->toastify->error($e->toToast());
				}
			} else {
				$this->toastify->error('Please correct the highlighted fields');
			}
		}

		$this->render('pages/employees/service_form', [
			'title' => 'New Repair Service',
			'service' => $this->formServiceData([]),
			'is_new' => TRUE,
		]);
	}

	//Edits an existing service.
	public function edit(int $id)
	{
		try {
			$service = $this->RepairServiceModel->getServiceById($id);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			redirect('employee/services', 'refresh');
			return;
		}

		if ($this->input->post('update_service')) {
			$rules = $this->config->item('service_edit');
			$this->form_validation->set_rules($rules);

			if ($this->form_validation->run() === TRUE) {
				try {
					$this->RepairServiceModel->updateService($id, $this->serviceDataFromPost());
					$this->toastify->success('Repair service updated.');
					redirect('employee/services', 'refresh');
					return;
				} catch (AppException $e) {
					$this->toastify->error($e->toToast());
				}
			} else {
				$this->toastify->error('Please correct the highlighted fields');
			}
		}

		$service['id'] = $id;

		$this->render('pages/employees/service_form', [
			'title' => 'Edit Repair Service',
			'service' => $this->formServiceData($service),
			'is_new' => FALSE,
		]);
	}

	//Activates or deactivates a service. Inactive services are hidden from the
	//booking form but kept for bookings that already reference them.
	public function toggle(int $id)
	{
		try {
			$service = $this->RepairServiceModel->getServiceById($id);
			$active = (int) $service['is_active'] === 1 ? 0 : 1;
			$this->RepairServiceModel->updateService($id, ['is_active' => $active]);
			$this->toastify->success($active === 1 ? 'Service activated.' : 'Service deactivated.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('employee/services', 'refresh');
	}

	//Builds the insert/update payload from POST.
	private function serviceDataFromPost(): array
	{
		return [
			'name' => trim((string) $this->input->post('name', true)),
			'description' => trim((string) $this->input->post('description', true)),
			'base_price' => (float) $this->input->post('base_price', true),
			'sort_order' => (int) $this->input->post('sort_order', true),
		];
	}

	//Merges posted values over a service row so the form repopulates on error.
	private function formServiceData(array $service): array
	{
		foreach (['name', 'description', 'base_price', 'sort_order'] as $field) {
			$posted = $this->input->post($field, true);
			if ($posted !== NULL) {
				$service[$field] = $posted;
			}
		}

		$service['base_price'] = $service['base_price'] ?? '0.00';
		$service['sort_order'] = $service['sort_order'] ?? '0';
		return $service;
	}
}
