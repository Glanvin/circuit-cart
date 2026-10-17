<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Product reviews. Reviews are auto-published on submit (status only ever
 * moves to 'hidden' through staff moderation, never through the storefront).
 * order_verified is computed server-side from the reviewer's delivered online
 * orders so a "verified purchase" badge can never be forged by the client.
 */
class ReviewModel extends CI_Model {

	public function __construct() {
		parent::__construct();
	}

	//True when the customer has a DELIVERED online order containing the part.
	public function hasDeliveredOrderWithPart(string $email, int $part_id): bool {
		if ($email === '' || $part_id <= 0) {
			return FALSE;
		}

		$this->db->where('origin', 'online');
		$this->db->where('status', 'delivered');
		$this->db->where('customer_email', strtolower($email));
		$query = $this->db->get('tbl_sales');

		if (!$query) {
			throw new AppException('Q09');
		}

		foreach ($query->result_array() as $order) {
			$lines = json_decode($order['items'] ?? '[]', TRUE) ?: [];

			foreach ($lines as $line) {
				if ((int) ($line['part_id'] ?? 0) === $part_id) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	//Creates or updates the customer's review for a part and returns the review
	//id. $data: part_id, username, user_email (for the verified flag), rating,
	//title, body. Re-submitting replaces the earlier review for the same part.
	public function createOrUpdateReview(array $data) {
		$part_id   = (int) ($data['part_id'] ?? 0);
		$username  = (string) ($data['username'] ?? '');
		$rating    = (int) ($data['rating'] ?? 0);
		$title     = trim((string) ($data['title'] ?? ''));
		$body      = trim((string) ($data['body'] ?? ''));
		$email     = (string) ($data['user_email'] ?? '');

		if ($part_id <= 0 || $username === '' || $rating < 1 || $rating > 5) {
			throw new AppException('D19');
		}

		$verified = $this->hasDeliveredOrderWithPart($email, $part_id) ? 1 : 0;

		//Replace an earlier review for the same product rather than stacking.
		$this->db->where('username', $username);
		$this->db->where('part_id', $part_id);
		$existing = $this->db->get('tbl_product_reviews');

		if (!$existing) {
			throw new AppException('Q18');
		}

		$row = $existing->row_array();

		if (!empty($row)) {
			$payload = [
				'rating'        => $rating,
				'title'         => $title,
				'body'          => $body,
				'order_verified' => $verified,
				'updated_at'    => date('Y-m-d H:i:s'),
			];

			$this->db->where('id', (int) $row['id']);

			if (!$this->db->update('tbl_product_reviews', $payload)) {
				throw new AppException('Q18');
			}

			return (int) $row['id'];
		}

		if (!$this->db->insert('tbl_product_reviews', [
			'part_id'        => $part_id,
			'username'       => $username,
			'rating'         => $rating,
			'title'          => $title,
			'body'           => $body,
			'status'         => 'published',
			'order_verified' => $verified,
		])) {
			throw new AppException('Q18');
		}

		return (int) $this->db->insert_id();
	}

	//Published reviews for a product, newest first.
	public function getReviewsForPart(int $part_id, string $status = 'published'): array {
		$this->db->where('part_id', $part_id);

		if ($status !== '') {
			$this->db->where('status', $status);
		}

		$this->db->order_by('created_at', 'DESC');
		$this->db->order_by('id', 'DESC');
		$query = $this->db->get('tbl_product_reviews');

		if (!$query) {
			throw new AppException('Q18');
		}

		return $query->result_array();
	}

	//Average rating and published review count for a product.
	public function getAverageRating(int $part_id): array {
		$this->db->where('part_id', $part_id);
		$this->db->where('status', 'published');
		$count = (int) $this->db->count_all_results('tbl_product_reviews');

		if ($count === 0) {
			return ['avg' => 0.0, 'count' => 0];
		}

		$this->db->select('AVG(rating) AS avg_rating', FALSE);
		$this->db->where('part_id', $part_id);
		$this->db->where('status', 'published');
		$query = $this->db->get('tbl_product_reviews');

		if (!$query) {
			throw new AppException('Q18');
		}

		$row = $query->row_array();

		return ['avg' => round((float) ($row['avg_rating'] ?? 0), 1), 'count' => $count];
	}

	//Staff moderation list with optional filters. $filters: status, part_id, q.
	public function getAllReviews(array $filters = [], string $sort = 'newest', ?int $page = NULL, int $perPage = 15): array {
		$this->db->select('tbl_product_reviews.*, tbl_sku.part_name AS part_name, tbl_sku.sku AS sku');
		$this->db->join('tbl_sku', 'tbl_sku.part_id = tbl_product_reviews.part_id', 'left');

		$this->applyReviewFilters($filters);

		$this->applyReviewSort($sort);

		if ($page !== NULL && $page > 0) {
			$this->db->limit($perPage, ($page - 1) * $perPage);
		}

		$query = $this->db->get('tbl_product_reviews');

		if (!$query) {
			throw new AppException('Q18');
		}

		return $query->result_array();
	}

	//Staff list total, for pagination.
	public function countAllReviews(array $filters = []): int {
		$this->applyReviewFilters($filters);

		return (int) $this->db->count_all_results('tbl_product_reviews');
	}

	//Flips a review between 'published' and 'hidden' for staff moderation.
	public function setReviewStatus(int $id, string $status): bool {
		if (!in_array($status, ['published', 'hidden'], TRUE)) {
			throw new AppException('D20');
		}

		$this->db->where('id', $id);

		if (!$this->db->update('tbl_product_reviews', ['status' => $status])) {
			throw new AppException('Q18');
		}

		return $this->db->affected_rows() > 0;
	}

	//Permanently removes a review (staff action).
	public function deleteReview(int $id): bool {
		$this->db->where('id', $id);

		if (!$this->db->delete('tbl_product_reviews')) {
			throw new AppException('Q18');
		}

		return $this->db->affected_rows() > 0;
	}

	//Shared filter builder for the staff list and its count.
	private function applyReviewFilters(array $filters) {
		if (!empty($filters['status'])) {
			$this->db->where('status', $filters['status']);
		}

		if (!empty($filters['part_id'])) {
			$this->db->where('part_id', (int) $filters['part_id']);
		}

		if (!empty($filters['username'])) {
			$this->db->where('username', $filters['username']);
		}

		if (!empty($filters['q'])) {
			$this->db->group_start();
			$this->db->like('title', $filters['q']);
			$this->db->or_like('body', $filters['q']);
			$this->db->or_like('username', $filters['q']);
			$this->db->group_end();
		}
	}

	//Whitelisted sort options for the staff list.
	private function applyReviewSort(string $sort) {
		switch ($sort) {
			case 'oldest':
				$this->db->order_by('created_at', 'ASC');
				$this->db->order_by('id', 'ASC');
				break;

			case 'rating_high':
				$this->db->order_by('rating', 'DESC');
				$this->db->order_by('id', 'DESC');
				break;

			case 'rating_low':
				$this->db->order_by('rating', 'ASC');
				$this->db->order_by('id', 'ASC');
				break;

			case 'newest':
			default:
				$this->db->order_by('created_at', 'DESC');
				$this->db->order_by('id', 'DESC');
				break;
		}
	}
}
