<?php

use PHPUnit\Framework\TestCase;

class PasswordResetTest extends TestCase {

	private $controller;
	private $session;
	private $toastify;
	private $input;
	private $config;
	private $db;
	private $loader;
	private $email;

	protected function setUp(): void {
		redirect_stop(FALSE);
		$GLOBALS['redirect_target'] = NULL;

		$controller = new AuthController();

		$controller->session  = $this->session  = new FakeSession();
		$controller->toastify = $this->toastify = new FakeToastify($this->session);
		$controller->input    = $this->input    = new FakeInput();
		$controller->config   = $this->config   = new FakeConfig();
		$controller->db       = $this->db       = new FakeDb();
		$controller->load     = $this->loader   = new FakeLoader();
		$controller->email    = $this->email    = new FakeEmail();
		$controller->UserModel = new UserModel();

		$this->controller = $controller;
	}

	private function views(): array {
		return array_column($this->loader->viewCalls, 'view');
	}

	// ----------------------------------------------------------------
	// forgot()
	// ----------------------------------------------------------------

	public function testForgotRendersFormForGuests() {
		$this->controller->forgot();
		$this->assertContains('auth/forgot_password', $this->views());
	}

	public function testForgotRedirectsSignedInUser() {
		$this->session->set_userdata('username', 'alice');
		$this->controller->forgot();
		$this->assertSame('settings', redirect_target());
	}

	// ----------------------------------------------------------------
	// requestReset()
	// ----------------------------------------------------------------

	public function testRequestResetRejectsInvalidEmail() {
		$this->input->postData = ['email' => 'not-an-email'];

		$this->controller->requestReset();

		$this->assertSame('auth/forgot', redirect_target());
		$this->assertNotNull($this->session->flashdata('toast_error'));
	}

	public function testRequestResetStoresHashedTokenAndEmailsForKnownUser() {
		$this->db->rows = [['id' => 7, 'email' => 'alice@example.com']];
		$this->input->postData = ['email' => 'Alice@Example.com'];

		$this->controller->requestReset();

		// Insert recorded into tbl_password_resets
		$this->assertSame('tbl_password_resets', $this->db->lastInsertTable);
		$this->assertArrayHasKey('token_hash', $this->db->lastInsertData);
		$this->assertSame(7, $this->db->lastInsertData['user_id']);
		// Only the hash is stored, never the raw token
		$this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $this->db->lastInsertData['token_hash']);
		$this->assertSame('auth/signin', redirect_target());
	}

	public function testRequestResetDoesNotRevealUnknownEmail() {
		$this->db->rows = [];
		$this->input->postData = ['email' => 'ghost@example.com'];

		$this->controller->requestReset();

		$this->assertNull($this->db->lastInsertTable);
		$this->assertSame('auth/signin', redirect_target());
		$this->assertNotNull($this->session->flashdata('toast_success'));
	}

	// ----------------------------------------------------------------
	// reset()
	// ----------------------------------------------------------------

	public function testResetRendersInvalidForBadToken() {
		$this->controller->reset('short');
		$this->assertContains('auth/reset_password', $this->views());
		// token_valid is passed as false
		$call = end($this->loader->viewCalls);
		$this->assertFalse($call['data']['token_valid']);
	}

	public function testResetRendersValidForLiveToken() {
		// Build a valid token/hash pair
		$token = str_repeat('a', 64);
		$hash = hash('sha256', $token);
		$this->db->rows = [[
			'id' => 3,
			'user_id' => 9,
			'token_hash' => $hash,
			'expires_at' => date('Y-m-d H:i:s', time() + 600),
		]];

		$this->controller->reset($token);

		$call = end($this->loader->viewCalls);
		$this->assertTrue($call['data']['token_valid']);
	}

	public function testResetTreatsExpiredTokenAsInvalid() {
		$token = str_repeat('b', 64);
		$hash = hash('sha256', $token);
		$this->db->rows = [[
			'id' => 4,
			'user_id' => 9,
			'token_hash' => $hash,
			'expires_at' => date('Y-m-d H:i:s', time() - 60),
		]];

		$this->controller->reset($token);

		$call = end($this->loader->viewCalls);
		$this->assertFalse($call['data']['token_valid']);
	}

	// ----------------------------------------------------------------
	// applyReset()
	// ----------------------------------------------------------------

	public function testApplyResetRejectsShortPassword() {
		$this->input->postData = ['token' => 'x', 'password' => 'abc', 'confirm_password' => 'abc'];

		$this->controller->applyReset();

		$this->assertSame('auth/reset/x', redirect_target());
		$this->assertNotNull($this->session->flashdata('toast_error'));
	}

	public function testApplyResetRejectsMismatch() {
		$this->input->postData = ['token' => 'x', 'password' => 'abcdef', 'confirm_password' => 'ghijkl'];

		$this->controller->applyReset();

		$this->assertSame('auth/reset/x', redirect_target());
		$this->assertNotNull($this->session->flashdata('toast_error'));
	}

	public function testApplyResetUpdatesPasswordAndMarksTokenUsed() {
		$token = str_repeat('c', 64);
		$hash = hash('sha256', $token);
		$this->db->rows = [[
			'id' => 12,
			'user_id' => 5,
			'token_hash' => $hash,
			'expires_at' => date('Y-m-d H:i:s', time() + 600),
		]];
		$this->input->postData = ['token' => $token, 'password' => 'newsecret', 'confirm_password' => 'newsecret'];

		$this->controller->applyReset();

		$tables = array_column($this->db->updateCalls, 'table');
		$this->assertContains('tbl_users', $tables);
		$this->assertContains('tbl_password_resets', $tables);

		// Password was hashed, not stored raw
		foreach ($this->db->updateCalls as $call) {
			if ($call['table'] === 'tbl_users') {
				$this->assertArrayHasKey('password', $call['data']);
				$this->assertTrue(password_verify('newsecret', $call['data']['password']));
			}
		}

		$this->assertSame('auth/signin', redirect_target());
	}

	public function testApplyResetRejectsExpiredToken() {
		$token = str_repeat('d', 64);
		$hash = hash('sha256', $token);
		$this->db->rows = [[
			'id' => 13,
			'user_id' => 5,
			'token_hash' => $hash,
			'expires_at' => date('Y-m-d H:i:s', time() - 10),
		]];
		$this->input->postData = ['token' => $token, 'password' => 'newsecret', 'confirm_password' => 'newsecret'];

		$this->controller->applyReset();

		foreach ($this->db->updateCalls as $call) {
			$this->assertNotSame('tbl_users', $call['table']);
		}
		$this->assertSame('auth/forgot', redirect_target());
	}
}
