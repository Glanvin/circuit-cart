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

    /*
     * Returns bookings filtered by status (empty = open bookings), a case-
     * insensitive search over username/device/issue, and a preferred_date sort.
     */
    public function getFilteredBookings($status = null, $q = '', $sort = 'newest') {
        if (!empty($status)) {
            $this->db->where('status', $status);
        } else {
            $this->db->where_in('status', ['pending', 'approved', 'rescheduled']);
        }

        if ($q !== '') {
            $this->db->group_start();
            $this->db->like('username', $q);
            $this->db->or_like('device', $q);
            $this->db->or_like('issue', $q);
            $this->db->group_end();
        }

        $this->db->order_by('preferred_date', $sort === 'oldest' ? 'ASC' : 'DESC');
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

    //----------------------------------------------------------------------
    // Appointment Slots
    //----------------------------------------------------------------------

    //Returns active appointment slots for a given date.
    public function getSlotsForDate($date) {
        $this->db->where('slot_date', $date);
        $this->db->where('is_active', 1);
        $this->db->order_by('start_time', 'ASC');
        $query = $this->db->get('tbl_appointment_slots');
        if (!$query) {
            throw new AppException('Q10');
        }
        return $query->result_array();
    }

    //Checks if a specific time slot on a date has capacity for a new booking.
    //Returns the slot row if available, FALSE if not.
    public function checkSlotAvailability($date, $time) {
        $this->db->where('slot_date', $date);
        $this->db->where('start_time <=', $time);
        $this->db->where('end_time >', $time);
        $this->db->where('is_active', 1);
        $query = $this->db->get('tbl_appointment_slots');
        if (!$query) {
            throw new AppException('Q10');
        }

        $slot = $query->row_array();
        if (!$slot) {
            return FALSE;
        }

        //Count existing bookings in this slot (pending, approved, rescheduled)
        $this->db->where('preferred_date', $date);
        $this->db->where('preferred_time', $slot['start_time']);
        $this->db->where_in('status', ['pending', 'approved', 'rescheduled']);
        $bookingQuery = $this->db->get('tbl_booking');
        if (!$bookingQuery) {
            throw new AppException('Q04');
        }

        $bookingCount = $bookingQuery->num_rows();

        return $bookingCount < (int) $slot['capacity'] ? $slot : FALSE;
    }

    //Returns available slots for a date as ['time' => 'HH:MM', 'end_time' => 'HH:MM', 'capacity' => n, 'booked' => n]
    public function getAvailableSlotsForDate($date) {
        $slots = $this->getSlotsForDate($date);
        $available = [];

        foreach ($slots as $slot) {
            $this->db->where('preferred_date', $date);
            $this->db->where('preferred_time', $slot['start_time']);
            $this->db->where_in('status', ['pending', 'approved', 'rescheduled']);
            $bookingQuery = $this->db->get('tbl_booking');
            if (!$bookingQuery) {
                throw new AppException('Q04');
            }

            $booked = $bookingQuery->num_rows();
            $capacity = (int) $slot['capacity'];

            if ($booked < $capacity) {
                $available[] = [
                    'time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'capacity' => $capacity,
                    'booked' => $booked,
                    'remaining' => $capacity - $booked,
                ];
            }
        }

        return $available;
    }

    //Checks if a specific time slot on a date has capacity for a new booking,
    //optionally excluding a specific booking ID (for reschedule).
    //Returns the slot row if available, FALSE if not.
    public function checkSlotAvailabilityExcluding($date, $time, $excludeBookingId = NULL) {
        $this->db->where('slot_date', $date);
        $this->db->where('start_time <=', $time);
        $this->db->where('end_time >', $time);
        $this->db->where('is_active', 1);
        $query = $this->db->get('tbl_appointment_slots');
        if (!$query) {
            throw new AppException('Q10');
        }

        $slot = $query->row_array();
        if (!$slot) {
            return FALSE;
        }

        //Count existing bookings in this slot (pending, approved, rescheduled)
        $this->db->where('preferred_date', $date);
        $this->db->where('preferred_time', $slot['start_time']);
        $this->db->where_in('status', ['pending', 'approved', 'rescheduled']);
        if ($excludeBookingId !== NULL) {
            $this->db->where('id !=', $excludeBookingId);
        }
        $bookingQuery = $this->db->get('tbl_booking');
        if (!$bookingQuery) {
            throw new AppException('Q04');
        }

        $bookingCount = $bookingQuery->num_rows();

        return $bookingCount < (int) $slot['capacity'] ? $slot : FALSE;
    }
}