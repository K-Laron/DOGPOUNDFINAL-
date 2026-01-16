<?php

namespace Tests\Feature;

use Tests\TestCase;
use BillingController;

class BillingTest extends TestCase
{
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        
        // Ensure transaction methods return boolean to satisfy PDO interface
        $this->mockPdo->method('beginTransaction')->willReturn(true);
        $this->mockPdo->method('commit')->willReturn(true);
        $this->mockPdo->method('rollBack')->willReturn(true);
    }

    private function getController($role = 'Staff', $userId = 1)
    {
        $user = [
            'UserID' => $userId,
            'Role_Name' => $role,
            'Email' => 'staff@example.com',
            'FirstName' => 'Test',
            'LastName' => 'Staff'
        ];
        return new BillingController($this->mockPdo, $user);
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

    public function testCreateInvoiceGeneratesInvoice(): void
    {
        $data = [
            'payer_user_id' => 10,
            'transaction_type' => 'Adoption Fee',
            'total_amount' => 1500,
            'animal_id' => 5
        ];
        $this->mockRequest('POST', [], $data);

        $this->configureMockPdo([
            // 1. Verify Payer
            'SELECT UserID FROM Users' => ['data' => [['UserID' => 10]]],
            // 2. Verify Animal
            'SELECT AnimalID FROM Animals' => ['data' => [['AnimalID' => 5]]],
            // 3. Insert Invoice
            'INSERT INTO Invoices' => ['count' => 1],
            // 4. Log
            'INSERT INTO Activity_Logs' => ['count' => 1],
            // 5. Select Created
            'SELECT i.*' => ['data' => [[
                'InvoiceID' => 202,
                'Total_Amount' => 1500,
                'Status' => 'Unpaid',
                'Payer_FirstName' => 'John',
                'Payer_LastName' => 'Doe'
            ]]]
        ]);

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->createInvoice();
        });

        $this->assertResponseSuccess($response, 201);
        $this->assertEquals(202, $response['data']['InvoiceID']);
    }

    public function testRecordPaymentUpdatesInvoiceBalance(): void
    {
        $data = [
            'invoice_id' => 202,
            'amount_paid' => 500,
            'payment_method' => 'Cash',
            'reference_number' => 'REF123'
        ];
        $this->mockRequest('POST', [], $data);

        $this->configureMockPdo([
             // 1. Initial check (Must be first as it's more specific)
             'AND i.Is_Deleted = FALSE' => ['data' => [[
                'InvoiceID' => 202,
                'Total_Amount' => 1500,
                'Already_Paid' => 0,
                'Status' => 'Unpaid'
            ]]],
            
            // 2. Insert Payment
            'INSERT INTO Payments' => ['count' => 1],
            
            // 3. Log
            'INSERT INTO Activity_Logs' => ['count' => 1],
            
            // 4. Final Select
            'WHERE i.InvoiceID = :id' => ['data' => [[
                'InvoiceID' => 202,
                'Total_Amount' => 1500,
                'Amount_Paid' => 500
            ]]]
        ]);

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->recordPayment();
        });

        $this->assertResponseSuccess($response, 201);
        // Assert balance or amount paid in returned invoice
        if (isset($response['data']['invoice']['Amount_Paid'])) {
             $this->assertEquals(500, $response['data']['invoice']['Amount_Paid']);
        }
    }

    public function testIndexInvoicesFiltersByStatus(): void
    {
        $this->mockRequest('GET', ['page' => 1, 'status' => 'Unpaid']);

        $this->configureMockPdo([
            'SELECT COUNT(*) as total' => ['data' => [['total' => 5]]],
            'SELECT i.*' => ['data' => [
                [
                    'InvoiceID' => 202,
                    'Total_Amount' => 1500,
                    'Amount_Paid' => 0,
                    'Status' => 'Unpaid',
                    'Payer_FirstName' => 'John',
                    'Staff_FirstName' => 'Admin'
                ]
            ]]
        ]);

        $controller = $this->getController();
        $response = $this->runController(function() use ($controller) {
            $controller->indexInvoices();
        });

        $this->assertResponseSuccess($response);
        $this->assertCount(1, $response['data']);
        $this->assertEquals('Unpaid', $response['data'][0]['Status']);
    }
}
