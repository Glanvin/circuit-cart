<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PromoAdminController extends MY_Controller {

	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_employees';

	protected array $allowedRoles = ['admin', 'manager'];

	//Lists every promo code with usage counts.
	public function index()
	{
		$promos = [];

		try {
			$promos = $this->PromoModel->getAllPromoCodes();
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/employees/promo_table', [
			'title' => 'Promo Codes',
			'promos' => $promos,
		]);
	}

	//Renders the create form and stores a new promo code on submit.
	public function new()
	{
		if ($this->input->post('create_promo')) {
			$rules = $this->config->item('promo_create');
			$this->form_validation->set_rules($rules);

			if ($this->form_validation->run() === TRUE) {
				try {
					$this->PromoModel->createPromoCode($this->promoDataFromPost(TRUE));
					$this->toastify->success('Promo code created.');
					redirect('employee/promos', 'refresh');
					return;
				} catch (AppException $e) {
					$this->toastify->error($e->toToast());
				}
			} else {
				$this->toastify->error('Please correct the highlighted fields');
			}
		}

		$this->render('pages/employees/promo_form', [
			'title' => 'New Promo Code',
			'promo' => $this->formPromoData([]),
			'is_new' => TRUE,
		]);
	}

	//Edits an existing promo code.
	public function edit(int $id)
	{
		try {
			$promo = $this->PromoModel->getPromoById($id);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			redirect('employee/promos', 'refresh');
			return;
		}

		if ($this->input->post('update_promo')) {
			$rules = $this->config->item('promo_edit');
			$this->form_validation->set_rules($rules);

			if ($this->form_validation->run() === TRUE) {
				try {
					$this->PromoModel->updatePromoCode($id, $this->promoDataFromPost(FALSE));
					$this->toastify->success('Promo code updated.');
					redirect('employee/promos', 'refresh');
					return;
				} catch (AppException $e) {
					$this->toastify->error($e->toToast());
				}
			} else {
				$this->toastify->error('Please correct the highlighted fields');
			}
		}

		$this->render('pages/employees/promo_form', [
			'title' => 'Edit Promo Code',
			'promo' => $this->formPromoData($promo),
			'is_new' => FALSE,
		]);
	}

	//Activates or deactivates a promo code.
	public function toggle(int $id)
	{
		try {
			$promo = $this->PromoModel->getPromoById($id);
			$active = (int) $promo['is_active'] === 1 ? 0 : 1;
			$this->PromoModel->updatePromoCode($id, ['is_active' => $active]);
			$this->toastify->success($active === 1 ? 'Promo code activated.' : 'Promo code deactivated.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('employee/promos', 'refresh');
	}

	//Builds the insert/update payload from POST. On create the code is
	//included; on edit the code is immutable for auditability.
	private function promoDataFromPost(bool $includeCode): array
	{
		$data = [
			'discount_type' => (string) $this->input->post('discount_type', true),
			'discount_value' => (float) $this->input->post('discount_value', true),
			'min_subtotal' => (float) $this->input->post('min_subtotal', true),
			'max_total_uses' => $this->optionalInt('max_total_uses'),
			'per_user_limit' => $this->optionalInt('per_user_limit'),
			'starts_at' => $this->optionalDateTime('starts_at'),
			'expires_at' => $this->optionalDateTime('expires_at'),
		];

		if ($includeCode) {
			$data['code'] = strtoupper(trim((string) $this->input->post('code', true)));
		}

		return $data;
	}

	private function optionalInt(string $field): ?int
	{
		$value = $this->input->post($field, true);
		return ($value === NULL || $value === '') ? NULL : (int) $value;
	}

	private function optionalDateTime(string $field): ?string
	{
		$value = trim((string) $this->input->post($field, true));
		if ($value === '') {
			return NULL;
		}
		return date('Y-m-d H:i:s', strtotime($value));
	}

	//Merges posted values over a promo row so the form repopulates on error.
	private function formPromoData(array $promo): array
	{
		foreach ([
			'code',
			'discount_type',
			'discount_value',
			'min_subtotal',
			'max_total_uses',
			'per_user_limit',
			'starts_at',
			'expires_at',
		] as $field) {
			$posted = $this->input->post($field, true);
			if ($posted !== NULL) {
				$promo[$field] = $posted;
			}
		}

		$promo['discount_type'] = $promo['discount_type'] ?? 'percent';
		return $promo;
	}
}
