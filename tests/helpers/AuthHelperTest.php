<?php

use PHPUnit\Framework\TestCase;

class AuthHelperTest extends TestCase {

    private $ci;
    private $session;

    protected function setUp(): void {
        $this->ci      = new CI_Controller();
        $this->session = new FakeSession();
        $this->ci->session = $this->session;
    }

    public function testCheckSessionThrowsWhenSessionIsEmpty() {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(4);
        $this->expectExceptionMessage('The Stored Session is invalid. Please login again');

        check_session();
    }

    public function testCheckSessionThrowsWhenUsernameIsNull() {
        $this->session->set_userdata('username', NULL);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(4);
        $this->expectExceptionMessage('The Stored Session is invalid. Please login again');

        check_session();
    }

    public function testCheckSessionPassesWithValidUsername() {
        $this->session->set_userdata('username', 'alice');

        check_session();

        $this->assertTrue(TRUE);
    }
}
