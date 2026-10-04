<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BrowseController extends MY_Controller {

	protected bool $requireLogin = FALSE;

	//Renders the parts browse page, falling back to an empty list on failure.
	public function index()
	{
		try {
			$this->render('pages/browse', [
				'title' => 'Browse',
				'parts' => $this->PartsModel->getAllParts(),
			]);
		} catch (Exception $e) {
			$this->toastify->error('We are currently experiencing technical difficulties.');
			$this->render('pages/browse', [
				'title' => 'Browse',
				'parts' => [],
			]);
		}
	}
}
