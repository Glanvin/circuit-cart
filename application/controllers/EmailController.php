<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EmailController extends MY_Controller {

	// Restrict access to employee portal sessions
	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_employees';
	

	// Only users with 'manager' or 'admin' roles can access this controller
	protected array $allowedRoles = ['manager', 'admin'];

	/*
	 * Shows the email form and processes a submitted request, saving the optional
	 * attachment to a temp folder, mailing it, and cleaning up afterward.
	 */
	public function index()
	{
		if ($this->input->post('send_email')) {
			$this->form_validation->set_rules('to_email', 'Recipient Email', 'required|valid_email');
			$this->form_validation->set_rules('subject', 'Subject', 'required');
			$this->form_validation->set_rules('message', 'Message', 'required');

			if ($this->form_validation->run() === true) {
				try {
					$upload = $this->handleUpload();

					if (is_string($upload)) {
						$this->toastify->error($upload);
						$this->renderForm();
						return;
					}

					$this->sendEmailAndCleanup(
						$this->input->post('to_email', true),
						$this->input->post('subject', true),
						$this->input->post('message', true),
						$upload ? $upload['full_path'] : NULL
					);

					$this->toastify->success('Your email has been sent successfully.');
					redirect('email', 'refresh');
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

	/*
	 * Uploads a single file to the storage/tmp folder. Returns file data array on 
	 * success, NULL if empty, or an error string on failure.
	 */
	private function handleUpload()
	{
		if (empty($_FILES['attachment']['name'])) {
			return NULL;
		}

		$upload_path = FCPATH . 'storage/tmp/';

		if (!is_dir($upload_path)) {
			mkdir($upload_path, 0777, true);
		}

		// Guarantee write access for the web server user
		chmod($upload_path, 0777);

		$config['upload_path'] = $upload_path;
		$config['allowed_types'] = '*'; // Add specific types like 'pdf|jpg|png' if needed
		$config['max_size'] = 5120; // 5 MB
		$config['encrypt_name'] = true;

		$this->load->library('upload', $config);

		if (!$this->upload->do_upload('attachment')) {
			return strip_tags($this->upload->display_errors());
		}

		return $this->upload->data();
	}

	/*
	 * Sends the email with the attachment and unlinks the file from storage/tmp
	 */
	private function sendEmailAndCleanup($to, $subject, $message, $file_path): void
	{
		$this->load->library('email');

		$this->email->from('2092437@g.cu.edu.ph', 'Circuit Cart');
		$this->email->to($to);
		$this->email->subject($subject);
		$this->email->message($message);

		if ($file_path !== NULL && is_file($file_path)) {
			$this->email->attach($file_path);
		}

		$this->email->send();

		// Cleanup: Remove the file from storage/tmp after sending
		if ($file_path !== NULL && is_file($file_path)) {
			unlink($file_path);
		}
	}

	// Renders the email form page using MY_Controller's render method
	private function renderForm(): void
	{
		$this->render('pages/email_form', ['title' => 'Send Email']);
	}
}