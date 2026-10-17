<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Customer wishlist. A wishlist row is a (username, part_id) pair; the unique
 * index in the schema means a product can only be saved once. Ownership is by
 * username, matching tbl_booking and the signup rule's 20-character limit.
 */
class WishlistModel extends CI_Model {

	public function __construct() {
		parent::__construct();
	}

	//True when the customer already saved this product.
	public function isWishlisted(string $username, int $part_id): bool {
		if ($username === '' || $part_id <= 0) {
			return FALSE;
		}

		$this->db->where('username', $username);
		$this->db->where('part_id', $part_id);

		return (int) $this->db->count_all_results('tbl_wishlist') > 0;
	}

	//Saves a product to the wishlist. Returns TRUE when newly added, FALSE when
	//it was already saved (the unique pair makes this idempotent).
	public function add(string $username, int $part_id): bool {
		if ($username === '' || $part_id <= 0) {
			return FALSE;
		}

		if ($this->isWishlisted($username, $part_id)) {
			return FALSE;
		}

		if (!$this->db->insert('tbl_wishlist', [
			'username' => $username,
			'part_id'  => $part_id,
		])) {
			throw new AppException('Q17');
		}

		return TRUE;
	}

	//Removes a product from the wishlist. Returns TRUE when a row was removed.
	public function remove(string $username, int $part_id): bool {
		if ($username === '' || $part_id <= 0) {
			return FALSE;
		}

		$this->db->where('username', $username);
		$this->db->where('part_id', $part_id);

		if (!$this->db->delete('tbl_wishlist')) {
			throw new AppException('Q17');
		}

		return $this->db->affected_rows() > 0;
	}

	//Flips a product in or out of the wishlist and returns the NEW state
	//(TRUE = now wishlisted).
	public function toggle(string $username, int $part_id): bool {
		if ($username === '' || $part_id <= 0) {
			return FALSE;
		}

		if ($this->isWishlisted($username, $part_id)) {
			$this->remove($username, $part_id);
			return FALSE;
		}

		$this->add($username, $part_id);
		return TRUE;
	}

	//The wishlist joined to the catalog, newest first, for the wishlist page.
	public function getWishlistForUser(string $username): array {
		if ($username === '') {
			return [];
		}

		$this->db->select('tbl_wishlist.id AS wishlist_id, tbl_wishlist.created_at AS wishlisted_at, tbl_sku.*');
		$this->db->where('tbl_wishlist.username', $username);
		$this->db->join('tbl_sku', 'tbl_sku.part_id = tbl_wishlist.part_id');
		$this->db->order_by('tbl_wishlist.created_at', 'DESC');
		$query = $this->db->get('tbl_wishlist');

		if (!$query) {
			throw new AppException('Q17');
		}

		return $query->result_array();
	}

	//Just the saved part ids, so browse cards/detail can render filled hearts.
	public function getWishlistPartIds(string $username): array {
		if ($username === '') {
			return [];
		}

		$this->db->select('part_id');
		$this->db->where('username', $username);
		$query = $this->db->get('tbl_wishlist');

		if (!$query) {
			throw new AppException('Q17');
		}

		$ids = [];

		foreach ($query->result_array() as $row) {
			$ids[] = (int) $row['part_id'];
		}

		return $ids;
	}
}
