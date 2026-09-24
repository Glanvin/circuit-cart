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

					$this->BookingModel->createBooking(array_merge([
						'username' => $this->session->userdata('username'),
						'device' => $this->input->post('device', true),
						'issue' => $this->input->post('issue', true),
						'preferred_date' => $this->input->post('preferred_date', true),
						'preferred_time' => $this->input->post('preferred_time', true),
						'status' => 'pending',
						'images' => $uploads ? json_encode($uploads) : NULL,
					]));

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

		try {
			$bookings = $this->BookingModel->getBookingsByUsername($this->session->userdata('username'));
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/my_appointments', [
			'title' => 'My Appointments',
			'bookings' => $bookings,
		]);
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

	//Renders the booking form page.
	private function renderForm(): void
	{
		$this->render('pages/booking', ['title' => 'Book a Repair']);
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
}