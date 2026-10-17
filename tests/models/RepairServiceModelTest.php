<?php

use PHPUnit\Framework\TestCase;

class RepairServiceModelTest extends TestCase {

	private FakeDb $db;
	private RepairServiceModel $model;

	protected function setUp(): void {
		$this->db = new FakeDb();
		$this->model = new RepairServiceModel();
		$this->model->db = $this->db;
	}

	public function testGetActiveServicesReturnsRows() {
		$this->db->rows = [
			['id' => 1, 'name' => 'Diagnostics', 'is_active' => 1],
		];

		$services = $this->model->getActiveServices();

		$this->assertCount(1, $services);
		$this->assertSame('tbl_repair_services', $this->db->lastTable);
	}

	public function testGetActiveServicesThrowsQ19OnFailure() {
		$this->db->failQuery = TRUE;

		try {
			$this->model->getActiveServices();
			$this->fail('Expected AppException Q19');
		} catch (AppException $e) {
			$this->assertSame('Q19', $e->getErrorCode());
		}
	}

	public function testGetAllServicesThrowsQ19OnFailure() {
		$this->db->failQuery = TRUE;

		try {
			$this->model->getAllServices();
			$this->fail('Expected AppException Q19');
		} catch (AppException $e) {
			$this->assertSame('Q19', $e->getErrorCode());
		}
	}

	public function testGetServiceByIdReturnsRow() {
		$this->db->rows = [['id' => 4, 'name' => 'Battery Replacement']];

		$service = $this->model->getServiceById(4);

		$this->assertSame('Battery Replacement', $service['name']);
	}

	public function testGetServiceByIdThrowsP12WhenMissing() {
		$this->db->rows = [];

		try {
			$this->model->getServiceById(4);
			$this->fail('Expected AppException P12');
		} catch (AppException $e) {
			$this->assertSame('P12', $e->getErrorCode());
		}
	}

	public function testCreateServiceInsertsAndReturnsId() {
		$this->db->insertId = 12;

		$id = $this->model->createService(['name' => 'Diagnostics', 'base_price' => 0]);

		$this->assertSame(12, $id);
		$this->assertSame('tbl_repair_services', $this->db->lastInsertTable);
		$this->assertSame('Diagnostics', $this->db->lastInsertData['name']);
	}

	public function testCreateServiceRejectsEmptyNameWithD01() {
		try {
			$this->model->createService(['name' => '']);
			$this->fail('Expected AppException D01');
		} catch (AppException $e) {
			$this->assertSame('D01', $e->getErrorCode());
		}
	}

	public function testCreateServiceThrowsQ19WhenInsertFails() {
		$this->db->failQuery = TRUE;

		try {
			$this->model->createService(['name' => 'Diagnostics']);
			$this->fail('Expected AppException Q19');
		} catch (AppException $e) {
			$this->assertSame('Q19', $e->getErrorCode());
		}
	}

	public function testUpdateServiceWritesFields() {
		$this->model->updateService(4, ['base_price' => 999.00]);

		$this->assertSame('tbl_repair_services', $this->db->updateCalls[0]['table']);
		$this->assertSame(999.00, $this->db->updateCalls[0]['data']['base_price']);
	}

	public function testUpdateServiceRejectsEmptyDataWithD01() {
		try {
			$this->model->updateService(4, []);
			$this->fail('Expected AppException D01');
		} catch (AppException $e) {
			$this->assertSame('D01', $e->getErrorCode());
		}
	}
}
