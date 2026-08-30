<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UserModel extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function signInUser($username) {
        if(empty($username)) {
            throw new Exception("Data is empty", 5);
        }

        $this->db->where('username', $username);
        $query = $this->db->get('tbl_users');
        return $query->row_array();
    }

    public function registerUser($data) {
        if(empty($data)) {
            throw new Exception("Data is empty", 5);
        }
        return $this->db->insert('tbl_users', $data);
    }

    public function findUserById($id) {
        $this->db->where($id);
        $query = $this->db->get('tbl_users');
        if(!$query) {
            throw new Exception("Failed to get user", 6);
        }

        if($query->num_rows() == 0) {
            throw new Exception("No records of {$id}", 7);
        }

        return $query->row_array();
    }

    public function findUserByUsername($username) {
        $this->db->where($username);
        $query = $this->db->get('tbl_users');
        if(!$query) {
            throw new Exception("Failed to get user", 6);
        }

        if($query->num_rows() == 0) {
            throw new Exception("No records of {$username}", 7);
        }

        return $query->row_array();
    }
}