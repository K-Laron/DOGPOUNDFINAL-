<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use ImpoundRecord;
use PDO;

class ImpoundRecordModelTest extends TestCase
{
    private $impoundModel;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        $this->impoundModel = new ImpoundRecord($this->mockPdo);
    }

    public function testFindReturnsRecordById(): void
    {
        $expectedRecord = ['ImpoundID' => 1, 'Animal_Name' => 'Stray Dog'];
        $stmt = $this->createMockStatement([$expectedRecord]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->impoundModel->find(1);
        
        $this->assertEquals($expectedRecord, $result);
    }

    public function testCreateInsertsValidRecord(): void
    {
        // 1. Check existing record (findByAnimal) -> returns false (not found)
        $stmtCheck = $this->createMockStatement([]);
        $stmtCheck->method('fetch')->willReturn(false);
        
        // 2. Insert
        $stmtInsert = $this->createMockStatement([], 1);

        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtCheck, $stmtInsert);
            
        $this->mockPdo->method('lastInsertId')->willReturn('5');

        $data = [
            'animal_id' => 1,
            'capture_date' => '2023-01-01',
            'location_found' => 'Park',
            'impounding_officer' => 'Officer Joe'
        ];

        $result = $this->impoundModel->create($data);
        
        $this->assertEquals(5, $result);
    }

    public function testCreateFailsIfRecordExists(): void
    {
        // 1. Check existing record -> returns found record
        $stmtCheck = $this->createMockStatement([['ImpoundID' => 1]]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtCheck);

        $data = ['animal_id' => 1];
        $result = $this->impoundModel->create($data);
        
        $this->assertFalse($result);
    }

    public function testUpdateModifiesRecord(): void
    {
        $stmt = $this->createMockStatement([], 1);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->impoundModel->update(1, ['location_found' => 'New Location']);
        
        $this->assertTrue($result);
    }
}
