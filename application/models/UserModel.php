<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UserModel extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function signInUser($username) {
        if(empty($username)) {
            throw new AppException('D01');
        }

        $this->db->where('username', $username);
        $query = $this->db->get('tbl_users');
        return $query->row_array();
    }

    public function registerUser($data) {
        if(empty($data)) {
            throw new AppException('D01');
        }
        return $this->db->insert('tbl_users', $data);
    }

    public function findUserById($id) {
        $this->db->where('id', $id);
        $query = $this->db->get('tbl_users');
        if(!$query) {
            throw new AppException('Q03');
        }

        if($query->num_rows() == 0) {
            throw new AppException('P01', ['id' => $id]);
        }

        return $query->row_array();
    }

    public function findUserByUsername($username) {
        $this->db->where('username', $username);
        $query = $this->db->get('tbl_users');
        if(!$query) {
            throw new AppException('Q03');
        }

        if($query->num_rows() == 0) {
            throw new AppException('P01', ['id' => $username]);
        }

        return $query->row_array();
    }

    public function findUserByEmail($email) {
        $this->db->where('email', $email);
        $query = $this->db->get('tbl_users');
        if(!$query) {
            throw new AppException('Q03');
        }

        return $query->row_array();
    }

    public function updateUser($id, $data) {
        if(empty($data)) {
            throw new AppException('D01');
        }

        $this->db->where('id', $id);
        return $this->db->update('tbl_users', $data);
    }
}