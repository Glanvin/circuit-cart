<?php

use PHPUnit\Framework\TestCase;

class PartsModelTest extends TestCase {

	private $ci;
	private $db;
	private $model;

	protected function setUp(): void {
		$this->ci = new CI_Controller();
		$this->db = new FakeDb();
		$this->ci->db = $this->db;
		$this->ci->config = new FakeConfig();
		$this->ci->config->items['error_codes'] = error_codes();
		$this->model = new PartsModel();
	}

	public function testGetAllPartsQueriesTechPartsTable() {
		$this->db->rows = [
			['part_id' => 1, 'part_name' => 'iPhone 11 Screen'],
		];

		$result = $this->model->getAllParts();

		$this->assertSame('tbl_sku', $this->db->lastTable);
		$this->assertCount(1, $result);
	}

	public function testGetAllPartsFiltersActiveOnly() {
		$this->model->getAllParts();

		$this->assertContains(['is_active', 1], $this->db->whereCalls);
	}

	public function testGetAllPartsReturnsAllRows() {
		$this->db->rows = [
			['part_id' => 1, 'part_name' => 'A'],
			['part_id' => 2, 'part_name' => 'B'],
			['part_id' => 3, 'part_name' => 'C'],
		];

		$result = $this->model->getAllParts();

		$this->assertCount(3, $result);
		$this->assertSame('B', $result[1]['part_name']);
	}

	public function testGetAllPartsThrowsOnQueryFailure() {
		$this->db->failQuery = TRUE;

		try {
			$this->model->getAllParts();
			$this->fail('Expected AppException');
		} catch (AppException $e) {
			$this->assertSame('Q01', $e->getErrorCode());
			$this->assertSame('Failed to fetch parts.', $e->getMessage());
			$this->assertSame('Failed to fetch parts. [Q01]', $e->toToast());
		}
	}

	public function testGetPartByIdReturnsRowArray() {
		$this->db->rows = [
			['part_id' => 100001, 'part_name' => 'Steam Deck Fan'],
		];

		$part = $this->model->getPartById(100001);

		$this->assertSame('Steam Deck Fan', $part['part_name']);
	}

	public function testGetPartByIdThrowsWhenNotFound() {
		$this->db->rows = [];

		try {
			$this->model->getPartById(999999);
			$this->fail('Expected AppException');
		} catch (AppException $e) {
			$this->assertSame('P02', $e->getErrorCode());
			$this->assertSame('No tech part exists with ID 999999.', $e->getMessage());
			$this->assertSame('No tech part exists with ID 999999. [P02]', $e->toToast());
		}
	}

	public function testGetPartByIdThrowsOnQueryFailure() {
		$this->db->failQuery = TRUE;

		try {
			$this->model->getPartById(100001);
			$this->fail('Expected AppException');
		} catch (AppException $e) {
			$this->assertSame('Q02', $e->getErrorCode());
			$this->assertSame('Failed to fetch part with ID 100001.', $e->getMessage());
		}
	}

	public function testAdjustStockUsesAtomicDeltaAndRecordsLinkedMovement() {
		$this->db->rows = [
			['part_id' => 100001, 'part_name' => 'Steam Deck Fan', 'stock_quantity' => 8],
		];

		$this->model->adjustStock(100001, -3, 'sale', 'sale', 42, 7, 'Receipt ST-42');

		$this->assertSame(['stock_quantity', 'stock_quantity + -3', FALSE], $this->db->setCalls[0]);
		$this->assertContains(['stock_quantity >=', 3], $this->db->whereCalls);
		$this->assertSame('tbl_sku', $this->db->updateCalls[0]['table']);
		$this->assertSame('tbl_stock_movements', $this->db->lastInsertTable);
		$this->assertSame(100001, $this->db->lastInsertData['part_id']);
		$this->assertSame(-3, $this->db->lastInsertData['qty_delta']);
		$this->assertSame(42, $this->db->lastInsertData['reference_id']);
		$this->assertSame(7, $this->db->lastInsertData['employee_id']);
	}

	public function testAdjustStockRejectsConcurrentInsufficientStock() {
		$this->db->rows = [
			['part_id' => 100001, 'part_name' => 'Steam Deck Fan', 'stock_quantity' => 1],
		];
		$this->db->affectedRows = 0;

		try {
			$this->model->adjustStock(100001, -2, 'sale', 'sale', 42, 7);
			$this->fail('Expected AppException D02');
		} catch (AppException $e) {
			$this->assertSame('D02', $e->getErrorCode());
			$this->assertStringContainsString('Steam Deck Fan', $e->getMessage());
		}

		$this->assertNull($this->db->lastInsertTable);
	}

	public function testGetAllPartsAppliesLimitAndOffsetWhenPaged() {
		$this->model->getAllParts([], 'name_asc', 3, 12);

		$this->assertSame([12, 24], $this->db->limitCalls[0]);
	}

	public function testGetAllPartsOmitsLimitWhenUnpaged() {
		$this->model->getAllParts();

		$this->assertSame([], $this->db->limitCalls);
	}

	public function testGetAllPartsAppliesPriceRangeFilter() {
		$this->model->getAllParts(['min_price' => 100.0, 'max_price' => 500.0]);

		$this->assertContains(['selling_price >=', 100.0], $this->db->whereCalls);
		$this->assertContains(['selling_price <=', 500.0], $this->db->whereCalls);
	}

	public function testGetAllPartsIgnoresNonNumericPriceFilter() {
		$this->model->getAllParts(['min_price' => 'abc', 'max_price' => 'xyz']);

		$this->assertNotContains(['selling_price >=', 0.0], $this->db->whereCalls);
	}

	public function testCountAllPartsUsesFiltersAndReturnsCount() {
		$this->db->countAllResults = 42;

		$count = $this->model->countAllParts(['category' => 'Screen']);

		$this->assertSame(42, $count);
		$this->assertContains(['is_active', 1], $this->db->whereCalls);
		$this->assertContains(['category', 'Screen'], $this->db->whereCalls);
		$this->assertSame('tbl_sku', $this->db->lastTable);
	}

	public function testGetRelatedPartsExcludesSelfAndLimits() {
		$this->db->rows = [['part_id' => 2, 'part_name' => 'Other']];

		$result = $this->model->getRelatedParts(5, 'Screen', 'iPhone');

		$this->assertSame('tbl_sku', $this->db->lastTable);
		$this->assertContains(['part_id !=', 5], $this->db->whereCalls);
		$this->assertContains(['category', 'Screen'], $this->db->whereCalls);
		$this->assertSame([4, 0], $this->db->limitCalls[0]);
		$this->assertCount(1, $result);
	}

	public function testGetRelatedPartsReturnsEmptyOnFailure() {
		$this->db->failQuery = TRUE;

		$this->assertSame([], $this->model->getRelatedParts(5, 'Screen'));
	}

	public function testGetProductImagesReturnsOrderedGalleryRows() {
		$this->db->rows = [
			['id' => 1, 'part_id' => 5, 'image_path' => 'storage/products/a.webp', 'sort_order' => 0],
		];

		$result = $this->model->getProductImages(5);

		$this->assertSame('tbl_product_images', $this->db->lastTable);
		$this->assertContains(['part_id', 5], $this->db->whereCalls);
		$this->assertCount(1, $result);
		$this->assertSame('storage/products/a.webp', $result[0]['image_path']);
	}

	public function testGetProductImagesReturnsEmptyOnFailure() {
		$this->db->failQuery = TRUE;

		$this->assertSame([], $this->model->getProductImages(5));
	}

	public function testAddProductImageInsertsWithComputedSortOrder() {
		$this->db->insertId = 9;

		$id = $this->model->addProductImage(5, 'storage/products/b.webp');

		$this->assertSame(9, $id);
		$this->assertSame('tbl_product_images', $this->db->lastInsertTable);
		$this->assertSame(5, $this->db->lastInsertData['part_id']);
		$this->assertSame('storage/products/b.webp', $this->db->lastInsertData['image_path']);
		$this->assertSame(0, $this->db->lastInsertData['sort_order']);
	}

	public function testAddProductImageUsesExplicitSortOrderWhenGiven() {
		$this->model->addProductImage(5, 'storage/products/c.webp', 7);

		$this->assertSame(7, $this->db->lastInsertData['sort_order']);
	}

	public function testAddProductImageRejectsEmptyPathWithD01() {
		try {
			$this->model->addProductImage(5, '');
			$this->fail('Expected D01');
		} catch (AppException $e) {
			$this->assertSame('D01', $e->getErrorCode());
		}
		$this->assertNull($this->db->lastInsertTable);
	}

	public function testDeleteProductImageRemovesRow() {
		$ok = $this->model->deleteProductImage(3);

		$this->assertTrue($ok);
		$this->assertSame('tbl_product_images', $this->db->deleteCalls[0]['table']);
		$this->assertSame(['id' => 3], $this->db->deleteCalls[0]['where']);
	}
}
