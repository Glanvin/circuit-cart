<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class RepairJobModel extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    //Creates a new repair job from an approved booking.
    //Returns the new job ID.
    public function createFromBooking($booking_id, $technician_id = NULL) {
        if (empty($booking_id)) {
            throw new AppException('D01');
        }

        $booking = $this->getBookingForJob($booking_id);
        if (!$booking) {
            throw new AppException('P04', ['id' => $booking_id]);
        }

        $jobReference = $this->generateJobReference();

        $initialStatus = 'diagnosis';
        $statusHistory = [[
            'status' => $initialStatus,
            'timestamp' => date('Y-m-d H:i:s'),
            'note' => 'Repair job created from booking #' . $booking_id,
        ]];

        $job = [
            'job_reference' => $jobReference,
            'booking_id' => $booking_id,
            'technician_id' => $technician_id,
            'device' => $booking['device'],
            'issue' => $booking['issue'],
            'status' => $initialStatus,
            'status_history' => json_encode($statusHistory),
            'started_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->trans_start();

        $this->db->insert('tbl_repair_jobs', $job);
        $job_id = $this->db->insert_id();

        //Update booking status to link to job (optional: add job_reference to booking)
        $this->db->where('id', $booking_id);
        $this->db->update('tbl_booking', ['status' => 'finished']);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            throw new AppException('Q11');
        }

        return $job_id;
    }

    //Gets a repair job by ID with decoded JSON fields.
    public function getJobById($id) {
        $this->db->where('id', $id);
        $query = $this->db->get('tbl_repair_jobs');
        if (!$query) {
            throw new AppException('Q11');
        }

        if ($query->num_rows() === 0) {
            throw new AppException('P09', ['id' => $id]);
        }

        $job = $query->row_array();
        $this->decodeJob($job);
        return $job;
    }

    //Gets a repair job by reference number.
    public function getJobByReference($reference) {
        $this->db->where('job_reference', $reference);
        $query = $this->db->get('tbl_repair_jobs');
        if (!$query) {
            throw new AppException('Q11');
        }

        if ($query->num_rows() === 0) {
            return NULL;
        }

        $job = $query->row_array();
        $this->decodeJob($job);
        return $job;
    }

    //Gets repair job by booking ID.
    public function getJobByBookingId($booking_id) {
        $this->db->where('booking_id', $booking_id);
        $query = $this->db->get('tbl_repair_jobs');
        if (!$query) {
            throw new AppException('Q11');
        }

        if ($query->num_rows() === 0) {
            return NULL;
        }

        $job = $query->row_array();
        $this->decodeJob($job);
        return $job;
    }

    //Lists repair jobs with optional filters.
    public function getJobs($filters = [], $sort = 'newest') {
        $this->db->select('rj.*, e.firstname as tech_firstname, e.lastname as tech_lastname');
        $this->db->from('tbl_repair_jobs rj');
        $this->db->join('tbl_employees e', 'rj.technician_id = e.employee_id', 'left');

        if (!empty($filters['status'])) {
            $this->db->where('rj.status', $filters['status']);
        }
        if (!empty($filters['technician_id'])) {
            $this->db->where('rj.technician_id', $filters['technician_id']);
        }
        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $this->db->group_start();
            $this->db->like('rj.job_reference', $q);
            $this->db->or_like('rj.device', $q);
            $this->db->or_like('rj.issue', $q);
            $this->db->group_end();
        }

        switch ($sort) {
            case 'oldest':
                $this->db->order_by('rj.created_at', 'ASC');
                break;
            case 'reference':
                $this->db->order_by('rj.job_reference', 'ASC');
                break;
            default:
                $this->db->order_by('rj.created_at', 'DESC');
        }

        $query = $this->db->get();
        if (!$query) {
            throw new AppException('Q11');
        }

        $jobs = $query->result_array();
        foreach ($jobs as &$job) {
            $this->decodeJob($job);
        }

        return $jobs;
    }

    //Gets repair jobs for a specific customer (via booking username).
    public function getCustomerJobs($username) {
        $this->db->select('rj.*');
        $this->db->from('tbl_repair_jobs rj');
        $this->db->join('tbl_booking b', 'rj.booking_id = b.id');
        $this->db->where('b.username', $username);
        $this->db->order_by('rj.created_at', 'DESC');
        $query = $this->db->get();
        if (!$query) {
            throw new AppException('Q11');
        }

        $jobs = $query->result_array();
        foreach ($jobs as &$job) {
            $this->decodeJob($job);
        }
        return $jobs;
    }

    //Updates a repair job.
    public function updateJob($id, $data) {
        if (empty($data)) {
            throw new AppException('D01');
        }

        $this->db->where('id', $id);
        return $this->db->update('tbl_repair_jobs', $data);
    }

    //Updates job status with history tracking.
    public function updateStatus($id, $newStatus, $note = '') {
        $job = $this->getJobById($id);

        $history = $job['status_history'] ?? [];
        $history[] = [
            'status' => $newStatus,
            'timestamp' => date('Y-m-d H:i:s'),
            'note' => $note ?: 'Status changed to ' . $newStatus,
        ];

        $data = [
            'status' => $newStatus,
            'status_history' => json_encode($history),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($newStatus === 'in_progress' && empty($job['started_at'])) {
            $data['started_at'] = date('Y-m-d H:i:s');
        } elseif ($newStatus === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        } elseif ($newStatus === 'ready_for_pickup') {
            //No timestamp needed here, but could add notified_at
        }

        return $this->updateJob($id, $data);
    }

    //Adds diagnosis and estimate.
    public function setDiagnosis($id, $diagnosis, $estimate_total = NULL) {
        $data = ['diagnosis' => $diagnosis];
        if ($estimate_total !== NULL) {
            $data['estimate_total'] = $estimate_total;
            $data['status'] = 'estimate_pending';
            $history = $this->getJobById($id)['status_history'] ?? [];
            $history[] = [
                'status' => 'estimate_pending',
                'timestamp' => date('Y-m-d H:i:s'),
                'note' => 'Estimate provided: ₱' . number_format($estimate_total, 2),
            ];
            $data['status_history'] = json_encode($history);
        }
        return $this->updateJob($id, $data);
    }

    //Records customer approval of estimate.
    public function approveEstimate($id) {
        $job = $this->getJobById($id);
        if ((float) $job['estimate_total'] <= 0) {
            throw new AppException('D03', ['msg' => 'No estimate to approve']);
        }

        $history = $job['status_history'] ?? [];
        $history[] = [
            'status' => 'approved',
            'timestamp' => date('Y-m-d H:i:s'),
            'note' => 'Customer approved estimate of ₱' . number_format($job['estimate_total'], 2),
        ];

        return $this->updateJob($id, [
            'estimate_approved' => 1,
            'estimate_approved_at' => date('Y-m-d H:i:s'),
            'status' => 'approved',
            'status_history' => json_encode($history),
        ]);
    }

    //Records parts used (decrements stock).
    public function addPartsUsed($id, $parts) {
        //parts = array of [part_id, qty, price]
        $job = $this->getJobById($id);
        $existing = $job['parts_used'] ?? [];

        $this->db->trans_start();

        foreach ($parts as $part) {
            $part_id = (int) $part['part_id'];
            $qty = (int) $part['qty'];

            //Verify stock
            $this->db->where('part_id', $part_id);
            $query = $this->db->get('tbl_sku');
            if (!$query || $query->num_rows() === 0) {
                throw new AppException('P02', ['id' => $part_id]);
            }
            $sku = $query->row_array();
            if ((int) $sku['stock_quantity'] < $qty) {
                throw new AppException('D02', ['item' => $sku['part_name']]);
            }

            //Decrement stock and record movement
            $this->PartsModel->adjustStock(
                $part_id,
                -$qty,
                'repair_use',
                'repair_job',
                $id,
                NULL, //technician_id not passed here
                'Repair job ' . $job['job_reference']
            );

            $existing[] = [
                'part_id' => $part_id,
                'name' => $sku['part_name'],
                'qty' => $qty,
                'price' => (float) $part['price'],
                'added_at' => date('Y-m-d H:i:s'),
            ];
        }

        $this->updateJob($id, ['parts_used' => json_encode($existing)]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            throw new AppException('Q11');
        }

        return TRUE;
    }

    //Adds labor line items.
    public function addLaborItems($id, $labor_items) {
        //labor_items = array of [description, hours, rate, total]
        $job = $this->getJobById($id);
        $existing = $job['labor_items'] ?? [];

        foreach ($labor_items as $item) {
            $existing[] = [
                'description' => $item['description'],
                'hours' => (float) $item['hours'],
                'rate' => (float) $item['rate'],
                'total' => (float) $item['total'],
                'added_at' => date('Y-m-d H:i:s'),
            ];
        }

        return $this->updateJob($id, ['labor_items' => json_encode($existing)]);
    }

    //Adds technician note.
    public function addNote($id, $note) {
        $job = $this->getJobById($id);
        $notes = $job['technician_notes'] ?? '';
        $timestamp = date('Y-m-d H:i:s');
        $newNote = "\n[$timestamp] " . $note;
        return $this->updateJob($id, ['technician_notes' => $notes . $newNote]);
    }

    //Marks job as ready for pickup and notifies customer.
    public function readyForPickup($id) {
        return $this->updateStatus($id, 'ready_for_pickup', 'Repair completed, device ready for pickup');
    }

    //Marks job as picked up.
    public function markPickedUp($id) {
        return $this->updateJob($id, [
            'status' => 'completed',
            'picked_up_at' => date('Y-m-d H:i:s'),
            'status_history' => json_encode(array_merge(
                $this->getJobById($id)['status_history'] ?? [],
                [['status' => 'completed', 'timestamp' => date('Y-m-d H:i:s'), 'note' => 'Device picked up by customer']]
            )),
        ]);
    }

    //Generates unique job reference: RJ-YYYYMMDD-XXXXX
    private function generateJobReference(): string {
        return 'RJ-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }

    //Gets booking data needed for job creation.
    private function getBookingForJob($booking_id) {
        $this->db->where('id', $booking_id);
        $query = $this->db->get('tbl_booking');
        if (!$query || $query->num_rows() === 0) {
            return NULL;
        }
        return $query->row_array();
    }

    //Decodes JSON fields in job array.
    private function decodeJob(array &$job): void {
        foreach (['parts_used', 'labor_items', 'status_history'] as $field) {
            if (isset($job[$field])) {
                $job[$field] = json_decode($job[$field], true) ?? [];
            }
        }
    }
}