<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class StockController extends MY_Controller {

	protected bool $employeePortal = TRUE;

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
					$productData = [
						'sku' => strtoupper($this->input->post('sku', true)),
						'part_name' => $this->input->post('part_name', true),
						'category' => $this->input->post('category', true),
						'device_type' => $this->input->post('device_type', true),
						'cost_price' => $this->input->post('cost_price', true),
						'selling_price' => $this->input->post('selling_price', true),
						'stock_quantity' => (int) $this->input->post('stock_quantity', true),
						'compatibility' => json_encode($this->compatibilityFromPost(), JSON_UNESCAPED_UNICODE),
						'technical_specs' => json_encode($this->specsFromPost(), JSON_UNESCAPED_UNICODE),
						'is_active' => 1,
					];
					$image = $this->uploadProductImage();
					if ($image === FALSE) {
						throw new RuntimeException($this->upload->display_errors('', ''));
					}
					if ($image !== NULL) $productData['image_path'] = $image;
					$this->PartsModel->createSku($productData);

					$this->toastify->success('New stock item created.');
					redirect('inventory/stocks', 'refresh');
				} catch (AppException $e) {
					$this->toastify->error($e->toToast());
				} catch (RuntimeException $e) {
					$this->toastify->error($e->getMessage());
				}
			} else {
				$this->toastify->error('Please correct the highlighted fields');
			}
		}

		$this->render('pages/inventory/sku_form', [
			'title' => 'New Stock Item',
			'part' => $this->formPartData([]),
			'is_new' => true,
			'gallery' => [],
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

		//Deleting one gallery photo is its own small form so it works
		//independently of the main "Save changes" submit.
		if ($this->input->post('delete_gallery')) {
			$galleryId = (int) $this->input->post('delete_gallery_image');
			if ($galleryId > 0 && $this->PartsModel->deleteProductImage($galleryId)) {
				$this->toastify->success('Gallery image removed.');
			} else {
				$this->toastify->error('That gallery image could not be found.');
			}
			redirect('inventory/stocks/edit/' . $part_id, 'refresh');
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
						'compatibility' => json_encode($this->compatibilityFromPost(), JSON_UNESCAPED_UNICODE),
						'technical_specs' => json_encode($this->specsFromPost(), JSON_UNESCAPED_UNICODE),
					];
					$image = $this->uploadProductImage();
					if ($image === FALSE) {
						throw new RuntimeException($this->upload->display_errors('', ''));
					}
					if ($image !== NULL) $data['image_path'] = $image;
					if ($this->input->post('remove_image')) $data['image_path'] = NULL;
					$this->PartsModel->updateSku($part_id, $data);

					//Extra gallery photos are appended after the row is saved.
					$gallery = $this->uploadGalleryImages();
					if ($gallery === FALSE) {
						throw new RuntimeException($this->upload->display_errors('', ''));
					}
					foreach ($gallery as $galleryPath) {
						$this->PartsModel->addProductImage($part_id, $galleryPath);
					}

					$this->toastify->success('Stock item updated.');
					redirect('inventory/stocks', 'refresh');
				} catch (AppException $e) {
					$this->toastify->error($e->toToast());
				} catch (RuntimeException $e) {
					$this->toastify->error($e->getMessage());
				}
			} else {
				$this->toastify->error('Please correct the highlighted fields');
			}
		}

		$this->render('pages/inventory/sku_form', [
			'title' => 'Edit Stock Item',
			'part' => $this->formPartData($part),
			'is_new' => false,
			'gallery' => $this->PartsModel->getProductImages($part_id),
		]);
	}

	private function compatibilityFromPost(): array
	{
		$values = preg_split('/[\r\n,]+/', (string) $this->input->post('compatibility', true));
		return array_values(array_unique(array_filter(array_map('trim', $values))));
	}

	private function specsFromPost(): array
	{
		$keys = (array) $this->input->post('spec_key', true);
		$values = (array) $this->input->post('spec_value', true);
		$specs = [];
		foreach ($keys as $index => $key) {
			$key = trim((string) $key);
			$value = trim((string) ($values[$index] ?? ''));
			if ($key !== '' && $value !== '') $specs[$key] = $value;
		}
		return $specs;
	}

	private function formPartData(array $part): array
	{
		$compatibility = json_decode($part['compatibility'] ?? '[]', true);
		$part['compatibility_lines'] = is_array($compatibility) ? implode("\n", $compatibility) : '';
		if ($this->input->post('compatibility') !== NULL) {
			$part['compatibility_lines'] = (string) $this->input->post('compatibility', true);
		}
		$specs = json_decode($part['technical_specs'] ?? '{}', true);
		$part['spec_rows'] = [];
		$postKeys = $this->input->post('spec_key', true);
		$postValues = $this->input->post('spec_value', true);
		if (is_array($postKeys)) {
			foreach ($postKeys as $index => $key) {
				$part['spec_rows'][] = ['key' => (string) $key, 'value' => (string) (($postValues[$index] ?? ''))];
			}
		} elseif (is_array($specs)) {
			foreach ($specs as $key => $value) {
				$part['spec_rows'][] = ['key' => (string) $key, 'value' => is_scalar($value) ? (string) $value : json_encode($value)];
			}
		}
		if (empty($part['spec_rows'])) $part['spec_rows'][] = ['key' => '', 'value' => ''];
		return $part;
	}

	private function uploadProductImage()
	{
		if (empty($_FILES['product_image']['name'])) return NULL;
		$this->config->load('upload');
		$config = $this->config->item('upload')['products'];
		if (!is_dir($config['upload_path']) && !mkdir($config['upload_path'], 0755, true) && !is_dir($config['upload_path'])) {
			throw new RuntimeException('The product image folder is not writable.');
		}
		$this->load->library('upload', $config);
		$this->upload->initialize($config, true);
		if (!$this->upload->do_upload('product_image')) return FALSE;
		$file = $this->upload->data();
		return 'storage/products/' . $file['file_name'];
	}

	//Uploads every file in the multi-file "gallery_images" input and returns
	//their stored paths. Returns an empty array when nothing was uploaded or
	//FALSE when CI's upload library rejected a file (same contract as
	//uploadProductImage()).
	private function uploadGalleryImages()
	{
		if (empty($_FILES['gallery_images']) || empty($_FILES['gallery_images']['name'])) return [];

		$this->config->load('upload');
		$config = $this->config->item('upload')['products'];
		if (!is_dir($config['upload_path']) && !mkdir($config['upload_path'], 0755, true) && !is_dir($config['upload_path'])) {
			throw new RuntimeException('The product image folder is not writable.');
		}

		$this->load->library('upload', $config);
		$paths = [];

		foreach (array_keys((array) $_FILES['gallery_images']['name']) as $i) {
			if (empty($_FILES['gallery_images']['name'][$i])) continue;

			$this->upload->initialize($config, true);
			if (!$this->upload->do_upload('gallery_images[' . $i . ']')) return FALSE;

			$file = $this->upload->data();
			$paths[] = 'storage/products/' . $file['file_name'];
		}

		return $paths;
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
