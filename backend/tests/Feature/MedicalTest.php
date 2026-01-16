<?php

namespace Tests\Feature;

use Tests\TestCase;
use MedicalController;

class MedicalTest extends TestCase
{
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
    }

    private function getController($role = 'Veterinarian', $userId = 2, $userRoleName = 'Veterinarian')
    {
        $user = [
            'UserID' => $userId,
            'Role_Name' => $userRoleName,
            'Email' => 'vet@example.com',
            'FirstName' => 'Doc',
            'LastName' => 'Tor'
        ];
        return new MedicalController($this->mockPdo, $user);
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

    public function testIndexReturnsPaginatedRecords(): void
    {
        $this->mockRequest('GET', ['page' => 1]);

        $this->configureMockPdo([
            'SELECT COUNT(*) as total' => ['data' => [['total' => 20]]],
            'SELECT mr.*' => ['data' => [
                [
                    'RecordID' => 1,
                    'AnimalID' => 1,
                    'Diagnosis_Type' => 'Checkup',
                    'Vet_FirstName' => 'John',
                    'Vet_LastName' => 'Doe',
                    'Animal_Name' => 'Buddy'
                ]
            ]]
        ]);

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->index();
        });

        $this->assertResponseSuccess($response);
        $this->assertArrayHasKey('pagination', $response);
        $this->assertCount(1, $response['data']);
        $this->assertEquals('Checkup', $response['data'][0]['Diagnosis_Type']);
    }

    public function testStoreCreatesRecord(): void
    {
        $data = [
            'animal_id' => 1,
            'diagnosis_type' => 'Vaccination',
            'treatment_notes' => 'Rabies shot',
            'vaccine_name' => 'Rabies',
            'vet_id' => 5 // Explicit vet ID
        ];
        $this->mockRequest('POST', [], $data);

        $this->configureMockPdo([
            // 1. Verify Animal
            'SELECT AnimalID FROM Animals' => ['data' => [['AnimalID' => 1]]],
            // 2. Verify Vet
            'SELECT VetID FROM Veterinarians' => ['data' => [['VetID' => 5]]],
            // 3. Insert
            'INSERT INTO Medical_Records' => ['count' => 1],
            // 4. Log
            'INSERT INTO Activity_Logs' => ['count' => 1],
            // 5. Select Created
            'SELECT mr.*' => ['data' => [[
                'RecordID' => 101,
                'Diagnosis_Type' => 'Vaccination',
                'Vaccine_Name' => 'Rabies'
            ]]]
        ]);

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->store();
        });

        $this->assertResponseSuccess($response, 201);
        $this->assertEquals(101, $response['data']['RecordID']);
    }

    public function testUpdateModifiesRecord(): void
    {
        $data = ['treatment_notes' => 'Follow up needed'];
        $this->mockRequest('PUT', [], $data);

        $this->configureMockPdo([
            // 1. Check existence
            'SELECT RecordID FROM Medical_Records' => ['data' => [['RecordID' => 1]]],
            // 2. Update
            'UPDATE Medical_Records' => ['count' => 1],
            // 3. Log
            'INSERT INTO Activity_Logs' => ['count' => 1],
            // 4. Return updated
            'SELECT mr.*' => ['data' => [[
                'RecordID' => 1,
                'Treatment_Notes' => 'Follow up needed'
            ]]]
        ]);

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->update(1);
        });

        $this->assertResponseSuccess($response);
        $this->assertEquals('Follow up needed', $response['data']['Treatment_Notes']);
    }

    public function testOverdueReturnsOverdueRecords(): void
    {
        $this->mockRequest('GET', []);
        
        // Query: SELECT mr.* ... FROM Medical_Records mr ... WHERE ... Next_Due_Date < CURDATE() ...
        $this->configureMockPdo([
            'WHERE mr.Next_Due_Date IS NOT NULL' => ['data' => [
                [
                    'RecordID' => 5,
                    'AnimalID' => 2,
                    'Next_Due_Date' => '2023-01-01',
                    'Days_Overdue' => 10
                ]
            ]]
        ]);
        
        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->overdue();
        });
        
        $this->assertResponseSuccess($response);
        $this->assertCount(1, $response['data']);
        $this->assertEquals(5, $response['data'][0]['RecordID']);
    }
}
