<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use MedicalRecord;
use PDO;

class MedicalRecordModelTest extends TestCase
{
    private $medicalRecordModel;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        $this->medicalRecordModel = new MedicalRecord($this->mockPdo);
    }

    public function testFindReturnsRecordById(): void
    {
        $expectedRecord = ['RecordID' => 1, 'Diagnosis_Type' => 'Vaccination'];
        $stmt = $this->createMockStatement([$expectedRecord]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->medicalRecordModel->find(1);
        
        $this->assertEquals($expectedRecord, $result);
    }

    public function testCreateInsertsValidRecord(): void
    {
        $stmt = $this->createMockStatement([], 1);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);
            
        $this->mockPdo->method('lastInsertId')
            ->willReturn('5');

        $data = [
            'animal_id' => 1,
            'vet_id' => 1,
            'diagnosis_type' => 'Vaccination',
            'treatment_notes' => 'Rabies shot'
        ];

        $result = $this->medicalRecordModel->create($data);
        
        $this->assertEquals(5, $result);
    }

    public function testCreateFailsWithInvalidDiagnosisType(): void
    {
        $data = [
            'animal_id' => 1,
            'vet_id' => 1,
            'diagnosis_type' => 'InvalidType',
            'treatment_notes' => 'Notes'
        ];

        $this->mockPdo->expects($this->never())->method('prepare');

        $result = $this->medicalRecordModel->create($data);
        
        $this->assertFalse($result);
    }

    public function testPaginateReturnsDataAndTotal(): void
    {
        // 1. Count query
        $stmtCount = $this->createMockStatement([['total' => 10]]);
        
        // 2. Data query
        $items = [
            ['RecordID' => 1, 'Diagnosis_Type' => 'Checkup'],
            ['RecordID' => 2, 'Diagnosis_Type' => 'Surgery']
        ];
        $stmtData = $this->createMockStatement($items);

        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtCount, $stmtData);

        $result = $this->medicalRecordModel->paginate(1, 10);
        
        $this->assertEquals(10, $result['total']);
        $this->assertCount(2, $result['data']);
    }

    public function testGetUpcomingReturnsRecords(): void
    {
        $items = [
            ['RecordID' => 1, 'Next_Due_Date' => '2023-12-01'],
        ];
        $stmt = $this->createMockStatement($items);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('BETWEEN CURDATE() AND DATE_ADD'))
            ->willReturn($stmt);

        $result = $this->medicalRecordModel->getUpcoming(7);
        
        $this->assertEquals($items, $result);
    }
}
