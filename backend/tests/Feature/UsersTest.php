<?php

namespace Tests\Feature;

use Tests\TestCase;
use UserController;

class UsersTest extends TestCase
{
    private $userController;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
    }
    
    private function getController()
    {
        // Mock authenticated Admin user
        $adminUser = [
            'UserID' => 1,
            'Role_Name' => 'Admin',
            'Account_Status' => 'Active',
            'Is_Deleted' => 0
        ];
        return new UserController($this->mockPdo, $adminUser);
    }

    private function configureMockPdo(array $queryMap)
    {
        $this->mockPdo->method('prepare')->willReturnCallback(function($query) use ($queryMap) {
            foreach ($queryMap as $pattern => $result) {
                if (stripos($query, $pattern) !== false) {
                    return $this->createMockStatement($result['data'] ?? [], $result['count'] ?? -1);
                }
            }
            // Default empty statement if no match
            return $this->createMockStatement([]);
        });
        
        $this->mockPdo->method('lastInsertId')->willReturn('10');
    }

    public function testIndexReturnsPaginatedUsers(): void
    {
        $this->mockRequest('GET', ['page' => 1, 'per_page' => 10]);

        // Mock Queries
        $this->configureMockPdo([
            'SELECT COUNT(*)' => ['data' => [['total' => 2]]], // Array of rows
            'u.UserID' => ['data' => [
                [
                    'UserID' => 1, 'RoleID' => 1, 'Role_Name' => 'Admin', 'FirstName' => 'Admin', 'LastName' => 'User',
                    'Username' => 'admin', 'Email' => 'admin@example.com', 'Contact_Number' => '000', 'Account_Status' => 'Active',
                    'Created_At' => '2023-01-01', 'Updated_At' => 'now'
                ],
                [
                    'UserID' => 2, 'RoleID' => 2, 'Role_Name' => 'Staff', 'FirstName' => 'Staff', 'LastName' => 'User',
                    'Username' => 'staff', 'Email' => 'staff@example.com', 'Contact_Number' => '111', 'Account_Status' => 'Active',
                    'Created_At' => '2023-01-01', 'Updated_At' => 'now'
                ]
            ]]
        ]);
        
        $controller = $this->getController();

        $response = $this->runController(function() use ($controller) {
            $controller->index();
        });

        $this->assertResponseSuccess($response);
        $this->assertCount(2, $response['data']);
        $this->assertEquals(2, $response['pagination']['total_items']);
    }

    public function testShowReturnsUserDetails(): void
    {
        // Mock Queries
        $this->configureMockPdo([
            'WHERE u.UserID = :id' => ['data' => [[ // Wrap in array of rows
                'UserID' => 2, 'RoleID' => 2, 'Role_Name' => 'Staff', 'FirstName' => 'Staff', 'LastName' => 'User',
                'Username' => 'staff', 'Email' => 'staff@example.com', 'Contact_Number' => '09123456789', 'Avatar_Url' => null,
                'Account_Status' => 'Active', 'Created_At' => '2023-01-01', 'Updated_At' => '2023-01-01', 'Role_Name' => 'Staff'
            ]]],
            'SELECT Log_Date' => ['data' => []], 
            'SELECT COUNT(*)' => ['data' => [['count' => 0]]]
        ]);

        $controller = $this->getController();

        $response = $this->runController(function() use ($controller) {
            $controller->show(2);
        });

        $this->assertResponseSuccess($response);
        $this->assertEquals('staff@example.com', $response['data']['email']);
    }

    public function testStoreCreatesUser(): void
    {
        $data = [
            'role_id' => 2,
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'contact_number' => '09123456789'
        ];
        $this->mockRequest('POST', [], $data);

        $this->configureMockPdo([
            'SELECT COUNT(*) as count FROM Users WHERE Email' => ['data' => [['count' => 0]]], 
            'SELECT RoleID' => ['data' => [['RoleID' => 2, 'Role_Name' => 'Staff']]], 
            'INSERT INTO Users' => ['count' => 1],
            'INSERT INTO Activity_Logs' => ['count' => 1],
            'WHERE u.UserID = :id' => ['data' => [[ 
                'UserID' => 10, 'RoleID' => 2, 'Role_Name' => 'Staff', 'FirstName' => 'New', 'LastName' => 'User',
                'Username' => null, 'Email' => 'new@example.com', 'Contact_Number' => '09123456789', 'Avatar_Url' => null,
                'Account_Status' => 'Active', 'Created_At' => '2023-01-01', 'Updated_At' => 'now'
            ]]]
        ]);
        
        $controller = $this->getController();

        $response = $this->runController(function() use ($controller) {
            $controller->store();
        });

        $this->assertResponseSuccess($response, 201);
        $this->assertEquals(10, $response['data']['id']);
    }
    
    public function testStoreValidatesInput(): void
    {
        $this->mockRequest('POST', [], []);
        $this->configureMockPdo([]); // No queries allowed
        
        $controller = $this->getController();
        
        $response = $this->runController(function() use ($controller) {
            $controller->store();
        });
        
        $this->assertResponseError($response, 422);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('email', $response['errors']);
    }

    public function testDestroySoftDeletesUser(): void
    {
        $this->configureMockPdo([
            'WHERE u.UserID = :id' => ['data' => [[
                'UserID' => 2, 'RoleID' => 2, 'Role_Name' => 'Staff', 'FirstName' => 'Staff', 'LastName' => 'User',
                'Username' => 'staff', 'Email' => 'staff@example.com', 'Contact_Number' => '111', 'Avatar_Url' => null,
                'Account_Status' => 'Active', 'Created_At' => '2023-01-01', 'Updated_At' => '2023-01-01', 'Role_Name' => 'Staff'
            ]]],
            'SELECT COUNT(*)' => ['data' => [['count' => 0]]], 
            'SELECT v.VetID' => ['data' => []],
            'UPDATE Users' => ['count' => 1],
            'INSERT INTO Activity_Logs' => ['count' => 1]
        ]);
        
        $controller = $this->getController();

        $response = $this->runController(function() use ($controller) {
            $controller->destroy(2);
        });

        $this->assertResponseSuccess($response);
    }
}
