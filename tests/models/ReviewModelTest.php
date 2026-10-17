<?php

use PHPUnit\Framework\TestCase;

class ReviewModelTest extends TestCase {

	private FakeDb $db;
	private ReviewModel $model;

	protected function setUp(): void {
		$this->db = new FakeDb();
		$this->model = new ReviewModel();
		$this->model->db = $this->db;
	}

	private function orderWithItems(array $items): array {
		return ['id' => 1, 'items' => json_encode($items)];
	}

	public function testHasDeliveredOrderWithPartTrueWhenPartPresent() {
		$this->db->rows = [
			$this->orderWithItems([['part_id' => 3], ['part_id' => 7]]),
		];

		$this->assertTrue($this->model->hasDeliveredOrderWithPart('buyer@example.com', 7));
	}

	public function testHasDeliveredOrderWithPartFalseWhenPartAbsent() {
		$this->db->rows = [
			$this->orderWithItems([['part_id' => 3]]),
		];

		$this->assertFalse($this->model->hasDeliveredOrderWithPart('buyer@example.com', 7));
	}

	public function testHasDeliveredOrderWithPartFalseForEmptyEmail() {
		$this->assertFalse($this->model->hasDeliveredOrderWithPart('', 7));
	}

	public function testHasDeliveredOrderWithPartLowercasesEmail() {
		$this->db->rows = [];

		$this->model->hasDeliveredOrderWithPart('Buyer@Example.com', 7);

		$this->assertSame(['customer_email', 'buyer@example.com'], $this->db->whereCalls[2]);
	}

	public function testCreateOrUpdateRejectsInvalidRatingWithD19() {
		try {
			$this->model->createOrUpdateReview([
				'part_id' => 5,
				'username' => 'alice',
				'rating' => 0,
			]);
			$this->fail('Expected D19');
		} catch (AppException $e) {
			$this->assertSame('D19', $e->getErrorCode());
		}
	}

	public function testCreateOrUpdateInsertsNewReview() {
		$this->db->rows = [];
		$this->db->insertId = 42;

		$id = $this->model->createOrUpdateReview([
			'part_id' => 5,
			'username' => 'alice',
			'user_email' => 'alice@example.com',
			'rating' => 4,
			'title' => 'Great part',
			'body' => 'Worked perfectly in my phone.',
		]);

		$this->assertSame(42, $id);
		$this->assertSame('tbl_product_reviews', $this->db->lastInsertTable);
		$this->assertSame('published', $this->db->lastInsertData['status']);
		$this->assertSame(0, $this->db->lastInsertData['order_verified']);
	}

	public function testCreateOrUpdateVerifiesDeliveredBuyer() {
		// The same canned rows feed both the order lookup and the existing-review
		// lookup, so one row can stand in for both.
		$this->db->rows = [
			['id' => 7, 'items' => json_encode([['part_id' => 5]])],
		];

		$id = $this->model->createOrUpdateReview([
			'part_id' => 5,
			'username' => 'alice',
			'user_email' => 'alice@example.com',
			'rating' => 5,
			'title' => 'Verified',
			'body' => 'Bought and love it.',
		]);

		$this->assertSame(7, $id);
		$this->assertSame('tbl_product_reviews', $this->db->updateCalls[0]['table']);
		$this->assertSame(1, $this->db->updateCalls[0]['data']['order_verified']);
		$this->assertNull($this->db->lastInsertTable);
	}

	public function testCreateOrUpdateThrowsQ18WhenInsertFails() {
		$this->db->rows = [];
		$this->db->failQuery = TRUE;

		try {
			$this->model->createOrUpdateReview([
				'part_id' => 5,
				'username' => 'alice',
				'rating' => 5,
			]);
			$this->fail('Expected Q18');
		} catch (AppException $e) {
			$this->assertSame('Q18', $e->getErrorCode());
		}
	}

	public function testGetReviewsForPartReturnsRows() {
		$this->db->rows = [['id' => 1, 'rating' => 5]];

		$result = $this->model->getReviewsForPart(5);

		$this->assertCount(1, $result);
		$this->assertSame('tbl_product_reviews', $this->db->lastTable);
	}

	public function testGetReviewsForPartThrowsQ18OnFailure() {
		$this->db->failQuery = TRUE;

		try {
			$this->model->getReviewsForPart(5);
			$this->fail('Expected Q18');
		} catch (AppException $e) {
			$this->assertSame('Q18', $e->getErrorCode());
		}
	}

	public function testGetAverageRatingReturnsZeroWhenNoReviews() {
		$this->db->countAllResults = 0;

		$this->assertSame(['avg' => 0.0, 'count' => 0], $this->model->getAverageRating(5));
	}

	public function testGetAverageRatingRoundsAverage() {
		$this->db->countAllResults = 3;
		$this->db->rows = [['avg_rating' => '4.333']];

		$this->assertSame(['avg' => 4.3, 'count' => 3], $this->model->getAverageRating(5));
	}

	public function testGetAllReviewsReturnsRows() {
		$this->db->rows = [['id' => 1, 'part_name' => 'Battery']];

		$result = $this->model->getAllReviews([], 'newest', 1, 15);

		$this->assertCount(1, $result);
		$this->assertSame('tbl_product_reviews', $this->db->lastTable);
		$this->assertSame([[15, 0]], $this->db->limitCalls);
	}

	public function testCountAllReviewsReturnsCount() {
		$this->db->countAllResults = 8;

		$this->assertSame(8, $this->model->countAllReviews(['status' => 'hidden']));
	}

	public function testSetReviewStatusRejectsBadStatusWithD20() {
		try {
			$this->model->setReviewStatus(1, 'bogus');
			$this->fail('Expected D20');
		} catch (AppException $e) {
			$this->assertSame('D20', $e->getErrorCode());
		}
	}

	public function testSetReviewStatusUpdatesRow() {
		$this->db->affectedRows = 1;

		$this->assertTrue($this->model->setReviewStatus(1, 'hidden'));
		$this->assertSame(['status' => 'hidden'], $this->db->updateCalls[0]['data']);
	}

	public function testSetReviewStatusReturnsFalseWhenUnchanged() {
		$this->db->affectedRows = 0;

		$this->assertFalse($this->model->setReviewStatus(1, 'published'));
	}

	public function testDeleteReviewRemovesRow() {
		$this->db->affectedRows = 1;

		$this->assertTrue($this->model->deleteReview(3));
		$this->assertSame('tbl_product_reviews', $this->db->deleteCalls[0]['table']);
	}

	public function testDeleteReviewThrowsQ18WhenDeleteFails() {
		$this->db->failQuery = TRUE;

		try {
			$this->model->deleteReview(3);
			$this->fail('Expected Q18');
		} catch (AppException $e) {
			$this->assertSame('Q18', $e->getErrorCode());
		}
	}
}
