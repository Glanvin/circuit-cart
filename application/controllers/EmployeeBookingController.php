<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EmployeeBookingController extends MY_Controller {

	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_employees';

	protected array $allowedRoles = ['admin', 'technician'];

	//Terminal states. No further action may be taken on these bookings.
	private const CLOSED = ['declined', 'finished'];

	//Open states that a repair may be marked as finished from.
	private const FINISHABLE = ['approved', 'rescheduled'];

	//Lists bookings, optionally filtered by the status query parameter.
	public function index()
	{
		$bookings = [];
		$status = strtolower((string) $this->input->get('status', true));
		$status = in_array($status, ['pending', 'approved', 'rescheduled', 'declined', 'finished']) ? $status : '';
		$q = trim((string) $this->input->get('q', true));
		$sort = strtolower((string) $this->input->get('sort', true));
		$sort = in_array($sort, ['newest', 'oldest'], true) ? $sort : 'newest';

		try {
			$bookings = $this->BookingModel->getFilteredBookings($status, $q, $sort);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/employees/bookings', [
			'title' => 'Bookings',
			'bookings' => $bookings,
			'status' => $status,
			'q' => $q,
			'sort' => $sort,
		]);
	}

	//Approves a booking and notifies the owner.
	public function approve(int $booking_id)
	{
		$this->setStatus($booking_id, 'approved', 'Appointment Approved - Circuit Cart', 'Good news! Your repair appointment has been approved.');
	}

	//Declines a booking and notifies the owner.
	public function decline(int $booking_id)
	{
		$this->setStatus($booking_id, 'declined', 'Appointment Declined - Circuit Cart', 'We are sorry, but your repair appointment request has been declined.');
	}

	/*
	 * Marks an ongoing repair as completed, locking the record, notifying the
	 * owner, and closing the booking ticket. Creates a repair job for tracking.
	 */
	public function finish(int $booking_id)
	{
		try {
			$booking = $this->lockedBooking($booking_id);

			if (!in_array($booking['status'], self::FINISHABLE)) {
				$this->toastify->error('Only approved or rescheduled bookings can be marked as finished.');
				redirect('storage/booking', 'refresh');
				return;
			}

			//Assign to current technician if they're a technician, otherwise leave unassigned
			$technician_id = in_array($this->session->userdata('employee_role'), ['technician', 'admin'])
				? (int) $this->session->userdata('employee_id')
				: NULL;

			//Create repair job from booking
			$job_id = $this->RepairJobModel->createFromBooking($booking_id, $technician_id);
			$job = $this->RepairJobModel->getJobById($job_id);

			//Update booking status
			$this->BookingModel->updateBooking($booking_id, ['status' => 'finished']);
			$booking['status'] = 'finished';

			send_booking_notification(
				$booking['username'],
				'Repair Job Created - Circuit Cart',
				'Your repair has been accepted. A repair job (' . $job['job_reference'] . ') has been created. Our technician will begin diagnosis shortly.',
				array_merge(booking_details($booking), [
					'Job Reference' => $job['job_reference'],
					'Status' => ucfirst($job['status']),
				])
			);

			$this->toastify->success('Booking finished. Repair job ' . $job['job_reference'] . ' created.');
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

	//Applies the given status to a booking and emails its owner.
	private function setStatus(int $booking_id, string $status, string $subject, string $message): void
	{
		try {
			$booking = $this->lockedBooking($booking_id);

			$this->BookingModel->updateBooking($booking_id, ['status' => $status]);
			$booking['status'] = $status;

			send_booking_notification($booking['username'], $subject, $message, booking_details($booking));

			$this->toastify->success('Booking marked as ' . $status . '.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('storage/booking', 'refresh');
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