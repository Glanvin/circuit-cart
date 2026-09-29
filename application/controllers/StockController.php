<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class StockController extends MY_Controller {

	protected string $navbar = 'templates/navbar_employees';

	protected array $allowedRoles = ['admin', 'manager', 'logistics'];

	//Inventory page sort options (getAllPartsAdmin), stock descending by default.
	private const STOCK_SORTS = ['stock_desc', 'stock_asc', 'name_asc', 'name_desc', 'id_asc'];

	public function index()
	{
		$filters = [
			'q' => trim((string) $this->input->get('q', true)),
			'category' => (string) $this->input->get('category', true),
			'device_type' => (string) $this->input->get('device_type', true),
		];

		$sort = (string) $this->input->get('sort', true);

		if (!in_array($sort, self::STOCK_SORTS, true)) {
			$sort = 'stock_desc';
		}

		$data = [
			'title' => 'Stocks',
			'parts' => [],
			'filters' => $filters,
			'sort' => $sort,
			'categories' => [],
			'device_types' => [],
		];

		try {
			$data['parts'] = $this->PartsModel->getAllPartsAdmin($filters, $sort);
			$options = $this->PartsModel->stockFilterOptions();
			$data['categories'] = $options['categories'];
			$data['device_types'] = $options['device_types'];
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/inventory/stock_table', $data);
	}

	//Renders the new-item form and creates the SKU on submit.
	public function newSKU()
	{
		if ($this->input->post('create_sku')) {
			$rules = $this->config->item('sku_create');
			$this->form_validation->set_rules($rules);

			if ($this->form_validation->run() === TRUE) {
				try {
					$this->PartsModel->createSku([
						'sku' => strtoupper($this->input->post('sku', true)),
						'part_name' => $this->input->post('part_name', true),
						'category' => $this->input->post('category', true),
						'device_type' => $this->input->post('device_type', true),
						'cost_price' => $this->input->post('cost_price', true),
						'selling_price' => $this->input->post('selling_price', true),
						'stock_quantity' => (int) $this->input->post('stock_quantity', true),
						'compatibility' => $this->input->post('compatibility', true),
						'technical_specs' => $this->input->post('technical_specs', true),
						'is_active' => 1,
					]);

					$this->toastify->success('New stock item created.');
					redirect('inventory/stocks', 'refresh');
				} catch (AppException $e) {
					$this->toastify->error($e->toToast());
				}
			} else {
				$this->toastify->error('Please correct the highlighted fields');
			}
		}

		$this->render('pages/inventory/sku_form', [
			'title' => 'New Stock Item',
			'part' => [],
			'is_new' => true,
		]);
	}

	//Edits an existing inventory item's details.
	public function edit(int $part_id)
	{
		try {
			$part = $this->PartsModel->getPartById($part_id);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			redirect('inventory/stocks', 'refresh');
			return;
		}

		if ($this->input->post('update_sku')) {
			$rules = $this->config->item('sku_edit');
			$this->form_validation->set_rules($rules);

			if ($this->form_validation->run() === TRUE) {
				try {
					$data = [
						'part_name' => $this->input->post('part_name', true),
						'category' => $this->input->post('category', true),
						'device_type' => $this->input->post('device_type', true),
						'cost_price' => $this->input->post('cost_price', true),
						'selling_price' => $this->input->post('selling_price', true),
						'compatibility' => $this->input->post('compatibility', true),
						'technical_specs' => $this->input->post('technical_specs', true),
					];

					if ($this->input->post('stock_quantity') !== NULL) {
						$data['stock_quantity'] = (int) $this->input->post('stock_quantity', true);
					}

					$this->PartsModel->updateSku($part_id, $data);

					$this->toastify->success('Stock item updated.');
					redirect('inventory/stocks', 'refresh');
				} catch (AppException $e) {
					$this->toastify->error($e->toToast());
				}
			} else {
				$this->toastify->error('Please correct the highlighted fields');
			}
		}

		$this->render('pages/inventory/sku_form', [
			'title' => 'Edit Stock Item',
			'part' => $part,
			'is_new' => false,
		]);
	}

	//Sets the absolute stock quantity for an item.
	public function addStock(int $part_id)
	{
		try {
			$part = $this->PartsModel->getPartById($part_id);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			redirect('inventory/stocks', 'refresh');
			return;
		}

		if ($this->input->post('set_stock')) {
			$rules = $this->config->item('add_stock');
			$this->form_validation->set_rules($rules);

			if ($this->form_validation->run() === TRUE) {
				try {
					$this->PartsModel->setStock($part_id, (int) $this->input->post('stock_quantity', true));

					$this->toastify->success('Stock quantity updated.');
					redirect('inventory/stocks', 'refresh');
				} catch (AppException $e) {
					$this->toastify->error($e->toToast());
				}
			} else {
				$this->toastify->error('Please correct the highlighted fields');
			}
		}

		$this->render('pages/inventory/add_stock', [
			'title' => 'Add Stock',
			'part' => $part,
		]);
	}

	//Soft-deletes an item by flipping is_active to 0 (consistent with the
	//active-only browse/POS queries).
	public function remove(int $part_id)
	{
		try {
			$this->PartsModel->getPartById($part_id);
			$this->PartsModel->updateSku($part_id, ['is_active' => 0]);

			$this->toastify->success('Item removed from inventory.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('inventory/stocks', 'refresh');
	}
}