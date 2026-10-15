<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin_users_helpers.php';

class FakeDbForAdminUsersTest
{
    public $existingUser = null;
    public $insertedTable = null;
    public $insertedData = null;

    public function fetchOne($sql, $params = [])
    {
        return $this->existingUser;
    }

    public function insert($table, $data)
    {
        $this->insertedTable = $table;
        $this->insertedData = $data;
        return 1;
    }
}

function assertTrue($condition, $message)
{
    if (!$condition) {
        throw new Exception("Assertion failed: " . $message);
    }
}

function assertThrows($callback, $expectedMessage)
{
    try {
        $callback();
        throw new Exception("Expected exception was not thrown.");
    } catch (Exception $e) {
        assertTrue($e->getMessage() === $expectedMessage, "Expected '{$expectedMessage}', got '{$e->getMessage()}'");
    }
}

function testAddCustomerSuccess()
{
    $db = new FakeDbForAdminUsersTest();
    addCustomerFromAdmin($db, [
        'full_name' => 'Customer One',
        'username' => 'customer1',
        'email' => 'customer1@example.com',
        'password' => 'strongpass123',
        'role' => 'admin'
    ]);

    assertTrue($db->insertedTable === 'users', "User should be inserted into users table");
    assertTrue($db->insertedData['role'] === 'customer', "Role should always be customer");
    assertTrue(password_verify('strongpass123', $db->insertedData['password_hash']), "Password hash should match");
}

function testAddCustomerDuplicate()
{
    $db = new FakeDbForAdminUsersTest();
    $db->existingUser = ['user_id' => 7];

    assertThrows(function () use ($db) {
        addCustomerFromAdmin($db, [
            'username' => 'existing',
            'email' => 'existing@example.com',
            'password' => 'strongpass123'
        ]);
    }, "Username or email already exists.");
}

function testAddCustomerInvalidEmail()
{
    $db = new FakeDbForAdminUsersTest();

    assertThrows(function () use ($db) {
        addCustomerFromAdmin($db, [
            'username' => 'userx',
            'email' => 'bad-email',
            'password' => 'strongpass123'
        ]);
    }, "Please enter a valid email address.");
}

function testAddCustomerShortPassword()
{
    $db = new FakeDbForAdminUsersTest();

    assertThrows(function () use ($db) {
        addCustomerFromAdmin($db, [
            'username' => 'userx',
            'email' => 'userx@example.com',
            'password' => '123'
        ]);
    }, "Password must be at least 8 characters long.");
}

function runAllTests()
{
    testAddCustomerSuccess();
    testAddCustomerDuplicate();
    testAddCustomerInvalidEmail();
    testAddCustomerShortPassword();
    echo "PASS: admin_users customer add tests\n";
}

runAllTests();

