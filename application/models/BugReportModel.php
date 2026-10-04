<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BugReportModel extends CI_Model {

    //Initializes the model.
    public function __construct() {
        parent::__construct();
    }

    //Inserts a new bug report record.
    public function createBugReport($data) {
        if (empty($data)) {
            throw new AppException('D01');
        }
        return $this->db->insert('tbl_bug_reports', $data);
    }

    //Lists bug reports, filtered by status and optionally narrowed to a matching
    //username or email.
    public function getBugReports($status = 'new', $q = '') {
        if ($status !== NULL && $status !== '' && $status !== 'all') {
            $this->db->where('status', $status);
        }

        if ($q !== '') {
            $this->db->group_start();
            $this->db->like('username', $q);
            $this->db->or_like('email', $q);
            $this->db->group_end();
        }

        $this->db->order_by('id', 'DESC');
        $query = $this->db->get('tbl_bug_reports');
        if (!$query) {
            throw new AppException('Q06');
        }
        return $query->result_array();
    }

    //Returns a single bug report by ID.
    public function getBugReportById($id) {
        $this->db->where('id', $id);
        $query = $this->db->get('tbl_bug_reports');
        if (!$query) {
            throw new AppException('Q06');
        }

        if ($query->num_rows() == 0) {
            throw new AppException('P06', ['id' => $id]);
        }

        return $query->row_array();
    }

    //Updates a bug report's status by ID.
    public function updateStatus($id, $status) {
        if (empty($status)) {
            throw new AppException('D01');
        }

        $this->db->where('id', $id);
        return $this->db->update('tbl_bug_reports', ['status' => $status]);
    }

    //Counts reports from the given submitters created within the last hours. Used
    //by the rate limiter; IP addresses are never collected.
    public function countRecentBySubmitter(array $identifiers, int $hours = 5): int {
        $identifiers = array_values(array_filter(array_map('trim', $identifiers)));

        if ($identifiers === []) {
            return 0;
        }

        $this->db->group_start();
        foreach ($identifiers as $identifier) {
            $this->db->or_where('username', $identifier);
            $this->db->or_where('email', $identifier);
        }
        $this->db->group_end();

        $this->db->where('created_at >=', date('Y-m-d H:i:s', strtotime('-' . (int) $hours . ' hours')));

        return (int) $this->db->count_all_results('tbl_bug_reports');
    }
}