<?php

use PHPUnit\Framework\TestCase;

//Stock double that records adjustments instead of touching inventory.
class RepairPartsRecorder {
	public $calls = [];

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

//Employee double for assignment lookups.
class RepairEmployeeStub {
	public $employees = [];

	public function getEmployeeById($id) {
		return $this->employees[(int) $id] ?? [];
	}
}

class RepairJobModelTest extends TestCase {

	private FakeDb $db;
	private RepairPartsRecorder $parts;
	private RepairEmployeeStub $employees;
	private RepairJobModel $model;

	protected function setUp(): void {
		$this->db = new FakeDb();
		$this->parts = new RepairPartsRecorder();
		$this->employees = new RepairEmployeeStub();
		$this->model = new RepairJobModel();
		$this->model->db = $this->db;
		$this->model->PartsModel = $this->parts;
		$this->model->EmployeeModel = $this->employees;
	}

	private function jobRow(array $overrides = []): array {
		return array_merge([
			'id' => 3,
			'job_reference' => 'RJ-20260101-ABCDE',
			'booking_id' => 9,
			'device' => 'iPhone 12',
			'issue' => 'Cracked screen',
			'diagnosis' => 'Screen assembly failed',
			'estimate_total' => '500.00',
			'estimate_approved' => 0,
			'estimate_approved_at' => NULL,
			'estimate_token' => 'tok123',
			'estimate_token_expires_at' => date('Y-m-d H:i:s', time() + 3600),
			'estimate_declined_at' => NULL,
			'estimate_declined_reason' => NULL,
			'public_token' => 'pub456',
			'status' => 'estimate_pending',
			'status_history' => json_encode([]),
			'parts_used' => json_encode([]),
			'labor_items' => json_encode([]),
		], $overrides);
	}

	public function testCustomerApproveConsumesTokenAndSetsApproved() {
		$this->db->rows = [$this->jobRow()];

		$result = $this->model->customerApproveEstimate('tok123');

		$this->assertNotEmpty($this->db->updateCalls);
		$data = $this->db->updateCalls[0]['data'];
		$this->assertSame(1, $data['estimate_approved']);
		$this->assertSame('approved', $data['status']);
		$this->assertNull($data['estimate_token']);
		$this->assertSame('pub456', $result['public_token']);
	}

	public function testExpiredEstimateTokenThrowsD10() {
		$this->db->rows = [$this->jobRow(['estimate_token_expires_at' => date('Y-m-d H:i:s', time() - 60)])];

		try {
			$this->model->getJobByEstimateToken('tok123');
			$this->fail('Expected AppException D10');
		} catch (AppException $e) {
			$this->assertSame('D10', $e->getErrorCode());
		}
	}

	public function testBlankEstimateTokenThrowsD10() {
		try {
			$this->model->getJobByEstimateToken('   ');
			$this->fail('Expected AppException D10');
		} catch (AppException $e) {
			$this->assertSame('D10', $e->getErrorCode());
		}
	}

	public function testLoginlessApproveWithZeroEstimateThrowsD04() {
		$this->db->rows = [$this->jobRow(['estimate_total' => '0.00'])];

		try {
			$this->model->customerApproveEstimate('tok123');
			$this->fail('Expected AppException D04');
		} catch (AppException $e) {
			$this->assertSame('D04', $e->getErrorCode());
		}
	}

	public function testCustomerDeclineRecordsReasonAndResetsStatus() {
		$this->db->rows = [$this->jobRow()];

		$this->model->customerDeclineEstimate('tok123', 'Too expensive');

		$data = $this->db->updateCalls[0]['data'];
		$this->assertSame('diagnosis', $data['status']);
		$this->assertNull($data['estimate_token']);
		$this->assertSame('Too expensive', $data['estimate_declined_reason']);
		$this->assertNotNull($data['estimate_declined_at']);

		$history = json_decode($data['status_history'], TRUE);
		$this->assertSame('estimate_declined', $history[0]['status']);
	}

	public function testBlankPublicTokenThrowsP10() {
		try {
			$this->model->getJobByPublicToken('');
			$this->fail('Expected AppException P10');
		} catch (AppException $e) {
			$this->assertSame('P10', $e->getErrorCode());
		}
	}

	public function testGetJobByPublicTokenReturnsDecodedJob() {
		$this->db->rows = [$this->jobRow()];

		$job = $this->model->getJobByPublicToken('pub456');

		$this->assertSame(3, $job['id']);
		$this->assertIsArray($job['status_history']);
		$this->assertSame('pub456', $job['public_token']);
	}

	public function testUpdateStatusStampsStartedAtOnInProgress() {
		$this->db->rows = [$this->jobRow(['status' => 'approved', 'started_at' => NULL])];

		$this->model->updateStatus(3, 'in_progress', 'Work begins');

		$data = $this->db->updateCalls[0]['data'];
		$this->assertSame('in_progress', $data['status']);
		$this->assertNotNull($data['started_at']);
	}

	public function testUpdateStatusRejectsIllegalTransitionWithD13() {
		$this->db->rows = [$this->jobRow(['status' => 'diagnosis'])];

		try {
			$this->model->updateStatus(3, 'completed');
			$this->fail('Expected AppException D13');
		} catch (AppException $e) {
			$this->assertSame('D13', $e->getErrorCode());
		}

		$this->assertEmpty($this->db->updateCalls);
	}

	public function testUpdateStatusOnTerminalJobThrowsD12() {
		$this->db->rows = [$this->jobRow(['status' => 'cancelled'])];

		try {
			$this->model->updateStatus(3, 'in_progress');
			$this->fail('Expected AppException D12');
		} catch (AppException $e) {
			$this->assertSame('D12', $e->getErrorCode());
		}
	}

	public function testCancellingJobReturnsPartsToStock() {
		$this->db->rows = [$this->jobRow([
			'status' => 'approved',
			'parts_used' => json_encode([
				['part_id' => 1, 'name' => 'Screen', 'qty' => 2, 'price' => 50.00, 'added_at' => '2026-01-01 10:00:00'],
			]),
		])];

		$this->model->updateStatus(3, 'cancelled', 'Customer changed mind', 7);

		$this->assertCount(1, $this->parts->calls);
		$this->assertSame('return', $this->parts->calls[0]['reason']);
		$this->assertSame(2, $this->parts->calls[0]['delta']);
		$this->assertSame('repair_job', $this->parts->calls[0]['reference_type']);
		$this->assertSame(3, $this->parts->calls[0]['reference_id']);
		$this->assertSame(7, $this->parts->calls[0]['employee_id']);

		$data = $this->db->updateCalls[0]['data'];
		$this->assertSame('cancelled', $data['status']);

		$history = json_decode($data['status_history'], TRUE);
		$last = end($history);
		$this->assertSame('cancelled', $last['status']);
		$this->assertStringContainsString('returned 1 part line', $last['note']);
	}

	public function testAssignTechnicianRecordsHistoryAndId() {
		$this->db->rows = [$this->jobRow(['status' => 'diagnosis', 'technician_id' => NULL])];
		$this->employees->employees = [
			5 => ['employee_id' => 5, 'firstname' => 'Ada', 'lastname' => 'Lovelace', 'role' => 'technician', 'is_active' => 1],
		];

		$this->model->assignTechnician(3, 5);

		$data = $this->db->updateCalls[0]['data'];
		$this->assertSame(5, $data['technician_id']);

		$history = json_decode($data['status_history'], TRUE);
		$last = end($history);
		$this->assertStringContainsString('Assigned to Ada Lovelace', $last['note']);
	}

	public function testAssignTechnicianAllowsUnassign() {
		$this->db->rows = [$this->jobRow(['status' => 'in_progress', 'technician_id' => 5])];

		$this->model->assignTechnician(3, NULL);

		$data = $this->db->updateCalls[0]['data'];
		$this->assertNull($data['technician_id']);

		$history = json_decode($data['status_history'], TRUE);
		$last = end($history);
		$this->assertStringContainsString('unassigned', strtolower($last['note']));
	}

	public function testAssignInactiveEmployeeThrowsD13() {
		$this->db->rows = [$this->jobRow(['status' => 'diagnosis', 'technician_id' => NULL])];
		$this->employees->employees = [
			5 => ['employee_id' => 5, 'firstname' => 'Ada', 'lastname' => 'Lovelace', 'role' => 'cashier', 'is_active' => 1],
		];

		try {
			$this->model->assignTechnician(3, 5);
			$this->fail('Expected AppException D13');
		} catch (AppException $e) {
			$this->assertSame('D13', $e->getErrorCode());
		}
	}

	public function testAssignOnClosedJobThrowsD12() {
		$this->db->rows = [$this->jobRow(['status' => 'completed', 'technician_id' => 5])];

		try {
			$this->model->assignTechnician(3, NULL);
			$this->fail('Expected AppException D12');
		} catch (AppException $e) {
			$this->assertSame('D12', $e->getErrorCode());
		}
	}

	public function testMarkPickedUpStampsCompletedAt() {
		$this->db->rows = [$this->jobRow(['status' => 'ready_for_pickup'])];

		$this->model->markPickedUp(3);

		$data = $this->db->updateCalls[0]['data'];
		$this->assertSame('completed', $data['status']);
		$this->assertNotNull($data['completed_at']);
		$this->assertNotNull($data['picked_up_at']);
	}

	public function testCompleteClosesJobFromAnyActiveState() {
		$this->db->rows = [$this->jobRow(['status' => 'in_progress'])];

		$this->model->complete(3, 'Booking marked finished');

		$data = $this->db->updateCalls[0]['data'];
		$this->assertSame('completed', $data['status']);
		$this->assertNotNull($data['completed_at']);
	}

	public function testCompleteLeavesTerminalJobUntouched() {
		$this->db->rows = [$this->jobRow(['status' => 'cancelled'])];

		$this->model->complete(3);

		$this->assertCount(0, $this->db->updateCalls);
	}

	public function testReopenReturnsCompletedJobToInProgress() {
		$this->db->rows = [$this->jobRow(['status' => 'completed'])];

		$this->model->reopen(3, 'Booking reopened');

		$data = $this->db->updateCalls[0]['data'];
		$this->assertSame('in_progress', $data['status']);
		$this->assertNull($data['completed_at']);
	}

	public function testReopenLeavesNonCompletedJobUntouched() {
		$this->db->rows = [$this->jobRow(['status' => 'in_progress'])];

		$this->model->reopen(3);

		$this->assertCount(0, $this->db->updateCalls);
	}

	// -------------------------------------------------------------------
	// Payments / deposits
	// -------------------------------------------------------------------

	public function testComputeTotalsSumsPartsAndLaborMinusPaid() {
		$this->db->rows = [['paid' => '100.00']];

		$totals = $this->model->computeTotals([
			'id' => 3,
			'parts_used' => [
				['part_id' => 1, 'qty' => 2, 'price' => 50.00],
				['part_id' => 2, 'qty' => 1, 'price' => 25.50],
			],
			'labor_items' => [
				['total' => 200.00],
			],
		]);

		$this->assertSame(125.50, $totals['parts']);
		$this->assertSame(200.00, $totals['labor']);
		$this->assertSame(325.50, $totals['recorded']);
		$this->assertSame(100.00, $totals['paid']);
		$this->assertSame(225.50, $totals['balance']);
	}

	public function testComputeTotalsDefaultsToZeroPaid() {
		$this->db->rows = [$this->jobRow()];

		$totals = $this->model->computeTotals($this->jobRow(['parts_used' => [], 'labor_items' => []]));

		$this->assertSame(0.0, $totals['recorded']);
		$this->assertSame(0.0, $totals['paid']);
		$this->assertSame(0.0, $totals['balance']);
	}

	public function testGetAmountPaidThrowsQ20OnQueryFailure() {
		$this->db->failQuery = TRUE;

		try {
			$this->model->getAmountPaid(3);
			$this->fail('Expected AppException Q20');
		} catch (AppException $e) {
			$this->assertSame('Q20', $e->getErrorCode());
		}
	}

	public function testRecordPaymentInsertsDepositAndAppendsHistory() {
		$this->db->rows = [$this->jobRow(['status' => 'approved'])];
		$this->db->countAllResults = 1; // payment method is active
		$this->db->insertId = 42;

		$id = $this->model->recordPayment(3, [
			'amount' => 200,
			'kind' => 'deposit',
			'method_id' => 2,
			'reference' => 'GC-123',
		], 7);

		$this->assertSame(42, $id);
		$this->assertSame('tbl_repair_payments', $this->db->lastInsertTable);
		$this->assertSame(200.00, $this->db->lastInsertData['amount']);
		$this->assertSame('deposit', $this->db->lastInsertData['kind']);
		$this->assertSame(2, $this->db->lastInsertData['method_id']);
		$this->assertSame('GC-123', $this->db->lastInsertData['reference']);
		$this->assertSame(7, $this->db->lastInsertData['recorded_by']);

		$data = $this->db->updateCalls[0]['data'];
		$history = json_decode($data['status_history'], TRUE);
		$last = end($history);
		$this->assertStringContainsString('Deposit of', $last['note']);
	}

	public function testRecordPaymentWithoutMethodStoresNull() {
		$this->db->rows = [$this->jobRow(['status' => 'in_progress'])];

		$this->model->recordPayment(3, ['amount' => 50, 'method_id' => 0], NULL);

		$this->assertNull($this->db->lastInsertData['method_id']);
		$this->assertNull($this->db->lastInsertData['recorded_by']);
		$this->assertSame('payment', $this->db->lastInsertData['kind']);
		$this->assertNull($this->db->lastInsertData['reference']);
	}

	public function testRecordPaymentRejectsZeroAmountD21() {
		$this->db->rows = [$this->jobRow(['status' => 'approved'])];

		try {
			$this->model->recordPayment(3, ['amount' => 0], 7);
			$this->fail('Expected AppException D21');
		} catch (AppException $e) {
			$this->assertSame('D21', $e->getErrorCode());
		}

		$this->assertNull($this->db->lastInsertTable);
	}

	public function testRecordPaymentRejectsInactiveMethodD23() {
		$this->db->rows = [$this->jobRow(['status' => 'approved'])];
		$this->db->countAllResults = 0; // no active method with that id

		try {
			$this->model->recordPayment(3, ['amount' => 100, 'method_id' => 99], 7);
			$this->fail('Expected AppException D23');
		} catch (AppException $e) {
			$this->assertSame('D23', $e->getErrorCode());
		}

		$this->assertNull($this->db->lastInsertTable);
	}

	public function testRecordPaymentOnClosedJobThrowsD12() {
		$this->db->rows = [$this->jobRow(['status' => 'completed'])];

		try {
			$this->model->recordPayment(3, ['amount' => 100], 7);
			$this->fail('Expected AppException D12');
		} catch (AppException $e) {
			$this->assertSame('D12', $e->getErrorCode());
		}
	}

	public function testRecordPaymentThrowsQ20WhenTransactionFails() {
		$this->db->rows = [$this->jobRow(['status' => 'approved'])];
		$this->db->transStatus = FALSE;

		try {
			$this->model->recordPayment(3, ['amount' => 100], 7);
			$this->fail('Expected AppException Q20');
		} catch (AppException $e) {
			$this->assertSame('Q20', $e->getErrorCode());
		}
	}
}
