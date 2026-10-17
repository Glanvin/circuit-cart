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
			$sess = $this->session->userdata('email');
			if (empty($sess) || strtolower($order['customer_email']) !== strtolower($sess)) {
				throw new AppException('P03');
			}
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

	//Lets a customer cancel their own online order before it ships. Restocks
	//the order through the model (transaction + stock movements).
	public function cancel($id)
	{
		try {
			$order = $this->SalesModel->getSaleById((int) $id);

			//Verify ownership
			$sess = $this->session->userdata('email');
			if (empty($sess) || strtolower($order['customer_email']) !== strtolower($sess)) {
				throw new AppException('P03');
			}

			$this->SalesModel->cancelOrder((int) $id, NULL, 'Cancelled by customer', ['processing']);
			$this->toastify->success('Order ' . $order['receipt_no'] . ' has been cancelled.');

			$order['status'] = 'cancelled';
			send_order_status_email($order, 'cancelled');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('orders', 'refresh');
	}
}