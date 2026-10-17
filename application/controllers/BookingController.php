<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BookingController extends MY_Controller {

	//Maximum number of device photos a booking may include.
	private const MAX_IMAGES = 3;

	/*
	 * Shows the booking form and processes a submitted request, saving optional
	 * device photos and emailing a confirmation when validation passes.
	 */
	public function index()
	{
		if ($this->input->post('booking')) {
			$this->form_validation->set_rules($this->config->item('booking_form'));

			if ($this->form_validation->run() === true) {
				try {
					$uploads = $this->handleUploads();

					if (is_string($uploads)) {
						$this->toastify->error($uploads);
						$this->renderForm();
						return;
					}

					$preferredDate = $this->input->post('preferred_date', true);
					$preferredTime = $this->input->post('preferred_time', true);

					//Check appointment slot availability
					$slot = $this->BookingModel->checkSlotAvailability($preferredDate, $preferredTime);
					if (!$slot) {
						$this->toastify->error('The selected time slot is not available or has reached capacity. Please choose another date/time.');
						$this->renderForm();
						return;
					}

					$this->BookingModel->createBooking([
						'username' => $this->session->userdata('username'),
						'service_id' => $this->resolveServiceId(),
						'device' => $this->input->post('device', true),
						'issue' => $this->input->post('issue', true),
						'preferred_date' => $preferredDate,
						'preferred_time' => $slot['start_time'],  //Normalize to slot start time
						'status' => 'pending',
						'images' => $uploads ? json_encode($uploads) : NULL,
					]);

					$this->toastify->success('Your repair booking has been submitted. You will receive an email once it is reviewed.');
					$this->notifyPending();

					redirect('booking/appointments', 'refresh');
					return;
				} catch (Exception $e) {
					$this->toastify->error('We are currently experiencing technical difficulties.');
				}
			} else {
				$this->toastify->error('Please correct the highlighted fields.');
			}
		}

		$this->renderForm();
	}

	//Lists the signed-in user's own repair appointments.
	public function appointments()
	{
		$bookings = [];
		$jobs = [];

		try {
			$bookings = $this->BookingModel->getBookingsByUsername($this->session->userdata('username'));

			$ids = array_map(static fn($b) => (int) $b['id'], $bookings);
			$jobs = $this->RepairJobModel->getJobsByBookingIds($ids);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/my_appointments', [
			'title' => 'My Appointments',
			'bookings' => $bookings,
			'jobs' => $jobs,
		]);
	}

	//Cancels the signed-in user's own booking and notifies them.
	public function cancel(int $booking_id)
	{
		try {
			$booking = $this->BookingModel->cancelBookingByCustomer(
				$booking_id,
				$this->session->userdata('username'),
				(string) $this->input->post('reason', true)
			);

			send_booking_notification(
				$booking['username'],
				'Appointment Cancelled - Circuit Cart',
				'Your repair appointment has been cancelled. If this was a mistake, you can book a new appointment any time.',
				booking_details($booking)
			);

			$this->toastify->success('Your appointment has been cancelled.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('booking/appointments', 'refresh');
	}

	//Reschedules the signed-in user's own pending booking to a new slot.
	public function reschedule(int $booking_id)
	{
		$date = (string) $this->input->post('preferred_date', true);
		$time = (string) $this->input->post('preferred_time', true);

		//Browsers submit <input type="time"> as HH:MM; seconds are rejected.
		$dateOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
			&& (bool) DateTimeImmutable::createFromFormat('Y-m-d', $date);
		$timeOk = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;

		if (!$dateOk || !$timeOk) {
			$this->toastify->error('Please provide a valid new date and time.');
			redirect('booking/appointments', 'refresh');
			return;
		}

		try {
			$booking = $this->BookingModel->rescheduleBookingByCustomer(
				$booking_id,
				$this->session->userdata('username'),
				$date,
				substr($time, 0, 5)
			);

			send_booking_notification(
				$booking['username'],
				'Appointment Rescheduled - Circuit Cart',
				'Your repair appointment request has been moved to a new date and time. Please take note of the updated schedule.',
				booking_details($booking)
			);

			$this->toastify->success('Your appointment has been rescheduled.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('booking/appointments', 'refresh');
	}

	/*
	 * Uploads up to MAX_IMAGES optional device photos (5 MB max each) into the
	 * upload folder. Returns saved filenames on success or an error string.
	 */
	private function handleUploads()
	{
		$files = $_FILES['images'] ?? NULL;

		if (empty($files) || !is_array($files['name'])) {
			return [];
		}

		$names = array_values(array_filter($files['name'], static function ($name) {
			return $name !== '';
		}));

		if (count($names) === 0) {
			return [];
		}

		if (count($names) > self::MAX_IMAGES) {
			return 'You can upload up to ' . self::MAX_IMAGES . ' photos.';
		}

		$this->config->load('upload');

		$config = $this->config->item('booking', 'upload');
		$config['max_size'] = (int) $config['max_size'];
		$config['is_image'] = (bool) $config['is_image'];
		$config['encrypt_name'] = (bool) $config['encrypt_name'];
		$config['overwrite'] = (bool) $config['overwrite'];

		if (!is_dir($config['upload_path'])) {
			mkdir($config['upload_path'], 0777, true);
		}

		//Guarantee write access for the web server user even if folder
		//permissions were reset after creation.
		chmod($config['upload_path'], 0777);

		$this->load->library('upload', $config);

		$saved = [];
		$total = count($files['name']);

		for ($i = 0; $i < $total; $i++) {
			if (empty($files['name'][$i])) {
				continue;
			}

			$_FILES['images'] = [
				'name' => $files['name'][$i],
				'type' => $files['type'][$i],
				'tmp_name' => $files['tmp_name'][$i],
				'error' => $files['error'][$i],
				'size' => $files['size'][$i],
			];

			if (!$this->upload->do_upload('images')) {
				$this->cleanupUploads($saved);
				return strip_tags($this->upload->display_errors());
			}

			$saved[] = $this->upload->data('file_name');

			if (count($saved) >= self::MAX_IMAGES) {
				break;
			}
		}

		return $saved;
	}

	//Removes files already saved when a later upload in the batch fails.
	private function cleanupUploads(array $saved): void
	{
		$path = FCPATH . 'storage/booking/';

		foreach ($saved as $file) {
			$target = $path . $file;
			if (is_file($target)) {
				unlink($target);
			}
		}
	}

	/*
	 * Returns a validated active service id from POST, or NULL when none was
	 * chosen. A forged or deactivated id is ignored rather than trusted, so a
	 * client can never attach an arbitrary service row to a booking.
	 */
	private function resolveServiceId(): ?int
	{
		$service_id = (int) $this->input->post('service_id', true);

		if ($service_id <= 0) {
			return NULL;
		}

		try {
			$service = $this->RepairServiceModel->getServiceById($service_id);
		} catch (AppException $e) {
			return NULL;
		}

		return (int) $service['is_active'] === 1 ? $service_id : NULL;
	}

	//Renders the booking form page.
	private function renderForm(): void
	{
		$services = [];

		//The service catalog is optional; a missing table must not block a
		//booking, so a failure simply leaves the select empty.
		try {
			$services = $this->RepairServiceModel->getActiveServices();
		} catch (AppException $e) {
			$services = [];
		}

		$this->render('pages/booking', [
			'title' => 'Book a Repair',
			'services' => $services,
		]);
	}

	//Emails the "booking received" confirmation to the owner.
	private function notifyPending(): void
	{
		send_booking_notification(
			$this->session->userdata('username'),
			'Booking Received - Circuit Cart',
			'We received your repair booking request. Our team will review it and notify you once your appointment is approved or rescheduled.',
			booking_details([
				'device' => $this->input->post('device', true),
				'issue' => $this->input->post('issue', true),
				'preferred_date' => $this->input->post('preferred_date', true),
				'preferred_time' => $this->input->post('preferred_time', true),
				'status' => 'pending',
			])
		);
	}

	//AJAX endpoint: returns available appointment slots for a given date.
	public function getSlots($date)
	{
		$this->output->set_content_type('application/json');

		//Validate date format
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !DateTimeImmutable::createFromFormat('Y-m-d', $date)) {
			$this->output->set_output(json_encode(['ok' => false, 'error' => 'Invalid date.']));
			return;
		}

		try {
			$slots = $this->BookingModel->getAvailableSlotsForDate($date);
			$this->output->set_output(json_encode(['ok' => true, 'slots' => $slots]));
		} catch (AppException $e) {
			$this->output->set_output(json_encode(['ok' => false, 'error' => $e->toToast()]));
		}
	}
}