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

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Failed to fetch parts.');

		$this->model->getAllParts();
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

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('No tech part exists with ID 999999.');

		$this->model->getPartById(999999);
	}

	public function testGetPartByIdThrowsOnQueryFailure() {
		$this->db->failQuery = TRUE;

		$this->expectException(Exception::class);

		$this->model->getPartById(100001);
	}
}
