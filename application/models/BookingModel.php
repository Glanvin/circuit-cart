<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BookingModel extends CI_Model {

    //Initializes the model.
    public function __construct() {
        parent::__construct();
    }

    //Inserts a new booking record.
    public function createBooking($data) {
        if (empty($data)) {
            throw new AppException('D01');
        }
        return $this->db->insert('tbl_booking', $data);
    }

    //Returns all bookings, optionally filtered by status.
    public function getAllBookings($status = null) {
        if (!empty($status)) {
            $this->db->where('status', $status);
        }
        $this->db->order_by('id', 'DESC');
        $query = $this->db->get('tbl_booking');
        if (!$query) {
            throw new AppException('Q04');
        }
        return $query->result_array();
    }

    //Open (still actionable) bookings: waiting to be handled or already ongoing.
    public function getOpenBookings() {
        $this->db->where_in('status', ['pending', 'approved', 'rescheduled']);
        $this->db->order_by('id', 'DESC');
        $query = $this->db->get('tbl_booking');
        if (!$query) {
            throw new AppException('Q04');
        }
        return $query->result_array();
    }

    //Returns the bookings submitted by the given user.
    public function getBookingsByUsername($username) {
        if (empty($username)) {
            throw new AppException('D01');
        }

        $this->db->where('username', $username);
        $this->db->order_by('id', 'DESC');
        $query = $this->db->get('tbl_booking');
        if (!$query) {
            throw new AppException('Q04');
        }
        return $query->result_array();
    }

    //Returns a single booking by ID.
    public function getBookingById($id) {
        $this->db->where('id', $id);
        $query = $this->db->get('tbl_booking');
        if (!$query) {
            throw new AppException('Q04');
        }

        if ($query->num_rows() == 0) {
            throw new AppException('P04', ['id' => $id]);
        }

        return $query->row_array();
    }

    //Updates a booking record by ID.
    public function updateBooking($id, $data) {
        if (empty($data)) {
            throw new AppException('D01');
        }

        $this->db->where('id', $id);
        return $this->db->update('tbl_booking', $data);
    }
}