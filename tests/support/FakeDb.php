<?php

class FakeDb {

	/**
	 * Canned result rows returned by get().
	 * @var array
	 */
	public $rows = [];

	/**
	 * When TRUE, get() returns FALSE to simulate a failed query.
	 * @var bool
	 */
	public $failQuery = FALSE;

	/**
	 * Record of where() calls: [['field', 'value'], ...]
	 * @var array
	 */
	public $whereCalls = [];

	/**
	 * Table passed to the last get() call.
	 * @var string|null
	 */
	public $lastTable;

	/**
	 * Table passed to the last insert() call.
	 * @var string|null
	 */
	public $lastInsertTable;

	/**
	 * Data array passed to the last insert() call.
	 * @var array|null
	 */
	public $lastInsertData;

	/**
	 * Record of update() calls: [['table' => ..., 'data' => ...], ...]
	 * @var array
	 */
	public $updateCalls = [];

	/**
	 * Value returned by the next insert_id() call.
	 * @var int
	 */
	public $insertId = 1;

	/**
	 * Transaction result returned by trans_status().
	 * @var bool
	 */
	public $transStatus = TRUE;

	/**
	 * Values passed to set(), including arithmetic update expressions.
	 * @var array
	 */
	public $setCalls = [];

	/**
	 * Value returned by affected_rows().
	 * @var int
	 */
	public $affectedRows = 1;

	/**
	 * Value returned by count_all_results().
	 * @var int
	 */
	public $countAllResults = 0;

	/**
	 * Error payload for db->error() parity with CI.
	 */
	public function error() {
		return ['code' => 0, 'message' => ''];
	}

	public function where($field, $value = NULL) {
		$this->whereCalls[] = [$field, $value];
		return $this;
	}

	public function or_where($field, $value = NULL) {
		$this->whereCalls[] = ['OR ' . $field, $value];
		return $this;
	}

	public function where_in($field, $values = []) {
		$this->whereCalls[] = [$field . ' IN', $values];
		return $this;
	}

	public function where_not_in($field, $values = []) {
		$this->whereCalls[] = [$field . ' NOT IN', $values];
		return $this;
	}

	public function group_start() {
		return $this;
	}

	public function group_end() {
		return $this;
	}

	public function like($field, $match = '', $side = 'both') {
		return $this;
	}

	public function or_like($field, $match = '', $side = 'both') {
		return $this;
	}

	/**
	 * Record of limit() calls: [[value, offset], ...]
	 * @var array
	 */
	public $limitCalls = [];

	public function limit($value, $offset = 0) {
		$this->limitCalls[] = [$value, $offset];
		return $this;
	}

	public function order_by($field, $direction = 'ASC') {
		return $this;
	}

	public function select($field, $escape = NULL) {
		return $this;
	}

	public function from($table) {
		$this->lastTable = $table;
		return $this;
	}

	public function join($table, $condition, $type = '') {
		return $this;
	}

	public function distinct() {
		return $this;
	}

	public function insert($table, $data) {
		$this->lastInsertTable = $table;
		$this->lastInsertData  = $data;
		return ! $this->failQuery;
	}

	public function insert_id() {
		return $this->insertId;
	}

	public function set($key, $value = '', $escape = NULL) {
		$this->setCalls[] = [$key, $value, $escape];
		return $this;
	}

	public function update($table, $data = NULL) {
		$this->updateCalls[] = ['table' => $table, 'data' => $data];
		return ! $this->failQuery;
	}

	/**
	 * Record of delete() calls: [['table' => ..., 'where' => [...]], ...]
	 * @var array
	 */
	public $deleteCalls = [];

	public function delete($table, $where = NULL) {
		$this->deleteCalls[] = ['table' => $table, 'where' => $where];
		return ! $this->failQuery;
	}

	public function trans_start() {
		return TRUE;
	}

	public function trans_complete() {
		return TRUE;
	}

	public function trans_status() {
		return $this->transStatus;
	}

	public function trans_rollback() {
		$this->transStatus = FALSE;
		return TRUE;
	}

	public function affected_rows() {
		return $this->affectedRows;
	}

	public function count_all_results($table = '') {
		if ($table !== '') {
			$this->lastTable = $table;
		}
		return $this->countAllResults;
	}

	public function get($table = NULL) {
		if ($table !== NULL) {
			$this->lastTable = $table;
		}

		if ($this->failQuery) {
			return FALSE;
		}

		return new FakeDbResult($this->rows);
	}

	/**
	 * Clears all recorded/state between tests or iterations.
	 */
	public function reset() {
		$this->rows            = [];
		$this->failQuery       = FALSE;
		$this->whereCalls      = [];
		$this->lastTable       = NULL;
		$this->lastInsertTable = NULL;
		$this->lastInsertData  = NULL;
		$this->updateCalls     = [];
		$this->insertId        = 1;
		$this->transStatus     = TRUE;
		$this->setCalls        = [];
		$this->affectedRows    = 1;
		$this->countAllResults = 0;
		$this->limitCalls      = [];
		$this->deleteCalls     = [];
	}
}

class FakeDbResult {

	private $rows;

	public function __construct(array $rows) {
		$this->rows = $rows;
	}

	public function result_array() {
		return $this->rows;
	}

	public function row_array() {
		return $this->rows[0] ?? [];
	}

	public function num_rows() {
		return count($this->rows);
	}
}
