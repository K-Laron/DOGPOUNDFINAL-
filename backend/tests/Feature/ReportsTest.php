<?php

namespace Tests\Feature;

use Tests\TestCase;
use DashboardController;
use BillingController;

class ReportsTest extends TestCase
{
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
    }

    private function getDashboardController($role = 'Admin', $userId = 1)
    {
        $user = [
            'UserID' => $userId,
            'Role_Name' => $role,
            'Email' => 'admin@example.com',
            'FirstName' => 'Admin',
            'LastName' => 'User'
        ];
        return new DashboardController($this->mockPdo, $user);
    }

    private function getBillingController($role = 'Admin', $userId = 1)
    {
        $user = [
            'UserID' => $userId,
            'Role_Name' => $role,
            'Email' => 'admin@example.com',
            'FirstName' => 'Admin',
            'LastName' => 'User'
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
            // Return empty statement for unmet queries (to avoid crashes on loose mocks)
            return $this->createMockStatement([]);
        });
    }

    public function testDashboardStatsReturnsAggregatedData(): void
    {
        $this->mockRequest('GET', []);

        // We need to verify that the controller aggregates data correctly.
        // We provide minimal mock data for each section to ensure the keys exist.
        $this->configureMockPdo([
            // Animals
            'SELECT COUNT(*) as total' => ['data' => [['total' => 10, 'available' => 5]]],
            // Adoptions
            'SELECT SUM(CASE WHEN Status' => ['data' => [['pending' => 2, 'completed_this_month' => 1]]],
            // Inventory Low Stock
            'Inventory WHERE Quantity_On_Hand <=' => ['data' => [['count' => 3]]],
            // Inventory Expiring
            'Inventory WHERE Expiration_Date IS' => ['data' => [['count' => 1]]],
            // Inventory Critical
            'FROM Inventory WHERE Quantity_On_Hand' => ['data' => [
                ['ItemID' => 1, 'Item_Name' => 'Bandages']
            ]],
            // Medical Upcoming Count
            'FROM Medical_Records mr JOIN Animals a' => ['data' => [['count' => 2]]],
            // Medical Upcoming List
            'SELECT mr.RecordID' => ['data' => [
                ['RecordID' => 1, 'Diagnosis_Type' => 'Checkup']
            ]],
            // Finance
            'FROM Invoices WHERE Is_Deleted' => ['data' => [['unpaid_count' => 5, 'collected_this_month' => 5000]]],
            // Users
            'FROM Users u JOIN Roles' => ['data' => [['total_users' => 20]]],
            // Monthly Intake
            'FROM Animals WHERE Is_Deleted = FALSE AND Intake_Date >=' => ['data' => [['month' => 'January']]],
            // Status Distribution
            'SELECT Current_Status, COUNT(*) as count' => ['data' => [['Current_Status' => 'Available', 'count' => 5]]],
            // Revenue Last Month
            'SELECT COALESCE(SUM(Total_Amount)' => ['data' => [['last_month_revenue' => 4000]]],
            // Current Month Animals
            'MONTH(Intake_Date) = MONTH(CURRENT_DATE)' => ['data' => [['count' => 8]]],
            // Last Month Animals
            'MONTH(Intake_Date) = MONTH(DATE_SUB' => ['data' => [['count' => 6]]]
        ]);

        $controller = $this->getDashboardController();
        $response = $this->runController(function() use ($controller) {
            $controller->statistics();
        });

        $this->assertResponseSuccess($response);
        $this->assertArrayHasKey('animals', $response['data']);
        $this->assertArrayHasKey('adoptions', $response['data']);
        $this->assertArrayHasKey('inventory', $response['data']);
        $this->assertArrayHasKey('finance', $response['data']);
        
        // derived stats
        $this->assertEquals(10, $response['data']['total_animals']);
        $this->assertEquals(5000, $response['data']['revenue_this_month']);
    }

    public function testIntakeStatsReturnsTimeData(): void
    {
        $this->mockRequest('GET', ['period' => 'week']);

        $this->configureMockPdo([
            'GROUP BY DATE(Intake_Date)' => ['data' => [
                ['label' => 'Today', 'date_val' => date('Y-m-d'), 'dogs' => 1, 'cats' => 0]
            ]]
        ]);

        $controller = $this->getDashboardController();
        $response = $this->runController(function() use ($controller) {
            $controller->intakeStats();
        });

        $this->assertResponseSuccess($response);
        $this->assertIsArray($response['data']);
        // Should contain 7 days (including missing ones filled with 0)
        $this->assertGreaterThanOrEqual(7, count($response['data']));
    }

    public function testFinancialReportGeneratesSummary(): void
    {
        $this->mockRequest('GET', [
            'date_from' => '2023-01-01',
            'date_to' => '2023-12-31',
            'report_type' => 'summary'
        ]);

        $this->configureMockPdo([
            // 1. Invoices in range stats
            'SELECT COUNT(*) as invoice_count' => ['data' => [[
                'invoice_count' => 100,
                'total_billed' => 10000,
                'total_paid_invoices' => 8000,
                'total_unpaid_invoices' => 2000
            ]]],
            // 2. Payments in range stats
            'SELECT COALESCE(SUM(Amount_Paid)' => ['data' => [[
                'total_collected' => 7500,
                'payment_count' => 90
            ]]]
        ]);

        $controller = $this->getBillingController();
        $response = $this->runController(function() use ($controller) {
            $controller->financialReport();
        });

        $this->assertResponseSuccess($response);
        $this->assertEquals('summary', $response['data']['report_type']);
        $this->assertEquals(10000, $response['data']['stats']['total_billed']);
    }
}
