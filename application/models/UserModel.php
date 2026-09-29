<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UserModel extends CI_Model {

    //Initializes the model.
    public function __construct() {
        parent::__construct();
    }

    //Returns the user matching the username, or FALSE when absent.
    public function signInUser($username) {
        if(empty($username)) {
            throw new AppException('D01');
        }

        $this->db->where('username', $username);
        $query = $this->db->get('tbl_users');
        return $query->row_array();
    }

    //Inserts a new user record.
    public function registerUser($data) {
        if(empty($data)) {
            throw new AppException('D01');
        }
        return $this->db->insert('tbl_users', $data);
    }

    //Returns a single user by ID.
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

    //Returns a single user by username.
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

    //Returns a single user by email.
    public function findUserByEmail($email) {
        $this->db->where('email', $email);
        $query = $this->db->get('tbl_users');
        if(!$query) {
            throw new AppException('Q03');
        }

        return $query->row_array();
    }

    //Updates a user record by ID.
    public function updateUser($id, $data) {
        if(empty($data)) {
            throw new AppException('D01');
        }

        $this->db->where('id', $id);
        return $this->db->update('tbl_users', $data);
    }

    //Physically deletes a user record by ID.
    public function deleteUser($id) {
        if(empty($id)) {
            throw new AppException('D01');
        }

        $this->db->where('id', $id);
        return $this->db->delete('tbl_users');
    }

    public function getAllUsers() {
        $this->db->order_by('id', 'DESC');
        $query = $this->db->get('tbl_users');

        if(!$query) {
            throw new AppException('Q03');
        }

        return $query->result_array();
    }

    public function countUsers() {
        $this->db->select('COUNT(*) AS total');
        $query = $this->db->get('tbl_users');

        if(!$query) {
            throw new AppException('Q03');
        }

        return (int) $query->row()->total;
    }

    //Users registered per day, for account_created dates on or after $since.
    public function getUserRegistrationsSince($since) {
        $this->db->select('DATE(account_created) AS day, COUNT(*) AS total');
        $this->db->where('account_created >=', $since);
        $this->db->group_by('day');
        $query = $this->db->get('tbl_users');

        if(!$query) {
            throw new AppException('Q03');
        }

        $rows = [];

        foreach ($query->result_array() as $row) {
            $rows[$row['day']] = (int) $row['total'];
        }

        return $rows;
    }
}