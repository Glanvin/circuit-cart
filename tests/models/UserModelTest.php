<?php

use PHPUnit\Framework\TestCase;

/**
 * Security and robustness tests for UserModel.
 *
 * Verifies that user input is passed to the query builder as bound
 * values (never concatenated into raw SQL) and that repeated sign-ins
 * scale without state corruption.
 */
class UserModelTest extends TestCase {

    private $ci;
    private $db;
    private $model;

    protected function setUp(): void {
        $this->ci    = new CI_Controller();
        $this->db    = new FakeDb();
        $this->ci->db = $this->db;
        $this->model = new UserModel();
    }

    // ----------------------------------------------------------------
    // SQL injection
    // ----------------------------------------------------------------

    public function testSignInUserBindsUsernameAsWhereValue() {
        $payload = "admin' OR '1'='1";

        $this->model->signInUser($payload);

        // Payload must sit in the VALUE slot only; field stays hardcoded.
        $this->assertSame([['username', $payload]], $this->db->whereCalls);
    }

    public function testSignInUserUsesHardcodedTable() {
        $this->model->signInUser("admin' OR '1'='1");

        $this->assertSame('tbl_users', $this->db->lastTable);
    }

    public function testRegisterUserPassesDataToPreparedInsert() {
        $payload = [
            'username' => "bob'; DROP TABLE users;--",
            'email'    => "bob@example.com';--",
            'password' => 'secret123',
        ];

        $result = $this->model->registerUser($payload);

        $this->assertTrue($result);
        $this->assertSame('tbl_users', $this->db->lastInsertTable);
        $this->assertSame($payload, $this->db->lastInsertData);
    }

    public function testSignInUserThrowsOnEmptyUsername() {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(5);
        $this->expectExceptionMessage('Data is empty');

        $this->model->signInUser('');
    }

    // ----------------------------------------------------------------
    // Concurrency / many users (deterministic correctness)
    // ----------------------------------------------------------------

    public function testSignInUserHandlesManyDistinctUsers() {
        $this->db->rows = [
            ['username' => 'alice', 'password' => 'secret', 'role' => 'admin'],
        ];

        $count = 1000;
        for ($i = 0; $i < $count; $i++) {
            $this->db->reset();
            $this->db->rows = [
                ['username' => "user{$i}", 'password' => "pass{$i}", 'role' => 'staff'],
            ];

            $result = $this->model->signInUser("user{$i}");

            $this->assertSame("user{$i}", $result['username']);
            $this->assertSame("pass{$i}", $result['password']);
            // Exactly one where() per call -> no state bleed between calls.
            $this->assertCount(1, $this->db->whereCalls);
            $this->assertSame('tbl_users', $this->db->lastTable);
        }
    }
}
