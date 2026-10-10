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
	 * Error payload for db->error() parity with CI.
	 */
	public function error() {
		return ['code' => 0, 'message' => ''];
	}

	public function where($field, $value = NULL) {
		$this->whereCalls[] = [$field, $value];
		return $this;
	}

	public function order_by($field, $direction = 'ASC') {
		return $this;
	}

	public function select($field) {
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

	public function get($table) {
		$this->lastTable = $table;

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
