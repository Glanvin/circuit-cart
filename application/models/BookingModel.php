<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BookingModel extends CI_Model {

    //Statuses a customer may cancel themselves.
    private const CUSTOMER_CANCELLABLE = ['pending', 'approved', 'rescheduled'];

    //Statuses a customer may reschedule themselves (only a fresh request).
    private const CUSTOMER_RESCHEDULABLE = ['pending'];

    //Repair jobs already closed may not be cancelled again.
    private const JOB_CLOSED = ['completed', 'cancelled'];

    //Hours before the appointment after which self-service is blocked.
    private const CANCELLATION_CUTOFF_HOURS = 24;

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
        $this->db->where_in('status', ['pending', 'approved', 'rescheduled', 'picked']);
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
            $this->db->where_in('status', ['pending', 'approved', 'rescheduled', 'picked']);
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
    // Customer self-service (cancel / reschedule)
    //----------------------------------------------------------------------

    /*
     * Cancels a booking on the customer's behalf. Only the owner may cancel,
     * only from CUSTOMER_CANCELLABLE, and never once the appointment is within
     * the cutoff window. An open repair job linked to the booking is cancelled
     * too (returning any used parts to stock). Returns the updated booking.
     */
    public function cancelBookingByCustomer($id, $username, $reason = '') {
        $booking = $this->ownedBooking($id, $username);

        if (!in_array($booking['status'], self::CUSTOMER_CANCELLABLE, TRUE)) {
            throw new AppException('D24');
        }

        $this->assertOutsideCutoff($booking);

        $note = 'Cancelled by customer';
        if (trim((string) $reason) !== '') {
            $note .= ': ' . trim((string) $reason);
        }

        $this->db->trans_start();

        //Close the linked repair job first so its parts return to stock before
        //the booking is marked cancelled. Jobs already closed are left alone.
        $job = $this->RepairJobModel->getJobByBookingId($id);
        if ($job && !in_array($job['status'], self::JOB_CLOSED, TRUE)) {
            $this->RepairJobModel->updateStatus($job['id'], 'cancelled', $note, NULL);
        }

        $this->updateBooking($id, [
            'status' => 'cancelled',
            'history' => json_encode($this->appendHistory($booking, 'cancelled', $note)),
        ]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            throw new AppException('Q04');
        }

        return $this->getBookingById($id);
    }

    /*
     * Reschedules a still-pending booking to a new date/time. Only the owner
     * may reschedule. The requested slot must have capacity (excluding this
     * booking) and the status stays 'pending' so the shop still reviews it.
     * Returns the updated booking.
     */
    public function rescheduleBookingByCustomer($id, $username, $date, $time) {
        $booking = $this->ownedBooking($id, $username);

        if (!in_array($booking['status'], self::CUSTOMER_RESCHEDULABLE, TRUE)) {
            throw new AppException('D14');
        }

        $slot = $this->checkSlotAvailabilityExcluding($date, $time, $id);
        if (!$slot) {
            throw new AppException('D25');
        }

        $note = 'Customer rescheduled to ' . $date . ' ' . substr($slot['start_time'], 0, 5);

        $this->updateBooking($id, [
            'preferred_date' => $date,
            'preferred_time' => $slot['start_time'],
            'history' => json_encode($this->appendHistory($booking, 'pending', $note)),
        ]);

        return $this->getBookingById($id);
    }

    /*
     * Loads a booking and verifies it belongs to $username. Throws P04 when it
     * is missing and P03 when it belongs to someone else, so a customer can
     * never act on another user's booking by guessing an id.
     */
    private function ownedBooking($id, $username) {
        $booking = $this->getBookingById($id);

        if ((string) $booking['username'] !== (string) $username) {
            throw new AppException('P03');
        }

        return $booking;
    }

    //Throws D24 when the appointment is past or within the cutoff window.
    private function assertOutsideCutoff(array $booking): void {
        $start = strtotime($booking['preferred_date'] . ' ' . $booking['preferred_time']);

        if ($start === FALSE || $start - time() < self::CANCELLATION_CUTOFF_HOURS * 3600) {
            throw new AppException('D24');
        }
    }

    //Returns the booking's history with a new entry appended.
    private function appendHistory(array $booking, string $status, string $note): array {
        $history = $booking['history'] ?? [];

        if (is_string($history)) {
            $history = json_decode($history, TRUE) ?: [];
        }

        $history[] = [
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s'),
            'note' => $note,
        ];

        return $history;
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

        //Count existing bookings in this slot (pending, approved, rescheduled, picked)
        $this->db->where('preferred_date', $date);
        $this->db->where('preferred_time', $slot['start_time']);
        $this->db->where_in('status', ['pending', 'approved', 'rescheduled', 'picked']);
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
            $this->db->where_in('status', ['pending', 'approved', 'rescheduled', 'picked']);
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

        //Count existing bookings in this slot (pending, approved, rescheduled, picked)
        $this->db->where('preferred_date', $date);
        $this->db->where('preferred_time', $slot['start_time']);
        $this->db->where_in('status', ['pending', 'approved', 'rescheduled', 'picked']);
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