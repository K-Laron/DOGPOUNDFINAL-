<?php

namespace Tests\Feature;

use Tests\TestCase;
use NotificationController;

class NotificationsTest extends TestCase
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
        return new NotificationController($this->mockPdo, $user);
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
    }

    public function testIndexAggregatesNotifications(): void
    {
        $this->mockRequest('GET', []);

        $this->configureMockPdo([
            // 1. Low Stock
            'Quantity_On_Hand <= Reorder_Level' => ['data' => [
                ['Item_Name' => 'Dog Food', 'Quantity_On_Hand' => 5, 'Reorder_Level' => 10]
            ]],
            // 2. Expiring
            'Expiration_Date BETWEEN' => ['data' => [
                ['Item_Name' => 'Vaccine A', 'Expiration_Date' => date('Y-m-d', strtotime('+5 days'))]
            ]],
            // 3. Pending Adoptions
            'Status = \'Pending\'' => ['data' => [
                [
                    'RequestID' => 101, 'AnimalName' => 'Buddy', 
                    'FirstName' => 'John', 'LastName' => 'Doe', 
                    'Request_Date' => date('Y-m-d H:i:s')
                ]
            ]],
            // 4. Unpaid Invoices
            'Status = \'Unpaid\'' => ['data' => [
                ['InvoiceID' => 202, 'Total_Amount' => 1000, 'Created_At' => date('Y-m-d H:i:s')]
            ]],
            // 5. Medical Due
            'Next_Due_Date BETWEEN' => ['data' => [
                ['Name' => 'Whiskers', 'Diagnosis_Type' => 'Vaccination', 'Next_Due_Date' => date('Y-m-d')]
            ]]
        ]);

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->index();
        });

        $this->assertResponseSuccess($response);
        
        // Use assertion to count approximate notifications, 
        // since we mocked 1 for each category, expect 5.
        $this->assertCount(5, $response['data']);
        
        // Verify types exist
        $types = array_column($response['data'], 'type');
        $this->assertContains('warning', $types); // Low stock / Medical
        $this->assertContains('danger', $types);  // Expiring / Unpaid
        $this->assertContains('info', $types);    // Adoption
    }
    
    public function testIndexReturnsEmptyArrayWhenNoAlerts(): void
    {
        $this->mockRequest('GET', []);

        // Return empty for all queries
        $this->configureMockPdo([]);

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->index();
        });

        $this->assertResponseSuccess($response);
        $this->assertEmpty($response['data']);
    }
}
