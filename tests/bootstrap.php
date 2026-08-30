<?php

/*
| Test bootstrap for PHPUnit (Option A: lightweight CI stubs)
|
| Defines the CodeIgniter constants the app files expect, provides a
| minimal fake CI_Controller / CI_Model / get_instance() so that
| application libraries and models can be instantiated without booting
| the full framework or connecting to a database.
*/

define('BASEPATH', __DIR__ . '/../system/');
define('APPPATH', __DIR__ . '/../application/');
define('ENVIRONMENT', 'testing');
define('CI_VERSION', '3.1.13');
define('SHOW_DEBUG_BACKTRACE', TRUE);

// ---------------------------------------------------------------------------
// Fake session used by Toastify and auth tests
// ---------------------------------------------------------------------------
class FakeSession {
	public $flashdata = [];
	private $userdata = [];

	public function set_flashdata($key, $value = NULL) {
		if (is_array($key)) {
			foreach ($key as $k => $v) {
				$this->flashdata[$k] = $v;
			}
			return;
		}
		$this->flashdata[$key] = $value;
	}

	public function flashdata($key = NULL) {
		if ($key === NULL) {
			return $this->flashdata;
		}
		return $this->flashdata[$key] ?? NULL;
	}

	/**
	 * Retrieves a single userdata value, or the entire userdata array.
	 *
	 * @param  string|null $key  Key to retrieve, or NULL for all userdata.
	 * @return mixed|null
	 */
	public function userdata($key = NULL) {
		if ($key === NULL) {
			return $this->userdata;
		}
		return $this->userdata[$key] ?? NULL;
	}

	/**
	 * Stores a value (or an array of values) in userdata.
	 *
	 * @param string|array $key    Single key or associative array of key/value pairs.
	 * @param mixed        $value  Value to store (ignored when $key is an array).
	 */
	public function set_userdata($key, $value = NULL) {
		if (is_array($key)) {
			foreach ($key as $k => $v) {
				$this->userdata[$k] = $v;
			}
			return;
		}
		$this->userdata[$key] = $value;
	}
}

// ---------------------------------------------------------------------------
// Minimal CI core stubs
// ---------------------------------------------------------------------------
#[\AllowDynamicProperties]
class CI_Controller {
	private static $instance;

	public function __construct() {
		self::$instance = $this;
	}

	public static function &get_instance() {
		return self::$instance;
	}
}

#[\AllowDynamicProperties]
class CI_Model {
	public function __construct() {
		log_message('info', 'Model Class Initialized');
	}
	public function __get($key) {
		// Emulate CI_Model magic loader: pull from the controller instance
		$ci = CI_Controller::get_instance();
		return isset($ci->{$key}) ? $ci->{$key} : NULL;
	}
}

function &get_instance() {
	return CI_Controller::get_instance();
}

function log_message($level, $message) {
	// no-op in tests
}

// ---------------------------------------------------------------------------
// Load test support fakes
// ---------------------------------------------------------------------------
require_once __DIR__ . '/support/FakeDb.php';

// ---------------------------------------------------------------------------
// Load the application classes under test
// ---------------------------------------------------------------------------
require_once APPPATH . 'libraries/Toastify.php';
require_once APPPATH . 'models/PartsModel.php';
require_once APPPATH . 'models/UserModel.php';

// ---------------------------------------------------------------------------
// Load application helpers
// ---------------------------------------------------------------------------
require_once APPPATH . 'helpers/my_helper.php';
