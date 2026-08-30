<?php

use PHPUnit\Framework\TestCase;

class IsInvalidTest extends TestCase {

	private $ci;

	protected function setUp(): void {
		$this->ci = new CI_Controller();
	}

	public function testReturnsEmptyWhenValidationLibraryNotLoaded() {
		// No form_validation property -> early return ''.
		$this->assertSame('', is_invalid('username'));
	}

	public function testReturnsEmptyWhenFieldHasNoError() {
		$this->ci->form_validation    = new stdClass();
		$this->ci->validationErrors    = ['username' => ''];

		$this->assertSame('', is_invalid('username'));
	}

	public function testReturnsErrorClassWhenFieldHasError() {
		$this->ci->form_validation = new stdClass();
		$this->ci->validationErrors = ['username' => 'Username cannot be blank'];

		$this->assertSame('is-invalid', is_invalid('username'));
	}

	public function testUsesProvidedErrorClass() {
		$this->ci->form_validation = new stdClass();
		$this->ci->validationErrors = ['email' => 'Email invalid'];

		$this->assertSame('has-error', is_invalid('email', 'has-error'));
	}

	public function testIgnoresErrorsForOtherFields() {
		$this->ci->form_validation = new stdClass();
		$this->ci->validationErrors = ['username' => 'error'];

		$this->assertSame('', is_invalid('email'));
	}
}
