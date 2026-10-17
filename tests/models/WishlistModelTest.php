<?php

use PHPUnit\Framework\TestCase;

class WishlistModelTest extends TestCase {

	private FakeDb $db;
	private WishlistModel $model;

	protected function setUp(): void {
		$this->db = new FakeDb();
		$this->model = new WishlistModel();
		$this->model->db = $this->db;
	}

	public function testIsWishlistedFalseForEmptyUsername() {
		$this->db->countAllResults = 1;

		$this->assertFalse($this->model->isWishlisted('', 5));
	}

	public function testIsWishlistedReflectsCount() {
		$this->db->countAllResults = 1;
		$this->assertTrue($this->model->isWishlisted('alice', 5));

		$this->db->countAllResults = 0;
		$this->assertFalse($this->model->isWishlisted('alice', 5));
	}

	public function testAddInsertsNewPair() {
		$this->db->countAllResults = 0;

		$this->assertTrue($this->model->add('alice', 5));
		$this->assertSame('tbl_wishlist', $this->db->lastInsertTable);
		$this->assertSame(['username' => 'alice', 'part_id' => 5], $this->db->lastInsertData);
	}

	public function testAddReturnsFalseWhenAlreadySaved() {
		$this->db->countAllResults = 1;

		$this->assertFalse($this->model->add('alice', 5));
		$this->assertNull($this->db->lastInsertTable);
	}

	public function testAddThrowsQ17WhenInsertFails() {
		$this->db->countAllResults = 0;
		$this->db->failQuery = TRUE;

		try {
			$this->model->add('alice', 5);
			$this->fail('Expected Q17');
		} catch (AppException $e) {
			$this->assertSame('Q17', $e->getErrorCode());
		}
	}

	public function testAddIgnoresInvalidInput() {
		$this->assertFalse($this->model->add('', 5));
		$this->assertFalse($this->model->add('alice', 0));
	}

	public function testRemoveReturnsTrueWhenRowDeleted() {
		$this->db->affectedRows = 1;

		$this->assertTrue($this->model->remove('alice', 5));
		$this->assertSame('tbl_wishlist', $this->db->deleteCalls[0]['table']);
	}

	public function testRemoveReturnsFalseWhenNothingDeleted() {
		$this->db->affectedRows = 0;

		$this->assertFalse($this->model->remove('alice', 5));
	}

	public function testRemoveThrowsQ17WhenDeleteFails() {
		$this->db->failQuery = TRUE;

		try {
			$this->model->remove('alice', 5);
			$this->fail('Expected Q17');
		} catch (AppException $e) {
			$this->assertSame('Q17', $e->getErrorCode());
		}
	}

	public function testToggleAddsWhenNotYetSaved() {
		$this->db->countAllResults = 0;

		$this->assertTrue($this->model->toggle('alice', 5));
		$this->assertSame('tbl_wishlist', $this->db->lastInsertTable);
	}

	public function testToggleRemovesWhenAlreadySaved() {
		$this->db->countAllResults = 1;
		$this->db->affectedRows = 1;

		$this->assertFalse($this->model->toggle('alice', 5));
		$this->assertSame('tbl_wishlist', $this->db->deleteCalls[0]['table']);
	}

	public function testGetWishlistForUserReturnsEmptyForGuest() {
		$this->assertSame([], $this->model->getWishlistForUser(''));
	}

	public function testGetWishlistForUserReturnsRows() {
		$this->db->rows = [['wishlist_id' => 1, 'part_id' => 5, 'part_name' => 'Battery']];

		$result = $this->model->getWishlistForUser('alice');

		$this->assertCount(1, $result);
		$this->assertSame('tbl_wishlist', $this->db->lastTable);
	}

	public function testGetWishlistForUserThrowsQ17OnFailure() {
		$this->db->failQuery = TRUE;

		try {
			$this->model->getWishlistForUser('alice');
			$this->fail('Expected Q17');
		} catch (AppException $e) {
			$this->assertSame('Q17', $e->getErrorCode());
		}
	}

	public function testGetWishlistPartIdsCastsToInt() {
		$this->db->rows = [['part_id' => '5'], ['part_id' => '9']];

		$this->assertSame([5, 9], $this->model->getWishlistPartIds('alice'));
	}

	public function testGetWishlistPartIdsEmptyForGuest() {
		$this->assertSame([], $this->model->getWishlistPartIds(''));
	}

	public function testGetWishlistPartIdsThrowsQ17OnFailure() {
		$this->db->failQuery = TRUE;

		try {
			$this->model->getWishlistPartIds('alice');
			$this->fail('Expected Q17');
		} catch (AppException $e) {
			$this->assertSame('Q17', $e->getErrorCode());
		}
	}
}
