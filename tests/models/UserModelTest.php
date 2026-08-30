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
        $this->ci->config = new FakeConfig();
        $this->ci->config->items['error_codes'] = error_codes();
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
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Data is empty');

        $this->model->signInUser('');
    }

    // ----------------------------------------------------------------
    // findUserById / findUserByUsername
    // ----------------------------------------------------------------

    public function testFindUserByIdReturnsRow() {
        $this->db->rows = [['id' => 7, 'username' => 'alice']];

        $user = $this->model->findUserById(7);

        $this->assertSame('alice', $user['username']);
    }

    public function testFindUserByUsernameReturnsRow() {
        $this->db->rows = [['username' => 'alice', 'role' => 'staff']];

        $user = $this->model->findUserByUsername('alice');

        $this->assertSame('alice', $user['username']);
    }

    public function testFindUserByUsernameBindsUsernameAsWhereValue() {
        $this->db->rows = [['username' => 'safe', 'role' => 'staff']];
        $payload = "admin' OR '1'='1";

        $this->model->findUserByUsername($payload);

        // The username must be bound as a bare value, never concatenated into
        // raw SQL (correct: ['username', $payload]).
        $this->assertSame([['username', $payload]], $this->db->whereCalls);
    }

    public function testFindUserByIdThrowsOnQueryFailure() {
        $this->db->failQuery = TRUE;

        try {
            $this->model->findUserById(7);
            $this->fail('Expected AppException');
        } catch (AppException $e) {
            $this->assertSame('Q03', $e->getErrorCode());
            $this->assertSame('Failed to get user', $e->getMessage());
        }
    }

    public function testFindUserByIdThrowsWhenNotFound() {
        $this->db->rows = [];

        try {
            $this->model->findUserById(7);
            $this->fail('Expected AppException');
        } catch (AppException $e) {
            $this->assertSame('P01', $e->getErrorCode());
            $this->assertSame('No records of 7', $e->getMessage());
        }
    }

    public function testFindUserByUsernameThrowsOnQueryFailure() {
        $this->db->failQuery = TRUE;

        try {
            $this->model->findUserByUsername('alice');
            $this->fail('Expected AppException');
        } catch (AppException $e) {
            $this->assertSame('Q03', $e->getErrorCode());
            $this->assertSame('Failed to get user', $e->getMessage());
        }
    }

    public function testFindUserByUsernameThrowsWhenNotFound() {
        $this->db->rows = [];

        try {
            $this->model->findUserByUsername('alice');
            $this->fail('Expected AppException');
        } catch (AppException $e) {
            $this->assertSame('P01', $e->getErrorCode());
            $this->assertSame('No records of alice', $e->getMessage());
        }
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
