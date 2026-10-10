<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class OrdersController extends MY_Controller {

	protected string $navbar = 'templates/navbar';

	//Lists the signed-in customer's online orders.
	public function index()
	{
		$orders = [];

		try {
			$orders = $this->SalesModel->getCustomerOrders($this->session->userdata('email'));
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/orders', [
			'title' => 'My Orders',
			'orders' => $orders,
		]);
	}

	//Shows details for a single order.
	public function view($id)
	{
		try {
			$order = $this->SalesModel->getSaleById((int) $id);

			//Verify ownership
			if ($order['customer_email'] !== $this->session->userdata('email')) {
				throw new AppException('P03');
			}

			$order['items'] = json_decode($order['items'] ?? '[]', true);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
			redirect('orders', 'refresh');
			return;
		}

		$this->render('pages/order_detail', [
			'title' => 'Order #' . $order['receipt_no'],
			'order' => $order,
		]);
	}
}