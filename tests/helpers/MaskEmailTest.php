<?php

use PHPUnit\Framework\TestCase;

class MaskEmailTest extends TestCase {

	public function testMasksLocalPartKeepingDomainVisible() {
		$this->assertSame('a****@example.com', mask_email('alice@example.com'));
	}

	public function testMasksMultiCharacterLocalPart() {
		$this->assertSame('j*******@gmail.com', mask_email('john_doe@gmail.com'));
	}

	public function testLeavesSingleCharacterLocalPartUnmasked() {
		$this->assertSame('a@b.co', mask_email('a@b.co'));
	}

	public function testReturnsEmptyStringWhenEmpty() {
		$this->assertSame('', mask_email(''));
	}

	public function testReturnsInputWithoutAtSignUnchanged() {
		$this->assertSame('not-an-email', mask_email('not-an-email'));
	}
}