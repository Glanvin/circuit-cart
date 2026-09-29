<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EmployeeModel extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    //Finds an employee by their 6-digit login ID (employee_id column).
    public function findByEmployeeId($employee_id) {
        if ($employee_id === '' || $employee_id === NULL) {
            throw new AppException('D01');
        }

        $this->db->where('employee_id', (int) $employee_id);
        $query = $this->db->get('tbl_employees');

        if (!$query) {
            throw new AppException('Q07');
        }

        if ($query->num_rows() === 0) {
            throw new AppException('P07', ['id' => $employee_id]);
        }

        return $query->row_array();
    }

    public function getEmployeeById($id) {
        $this->db->where('employee_id', $id);
        $query = $this->db->get('tbl_employees');

        if (!$query) {
            throw new AppException('Q07');
        }

        if ($query->num_rows() === 0) {
            throw new AppException('P07', ['id' => $id]);
        }

        return $query->row_array();
    }

    public function getAllEmployees() {
        $this->db->order_by('employee_id', 'ASC');
        $query = $this->db->get('tbl_employees');

        if (!$query) {
            throw new AppException('Q07');
        }

        return $query->result_array();
    }

    //Finds an employee by email address, or returns NULL when none matches.
    public function findEmployeeByEmail($email) {
        $this->db->where('email', strtolower((string) $email));
        $query = $this->db->get('tbl_employees');

        if (!$query) {
            throw new AppException('Q07');
        }

        return $query->num_rows() > 0 ? $query->row_array() : NULL;
    }

    public function createEmployee($data) {
        if (empty($data)) {
            throw new AppException('D01');
        }

        if (!$this->db->insert('tbl_employees', $data)) {
            throw new AppException('Q07');
        }

        return $this->db->insert_id();
    }

    public function updateEmployee($id, $data) {
        if (empty($data)) {
            throw new AppException('D01');
        }

        $this->db->where('employee_id', $id);
        return $this->db->update('tbl_employees', $data);
    }

    //----------------------------------------------------------------------
    // Attendance
    //----------------------------------------------------------------------

    //Returns the attendance history for an employee as a decoded array of
    //{date, time_in, time_out} records. Missing or malformed JSON yields [].
    public function getAttendance($employee_id) {
        $employee = $this->findByEmployeeId($employee_id);

        return self::decodeAttendance($employee['attendance_history'] ?? '');
    }

    //Decodes an attendance_history JSON string into an array of records.
    public static function decodeAttendance($raw) {
        if (empty($raw)) {
            return [];
        }

        $records = json_decode((string) $raw, true);

        return is_array($records) ? array_values($records) : [];
    }

    //Clocks the employee in for today. Appends a {date, time_in, time_out}
    //record where time_out stays null until out. Returns the new record.
    public function timeIn($employee_id, int $timestamp = NULL) {
        $timestamp = $timestamp ?: time();
        $records = $this->getAttendance($employee_id);
        $today = date('Y-m-d', $timestamp);
        $now = date('H:i', $timestamp);

        foreach ($records as $record) {
            if (($record['date'] ?? '') === $today && empty($record['time_out'])) {
                return ['ok' => false, 'message' => 'You are already clocked in today.'];
            }
        }

        $record = ['date' => $today, 'time_in' => $now, 'time_out' => null];
        $records[] = $record;

        $this->storeAttendance($employee_id, $records);

        return ['ok' => true, 'record' => $record];
    }

    //Clocks the employee out, filling the time_out of today's open record.
    public function timeOut($employee_id, int $timestamp = NULL) {
        $timestamp = $timestamp ?: time();
        $records = $this->getAttendance($employee_id);
        $today = date('Y-m-d', $timestamp);
        $now = date('H:i', $timestamp);

        foreach ($records as $i => $record) {
            if (($record['date'] ?? '') === $today && empty($record['time_out'])) {
                $records[$i]['time_out'] = $now;
                $record['time_out'] = $now;

                $this->storeAttendance($employee_id, $records);

                return ['ok' => true, 'record' => $records[$i]];
            }
        }

        return ['ok' => false, 'message' => 'No active clock-in found for today.'];
    }

    //Counts attendance records per date across all employees for records on
    //or after the given date. Returns ['date' => count] keyed by Y-m-d.
    public function attendanceTotalsSince($since) {
        $employees = $this->getAllEmployees();
        $totals = [];

        foreach ($employees as $employee) {
            foreach (self::decodeAttendance($employee['attendance_history'] ?? '') as $record) {
                $date = (string) ($record['date'] ?? '');

                if ($date < $since || $date === '') {
                    continue;
                }

                $totals[$date] = ($totals[$date] ?? 0) + 1;
            }
        }

        return $totals;
    }

    //Renames/rewards nothing: persists the JSON string back to the row.
    private function storeAttendance($employee_id, array $records) {
        $json = json_encode(array_values($records));

        $this->db->where('employee_id', (int) $employee_id);
        $this->db->update('tbl_employees', ['attendance_history' => $json]);
    }
}