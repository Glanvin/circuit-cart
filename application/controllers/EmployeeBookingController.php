<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EmployeeBookingController extends MY_Controller {

	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_employees';

	protected array $allowedRoles = ['admin', 'manager', 'technician'];

	//Terminal states. No further action may be taken on these bookings.
	private const CLOSED = ['declined', 'cancelled'];

	//Only a fresh request may be approved, declined or rescheduled.
	private const REVIEWABLE = ['pending'];

	//Lists bookings, optionally filtered by the status query parameter.
	public function index()
	{
		$bookings = [];
		$status = strtolower((string) $this->input->get('status', true));
		$status = in_array($status, ['pending', 'approved', 'rescheduled', 'picked', 'declined', 'cancelled', 'finished']) ? $status : '';
		$q = trim((string) $this->input->get('q', true));
		$sort = strtolower((string) $this->input->get('sort', true));
		$sort = in_array($sort, ['newest', 'oldest'], true) ? $sort : 'newest';

		$jobs = [];

		try {
			$bookings = $this->BookingModel->getFilteredBookings($status, $q, $sort);

			$ids = array_map(static fn($b) => (int) $b['id'], $bookings);
			$jobs = $this->RepairJobModel->getJobsByBookingIds($ids);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/employees/bookings', [
			'title' => 'Bookings',
			'bookings' => $bookings,
			'jobs' => $jobs,
			'status' => $status,
			'q' => $q,
			'sort' => $sort,
		]);
	}

	//Approves a fresh booking and notifies the owner.
	public function approve(int $booking_id)
	{
		try {
			$booking = $this->requireStatus($booking_id, self::REVIEWABLE);

			//Approval opens the repair job straight away. A technician then picks
			//the job up, and the booking is finished once the repair is done.
			$job = $this->openRepairJob($booking_id);

			$this->BookingModel->updateBooking($booking_id, ['status' => 'approved']);
			$booking['status'] = 'approved';

			send_booking_notification(
				$booking['username'],
				'Appointment Approved - Circuit Cart',
				'Good news! Your repair appointment has been approved. A repair job (' . $job['job_reference'] . ') has been opened and a technician will pick it up shortly.',
				array_merge(booking_details($booking), ['Job Reference' => $job['job_reference']])
			);

			$this->toastify->success('Booking approved. Repair job ' . $job['job_reference'] . ' opened.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('storage/booking', 'refresh');
	}

	//Declines a fresh booking and notifies the owner.
	public function decline(int $booking_id)
	{
		$this->transition($booking_id, self::REVIEWABLE, 'declined', 'Appointment Declined - Circuit Cart', 'We are sorry, but your repair appointment request has been declined.');
	}

	/*
	 * A technician claims an approved booking and its repair job, moving the
	 * booking to 'picked' so it can be worked on and then finished.
	 */
	public function pick(int $booking_id)
	{
		try {
			$booking = $this->requireStatus($booking_id, ['approved', 'rescheduled']);

			$employee_id = (int) $this->session->userdata('employee_id');

			//Legacy approved/rescheduled bookings may predate job-on-approval,
			//so open the job here if it is missing before assigning it.
			$job = $this->openRepairJob($booking_id);
			$this->RepairJobModel->assignTechnician($job['id'], $employee_id);

			$this->BookingModel->updateBooking($booking_id, ['status' => 'picked']);
			$this->toastify->success('Booking picked up. It is now assigned to you.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('storage/booking', 'refresh');
	}

	/*
	 * Marks a picked-up booking finished once the repair is done, closing its
	 * linked repair job. The booking can be reopened later with unfinish().
	 */
	public function finish(int $booking_id)
	{
		try {
			$booking = $this->requireStatus($booking_id, ['picked']);

			$job = $this->RepairJobModel->getJobByBookingId($booking_id);
			if ($job) {
				$job = $this->RepairJobModel->complete($job['id'], 'Booking marked finished');
			}

			$this->BookingModel->updateBooking($booking_id, ['status' => 'finished']);
			$booking['status'] = 'finished';

			send_booking_notification(
				$booking['username'],
				'Repair Completed - Circuit Cart',
				'Your repair is complete. Thank you for choosing Circuit Cart!',
				booking_details($booking)
			);

			$this->toastify->success('Booking marked as finished.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('storage/booking', 'refresh');
	}

	/*
	 * Reopens a finished booking back to picked and reopens its repair job, so
	 * the assigned technician can continue.
	 */
	public function unfinish(int $booking_id)
	{
		try {
			$booking = $this->requireStatus($booking_id, ['finished']);

			$job = $this->RepairJobModel->getJobByBookingId($booking_id);
			if ($job) {
				$this->RepairJobModel->reopen($job['id'], 'Booking reopened');
			}

			$this->BookingModel->updateBooking($booking_id, ['status' => 'picked']);
			$this->toastify->success('Booking reopened. It is picked up again.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('storage/booking', 'refresh');
	}

	/*
	 * Moves a booking to a new preferred date/time when the requested slot is
	 * not available, and notifies the customer of the change.
	 */
	public function reschedule(int $booking_id)
	{
		$date = (string) $this->input->post('preferred_date', true);
		$time = (string) $this->input->post('preferred_time', true);

		//Browsers submit <input type="time"> as HH:MM. Only hour and minute are
		//kept; seconds are rejected everywhere.
		$dateOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
			&& (bool) DateTimeImmutable::createFromFormat('Y-m-d', $date);
		$timeOk = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;

		if (!$dateOk || !$timeOk) {
			$this->toastify->error('Please provide a valid new date and time.');
			redirect('storage/booking', 'refresh');
			return;
		}

		//Normalize to HH:MM so records and emails stay consistent.
		$time = substr($time, 0, 5);

		try {
			$booking = $this->lockedBooking($booking_id);

			if (!in_array($booking['status'], self::REVIEWABLE, TRUE)) {
				$this->toastify->error('Only a pending booking can be rescheduled.');
				redirect('storage/booking', 'refresh');
				return;
			}

			//Check slot availability for the new date/time (excluding this booking)
			$slot = $this->BookingModel->checkSlotAvailabilityExcluding($date, $time, $booking_id);
			if (!$slot) {
				$this->toastify->error('The selected time slot is not available or has reached capacity.');
				redirect('storage/booking', 'refresh');
				return;
			}

			$this->BookingModel->updateBooking($booking_id, [
				'preferred_date' => $date,
				'preferred_time' => $slot['start_time'],  //Normalize to slot start time
				'status' => 'rescheduled',
			]);

			$booking['preferred_date'] = $date;
			$booking['preferred_time'] = $slot['start_time'];
			$booking['status'] = 'rescheduled';

			//Rescheduling also accepts the booking, so open (or keep) its job.
			$this->openRepairJob($booking_id);

			send_booking_notification(
				$booking['username'],
				'Appointment Rescheduled - Circuit Cart',
				'Your repair appointment has been rescheduled. Please take note of your new preferred date and time.',
				booking_details($booking)
			);

			$this->toastify->success('Booking rescheduled.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('storage/booking', 'refresh');
	}

	/*
	 * Applies a status change only when the booking is currently in one of the
	 * allowed $from states, then emails the owner. Prevents re-approving or
	 * re-declining a booking that has already moved on.
	 */
	private function transition(int $booking_id, array $from, string $status, string $subject, string $message): void
	{
		try {
			$booking = $this->lockedBooking($booking_id);

			if (!in_array($booking['status'], $from, TRUE)) {
				$this->toastify->error('This booking is already ' . $booking['status'] . ' and cannot be changed.');
				redirect('storage/booking', 'refresh');
				return;
			}

			$this->BookingModel->updateBooking($booking_id, ['status' => $status]);
			$booking['status'] = $status;

			send_booking_notification($booking['username'], $subject, $message, booking_details($booking));

			$this->toastify->success('Booking marked as ' . $status . '.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('storage/booking', 'refresh');
	}

	//Returns the repair job linked to a booking, creating it in diagnosis on
	//first approval. Falls back to NULL if the job cannot be read.
	private function openRepairJob(int $booking_id): ?array
	{
		$job = $this->RepairJobModel->getJobByBookingId($booking_id);

		if ($job) {
			return $job;
		}

		$job_id = $this->RepairJobModel->createFromBooking($booking_id, NULL);
		return $this->RepairJobModel->getJobById($job_id);
	}

	//Returns the booking when its status is one of $allowed, otherwise throws.
	//Declined bookings are rejected earlier by lockedBooking().
	private function requireStatus(int $booking_id, array $allowed): array
	{
		$booking = $this->lockedBooking($booking_id);

		if (!in_array($booking['status'], $allowed, TRUE)) {
			throw new AppException('D14');
		}

		return $booking;
	}

	//Returns the booking, aborting with an error when it does not exist or is
	//already in a terminal (locked) state.
	private function lockedBooking(int $booking_id): array
	{
		$booking = $this->BookingModel->getBookingById($booking_id);

		if (in_array($booking['status'], self::CLOSED)) {
			throw new AppException('P05');
		}

		return $booking;
	}
}