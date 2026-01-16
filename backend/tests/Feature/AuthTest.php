<?php

namespace Tests\Feature;

use Tests\TestCase;
use AuthController;

class AuthTest extends TestCase
{
    private $authController;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
    }
    
    private function getController()
    {
        return new AuthController($this->mockPdo);
    }
    
    private function configureMockPdo(array $queryMap)
    {
        $this->mockPdo->method('prepare')->willReturnCallback(function($query) use ($queryMap) {
            foreach ($queryMap as $pattern => $result) {
                if (stripos($query, $pattern) !== false) {
                    return $this->createMockStatement($result['data'] ?? [], $result['count'] ?? -1);
                }
            }
            return $this->createMockStatement([]);
        });
        
        $this->mockPdo->method('lastInsertId')->willReturn('101');
    }

    public function testRegisterWithValidData(): void
    {
        $data = [
            'first_name' => 'PHPUnit',
            'last_name' => 'TestUser',
            'username' => 'phpunit_test',
            'email' => 'phpunit_test@example.com',
            'password' => 'TestPassword123!',
            'password_confirmation' => 'TestPassword123!',
            'contact_number' => '09123456789',
            'address' => '123 Test Street'
        ];
        $this->mockRequest('POST', [], $data);

        // Mock queries for register
        $this->configureMockPdo([
            'SELECT UserID FROM Users' => ['data' => []], // Empty result set (no conflict)
            'SELECT RoleID FROM Roles' => ['data' => [['RoleID' => 3, 'Role_Name' => 'Adopter']]],
            'INSERT INTO Users' => ['count' => 1],
            'INSERT INTO Activity_Logs' => ['count' => 1]
        ]);

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->register();
        });

        $this->assertResponseSuccess($response, 201);
        $this->assertArrayHasKey('data', $response);
    }

    public function testRegisterFailsWithMissingEmail(): void
    {
        $data = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'test_no_email',
            'password' => 'TestPassword123!',
            'password_confirmation' => 'TestPassword123!'
        ];
        $this->mockRequest('POST', [], $data);
        $this->configureMockPdo([]); 

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->register();
        });

        $this->assertResponseError($response, 422);
    }

    public function testLoginWithValidCredentials(): void
    {
        $data = [
            'username' => 'admin1',
            'password' => 'password'
        ];
        $this->mockRequest('POST', [], $data);
        
        $hash = password_hash('password', PASSWORD_DEFAULT);
        
        $this->configureMockPdo([
            'SELECT u.*, r.Role_Name' => ['data' => [[
                'UserID' => 1, 
                'Username' => 'admin1', 
                'Email' => 'admin@example.com',
                'Password_Hash' => $hash,
                'RoleID' => 1,
                'Role_Name' => 'Admin',
                'Account_Status' => 'Active',
                'FirstName' => 'Admin',
                'LastName' => 'User',
                'Contact_Number' => '000',
                'Is_Deleted' => 0
            ]]],
            'INSERT INTO Activity_Logs' => ['count' => 1]
        ]);

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->login();
        });

        $this->assertResponseSuccess($response);
        $this->assertArrayHasKey('access_token', $response['data']);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $data = [
            'username' => 'admin1',
            'password' => 'wrongpassword'
        ];
        $this->mockRequest('POST', [], $data);
        
        $hash = password_hash('password', PASSWORD_DEFAULT);
        
        $this->configureMockPdo([
            'SELECT u.*, r.Role_Name' => ['data' => [[
                'UserID' => 1, 
                'Username' => 'admin1', 
                'Password_Hash' => $hash,
                'RoleID' => 1,
                'Role_Name' => 'Admin',
                'Account_Status' => 'Active',
                'FirstName' => 'Admin',
                'LastName' => 'User',
                'Is_Deleted' => 0
            ]]]
        ]);

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->login();
        });

        $this->assertResponseError($response, 401);
    }

    public function testLoginFailsWithNonexistentUser(): void
    {
        $data = [
            'username' => 'nonexistent',
            'password' => 'password'
        ];
        $this->mockRequest('POST', [], $data);
        
        $this->configureMockPdo([
            'SELECT u.*, r.Role_Name' => ['data' => []] // No user found (empty array of rows implies fetch returns false? Or empty array?)
             // MockDatabaseTrait fetch returns current row. If data is empty array (no rows), fetch returns false? 
             // Ideally we pass empty data.
        ]);

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->login();
        });

        $this->assertResponseError($response, 401);
    }
}
