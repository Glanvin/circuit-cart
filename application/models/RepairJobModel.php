<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class RepairJobModel extends CI_Model {

    //How long a customer estimate link stays valid (seconds).
    private const ESTIMATE_TTL = 14 * 86400;

    //Allowed repair status transitions. A status must appear as a key, and the
    //value lists the statuses it may move to. Terminal statuses map to no target.
    private const TRANSITIONS = [
        'diagnosis'        => ['estimate_pending', 'approved', 'in_progress', 'cancelled'],
        'estimate_pending' => ['diagnosis', 'approved', 'in_progress', 'cancelled'],
        'approved'         => ['in_progress', 'ready_for_pickup', 'cancelled'],
        'in_progress'      => ['ready_for_pickup', 'cancelled'],
        'ready_for_pickup' => ['completed', 'cancelled'],
        'completed'        => [],
        'cancelled'        => [],
    ];

    //Statuses that may not move anywhere. Kept for a clearer closed-job error.
    private const TERMINAL = ['completed', 'cancelled'];

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
            'public_token' => $this->generateToken(),
        ];

        $this->db->trans_start();

        $this->db->insert('tbl_repair_jobs', $job);
        $job_id = $this->db->insert_id();

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            throw new AppException('Q11');
        }

        return $job_id;
    }

    //Gets a repair job by ID with decoded JSON fields. Joins the assigned
    //technician and the owning booking so views can show both without extra
    //lookups (the booking supplies the customer username).
    public function getJobById($id) {
        $this->db->select('rj.*, e.firstname as tech_firstname, e.lastname as tech_lastname, b.username as username');
        $this->db->from('tbl_repair_jobs rj');
        $this->db->join('tbl_employees e', 'rj.technician_id = e.employee_id', 'left');
        $this->db->join('tbl_booking b', 'rj.booking_id = b.id', 'left');
        $this->db->where('rj.id', $id);
        $query = $this->db->get();
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

    //Gets repair jobs by booking ID.
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

    //Returns repair jobs keyed by booking_id for a set of bookings, so a booking
    //list can show its linked job without one query per row.
    public function getJobsByBookingIds(array $booking_ids) {
        if (empty($booking_ids)) {
            return [];
        }

        $this->db->where_in('booking_id', $booking_ids);
        $query = $this->db->get('tbl_repair_jobs');
        if (!$query) {
            throw new AppException('Q11');
        }

        $map = [];
        foreach ($query->result_array() as $job) {
            $this->decodeJob($job);
            $map[(int) $job['booking_id']] = $job;
        }
        return $map;
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

    //Updates job status with history tracking. Rejects transitions the workflow
    //does not allow and reverses any consumed parts when a job is cancelled.
    //$employee_id is recorded against returned-stock movements for accountability.
    public function updateStatus($id, $newStatus, $note = '', $employee_id = NULL) {
        $job = $this->getJobById($id);
        $this->assertTransition($job['status'], $newStatus);

        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();

        $reversed = 0;
        if ($newStatus === 'cancelled') {
            $reversed = $this->reversePartsUsed($job, $employee_id);
        }

        $entryNote = $note ?: 'Status changed to ' . $newStatus;
        if ($reversed > 0) {
            $entryNote .= ' — returned ' . $reversed . ' part line(s) to stock';
        }

        $history = $job['status_history'] ?? [];
        $history[] = [
            'status' => $newStatus,
            'timestamp' => $now,
            'note' => $entryNote,
        ];

        $data = [
            'status' => $newStatus,
            'status_history' => json_encode($history),
            'updated_at' => $now,
        ];

        if ($newStatus === 'in_progress' && empty($job['started_at'])) {
            $data['started_at'] = $now;
        } elseif ($newStatus === 'completed') {
            $data['completed_at'] = $now;
        }

        $this->updateJob($id, $data);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            throw new AppException('Q11');
        }

        return TRUE;
    }

    //Assigns or clears the technician on an open job. The target must be an
    //active technician or admin. Returns the updated job.
    public function assignTechnician($id, $technician_id = NULL) {
        $job = $this->getJobById($id);

        if (in_array($job['status'], self::TERMINAL, TRUE)) {
            throw new AppException('D12');
        }

        $technician_id = $technician_id !== NULL ? (int) $technician_id : NULL;

        if ($technician_id) {
            $employee = $this->EmployeeModel->getEmployeeById($technician_id);

            if (empty($employee)) {
                throw new AppException('P07', ['id' => $technician_id]);
            }

            if (!in_array($employee['role'], ['technician', 'admin'], TRUE) || (int) $employee['is_active'] !== 1) {
                throw new AppException('D13', ['from' => 'assignment', 'to' => 'an inactive or non-technician employee']);
            }

            $label = trim(($employee['firstname'] ?? '') . ' ' . ($employee['lastname'] ?? ''));
            $note = 'Assigned to ' . ($label !== '' ? $label : 'technician #' . $technician_id);
        } else {
            $note = 'Technician unassigned';
        }

        $history = $job['status_history'] ?? [];
        $history[] = [
            'status' => $job['status'],
            'timestamp' => date('Y-m-d H:i:s'),
            'note' => $note,
        ];

        $this->updateJob($id, [
            'technician_id' => $technician_id,
            'status_history' => json_encode($history),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->getJobById($id);
    }

    //Throws when a status change is not permitted by the workflow.
    private function assertTransition(string $from, string $to): void {
        if ($from === $to) {
            return;
        }

        if (!in_array($to, self::TRANSITIONS[$from] ?? [], TRUE)) {
            if (in_array($from, self::TERMINAL, TRUE)) {
                throw new AppException('D12');
            }

            throw new AppException('D13', [
                'from' => ucfirst(str_replace('_', ' ', $from)),
                'to' => ucfirst(str_replace('_', ' ', $to)),
            ]);
        }
    }

    //Returns every part still recorded against the job to stock. Returns the
    //number of part lines reversed.
    private function reversePartsUsed(array $job, $employee_id = NULL): int {
        $parts = $job['parts_used'] ?? [];

        if (empty($parts)) {
            return 0;
        }

        foreach ($parts as $line) {
            $this->PartsModel->adjustStock(
                (int) $line['part_id'],
                (int) $line['qty'],
                'return',
                'repair_job',
                (int) $job['id'],
                $employee_id ?: NULL,
                'Repair job ' . $job['job_reference'] . ' cancelled'
            );
        }

        return count($parts);
    }

    //Adds diagnosis and estimate. When an estimate is provided a fresh customer
    //approval token is minted (invalidating any earlier one) and the job moves
    //to estimate_pending. Returns TRUE on success.
    public function setDiagnosis($id, $diagnosis, $estimate_total = NULL) {
        $job = $this->getJobById($id);
        $data = ['diagnosis' => $diagnosis];
        if ($estimate_total !== NULL) {
            $this->assertTransition($job['status'], 'estimate_pending');
            $data['estimate_total'] = $estimate_total;
            $data['status'] = 'estimate_pending';
            $data['estimate_token'] = $this->generateToken();
            $data['estimate_token_expires_at'] = date('Y-m-d H:i:s', time() + self::ESTIMATE_TTL);
            $data['estimate_approved'] = 0;
            $data['estimate_approved_at'] = NULL;
            $data['estimate_declined_at'] = NULL;
            $data['estimate_declined_reason'] = NULL;
            $history = $job['status_history'] ?? [];
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
            throw new AppException('D04');
        }

        $this->assertTransition($job['status'], 'approved');

        $history = $job['status_history'] ?? [];
        $history[] = [
            'status' => 'approved',
            'timestamp' => date('Y-m-d H:i:s'),
            'note' => 'Customer approved estimate of ₱' . number_format($job['estimate_total'], 2),
        ];

        return $this->updateJob($id, [
            'estimate_approved' => 1,
            'estimate_approved_at' => date('Y-m-d H:i:s'),
            'estimate_token' => NULL,
            'status' => 'approved',
            'status_history' => json_encode($history),
        ]);
    }

    //Looks up a repair job by its unguessable public status token.
    public function getJobByPublicToken(string $token) {
        $token = trim($token);

        if ($token === '') {
            throw new AppException('P10');
        }

        $this->db->where('public_token', $token);
        $query = $this->db->get('tbl_repair_jobs');

        if (!$query) {
            throw new AppException('Q11');
        }

        if ($query->num_rows() === 0) {
            throw new AppException('P10');
        }

        $job = $query->row_array();
        $this->decodeJob($job);
        return $job;
    }

    //Resolves a still-valid estimate token. Throws D10 when the link is unknown,
    //expired, already decided, or the job no longer awaits approval.
    public function getJobByEstimateToken(string $token) {
        $token = trim($token);

        if ($token === '') {
            throw new AppException('D10');
        }

        $this->db->where('estimate_token', $token);
        $this->db->where('estimate_approved', 0);
        $this->db->where('estimate_declined_at IS NULL', NULL, FALSE);
        $query = $this->db->get('tbl_repair_jobs');

        if (!$query) {
            throw new AppException('Q11');
        }

        if ($query->num_rows() === 0) {
            throw new AppException('D10');
        }

        $job = $query->row_array();

        if (!empty($job['estimate_token_expires_at']) && strtotime($job['estimate_token_expires_at']) < time()) {
            throw new AppException('D10');
        }

        $this->decodeJob($job);
        return $job;
    }

    //Customer approves the estimate through an emailed token link.
    public function customerApproveEstimate(string $token): array {
        $job = $this->getJobByEstimateToken($token);
        $this->approveEstimate($job['id']);
        return $this->getJobById($job['id']);
    }

    //Customer declines the estimate through an emailed token link. The token is
    //consumed and the reason recorded for the technician.
    public function customerDeclineEstimate(string $token, string $reason = ''): array {
        $job = $this->getJobByEstimateToken($token);
        $this->assertTransition($job['status'], 'diagnosis');

        $history = $job['status_history'] ?? [];
        $history[] = [
            'status' => 'estimate_declined',
            'timestamp' => date('Y-m-d H:i:s'),
            'note' => trim($reason) !== '' ? 'Customer declined estimate: ' . trim($reason) : 'Customer declined estimate',
        ];

        $this->updateJob($job['id'], [
            'estimate_token' => NULL,
            'estimate_declined_at' => date('Y-m-d H:i:s'),
            'estimate_declined_reason' => trim($reason) !== '' ? trim($reason) : NULL,
            'status' => 'diagnosis',
            'status_history' => json_encode($history),
        ]);

        return $this->getJobById($job['id']);
    }

    //Records parts used (decrements stock). Not permitted on a closed job so
    //stock can never drift past the cancellation reversal.
    public function addPartsUsed($id, $parts, $employee_id = NULL) {
        //parts = array of [part_id, qty, price]
        $job = $this->getJobById($id);

        if (in_array($job['status'], self::TERMINAL, TRUE)) {
            throw new AppException('D12');
        }

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
                $employee_id ?: NULL,
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

        if (in_array($job['status'], self::TERMINAL, TRUE)) {
            throw new AppException('D12');
        }

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

    /*
     * Booking-driven completion: closes the linked job when its booking is
     * marked finished. Unlike updateStatus() this may complete the job from any
     * non-terminal state, because closing the appointment is an explicit staff
     * action rather than a normal workflow step.
     */
    public function complete($id, string $note = '') {
        $job = $this->getJobById($id);

        if (in_array($job['status'], self::TERMINAL, TRUE)) {
            return $job;
        }

        $now = date('Y-m-d H:i:s');
        $history = $job['status_history'] ?? [];
        $history[] = [
            'status' => 'completed',
            'timestamp' => $now,
            'note' => $note !== '' ? $note : 'Marked finished from booking',
        ];

        $this->updateJob($id, [
            'status' => 'completed',
            'completed_at' => $now,
            'updated_at' => $now,
            'status_history' => json_encode($history),
        ]);

        return $this->getJobById($id);
    }

    /*
     * Booking-driven reopen: returns a completed job to in_progress when its
     * booking is marked unfinished. Jobs in other states are left untouched.
     */
    public function reopen($id, string $note = '') {
        $job = $this->getJobById($id);

        if ($job['status'] !== 'completed') {
            return $job;
        }

        $now = date('Y-m-d H:i:s');
        $history = $job['status_history'] ?? [];
        $history[] = [
            'status' => 'in_progress',
            'timestamp' => $now,
            'note' => $note !== '' ? $note : 'Reopened from booking',
        ];

        $this->updateJob($id, [
            'status' => 'in_progress',
            'completed_at' => NULL,
            'updated_at' => $now,
            'status_history' => json_encode($history),
        ]);

        return $this->getJobById($id);
    }

    //Marks job as picked up. Guarded so it can only complete a ready job.
    public function markPickedUp($id) {
        $job = $this->getJobById($id);
        $this->assertTransition($job['status'], 'completed');

        $now = date('Y-m-d H:i:s');

        return $this->updateJob($id, [
            'status' => 'completed',
            'picked_up_at' => $now,
            'completed_at' => $now,
            'updated_at' => $now,
            'status_history' => json_encode(array_merge(
                $job['status_history'] ?? [],
                [['status' => 'completed', 'timestamp' => $now, 'note' => 'Device picked up by customer']]
            )),
        ]);
    }

    //----------------------------------------------------------------------
    // Payments / deposits
    //----------------------------------------------------------------------

    //Every payment recorded against a job, oldest first, with the tender name
    //and the employee who recorded it.
    public function getPaymentsForJob($job_id) {
        $this->db->select('rp.*, pm.name as method_name, pm.code as method_code, e.firstname as emp_firstname, e.lastname as emp_lastname');
        $this->db->from('tbl_repair_payments rp');
        $this->db->join('tbl_payment_methods pm', 'rp.method_id = pm.id', 'left');
        $this->db->join('tbl_employees e', 'rp.recorded_by = e.employee_id', 'left');
        $this->db->where('rp.job_id', $job_id);
        $this->db->order_by('rp.created_at', 'ASC');
        $this->db->order_by('rp.id', 'ASC');
        $query = $this->db->get();

        if (!$query) {
            throw new AppException('Q20');
        }

        return $query->result_array();
    }

    //Total amount already collected against a job.
    public function getAmountPaid($job_id): float {
        $this->db->select('COALESCE(SUM(amount), 0) AS paid', FALSE);
        $this->db->where('job_id', $job_id);
        $query = $this->db->get('tbl_repair_payments');

        if (!$query) {
            throw new AppException('Q20');
        }

        $row = $query->row_array();
        return round((float) ($row['paid'] ?? 0), 2);
    }

    /*
     * Summarizes a job's charges and payments. The recorded total is the sum of
     * parts and labor (the authoritative charge); the balance is what remains
     * unpaid and is what the pickup gate checks before releasing a device.
     */
    public function computeTotals(array $job): array {
        $parts = 0.0;
        foreach ($job['parts_used'] ?? [] as $part) {
            $parts += (float) $part['qty'] * (float) $part['price'];
        }

        $labor = 0.0;
        foreach ($job['labor_items'] ?? [] as $item) {
            $labor += (float) $item['total'];
        }

        $recorded = round($parts + $labor, 2);
        $paid = $this->getAmountPaid((int) $job['id']);

        return [
            'parts' => round($parts, 2),
            'labor' => round($labor, 2),
            'recorded' => $recorded,
            'paid' => $paid,
            'balance' => round($recorded - $paid, 2),
        ];
    }

    /*
     * Records a deposit or payment against an open job. Amounts are re-read
     * from validated input by the caller and never trusted from the client.
     * Appends a history entry for auditability. Returns the new payment id.
     */
    public function recordPayment($job_id, array $data, $employee_id = NULL) {
        $job = $this->getJobById($job_id);

        if (in_array($job['status'], self::TERMINAL, TRUE)) {
            throw new AppException('D12');
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new AppException('D21');
        }

        $method_id = (int) ($data['method_id'] ?? 0);
        if ($method_id > 0 && !$this->paymentMethodExists($method_id)) {
            throw new AppException('D23');
        }

        $kind = (($data['kind'] ?? '') === 'deposit') ? 'deposit' : 'payment';
        $reference = trim((string) ($data['reference'] ?? ''));

        $this->db->trans_start();

        $this->db->insert('tbl_repair_payments', [
            'job_id' => (int) $job_id,
            'amount' => $amount,
            'method_id' => $method_id > 0 ? $method_id : NULL,
            'reference' => $reference !== '' ? $reference : NULL,
            'kind' => $kind,
            'recorded_by' => $employee_id ?: NULL,
        ]);

        $payment_id = (int) $this->db->insert_id();

        $history = $job['status_history'] ?? [];
        $history[] = [
            'status' => $job['status'],
            'timestamp' => date('Y-m-d H:i:s'),
            'note' => ucfirst($kind) . ' of ₱' . number_format($amount, 2) . ' recorded',
        ];

        $this->updateJob($job_id, [
            'status_history' => json_encode($history),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            throw new AppException('Q20');
        }

        return $payment_id;
    }

    //True when the id refers to an active payment method.
    private function paymentMethodExists($method_id): bool {
        $this->db->where('id', $method_id);
        $this->db->where('is_active', 1);
        return (int) $this->db->count_all_results('tbl_payment_methods') > 0;
    }

    //Generates unique job reference: RJ-YYYYMMDD-XXXXX
    private function generateJobReference(): string {
        return 'RJ-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }

    //Unguessable 32-character hex token for public status and estimate links.
    private function generateToken(): string {
        return bin2hex(random_bytes(16));
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