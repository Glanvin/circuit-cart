<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BugController extends MY_Controller {

	//Public page: guests may file reports too.
	protected bool $requireLogin = FALSE;

	//Maximum number of screenshots a bug report may include.
	private const MAX_IMAGES = 3;

	//Maximum submissions allowed and the rolling time window they apply to.
	private const MAX_SUBMISSIONS = 5;
	private const WINDOW_HOURS = 5;

	/*
	 * Shows the bug report form and handles submissions, enforcing the report
	 * rate limit and attaching optional screenshots before saving.
	 */
	public function index()
	{
		if ($this->input->post('report_bug')) {
			$this->form_validation->set_rules($this->config->item('bug_report'));

			if ($this->form_validation->run() === true) {
				try {
					$username = (string) $this->session->userdata('username');
					$email = trim((string) $this->input->post('email', true));

					if ($email === '' && $username !== '') {
						$email = trim((string) $this->session->userdata('email'));
					}

					if (!$this->rateLimitAllows($username, $email)) {
						$this->toastify->error('Report limit reached, try again at later time.');
						$this->renderForm();
						return;
					}

					$uploads = $this->handleUploads();

					if (is_string($uploads)) {
						$this->toastify->error($uploads);
						$this->renderForm();
						return;
					}

					$this->BugReportModel->createBugReport([
						'username' => $username !== '' ? $username : NULL,
						'email' => $email !== '' ? $email : NULL,
						'title' => $this->input->post('title', true),
						'description' => $this->input->post('description', true),
						'images' => $uploads ? json_encode($uploads) : NULL,
						'status' => 'new',
					]);

					$this->bumpAnonymousCount();

					$this->toastify->success('Thank you! Your bug report has been submitted.');
					redirect('bugs', 'refresh');
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
	 * Returns TRUE when the submitter may still file a report, identified by
	 * username, email, or a server-side anonymous counter. No IP is captured.
	 */
	private function rateLimitAllows(string $username, string $email): bool
	{
		$identifiers = [];
		if ($username !== '') {
			$identifiers[] = $username;
		}
		if ($email !== '') {
			$identifiers[] = $email;
		}

		if ($identifiers !== []) {
			return $this->BugReportModel->countRecentBySubmitter($identifiers, self::WINDOW_HOURS) < self::MAX_SUBMISSIONS;
		}

		$counts = $this->session->userdata('bug_report_counts');
		$counts = is_array($counts) ? $counts : [];
		$cutoff = time() - (self::WINDOW_HOURS * 3600);

		$counts = array_values(array_filter($counts, static function ($ts) use ($cutoff) {
			return is_numeric($ts) && (int) $ts > $cutoff;
		}));

		$this->session->set_userdata('bug_report_counts', $counts);

		return count($counts) < self::MAX_SUBMISSIONS;
	}

	//Records the submission for anonymous visitors so the rolling window stays
	//accurate. Only called after a successful insert.
	private function bumpAnonymousCount(): void
	{
		if (!empty($this->session->userdata('username')) || !empty($this->session->userdata('email'))) {
			return;
		}

		$counts = $this->session->userdata('bug_report_counts');
		$counts = is_array($counts) ? $counts : [];
		$counts[] = time();

		$this->session->set_userdata('bug_report_counts', $counts);
	}

	/*
	 * Uploads up to MAX_IMAGES optional screenshots (3 MB max each) into the
	 * bugs upload folder. Only signed-in users may attach files.
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

		if (empty($this->session->userdata('username'))) {
			return 'Sign in to attach screenshots.';
		}

		if (count($names) > self::MAX_IMAGES) {
			return 'You can upload up to ' . self::MAX_IMAGES . ' images.';
		}

		$this->config->load('upload');

		$config = $this->config->item('bugs', 'upload');
		$config['max_size'] = (int) $config['max_size'];
		$config['is_image'] = (bool) $config['is_image'];
		$config['encrypt_name'] = (bool) $config['encrypt_name'];
		$config['overwrite'] = (bool) $config['overwrite'];

		if (!is_dir($config['upload_path'])) {
			mkdir($config['upload_path'], 0777, true);
		}

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
		$path = FCPATH . 'storage/bugs/';

		foreach ($saved as $file) {
			$target = $path . $file;
			if (is_file($target)) {
				unlink($target);
			}
		}
	}

	//Renders the bug report form page.
	private function renderForm(): void
	{
		$this->render('pages/bugs/report', [
			'title' => 'Report a Bug',
			'logged_in' => !empty($this->session->userdata('username')),
			'user_email' => (string) $this->session->userdata('email'),
		]);
	}
}