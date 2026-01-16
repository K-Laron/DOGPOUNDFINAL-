<?php

namespace Tests\Feature;

use Tests\TestCase;
use AdoptionController;

class AdoptionsTest extends TestCase
{
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
    }
    
    private function getController($role = 'Adopter', $userId = 1)
    {
        $user = [
            'UserID' => $userId,
            'Role_Name' => $role,
            'Email' => 'test@example.com',
            'FirstName' => 'Test',
            'LastName' => 'User'
        ];
        return new AdoptionController($this->mockPdo, $user);
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
        
        $this->mockPdo->method('lastInsertId')->willReturn('202');
    }

    public function testIndexReturnsPaginatedRequestsForAdopter(): void
    {
        $this->mockRequest('GET', ['page' => 1]);
        
        // Mock queries:
        // 1. Count query
        // 2. Data query
        $this->configureMockPdo([
            'SELECT COUNT(*) as total' => ['data' => [['total' => 5]]],
            'SELECT ar.*' => ['data' => [ // Matches data query "SELECT ar.*, ..."
                [
                    'RequestID' => 1,
                    'AnimalID' => 10,
                    'Adopter_UserID' => 1,
                    'Status' => 'Pending',
                    'Request_Date' => '2023-01-01',
                    'Animal_Name' => 'Buddy',
                    'Animal_Type' => 'Dog',
                    'FirstName' => 'Test',
                    'LastName' => 'User',
                    'Email' => 'test@example.com'
                ]
            ]]
        ]);

        $controller = $this->getController('Adopter', 1);
        $response = $this->runController(function() use ($controller) {
            $controller->index();
        });

        $this->assertResponseSuccess($response);
        $this->assertArrayHasKey('pagination', $response);
        $this->assertCount(1, $response['data']);
    }

    public function testStoreCreatesRequest(): void
    {
        $data = ['animal_id' => 10];
        $this->mockRequest('POST', [], $data);
        
        $this->configureMockPdo([
            // 1. Check animal exists and available
            'FROM Animals WHERE AnimalID' => ['data' => [[
                'AnimalID' => 10,
                'Name' => 'Buddy',
                'Current_Status' => 'Available'
            ]]],
            // 2. Check for existing pending request
            'SELECT RequestID FROM Adoption_Requests' => ['data' => []], // None
            // 3. Insert
            'INSERT INTO Adoption_Requests' => ['count' => 1],
            // 4. Log
            'INSERT INTO Activity_Logs' => ['count' => 1],
            // 5. Get created request
            'FROM Adoption_Requests ar' => ['data' => [[
                'RequestID' => 202,
                'Status' => 'Pending',
                'Animal_Name' => 'Buddy'
            ]]]
        ]);

        $controller = $this->getController('Adopter', 1);
        $response = $this->runController(function() use ($controller) {
            $controller->store();
        });

        $this->assertResponseSuccess($response, 201);
        $this->assertEquals(202, $response['data']['RequestID']);
    }

    public function testStoreFailsIfAnimalNotAvailable(): void
    {
        $data = ['animal_id' => 10];
        $this->mockRequest('POST', [], $data);
        
        $this->configureMockPdo([
            'FROM Animals WHERE AnimalID' => ['data' => [[
                'AnimalID' => 10,
                'Name' => 'Buddy',
                'Current_Status' => 'Adopted' // Not Available
            ]]]
        ]);

        $controller = $this->getController('Adopter', 1);
        $response = $this->runController(function() use ($controller) {
            $controller->store();
        });

        $this->assertResponseError($response, 400);
    }
    
    public function testProcessUpdatesStatus(): void
    {
        $data = [
            'status' => 'Interview Scheduled',
            'interview_date' => '2023-01-02 10:00:00',
            'comments' => 'Good applicant'
        ];
        $this->mockRequest('PUT', [], $data);
        
        $this->mockPdo->method('beginTransaction')->willReturn(true);
        $this->mockPdo->method('commit')->willReturn(true);
        
        $this->configureMockPdo([
            // 4. Get updated request - Key: a.Type (matches a.Type as Animal_Type, specific to Query 2)
            // MUST be before a.AnimalID because Query 2 also contains a.AnimalID
            'a.Type' => ['data' => [[
                'RequestID' => 1,
                'Status' => 'Interview Scheduled'
            ]]],
            // 1. Get request (Validation) - Key: a.AnimalID
            'a.AnimalID' => ['data' => [[
                'RequestID' => 1,
                'AnimalID' => 10,
                'Status' => 'Pending'
            ]]],
            // 2. Update request
            'UPDATE Adoption_Requests' => ['count' => 1],
            // 3. Log
            'INSERT INTO Activity_Logs' => ['count' => 1]
        ]);

        $controller = $this->getController('Admin', 99);
        $response = $this->runController(function() use ($controller) {
            $controller->process(1);
        });

        $this->assertResponseSuccess($response);
        $this->assertEquals('Interview Scheduled', $response['data']['Status']);
    }
}
