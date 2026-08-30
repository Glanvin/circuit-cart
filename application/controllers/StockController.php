<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class StockController extends MY_Controller {

	protected string $navbar = 'templates/navbar_employees';

	protected ?string $requiredRole = 'admin';

	public function index()
	{
		try {
			$this->render('pages/inventory/stock_table', [
				'title' => 'Stocks',
				'parts' => $this->PartsModel->getAllParts(),
			]);
		} catch (Exception $e) {
			$this->toastify->error("{$e->getMessage()} [{$e->getCode()}]");
			$this->render('pages/inventory/stock_table', [
				'title' => 'Stocks',
				'parts' => []
			]);
		}
	}

	public function addStock() {

	}

	public function newSKU() {

	}
}
