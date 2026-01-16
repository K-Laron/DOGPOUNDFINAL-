<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use FeedingRecord;
use PDO;

class FeedingRecordModelTest extends TestCase
{
    private $feedingModel;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        $this->feedingModel = new FeedingRecord($this->mockPdo);
    }

    public function testFindReturnsRecordById(): void
    {
        $expectedRecord = ['FeedingID' => 1, 'Quantity_Used' => 100];
        $stmt = $this->createMockStatement([$expectedRecord]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->feedingModel->find(1);
        
        $this->assertEquals($expectedRecord, $result);
    }

    public function testCreateInsertsRecord(): void
    {
        $stmt = $this->createMockStatement([], 1);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);
            
        $this->mockPdo->method('lastInsertId')->willReturn('20');

        $data = [
            'animal_id' => 1,
            'user_id' => 2,
            'food_type' => 'Dry Food',
            'quantity_used' => 200
        ];

        $result = $this->feedingModel->create($data);
        
        $this->assertEquals(20, $result);
    }

    public function testGetTodayReturnsTodaysRecords(): void
    {
        $records = [['FeedingID' => 1], ['FeedingID' => 2]];
        $stmt = $this->createMockStatement($records);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('DATE(fr.Feeding_Time) = CURDATE()'))
            ->willReturn($stmt);

        $result = $this->feedingModel->getToday();
        
        $this->assertCount(2, $result);
    }

    public function testGetAnimalsNotFedToday(): void
    {
        $animals = [['AnimalID' => 1, 'Name' => 'Hungry Dog']];
        $stmt = $this->createMockStatement($animals);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('NOT IN'))
            ->willReturn($stmt);

        $result = $this->feedingModel->getAnimalsNotFedToday();
        
        $this->assertEquals('Hungry Dog', $result[0]['Name']);
    }
}
