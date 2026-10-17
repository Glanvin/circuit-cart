<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class WishlistController extends MY_Controller {

	protected string $navbar = 'templates/navbar';

	//Lists the signed-in customer's saved products.
	public function index()
	{
		$items = [];

		try {
			$items = $this->WishlistModel->getWishlistForUser($this->session->userdata('username'));
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->render('pages/wishlist', [
			'title' => 'My Wishlist',
			'items' => $items,
		]);
	}

	//Flips a product in or out of the wishlist from a card/detail heart button,
	//then returns to the page the shopper came from.
	public function toggle($part_id)
	{
		$part_id  = (int) $part_id;
		$username = (string) $this->session->userdata('username');

		try {
			//Reject unknown or deactivated products before touching the wishlist.
			$part = $this->PartsModel->getPartById($part_id);

			if ((int) ($part['is_active'] ?? 1) !== 1) {
				throw new AppException('P02', ['id' => $part_id]);
			}

			$saved = $this->WishlistModel->toggle($username, $part_id);
			$this->toastify->success($saved ? 'Saved to your wishlist.' : 'Removed from your wishlist.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$this->redirectBack();
	}

	//Removes a product from the wishlist and stays on the wishlist page.
	public function remove($part_id)
	{
		try {
			$this->WishlistModel->remove((string) $this->session->userdata('username'), (int) $part_id);
			$this->toastify->success('Removed from your wishlist.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('wishlist', 'refresh');
	}

	//Hearts are posted from browse/detail, so return there when the referer is
	//one of our own pages; otherwise fall back to the catalog.
	private function redirectBack()
	{
		$referer = (string) $this->input->server('HTTP_REFERER');

		if ($referer !== '' && strpos($referer, base_url()) === 0) {
			redirect($referer, 'refresh');
			return;
		}

		redirect('browse', 'refresh');
	}
}
