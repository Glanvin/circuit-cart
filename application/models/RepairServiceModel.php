<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Repair services. An optional catalog the customer can choose from when
 * booking a repair. base_price is a display-only starting hint; the amount
 * actually billed is always built from parts and labor on the repair job, so
 * it is never trusted as a charge.
 */
class RepairServiceModel extends CI_Model {

	public function __construct() {
		parent::__construct();
	}

	//Active services for the customer booking form, in display order.
	public function getActiveServices(): array {
		$this->db->where('is_active', 1);
		$this->db->order_by('sort_order', 'ASC');
		$this->db->order_by('name', 'ASC');
		$query = $this->db->get('tbl_repair_services');

		if (!$query) {
			throw new AppException('Q19');
		}

		return $query->result_array();
	}

	//Every service for the staff list, active first then by display order.
	public function getAllServices(): array {
		$this->db->order_by('is_active', 'DESC');
		$this->db->order_by('sort_order', 'ASC');
		$this->db->order_by('name', 'ASC');
		$query = $this->db->get('tbl_repair_services');

		if (!$query) {
			throw new AppException('Q19');
		}

		return $query->result_array();
	}

	//A single service by primary key.
	public function getServiceById($id) {
		$this->db->where('id', $id);
		$query = $this->db->get('tbl_repair_services', 1);

		if (!$query) {
			throw new AppException('Q19');
		}

		if ($query->num_rows() === 0) {
			throw new AppException('P12', ['id' => $id]);
		}

		return $query->row_array();
	}

	//Creates a service and returns its id. $data is normalized by the caller.
	public function createService(array $data) {
		if (empty($data['name'])) {
			throw new AppException('D01');
		}

		if (!$this->db->insert('tbl_repair_services', $data)) {
			throw new AppException('Q19');
		}

		return (int) $this->db->insert_id();
	}

	//Updates a service's editable fields.
	public function updateService($id, array $data) {
		if (empty($data)) {
			throw new AppException('D01');
		}

		$this->db->where('id', $id);
		return $this->db->update('tbl_repair_services', $data);
	}
}
