<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use ActivityLog;
use PDO;

class ActivityLogModelTest extends TestCase
{
    private $logModel;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        $this->logModel = new ActivityLog($this->mockPdo);
    }

    public function testFindReturnsLogById(): void
    {
        $expectedLog = ['LogID' => 1, 'Action_Type' => 'LOGIN'];
        $stmt = $this->createMockStatement([$expectedLog]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->logModel->find(1);
        
        $this->assertEquals($expectedLog, $result);
    }

    public function testLogInsertsEntry(): void
    {
        $stmt = $this->createMockStatement([], 1);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);
            
        $this->mockPdo->method('lastInsertId')->willReturn('100');

        // Mock $_SERVER for IP
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $result = $this->logModel->log(1, 'TEST_ACTION', 'Description');
        
        $this->assertEquals(100, $result);
    }

    public function testPaginateReturnsDataAndTotal(): void
    {
        // 1. Count query
        $stmtCount = $this->createMockStatement([['total' => 50]]);
        
        // 2. Data query
        $data = [['LogID' => 1], ['LogID' => 2]];
        $stmtData = $this->createMockStatement($data);

        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtCount, $stmtData);

        $result = $this->logModel->paginate(1, 10);
        
        $this->assertEquals(50, $result['total']);
        $this->assertCount(2, $result['data']);
    }
}
