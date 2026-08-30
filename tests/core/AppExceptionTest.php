<?php

use PHPUnit\Framework\TestCase;

class AppExceptionTest extends TestCase {

	protected function setUp(): void {
		$ci = new CI_Controller();
		$ci->config = new FakeConfig();
		$ci->config->items['error_codes'] = error_codes();
	}

	public function testCarriesStandardizedCode(): void {
		$e = new AppException('Q01');
		$this->assertSame('Q01', $e->getErrorCode());
	}

	public function testResolvesMessageFromConfig(): void {
		$e = new AppException('D01');
		$this->assertSame('Data is empty', $e->getMessage());
	}

	public function testSubstitutesNamedPlaceholders(): void {
		$e = new AppException('P02', ['id' => 100001]);
		$this->assertSame('No tech part exists with ID 100001.', $e->getMessage());
	}

	public function testToToastAppendsCodeInBrackets(): void {
		$e = new AppException('S01');
		$this->assertSame('The Stored Session is invalid. Please login again [S01]', $e->toToast());
	}

	public function testFallsBackToGenericMessageWhenConfigMissing(): void {
		$ci = CI_Controller::get_instance();
		$ci->config = new FakeConfig(); // no error_codes key

		$e = new AppException('P99');
		$this->assertSame('An unexpected error occurred [P99]', $e->toToast());
	}

	public function testIsARealException(): void {
		$this->assertInstanceOf(Exception::class, new AppException('D01'));
	}
}
