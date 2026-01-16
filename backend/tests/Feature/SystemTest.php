<?php

namespace Tests\Feature;

use Tests\TestCase;
use SystemController;

class SystemTest extends TestCase
{
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
    }

    private function getController($role = 'Admin', $userId = 1)
    {
        $user = [
            'UserID' => $userId,
            'Role_Name' => $role,
            'Email' => 'admin@example.com',
            'FirstName' => 'Admin',
            'LastName' => 'User'
        ];
        return new SystemController($this->mockPdo, $user);
    }

    private function configureMockPdo(array $queryMap)
    {
        // SystemController uses ->query() mostly, not prepare()
        $this->mockPdo->method('query')->willReturnCallback(function($query) use ($queryMap) {
            foreach ($queryMap as $pattern => $result) {
                if (stripos($query, $pattern) !== false) {
                    return $this->createMockStatement($result['data'] ?? [], $result['count'] ?? -1);
                }
            }
            return $this->createMockStatement([]);
        });

        // Also mock prepare if needed, though SystemController mostly uses query()
        $this->mockPdo->method('prepare')->willReturnCallback(function($query) use ($queryMap) {
            foreach ($queryMap as $pattern => $result) {
                if (stripos($query, $pattern) !== false) {
                    return $this->createMockStatement($result['data'] ?? [], $result['count'] ?? -1);
                }
            }
            return $this->createMockStatement([]);
        });
    }

    public function testHealthCheckReturnsStatus(): void
    {
        $this->mockRequest('GET', []);

        $this->configureMockPdo([
            'SELECT 1' => ['data' => [['1' => 1]]]
        ]);

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->health();
        });

        // Current implementation returns JSON directly via Response::json
        // runController captures this.
        
        $this->assertEquals('healthy', $response['status']);
        $this->assertArrayHasKey('checks', $response);
        $this->assertEquals('up', $response['checks']['database']['status']);
        $this->assertArrayHasKey('disk', $response['checks']);
        $this->assertArrayHasKey('memory', $response['checks']);
        $this->assertArrayHasKey('php', $response['checks']);
    }

    public function testHealthCheckHandlesDbFailure(): void
    {
        $this->mockRequest('GET', []);

        // Mock PDO to throw exception on query
        $this->mockPdo->method('query')->willThrowException(new \Exception("DB Down"));

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->health();
        });

        $this->assertEquals('unhealthy', $response['status']);
        $this->assertEquals('down', $response['checks']['database']['status']);
    }

    public function testInfoReturnsSystemDetails(): void
    {
        $this->mockRequest('GET', []);

        $this->configureMockPdo([
            // DB Version
            'SELECT VERSION()' => ['data' => [['version' => '8.0.30']]],
            // Table Stats
            'information_schema.TABLES' => ['data' => [
                ['TABLE_NAME' => 'Users', 'TABLE_ROWS' => 10],
                ['TABLE_NAME' => 'Animals', 'TABLE_ROWS' => 20]
            ]],
            // System Stats (Users)
            'FROM Users WHERE Is_Deleted' => ['data' => [['count' => 10]]],
            // System Stats (Animals)
            'FROM Animals WHERE Is_Deleted' => ['data' => [['count' => 20]]],
            // Active Adoptions
            'FROM Adoption_Requests WHERE Status' => ['data' => [['count' => 5]]],
            // Today Activities
            'FROM Activity_Logs WHERE DATE' => ['data' => [['count' => 50]]]
        ]);

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->info();
        });

        $this->assertResponseSuccess($response);
        $this->assertArrayHasKey('application', $response['data']);
        $this->assertArrayHasKey('server', $response['data']);
        $this->assertArrayHasKey('database', $response['data']);
        $this->assertArrayHasKey('statistics', $response['data']);
        
        $this->assertEquals('8.0.30', $response['data']['database']['version']);
        $this->assertEquals(2, $response['data']['database']['tables']); // 2 tables mocked
        $this->assertEquals(30, $response['data']['database']['table_rows']); // 10 + 20
        $this->assertEquals(10, $response['data']['statistics']['total_users']);
    }
}
