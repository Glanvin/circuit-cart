<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Public, token-gated repair portal. Customers reach these pages from links in
 * their email, so no login is required: the unguessable token is the credential.
 * The status page is read-only. Estimate approval/decline happens through POST
 * forms on the review page (protected by the app's CSRF token).
 */
class RepairStatusController extends MY_Controller {

	protected bool $requireLogin = FALSE;

	protected string $navbar = 'templates/navbar';

	//Shows the read-only status timeline for a repair job.
	public function index(string $token)
	{
		try {
			$job = $this->RepairJobModel->getJobByPublicToken($token);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			redirect('home', 'refresh');
			return;
		}

		$this->render('pages/repair_status', [
			'title' => 'Repair Status',
			'job' => $job,
		]);
	}

	//Shows the estimate with approve/decline actions while the token is valid.
	public function estimate(string $token)
	{
		try {
			$job = $this->RepairJobModel->getJobByEstimateToken($token);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			redirect('home', 'refresh');
			return;
		}

		$this->render('pages/repair_estimate', [
			'title' => 'Repair Estimate',
			'job' => $job,
			'token' => $token,
		]);
	}

	//Consumes the estimate token and marks the estimate approved.
	public function approve(string $token)
	{
		if ($this->input->method(TRUE) !== 'POST') {
			redirect('repair/estimate/' . $token, 'refresh');
			return;
		}

		try {
			$job = $this->RepairJobModel->customerApproveEstimate($token);
			$this->toastify->success('Thank you. Your estimate has been approved and we will begin the repair.');
			redirect('repair/status/' . $job['public_token'], 'refresh');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			redirect('home', 'refresh');
		}
	}

	//Records the customer's decline (with optional reason) and consumes the token.
	public function decline(string $token)
	{
		if ($this->input->method(TRUE) !== 'POST') {
			redirect('repair/estimate/' . $token, 'refresh');
			return;
		}

		$reason = (string) $this->input->post('reason', true);

		try {
			$job = $this->RepairJobModel->customerDeclineEstimate($token, $reason);
			$this->toastify->success('Your decision has been recorded. Please contact us if you would like to discuss the estimate.');
			redirect('repair/status/' . $job['public_token'], 'refresh');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			redirect('home', 'refresh');
		}
	}
}
