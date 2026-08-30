<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BrowseController extends MY_Controller {

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
