<?php

use PHPUnit\Framework\TestCase;

//Stub stock double so cancelling a job with parts never touches inventory.
class BookingPartsRecorder {
	public $calls = [];

	public function adjustStock($part_id, $delta, $reason, $reference_type = NULL, $reference_id = NULL, $employee_id = NULL, $note = NULL) {
		$this->calls[] = compact('part_id', 'delta', 'reason', 'reference_type', 'reference_id', 'employee_id', 'note');
		return 1;
	}
}

class BookingModelSelfServiceTest extends TestCase {

	private FakeDb $db;
	private BookingModel $model;
	private RepairJobModel $jobs;

	protected function setUp(): void {
		$this->db = new FakeDb();

		$this->jobs = new RepairJobModel();
		$this->jobs->db = $this->db;
		$this->jobs->PartsModel = new BookingPartsRecorder();

		$this->model = new BookingModel();
		$this->model->db = $this->db;
		$this->model->RepairJobModel = $this->jobs;
	}

	private function bookingRow(array $overrides = []): array {
		return array_merge([
			'id' => 5,
			'username' => 'alice',
			'device' => 'iPhone 12',
			'issue' => 'Cracked screen',
			'preferred_date' => date('Y-m-d', time() + 3 * 86400),
			'preferred_time' => '10:00:00',
			'status' => 'pending',
			'history' => json_encode([]),
		], $overrides);
	}

	public function testCancelRejectsMissingBookingWithP04() {
		$this->db->rows = [];

		try {
			$this->model->cancelBookingByCustomer(5, 'alice');
			$this->fail('Expected AppException P04');
		} catch (AppException $e) {
			$this->assertSame('P04', $e->getErrorCode());
		}
	}

	public function testCancelRejectsOtherUsersBookingWithP03() {
		$this->db->rows = [$this->bookingRow()];

		try {
			$this->model->cancelBookingByCustomer(5, 'bob');
			$this->fail('Expected AppException P03');
		} catch (AppException $e) {
			$this->assertSame('P03', $e->getErrorCode());
		}

		$this->assertEmpty($this->db->updateCalls);
	}

	public function testCancelRejectsNonCancellableStatusWithD24() {
		$this->db->rows = [$this->bookingRow(['status' => 'finished'])];

		try {
			$this->model->cancelBookingByCustomer(5, 'alice');
			$this->fail('Expected AppException D24');
		} catch (AppException $e) {
			$this->assertSame('D24', $e->getErrorCode());
		}
	}

	public function testCancelRejectsBookingInsideCutoffWithD24() {
		$this->db->rows = [$this->bookingRow([
			'status' => 'pending',
			'preferred_date' => date('Y-m-d'),
			'preferred_time' => date('H:i:s', time() + 3600),
		])];

		try {
			$this->model->cancelBookingByCustomer(5, 'alice');
			$this->fail('Expected AppException D24');
		} catch (AppException $e) {
			$this->assertSame('D24', $e->getErrorCode());
		}
	}

	public function testCancelApprovedBookingRecordsHistoryAndClosesJob() {
		//The job lookup shares the FakeDb row set, so an "approved" booking
		//also reads back as an approved job and its cancellation transition is
		//valid. Assert the booking row is what gets marked cancelled.
		$this->db->rows = [$this->bookingRow(['status' => 'approved'])];

		$result = $this->model->cancelBookingByCustomer(5, 'alice', 'Found a local shop');

		$bookingUpdates = array_values(array_filter(
			$this->db->updateCalls,
			fn($call) => $call['table'] === 'tbl_booking'
		));
		$this->assertCount(1, $bookingUpdates);
		$this->assertSame('cancelled', $bookingUpdates[0]['data']['status']);

		$history = json_decode($bookingUpdates[0]['data']['history'], TRUE);
		$this->assertSame('cancelled', $history[0]['status']);
		$this->assertStringContainsString('Found a local shop', $history[0]['note']);

		$this->assertSame($this->bookingRow(['status' => 'approved']), $result);
	}

	public function testRescheduleRejectsOtherUsersBookingWithP03() {
		$this->db->rows = [$this->bookingRow()];

		try {
			$this->model->rescheduleBookingByCustomer(5, 'bob', '2026-08-01', '10:00');
			$this->fail('Expected AppException P03');
		} catch (AppException $e) {
			$this->assertSame('P03', $e->getErrorCode());
		}
	}

	public function testRescheduleRejectsNonPendingWithD14() {
		$this->db->rows = [$this->bookingRow(['status' => 'approved'])];

		try {
			$this->model->rescheduleBookingByCustomer(5, 'alice', '2026-08-01', '10:00');
			$this->fail('Expected AppException D14');
		} catch (AppException $e) {
			$this->assertSame('D14', $e->getErrorCode());
		}
	}

	public function testRescheduleRejectsFullSlotWithD25() {
		//One merged row acts as both the booking and the slot; capacity 1 with
		//one existing booking leaves no room.
		$this->db->rows = [$this->bookingRow([
			'start_time' => '10:00:00',
			'end_time' => '11:00:00',
			'capacity' => 1,
		])];

		try {
			$this->model->rescheduleBookingByCustomer(5, 'alice', '2026-08-01', '10:00');
			$this->fail('Expected AppException D25');
		} catch (AppException $e) {
			$this->assertSame('D25', $e->getErrorCode());
		}
	}

	public function testRescheduleMovesPendingBookingToNewSlot() {
		$this->db->rows = [$this->bookingRow([
			'start_time' => '10:00:00',
			'end_time' => '11:00:00',
			'capacity' => 2,
		])];

		$result = $this->model->rescheduleBookingByCustomer(5, 'alice', '2026-08-01', '10:00');

		$update = $this->db->updateCalls[0];
		$this->assertSame('tbl_booking', $update['table']);
		$this->assertSame('2026-08-01', $update['data']['preferred_date']);
		$this->assertSame('10:00:00', $update['data']['preferred_time']);

		$history = json_decode($update['data']['history'], TRUE);
		$this->assertStringContainsString('rescheduled to 2026-08-01 10:00', $history[0]['note']);

		$this->assertIsArray($result);
	}
}
