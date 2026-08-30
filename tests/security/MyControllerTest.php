<?php

use PHPUnit\Framework\TestCase;

/**
 * A concrete MY_Controller subclass with a configurable protected $requiredRole,
 * so the authorization + render behaviour of the base class can be tested.
 */
class TestMyController extends MY_Controller {

	protected string $navbar = 'templates/navbar_fake';

	/** @var string|null role required, configured via constructor. */
	protected ?string $requiredRole = NULL;

	public function __construct($session = NULL, $toastify = NULL, $requiredRole = NULL) {
		// Inject fakes BEFORE the base constructor runs its session/role check.
		if ($session !== NULL) {
			$this->session = $session;
		}
		if ($toastify !== NULL) {
			$this->toastify = $toastify;
		}
		if ($requiredRole !== NULL) {
			$this->requiredRole = $requiredRole;
		}
		parent::__construct();
	}

	public function index() {
		$this->render('pages/dummy', ['title' => 'Dummy']);
	}
}

class MyControllerTest extends TestCase {

	private $session;
	private $toastify;

	protected function setUp(): void {
		redirect_stop(TRUE);
		$GLOBALS['redirect_target'] = NULL;

		$this->session  = new FakeSession();
		$this->toastify = new FakeToastify($this->session);
	}

	private function makeController($requiredRole = NULL): TestMyController {
		return new TestMyController($this->session, $this->toastify, $requiredRole);
	}

	// ----------------------------------------------------------------
	// Constructor: session validation
	// ----------------------------------------------------------------

	public function testConstructorThrowsForMissingSession() {
		$this->expectException(RedirectException::class);

		$this->makeController();
	}

	public function testMissingSessionRedirectsToAuthWithErrorToast() {
		try {
			$this->makeController();
			$this->fail('Expected RedirectException');
		} catch (RedirectException $e) {
		}

		$this->assertSame('auth', redirect_target());
		$this->assertNotEmpty($this->session->flashdata('toast_error'));
	}

	public function testConstructorAcceptsValidSession() {
		$this->session->set_userdata('username', 'alice');

		$controller = $this->makeController();

		$this->assertInstanceOf(TestMyController::class, $controller);
		$this->assertNull(redirect_target());
	}

	// ----------------------------------------------------------------
	// Constructor: role enforcement
	// ----------------------------------------------------------------

	public function testLackingRequiredRoleRedirectsToHomeWithErrorToast() {
		$this->session->set_userdata('username', 'alice');
		$this->session->set_userdata('role', 'staff');

		try {
			$this->makeController('admin');
			$this->fail('Expected RedirectException');
		} catch (RedirectException $e) {
		}

		$this->assertSame('home', redirect_target());
		$this->assertNotEmpty($this->session->flashdata('toast_error'));
	}

	public function testMatchingRoleAccepted() {
		$this->session->set_userdata('username', 'alice');
		$this->session->set_userdata('role', 'admin');

		$controller = $this->makeController('admin');

		$this->assertInstanceOf(TestMyController::class, $controller);
		$this->assertNull(redirect_target());
	}

	public function testAnyRoleAcceptedWhenRequiredRoleIsNull() {
		$this->session->set_userdata('username', 'alice');
		$this->session->set_userdata('role', 'staff');

		$controller = $this->makeController(NULL);

		$this->assertInstanceOf(TestMyController::class, $controller);
		$this->assertNull(redirect_target());
	}

	// ----------------------------------------------------------------
	// render(): template shell behaviour
	// ----------------------------------------------------------------

	public function testRenderLoadsHeaderNavbarPageAndFooterByDefault() {
		$this->session->set_userdata('username', 'alice');
		$controller  = $this->makeController();
		$loader      = new FakeLoader();
		$controller->load = $loader;

		$controller->index();

		$views = array_column($loader->viewCalls, 'view');
		$this->assertContains('templates/header', $views);
		$this->assertContains('templates/navbar_fake', $views);
		$this->assertContains('pages/dummy', $views);
		$this->assertContains('templates/footer', $views);
	}

	public function testRenderSkipsFooterWhenDisabled() {
		$this->session->set_userdata('username', 'alice');
		$controller  = $this->makeController();
		$loader      = new FakeLoader();
		$controller->load = $loader;

		$this->invokeRender($controller, ['enable_footer' => FALSE]);

		$views = array_column($loader->viewCalls, 'view');
		$this->assertNotContains('templates/footer', $views);
	}

	public function testRenderDefaultsTitleAndEnableFooter() {
		$this->session->set_userdata('username', 'alice');
		$controller  = $this->makeController();
		$loader      = new FakeLoader();
		$controller->load = $loader;

		$this->invokeRender($controller, ['title' => 'Stocks']);

		$header = null;
		foreach ($loader->viewCalls as $call) {
			if ($call['view'] === 'templates/header') {
				$header = $call['data'];
			}
		}
		$this->assertNotNull($header);
		$this->assertSame('Stocks', $header['title']);
		$this->assertTrue($header['enable_footer']);
	}

	private function invokeRender($controller, array $data = []) {
		$ref = new ReflectionMethod(TestMyController::class, 'render');
		$ref->invoke($controller, 'pages/dummy', $data);
	}
}
