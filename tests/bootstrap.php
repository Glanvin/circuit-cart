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

// Executable/system paths the app may reference.
define('FCPATH', BASEPATH . '../');
define('SYSDIR', 'system');

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

	/**
	 * Removes one key, an array of keys, or all userdata.
	 *
	 * @param string|array|null $key
	 */
	public function unset_userdata($key = NULL) {
		if ($key === NULL) {
			$this->userdata = [];
			return;
		}
		$keys = is_array($key) ? $key : [$key];
		foreach ($keys as $k) {
			unset($this->userdata[$k]);
		}
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
// Additional fakes used by controller tests
// ---------------------------------------------------------------------------

/**
 * Records view loads instead of rendering them.
 */
class FakeLoader {
	public $viewCalls = [];
	public $isLoaded = [
		'database'      => FALSE,
		'session'       => TRUE,
		'form_validation' => TRUE,
		'toastify'      => TRUE,
	];
	public $models = [];

	public function view($view, $data = NULL, $return = FALSE) {
		$this->viewCalls[] = ['view' => $view, 'data' => $data];
		if ($return) {
			// Real CI loader returns the rendered buffer when asked; hand
			// back a non-empty stub so deliver_email() builds a body.
			return '<p>rendered ' . $view . '</p>';
		}
		return NULL;
	}

	public function model($model) {
		$this->models[] = $model;
		return NULL;
	}
}

/**
 * Canned POST/GET input ($this->input->post() / get()).
 */
class FakeInput {
	public $postData = [];
	public $getData = [];
	public $serverData = [];

	public function post($key = NULL, $xss = FALSE) {
		if ($key === NULL) {
			return $this->postData;
		}
		return $this->postData[$key] ?? NULL;
	}

	public function get($key = NULL, $xss = FALSE) {
		if ($key === NULL) {
			return $this->getData;
		}
		return $this->getData[$key] ?? NULL;
	}

	public function server($key = NULL, $xss = FALSE) {
		if ($key === NULL) {
			return $this->serverData;
		}
		return $this->serverData[$key] ?? NULL;
	}
}

/**
 * Records output written by controllers (e.g. JSON cart endpoints).
 */
class FakeOutput {
	public $contentType = NULL;
	public $output = NULL;

	public function set_content_type($type) {
		$this->contentType = $type;
		return $this;
	}

	public function set_output($output) {
		$this->output = $output;
		return $this;
	}

	public function get_output() {
		return $this->output;
	}
}

/**
 * Records validation rules and returns a canned run() result.
 */
class FakeValidation {
	public $rules = [];
	public $runResult = TRUE;

	public function set_rules($rules) {
		$this->rules[] = $rules;
		return $this;
	}

	public function run() {
		return $this->runResult;
	}

	public function set_error_delimiters($prefix, $suffix) {
		return $this;
	}
}

/**
 * Canned config item lookups.
 */
class FakeConfig {
	public $items = [];

	public function item($key = NULL, $index = NULL) {
		if ($key === NULL) {
			return $this->items;
		}
		return $this->items[$key] ?? NULL;
	}
}

/**
 * Delegate to a specific FakeSession, avoiding the get_instance() capture that
 * the real Toastify performs. Lets controller tests inspect the exact session
 * that a controller writes toast flashdata to.
 */
class FakeToastify {
	public $session;

	public function __construct($session) {
		$this->session = $session;
	}

	public function success($message) {
		$this->session->set_flashdata('toast_success', $message);
	}

	public function error($message) {
		$this->session->set_flashdata('toast_error', $message);
	}

	public function info($message) {
		$this->session->set_flashdata('toast_info', $message);
	}
}

// ---------------------------------------------------------------------------
// Redirect / form helper stubs
// ---------------------------------------------------------------------------

/**
 * Set to TRUE to make redirect() record the destination and throw, mimicking
 * the real CI behaviour where redirect() terminates the request.
 */
$GLOBALS['redirect_stop'] = TRUE;
$GLOBALS['redirect_target'] = NULL;

function redirect($uri = '', $method = 'auto', $code = NULL) {
	$GLOBALS['redirect_target'] = $uri;
	if (!empty($GLOBALS['redirect_stop'])) {
		throw new RedirectException($uri);
	}
}

class RedirectException extends RuntimeException {
	public $uri;
	public function __construct($uri) {
		$this->uri = $uri;
		parent::__construct("Redirected to {$uri}");
	}
}

/**
 * Lets redirect() record the destination without halting execution.
 */
function redirect_stop(bool $stop = TRUE): void {
	$GLOBALS['redirect_stop'] = $stop;
}

function redirect_target(): ?string {
	return $GLOBALS['redirect_target'];
}

if (!function_exists('site_url')) {
	function site_url($uri = '', $protocol = NULL) {
		return 'http://localhost/' . ltrim($uri, '/');
	}
}

if (!function_exists('base_url')) {
	function base_url($uri = '', $protocol = NULL) {
		return 'http://localhost/' . ltrim($uri, '/');
	}
}

function form_error($field = '', $prefix = '', $suffix = '') {
	$ci = CI_Controller::get_instance();
	if (isset($ci->validationErrors[$field]) && $ci->validationErrors[$field]) {
		return $prefix . $ci->validationErrors[$field] . $suffix;
	}
	return '';
}

// ---------------------------------------------------------------------------
// Load test support fakes
// ---------------------------------------------------------------------------
require_once __DIR__ . '/support/FakeDb.php';
require_once __DIR__ . '/support/FakeEmail.php';

// ---------------------------------------------------------------------------
// Load the application classes under test
// ---------------------------------------------------------------------------
require_once APPPATH . 'core/AppException.php';
require_once APPPATH . 'libraries/Toastify.php';
require_once APPPATH . 'libraries/UserImporter.php';
require_once APPPATH . 'models/EmployeeModel.php';
require_once APPPATH . 'models/PartsModel.php';
require_once APPPATH . 'models/UserModel.php';
require_once APPPATH . 'models/PromoModel.php';
require_once APPPATH . 'models/WishlistModel.php';
require_once APPPATH . 'models/ReviewModel.php';
require_once APPPATH . 'models/SalesModel.php';
require_once APPPATH . 'models/RepairJobModel.php';
require_once APPPATH . 'models/RepairServiceModel.php';
require_once APPPATH . 'models/BookingModel.php';

// Returns the error_codes mapping loaded from the real config file.
function error_codes(): array {
	$config = [];
	require APPPATH . 'config/error_codes.php';
	return $config['error_codes'];
}

// ---------------------------------------------------------------------------
// Load application helpers
// ---------------------------------------------------------------------------
require_once APPPATH . 'helpers/my_helper.php';

// ---------------------------------------------------------------------------
// Load core + controllers under test
// ---------------------------------------------------------------------------
require_once APPPATH . 'core/MY_Controller.php';

require_once APPPATH . 'controllers/AuthController.php';
require_once APPPATH . 'controllers/CartController.php';
require_once APPPATH . 'controllers/CheckoutController.php';
require_once APPPATH . 'controllers/BrowseController.php';
require_once APPPATH . 'controllers/DashboardController.php';
require_once APPPATH . 'controllers/HomeController.php';
require_once APPPATH . 'controllers/ManagementController.php';
require_once APPPATH . 'controllers/PromoAdminController.php';
require_once APPPATH . 'controllers/StockController.php';
require_once APPPATH . 'controllers/WishlistController.php';
require_once APPPATH . 'controllers/ReviewController.php';
require_once APPPATH . 'controllers/ReviewAdminController.php';
require_once APPPATH . 'controllers/RepairServiceAdminController.php';
require_once APPPATH . 'controllers/BookingController.php';
require_once APPPATH . 'controllers/RepairJobController.php';

// ---------------------------------------------------------------------------
// Reset global redirect state once at boot
// ---------------------------------------------------------------------------
$GLOBALS['redirect_stop']   = TRUE;
$GLOBALS['redirect_target'] = NULL;

// ---------------------------------------------------------------------------
// Build a concrete MY_Controller subclass with session/toastify/db/load
// injected BEFORE the base constructor runs its session/role check.
// ---------------------------------------------------------------------------
function make_my_controller(string $class, $session, $toastify, $db = NULL, $loader = NULL) {
	$reflection = new ReflectionClass($class);
	$controller = $reflection->newInstanceWithoutConstructor();

	$controller->session  = $session;
	$controller->toastify = $toastify;
	$controller->input    = new FakeInput();
	$controller->config   = new FakeConfig();
	$controller->config->items['role_home'] = [
		'cashier'    => 'pos',
		'manager'    => 'employee/analytics',
		'technician' => 'storage/booking',
		'developer'  => 'storage/bugs',
		'logistics'  => 'inventory/stocks',
		'admin'      => 'employee/dashboard',
	];
	if ($db !== NULL) {
		$controller->db = $db;
		$controller->PartsModel = new PartsModel();
		$controller->UserModel  = new UserModel();
		$controller->PromoModel = new PromoModel();
		$controller->PromoModel->db = $db;
		$controller->WishlistModel = new WishlistModel();
		$controller->WishlistModel->db = $db;
		$controller->ReviewModel = new ReviewModel();
		$controller->ReviewModel->db = $db;
		$controller->SalesModel = new SalesModel();
		$controller->SalesModel->db = $db;
		$controller->EmployeeModel = new EmployeeModel();
		$controller->EmployeeModel->db = $db;
		$controller->RepairServiceModel = new RepairServiceModel();
		$controller->RepairServiceModel->db = $db;
		$controller->BookingModel = new BookingModel();
		$controller->BookingModel->db = $db;
		$controller->RepairJobModel = new RepairJobModel();
		$controller->RepairJobModel->db = $db;
	}
	$controller->output = new FakeOutput();
	$controller->form_validation = new FakeValidation();
	if ($loader !== NULL) {
		$controller->load = $loader;
	}

	// Run the real constructor chain (sets the CI singleton, session check, etc.)
	$ctor = $reflection->getConstructor();
	$ctor->invoke($controller);

	return $controller;
}
