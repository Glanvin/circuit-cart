<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PromoModel extends CI_Model {

	//Discount types supported by tbl_promo_codes.discount_type.
	public const TYPES = ['percent', 'fixed'];

	public function __construct() {
		parent::__construct();
	}

	//Creates a promo code. $data is validated and normalized by the caller.
	public function createPromoCode(array $data) {
		if (empty($data['code'])) {
			throw new AppException('D01');
		}

		if (!$this->db->insert('tbl_promo_codes', $data)) {
			throw new AppException('Q16');
		}

		return $this->db->insert_id();
	}

	//Updates an existing promo code's editable fields.
	public function updatePromoCode($id, array $data) {
		if (empty($data)) {
			throw new AppException('D01');
		}

		$this->db->where('id', $id);
		return $this->db->update('tbl_promo_codes', $data);
	}

	//Returns every promo code, newest first.
	public function getAllPromoCodes() {
		$this->db->order_by('created_at', 'DESC');
		$this->db->order_by('id', 'DESC');
		$query = $this->db->get('tbl_promo_codes');

		if (!$query) {
			throw new AppException('Q16');
		}

		return $query->result_array();
	}

	//Returns a single promo code by primary key.
	public function getPromoById($id) {
		$this->db->where('id', $id);
		$query = $this->db->get('tbl_promo_codes', 1);

		if (!$query) {
			throw new AppException('Q16');
		}

		if ($query->num_rows() === 0) {
			throw new AppException('P11', ['id' => $id]);
		}

		return $query->row_array();
	}

	//Returns a single promo code by its (case-insensitive) code.
	public function getPromoByCode(string $code) {
		$code = strtoupper(trim($code));
		if ($code === '') {
			return NULL;
		}

		$this->db->where('code', $code);
		$query = $this->db->get('tbl_promo_codes', 1);

		if (!$query) {
			throw new AppException('Q16');
		}

		return $query->num_rows() === 0 ? NULL : $query->row_array();
	}

	//Validates a code against the current cart subtotal and (optional) buyer
	//email. Throws an AppException with a specific code when unusable, or
	//returns the promo row with a computed 'discount' when valid.
	public function validatePromoCode(string $code, float $subtotal, ?string $customerEmail = NULL): array {
		$promo = $this->getPromoByCode($code);
		$display = strtoupper(trim($code));

		if ($promo === NULL || (int) $promo['is_active'] !== 1) {
			throw new AppException('D15', ['code' => $display]);
		}

		$now = time();

		if (!empty($promo['starts_at']) && strtotime($promo['starts_at']) > $now) {
			throw new AppException('D15', ['code' => $display]);
		}

		if (!empty($promo['expires_at']) && strtotime($promo['expires_at']) < $now) {
			throw new AppException('D16', ['code' => $display]);
		}

		if ((float) $promo['min_subtotal'] > 0 && $subtotal + 0.001 < (float) $promo['min_subtotal']) {
			throw new AppException('D17', [
				'code' => $display,
				'min' => number_format((float) $promo['min_subtotal'], 2),
			]);
		}

		if ($promo['max_total_uses'] !== NULL && (int) $promo['used_count'] >= (int) $promo['max_total_uses']) {
			throw new AppException('D18', ['code' => $display]);
		}

		if ($promo['per_user_limit'] !== NULL && $customerEmail !== NULL && $customerEmail !== '') {
			if ($this->getUserRedemptionCount((int) $promo['id'], $customerEmail) >= (int) $promo['per_user_limit']) {
				throw new AppException('D18', ['code' => $display]);
			}
		}

		$promo['discount'] = $this->computeDiscount($promo, $subtotal);

		return $promo;
	}

	//Discount amount for a validated promo, capped at the subtotal.
	public function computeDiscount(array $promo, float $subtotal): float {
		if ((string) $promo['discount_type'] === 'percent') {
			$discount = $subtotal * ((float) $promo['discount_value'] / 100);
		} else {
			$discount = (float) $promo['discount_value'];
		}

		$discount = round($discount, 2);

		return max(0.0, min($subtotal, $discount));
	}

	//Counts how many (non-cancelled) online sales a buyer has redeemed a code on.
	public function getUserRedemptionCount(int $promoId, string $email): int {
		$promo = $this->getPromoById($promoId);

		$this->db->where('promo_code', $promo['code']);
		$this->db->where('customer_email', strtolower(trim($email)));
		$this->db->where_not_in('status', ['cancelled', 'refunded', 'voided']);
		$this->db->from('tbl_sales');
		return (int) $this->db->count_all_results();
	}

	//Increments a promo's global redemption count. Called inside the sale
	//transaction so a rolled-back order never burns a use.
	public function incrementUsage(int $promoId): void {
		$this->db->set('used_count', 'used_count + 1', FALSE);
		$this->db->where('id', $promoId);
		$this->db->update('tbl_promo_codes');
	}
}
