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

	// ----------------------------------------------------------------
	// Edge cases
	// ----------------------------------------------------------------

	public function testNativeExceptionCodeIsAlwaysZero(): void {
		// The human-facing identifier lives in getErrorCode(); the PHP
		// exception code is always 0 so plain Exception::getCode() never
		// surfaces the letter code.
		$e = new AppException('Q01');
		$this->assertSame(0, $e->getCode());
		$this->assertSame('Q01', $e->getErrorCode());
	}

	public function testChainsPreviousException(): void {
		$previous = new RuntimeException('root cause');
		$e = new AppException('Q01', [], $previous);

		$this->assertSame($previous, $e->getPrevious());
	}

	public function testReplacesEveryOccurrenceOfAPlaceholder(): void {
		// str_replace replaces all matches, not just the first.
		$ci = CI_Controller::get_instance();
		$ci->config->items['error_codes'] = [
			'R01' => 'Record {id} and {id}',
		];
		$e = new AppException('R01', ['id' => 5]);
		$this->assertSame('Record 5 and 5', $e->getMessage());
	}

	public function testSubstitutesFalsyNumericValueAsString(): void {
		$ci = CI_Controller::get_instance();
		$ci->config->items['error_codes'] = ['N01' => 'Count is {n}'];
		$e = new AppException('N01', ['n' => 0]);
		$this->assertSame('Count is 0', $e->getMessage());
	}

	public function testIgnoresUnknownParamKeys(): void {
		// Extra params whose keys are not in the template are silently dropped.
		$e = new AppException('P02', ['id' => 7, 'extra' => 'ignored']);
		$this->assertSame('No tech part exists with ID 7.', $e->getMessage());
	}

	public function testLeavesBlankPlaceholderWhenParamMissing(): void {
		// Template is written for {id} but no param given -> literal {id} remains.
		$e = new AppException('P01');
		$this->assertStringContainsString('{id}', $e->getMessage());
	}

	public function testEmptyCodeUsesGenericMessageButKeepsCode(): void {
		$e = new AppException('');
		$this->assertSame('An unexpected error occurred', $e->getMessage());
		$this->assertSame('', $e->getErrorCode());
		$this->assertSame('An unexpected error occurred []', $e->toToast());
	}

	public function testGenericFallbackWhenConfigNotAnObject(): void {
		// resolveMessage() guards on isset/config-object/method_exists.
		$ci = CI_Controller::get_instance();
		$ci->config = 'not-an-object';

		$e = new AppException('Q01');
		$this->assertSame('An unexpected error occurred', $e->getMessage());
	}

	public function testGenericFallbackWhenConfigUnsetEntirely(): void {
		$ci = CI_Controller::get_instance();
		unset($ci->config);

		$e = new AppException('Q01');
		$this->assertSame('An unexpected error occurred', $e->getMessage());
	}

	public function testToToastIncludesPlaceholderResolvedValue(): void {
		$e = new AppException('P02', ['id' => 100002]);
		$this->assertSame('No tech part exists with ID 100002. [P02]', $e->toToast());
	}
}
