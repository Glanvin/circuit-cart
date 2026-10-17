<?php

use PHPUnit\Framework\TestCase;

//Test double that records stock adjustments instead of touching the database.
class RecordingPartsModel {
	public $calls = [];
	public $parts = [];

	public function getPartById($part_id) {
		if (!isset($this->parts[(int) $part_id])) {
			throw new AppException('P01', ['id' => $part_id]);
		}
		return $this->parts[(int) $part_id];
	}

	public function adjustStock($part_id, $delta, $reason, $reference_type = NULL, $reference_id = NULL, $employee_id = NULL, $note = NULL) {
		$this->calls[] = [
			'part_id' => $part_id,
			'delta' => $delta,
			'reason' => $reason,
			'reference_type' => $reference_type,
			'reference_id' => $reference_id,
			'employee_id' => $employee_id,
			'note' => $note,
		];
		return 1;
	}
}

class SalesModelTest extends TestCase {

	private FakeDb $db;
	private RecordingPartsModel $parts;
	private SalesModel $model;

	protected function setUp(): void {
		$this->db = new FakeDb();
		$this->parts = new RecordingPartsModel();
		$this->model = new SalesModel();
		$this->model->db = $this->db;
		$this->model->PartsModel = $this->parts;
	}

	private function saleRow(array $overrides = []): array {
		return array_merge([
			'id' => 5,
			'receipt_no' => 'ST-20260101-ABCDE',
			'origin' => 'store',
			'status' => 'completed',
			'sold_by' => 'Cashier One',
			'total' => '120.00',
			'subtotal' => '107.14',
			'tax_total' => '12.86',
			'created_at' => '2026-01-01 10:00:00',
			'items' => json_encode([
				['part_id' => 1, 'name' => 'Screen', 'qty' => 2, 'price' => 50.00],
				['part_id' => 2, 'name' => 'Battery', 'qty' => 1, 'price' => 20.00],
			]),
			'payment_methods' => json_encode([]),
			'tax_breakdown' => json_encode([]),
			'history' => json_encode([['action' => 'created', 'timestamp' => '2026-01-01 10:00:00']]),
			'refund_amount' => NULL,
			'refund_reason' => NULL,
			'refunded_at' => NULL,
			'refunded_by' => NULL,
		], $overrides);
	}

	public function testRefundRestocksLinesAndStampsAudit() {
		$this->db->rows = [$this->saleRow()];

		$result = $this->model->refundSale(5, 7, 'Damaged on arrival', TRUE, 'refund', 3);

		$this->assertCount(2, $this->parts->calls);
		$this->assertSame('refund', $this->parts->calls[0]['reason']);
		$this->assertSame(2, $this->parts->calls[0]['delta']);
		$this->assertSame(1, $this->parts->calls[1]['delta']);
		$this->assertSame('sale', $this->parts->calls[0]['reference_type']);
		$this->assertSame(5, $this->parts->calls[0]['reference_id']);
		$this->assertSame(7, $this->parts->calls[0]['employee_id']);

		$this->assertCount(1, $this->db->updateCalls);
		$update = $this->db->updateCalls[0];
		$this->assertSame('tbl_sales', $update['table']);
		$this->assertSame('refunded', $update['data']['status']);
		$this->assertSame('120.00', $update['data']['refund_amount']);
		$this->assertSame(7, $update['data']['refunded_by']);
		$this->assertSame('Damaged on arrival', $update['data']['refund_reason']);
		$this->assertSame(3, $update['data']['refund_authorized_by']);

		$history = json_decode($update['data']['history'], TRUE);
		$this->assertSame('refunded', $history[1]['action']);
		$this->assertSame(3, $history[1]['refund_authorized_by']);
		$this->assertSame($result['receipt_no'], 'ST-20260101-ABCDE');
	}

	public function testVoidUsesVoidedStatus() {
		$this->db->rows = [$this->saleRow()];

		$this->model->refundSale(5, 7, 'Wrong item', TRUE, 'void');

		$this->assertSame('voided', $this->db->updateCalls[0]['data']['status']);
	}

	public function testRefundWithoutRestockSkipsStockMovement() {
		$this->db->rows = [$this->saleRow()];

		$this->model->refundSale(5, 7, 'No restock', FALSE, 'refund');

		$this->assertCount(0, $this->parts->calls);
	}

	public function testRefundingOnlineOrderThrowsD07() {
		$this->db->rows = [$this->saleRow(['origin' => 'online', 'status' => 'delivered'])];

		try {
			$this->model->refundSale(5, 7, '', TRUE, 'refund');
			$this->fail('Expected AppException D07');
		} catch (AppException $e) {
			$this->assertSame('D07', $e->getErrorCode());
		}

		$this->assertCount(0, $this->parts->calls);
	}

	public function testRefundingAlreadyRefundedThrowsD06() {
		$this->db->rows = [$this->saleRow(['status' => 'refunded'])];

		try {
			$this->model->refundSale(5, 7, '', TRUE, 'refund');
			$this->fail('Expected AppException D06');
		} catch (AppException $e) {
			$this->assertSame('D06', $e->getErrorCode());
		}
	}

	public function testRefundTransactionFailureThrowsQ15() {
		$this->db->rows = [$this->saleRow()];
		$this->db->transStatus = FALSE;

		try {
			$this->model->refundSale(5, 7, '', TRUE, 'refund');
			$this->fail('Expected AppException Q15');
		} catch (AppException $e) {
			$this->assertSame('Q15', $e->getErrorCode());
		}
	}

	public function testCancelOrderRestocksAndMarksCancelled() {
		$this->db->rows = [$this->saleRow(['origin' => 'online', 'status' => 'processing'])];

		$this->model->cancelOrder(5, NULL, 'Cancelled by customer', ['processing']);

		$this->assertCount(2, $this->parts->calls);
		$this->assertSame('return', $this->parts->calls[0]['reason']);
		$this->assertSame('cancelled', $this->db->updateCalls[0]['data']['status']);
	}

	public function testCancelOrderFromDisallowedStatusThrowsD09() {
		$this->db->rows = [$this->saleRow(['origin' => 'online', 'status' => 'shipped'])];

		try {
			$this->model->cancelOrder(5, NULL, '', ['processing']);
			$this->fail('Expected AppException D09');
		} catch (AppException $e) {
			$this->assertSame('D09', $e->getErrorCode());
		}
	}

	public function testCancelStoreSaleThrowsD08() {
		$this->db->rows = [$this->saleRow(['origin' => 'store', 'status' => 'completed'])];

		try {
			$this->model->cancelOrder(5, NULL, '', ['processing']);
			$this->fail('Expected AppException D08');
		} catch (AppException $e) {
			$this->assertSame('D08', $e->getErrorCode());
		}
	}

	public function testGetSaleByReceiptUppercasesAndTrims() {
		$this->db->rows = [$this->saleRow()];

		$this->model->getSaleByReceipt(' st-20260101-abcde ');

		$this->assertSame(['receipt_no', 'ST-20260101-ABCDE'], $this->db->whereCalls[0]);
	}

	private function primeStoreTaxAndPart(): void {
		$this->db->rows = [['id' => 1, 'name' => 'VAT 12%', 'rate_percent' => '12.00']];
		$this->parts->parts = [
			1 => ['part_id' => 1, 'part_name' => 'Screen', 'selling_price' => '100.00', 'stock_quantity' => 10],
		];
	}

	public function testRecordSaleAllowsCashOverpayment() {
		$this->primeStoreTaxAndPart();

		$sale = $this->model->recordSale([
			'origin' => 'store',
			'sold_by' => 'Cashier One',
			'employee_id' => 7,
			'items' => [['part_id' => 1, 'qty' => 1]],
			'payment_methods' => [['method_id' => 1, 'code' => 'cash', 'amount' => 120.00, 'reference' => 'CASH-TEST']],
		]);

		$this->assertSame(100.0, (float) $sale['subtotal']);
		$this->assertSame(12.0, (float) $sale['tax_total']);
		$this->assertSame(112.0, (float) $sale['total']);
		$this->assertSame(120.0, (float) $sale['payment_methods'][0]['amount']);
		$this->assertSame('tbl_sales', $this->db->lastInsertTable);
	}

	public function testOnlineQuoteUsesCurrentCatalogPriceAndTaxRate() {
		$this->db->rows = [['id' => 1, 'name' => 'VAT 12%', 'rate_percent' => '12.00']];
		$this->parts->parts = [1 => [
			'part_id' => 1,
			'part_name' => 'Screen',
			'category' => 'Screen',
			'image_path' => 'storage/products/screen.webp',
			'selling_price' => '100.00',
			'stock_quantity' => 5,
		]];

		$quote = $this->model->quoteOnlineOrder([['part_id' => 1, 'quantity' => 2]]);

		$this->assertSame(200.0, $quote['subtotal']);
		$this->assertSame(24.0, $quote['tax']);
		$this->assertSame(10.0, $quote['shipping']);
		$this->assertSame(234.0, $quote['total']);
		$this->assertSame('storage/products/screen.webp', $quote['items'][0]['image_path']);
	}

	public function testRecordSaleRejectsUnderpaymentWithD05() {
		$this->primeStoreTaxAndPart();

		try {
			$this->model->recordSale([
				'origin' => 'store',
				'sold_by' => 'Cashier One',
				'employee_id' => 7,
				'items' => [['part_id' => 1, 'qty' => 1]],
				'payment_methods' => [['method_id' => 1, 'code' => 'cash', 'amount' => 100.00, 'reference' => NULL]],
			]);
			$this->fail('Expected AppException D05');
		} catch (AppException $e) {
			$this->assertSame('D05', $e->getErrorCode());
		}

		$this->assertNull($this->db->lastInsertTable);
	}

	public function testRecordSaleRejectsNonCashOverpaymentWithD05() {
		$this->primeStoreTaxAndPart();

		try {
			$this->model->recordSale([
				'origin' => 'store',
				'sold_by' => 'Cashier One',
				'employee_id' => 7,
				'items' => [['part_id' => 1, 'qty' => 1]],
				'payment_methods' => [['method_id' => 2, 'code' => 'card', 'amount' => 200.00, 'reference' => 'TXN-1']],
			]);
			$this->fail('Expected AppException D05');
		} catch (AppException $e) {
			$this->assertSame('D05', $e->getErrorCode());
		}

		$this->assertNull($this->db->lastInsertTable);
	}

	public function testRecordSaleRejectsInsufficientStockWithD02() {
		$this->primeStoreTaxAndPart();
		$this->parts->parts[1]['stock_quantity'] = 0;

		try {
			$this->model->recordSale([
				'origin' => 'store',
				'sold_by' => 'Cashier One',
				'employee_id' => 7,
				'items' => [['part_id' => 1, 'qty' => 1]],
				'payment_methods' => [['method_id' => 1, 'code' => 'cash', 'amount' => 120.00, 'reference' => NULL]],
			]);
			$this->fail('Expected AppException D02');
		} catch (AppException $e) {
			$this->assertSame('D02', $e->getErrorCode());
		}
	}

	public function testStoreTaxRatesDelegatesToActiveRates() {
		$this->db->rows = [['id' => 1, 'name' => 'VAT 12%', 'rate_percent' => '12.00']];

		$rates = $this->model->getStoreTaxRates();

		$this->assertCount(1, $rates);
		$this->assertSame('VAT 12%', $rates[0]['name']);
	}

	private function promoRow(array $overrides = []): array {
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

	private function primePromo(PromoModel $promo, array $row): void {
		$promoDb = new FakeDb();
		$promoDb->rows = [$row];
		$promo->db = $promoDb;
		$this->model->PromoModel = $promo;
	}

	public function testOnlineQuoteAppliesPercentPromoBeforeTax() {
		$this->db->rows = [['id' => 1, 'name' => 'VAT 12%', 'rate_percent' => '12.00']];
		$this->parts->parts = [1 => ['part_id' => 1, 'part_name' => 'Screen', 'category' => 'Screen', 'selling_price' => '100.00', 'stock_quantity' => 5]];
		$this->primePromo(new PromoModel(), $this->promoRow());

		$quote = $this->model->quoteOnlineOrder([['part_id' => 1, 'quantity' => 2]], 'welcome10');

		$this->assertSame(200.0, $quote['subtotal']);
		$this->assertSame(20.0, $quote['discount']);
		$this->assertSame('WELCOME10', $quote['promo_code']);
		// Tax is charged on the discounted subtotal (180), not the raw 200.
		$this->assertSame(21.6, $quote['tax']);
		$this->assertSame(211.6, $quote['total']);
	}

	public function testOnlineQuoteWithoutPromoHasZeroDiscount() {
		$this->db->rows = [['id' => 1, 'name' => 'VAT 12%', 'rate_percent' => '12.00']];
		$this->parts->parts = [1 => ['part_id' => 1, 'part_name' => 'Screen', 'category' => 'Screen', 'selling_price' => '100.00', 'stock_quantity' => 5]];
		$this->model->PromoModel = new PromoModel();

		$quote = $this->model->quoteOnlineOrder([['part_id' => 1, 'quantity' => 1]]);

		$this->assertSame(0.0, $quote['discount']);
		$this->assertNull($quote['promo_code']);
	}

	public function testOnlineQuotePropagatesInvalidPromo() {
		$this->db->rows = [['id' => 1, 'name' => 'VAT 12%', 'rate_percent' => '12.00']];
		$this->parts->parts = [1 => ['part_id' => 1, 'part_name' => 'Screen', 'category' => 'Screen', 'selling_price' => '100.00', 'stock_quantity' => 5]];
		$this->primePromo(new PromoModel(), $this->promoRow(['is_active' => 0]));

		$this->expectException(AppException::class);
		$this->model->quoteOnlineOrder([['part_id' => 1, 'quantity' => 1]], 'WELCOME10');
	}

	public function testRecordSaleAppliesPromoAndIncrementsUsage() {
		$this->db->rows = [['id' => 1, 'name' => 'VAT 12%', 'rate_percent' => '12.00']];
		$this->parts->parts = [1 => ['part_id' => 1, 'part_name' => 'Screen', 'selling_price' => '100.00', 'stock_quantity' => 10]];
		$promo = new PromoModel();
		$this->primePromo($promo, $this->promoRow());

		$sale = $this->model->recordSale([
			'origin' => 'online',
			'sold_by' => NULL,
			'customer_email' => 'buyer@example.com',
			'customer_address' => '1 Main St',
			'payment_code' => 'cod',
			'promo_code' => 'WELCOME10',
			'items' => [['part_id' => 1, 'qty' => 2, 'price' => 100.00]],
		]);

		$this->assertSame(200.0, (float) $sale['subtotal']);
		$this->assertSame(20.0, (float) $sale['discount_total']);
		$this->assertSame('WELCOME10', $sale['promo_code']);
		$this->assertSame(21.6, (float) $sale['tax_total']);
		$this->assertSame(211.6, (float) $sale['total']);
		// Usage is burned inside the transaction.
		$this->assertNotEmpty($promo->db->setCalls);
		$this->assertSame('used_count', $promo->db->setCalls[0][0]);
	}

	public function testRecordSaleStoreIgnoresPromoCode() {
		$this->primeStoreTaxAndPart();
		$this->model->PromoModel = new PromoModel();

		$sale = $this->model->recordSale([
			'origin' => 'store',
			'sold_by' => 'Cashier One',
			'employee_id' => 7,
			'promo_code' => 'WELCOME10',
			'items' => [['part_id' => 1, 'qty' => 1]],
			'payment_methods' => [['method_id' => 1, 'code' => 'cash', 'amount' => 120.00, 'reference' => NULL]],
		]);

		$this->assertSame(0.0, (float) $sale['discount_total']);
		$this->assertNull($sale['promo_code']);
	}
}
