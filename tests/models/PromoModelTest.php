<?php

use PHPUnit\Framework\TestCase;

class PromoModelTest extends TestCase {

	private FakeDb $db;
	private PromoModel $model;

	protected function setUp(): void {
		$this->db = new FakeDb();
		$this->model = new PromoModel();
		$this->model->db = $this->db;
	}

	private function promo(array $overrides = []): array {
		return array_merge([
			'id' => 1,
			'code' => 'WELCOME10',
			'discount_type' => 'percent',
			'discount_value' => '10.00',
			'min_subtotal' => '0.00',
			'max_total_uses' => NULL,
			'used_count' => 0,
			'per_user_limit' => NULL,
			'starts_at' => NULL,
			'expires_at' => NULL,
			'is_active' => 1,
		], $overrides);
	}

	public function testValidateReturnsDiscountForPercentCode() {
		$this->db->rows = [$this->promo()];

		$result = $this->model->validatePromoCode('welcome10', 200.0);

		$this->assertSame(20.0, $result['discount']);
		$this->assertSame('WELCOME10', $result['code']);
	}

	public function testValidateRejectsUnknownOrInactiveCodeWithD15() {
		$this->db->rows = [$this->promo(['is_active' => 0])];

		try {
			$this->model->validatePromoCode('WELCOME10', 100.0);
			$this->fail('Expected D15');
		} catch (AppException $e) {
			$this->assertSame('D15', $e->getErrorCode());
		}
	}

	public function testValidateRejectsNotYetStartedWithD15() {
		$this->db->rows = [$this->promo(['starts_at' => date('Y-m-d H:i:s', time() + 3600)])];

		try {
			$this->model->validatePromoCode('WELCOME10', 100.0);
			$this->fail('Expected D15');
		} catch (AppException $e) {
			$this->assertSame('D15', $e->getErrorCode());
		}
	}

	public function testValidateRejectsExpiredWithD16() {
		$this->db->rows = [$this->promo(['expires_at' => date('Y-m-d H:i:s', time() - 3600)])];

		try {
			$this->model->validatePromoCode('WELCOME10', 100.0);
			$this->fail('Expected D16');
		} catch (AppException $e) {
			$this->assertSame('D16', $e->getErrorCode());
		}
	}

	public function testValidateRejectsBelowMinimumWithD17() {
		$this->db->rows = [$this->promo(['min_subtotal' => '500.00'])];

		try {
			$this->model->validatePromoCode('WELCOME10', 100.0);
			$this->fail('Expected D17');
		} catch (AppException $e) {
			$this->assertSame('D17', $e->getErrorCode());
		}
	}

	public function testValidateRejectsWhenGlobalLimitReachedWithD18() {
		$this->db->rows = [$this->promo(['max_total_uses' => 5, 'used_count' => 5])];

		try {
			$this->model->validatePromoCode('WELCOME10', 100.0);
			$this->fail('Expected D18');
		} catch (AppException $e) {
			$this->assertSame('D18', $e->getErrorCode());
		}
	}

	public function testValidateRejectsWhenPerUserLimitReachedWithD18() {
		$this->db->rows = [$this->promo(['per_user_limit' => 1])];
		$this->db->countAllResults = 1;

		try {
			$this->model->validatePromoCode('WELCOME10', 100.0, 'buyer@example.com');
			$this->fail('Expected D18');
		} catch (AppException $e) {
			$this->assertSame('D18', $e->getErrorCode());
		}
	}

	public function testValidateSkipsPerUserLimitForGuests() {
		$this->db->rows = [$this->promo(['per_user_limit' => 1])];

		$result = $this->model->validatePromoCode('WELCOME10', 100.0, NULL);

		$this->assertSame(10.0, $result['discount']);
	}

	public function testComputeDiscountCapsFixedAtSubtotal() {
		$promo = $this->promo(['discount_type' => 'fixed', 'discount_value' => '500.00']);

		$this->assertSame(100.0, $this->model->computeDiscount($promo, 100.0));
	}

	public function testComputeDiscountRoundsPercent() {
		$promo = $this->promo(['discount_type' => 'percent', 'discount_value' => '33.33']);

		$this->assertSame(10.0, $this->model->computeDiscount($promo, 30.0));
	}

	public function testGetPromoByCodeUppercasesAndTrims() {
		$this->db->rows = [$this->promo()];

		$this->model->getPromoByCode(' welcome10 ');

		$this->assertSame(['code', 'WELCOME10'], $this->db->whereCalls[0]);
	}

	public function testGetPromoByCodeReturnsNullForEmpty() {
		$this->assertNull($this->model->getPromoByCode('   '));
	}

	public function testGetPromoByIdThrowsP11WhenMissing() {
		$this->db->rows = [];

		try {
			$this->model->getPromoById(99);
			$this->fail('Expected P11');
		} catch (AppException $e) {
			$this->assertSame('P11', $e->getErrorCode());
		}
	}

	public function testCreatePromoCodeRequiresCode() {
		try {
			$this->model->createPromoCode(['discount_type' => 'fixed']);
			$this->fail('Expected D01');
		} catch (AppException $e) {
			$this->assertSame('D01', $e->getErrorCode());
		}
	}

	public function testCreatePromoCodeInsertsAndReturnsId() {
		$this->db->insertId = 7;

		$id = $this->model->createPromoCode(['code' => 'SAVE5']);

		$this->assertSame(7, $id);
		$this->assertSame('tbl_promo_codes', $this->db->lastInsertTable);
		$this->assertSame('SAVE5', $this->db->lastInsertData['code']);
	}

	public function testUpdatePromoCodeRequiresData() {
		try {
			$this->model->updatePromoCode(1, []);
			$this->fail('Expected D01');
		} catch (AppException $e) {
			$this->assertSame('D01', $e->getErrorCode());
		}
	}

	public function testUpdatePromoCodeWritesRow() {
		$this->model->updatePromoCode(3, ['is_active' => 0]);

		$this->assertSame('tbl_promo_codes', $this->db->updateCalls[0]['table']);
		$this->assertSame(['is_active' => 0], $this->db->updateCalls[0]['data']);
	}

	public function testIncrementUsageUsesArithmeticExpression() {
		$this->model->incrementUsage(5);

		$this->assertSame(['used_count', 'used_count + 1', FALSE], $this->db->setCalls[0]);
	}
}
