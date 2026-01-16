<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Inventory;
use PDO;

class InventoryModelTest extends TestCase
{
    private $inventoryModel;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        $this->inventoryModel = new Inventory($this->mockPdo);
    }

    public function testFindReturnsItemById(): void
    {
        $expectedItem = ['ItemID' => 1, 'Item_Name' => 'Bandages'];
        $stmt = $this->createMockStatement([$expectedItem]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->inventoryModel->find(1);
        
        $this->assertEquals($expectedItem, $result);
    }

    public function testPaginateReturnsDataAndTotal(): void
    {
        // 1. Count query
        $stmtCount = $this->createMockStatement([['total' => 20]]);
        
        // 2. Data query
        $items = [
            ['ItemID' => 1, 'Item_Name' => 'A'],
            ['ItemID' => 2, 'Item_Name' => 'B']
        ];
        $stmtData = $this->createMockStatement($items);

        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtCount, $stmtData);

        $result = $this->inventoryModel->paginate(1, 10);
        
        $this->assertEquals(20, $result['total']);
        $this->assertCount(2, $result['data']);
    }

    public function testAdjustStockAddsQuantity(): void
    {
        // 1. find item to check current stock
        $item = ['ItemID' => 1, 'Quantity_On_Hand' => 10];
        $stmtFind = $this->createMockStatement([$item]);
        
        // 2. update
        $stmtUpdate = $this->createMockStatement([], 1);

        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtFind, $stmtUpdate);

        $result = $this->inventoryModel->addStock(1, 5);
        
        $this->assertTrue($result);
    }

    public function testAdjustStockPreventsNegativeStack(): void
    {
        // 1. find item
        $item = ['ItemID' => 1, 'Quantity_On_Hand' => 5];
        $stmtFind = $this->createMockStatement([$item]);

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtFind);

        // Should return false if subtracting more than available
        $result = $this->inventoryModel->subtractStock(1, 10);
        
        $this->assertFalse($result);
    }

    public function testGetAlertsAggregatesLists(): void
    {
        // 1. getLowStock
        $stmtLow = $this->createMockStatement([['ItemID' => 1]]);
        // 2. getExpiring
        $stmtExpiring = $this->createMockStatement([['ItemID' => 2]]);
        // 3. getExpired
        $stmtExpired = $this->createMockStatement([['ItemID' => 3]]);
        
        // Note: getAlerts implementation calls each method TWICE (once for list, once for count)
        // This is inefficient but is the current behavior.
        // We need 6 mocked statements.

        $stmtLow = $this->createMockStatement([['ItemID' => 1]]);
        $stmtExpiring = $this->createMockStatement([['ItemID' => 2]]);
        $stmtExpired = $this->createMockStatement([['ItemID' => 3]]);
        
        $this->mockPdo->expects($this->exactly(6))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $stmtLow, $stmtExpiring, $stmtExpired, // For 'low_stock', 'expiring_soon', 'expired' keys
                $stmtLow, $stmtExpiring, $stmtExpired  // For 'summary' counts
            );

        $result = $this->inventoryModel->getAlerts();
        
        $this->assertCount(1, $result['low_stock']);
        $this->assertCount(1, $result['expiring_soon']);
        $this->assertCount(1, $result['expired']);
    }
}
