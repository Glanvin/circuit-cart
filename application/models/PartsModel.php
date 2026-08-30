<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PartsModel extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    //Fetch all active tech parts
    public function getAllParts() {
        //Only fetch parts where is_active is 1
        $this->db->where('is_active', 1);
        $query = $this->db->get('tbl_sku');

        // heck if the query itself failed (syntax error, table missing)
        if (!$query) {
            throw new AppException('Q01');
        }

        return $query->result_array();
    }
    
    //Fetch a single tech part by its 6-digit ID
    public function getPartById($part_id) {
        $this->db->where('part_id', $part_id);
        $query = $this->db->get('tbl_sku');

        //Check if the query failed
        if (!$query) {
            throw new AppException('Q02', ['id' => $part_id]);
        }

        //Check if the part actually exists
        if ($query->num_rows() === 0) {
            throw new AppException('P02', ['id' => $part_id]);
        }

        return $query->row_array();
    }
}