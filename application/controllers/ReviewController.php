<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ReviewController extends MY_Controller {

	protected string $navbar = 'templates/navbar';

	//Creates or replaces the signed-in customer's review for a product. Reviews
	//are auto-published; order_verified is computed server-side by the model.
	public function create($part_id)
	{
		$part_id  = (int) $part_id;
		$username = (string) $this->session->userdata('username');

		try {
			$part = $this->PartsModel->getPartById($part_id);

			if ((int) ($part['is_active'] ?? 1) !== 1) {
				throw new AppException('P02', ['id' => $part_id]);
			}
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			redirect('browse', 'refresh');
			return;
		}

		$rules = $this->config->item('review_form');
		$this->form_validation->set_rules($rules);

		if ($this->form_validation->run() === FALSE) {
			$this->toastify->error('Please add a rating, a title and at least 10 characters of feedback.');
			redirect('browse/' . $part_id, 'refresh');
			return;
		}

		try {
			$this->ReviewModel->createOrUpdateReview([
				'part_id'    => $part_id,
				'username'   => $username,
				'user_email' => (string) $this->session->userdata('email'),
				'rating'     => (int) $this->input->post('rating', true),
				'title'      => (string) $this->input->post('title', true),
				'body'       => (string) $this->input->post('body', true),
			]);
			$this->toastify->success('Thanks! Your review has been posted.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('browse/' . $part_id, 'refresh');
	}
}
