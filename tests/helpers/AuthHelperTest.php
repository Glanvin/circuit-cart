<?php

use PHPUnit\Framework\TestCase;

class AuthHelperTest extends TestCase {

    private $ci;
    private $session;

    protected function setUp(): void {
        $this->ci      = new CI_Controller();
        $this->session = new FakeSession();
        $this->ci->session = $this->session;
        $this->ci->config = new FakeConfig();
        $this->ci->config->items['error_codes'] = error_codes();
    }

    public function testCheckSessionThrowsWhenSessionIsEmpty() {
        try {
            check_session();
            $this->fail('Expected AppException');
        } catch (AppException $e) {
            $this->assertSame('S01', $e->getErrorCode());
            $this->assertSame('The Stored Session is invalid. Please login again', $e->getMessage());
            $this->assertSame('The Stored Session is invalid. Please login again [S01]', $e->toToast());
        }
    }

    public function testCheckSessionThrowsWhenUsernameIsNull() {
        $this->session->set_userdata('username', NULL);

        $this->expectException(AppException::class);
        $this->expectExceptionMessage('The Stored Session is invalid. Please login again');

        check_session();
    }

    public function testCheckSessionPassesWithValidUsername() {
        $this->session->set_userdata('username', 'alice');

        check_session();

        $this->assertTrue(TRUE);
    }
}
