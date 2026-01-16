<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Animal;
use PDO;

class AnimalModelTest extends TestCase
{
    private $animalModel;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        $this->animalModel = new Animal($this->mockPdo);
    }

    public function testFindReturnsAnimalById(): void
    {
        $expectedAnimal = ['AnimalID' => 1, 'Name' => 'Buddy', 'Is_Deleted' => 0];
        $stmt = $this->createMockStatement([$expectedAnimal]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->animalModel->find(1);
        
        $this->assertEquals($expectedAnimal, $result);
    }

    public function testFindWithRelationsIncludesRelatedCounts(): void
    {
        // Mock sequence based on findWithRelations calls
        // 1. find() -> fetch animal
        // 2. Impound record -> fetch
        // 3. Medical records count -> fetch
        // 4. Adoption requests count -> fetch
        // 5. Feeding records count -> fetch

        $animalData = ['AnimalID' => 1, 'Name' => 'Buddy'];
        $stmtAnimal = $this->createMockStatement([$animalData]);
        
        $stmtImpound = $this->createMockStatement([['Date' => '2023-01-01']]);
        
        $stmtMedical = $this->createMockStatement([['count' => 5]]);
        
        $stmtAdoption = $this->createMockStatement([['count' => 2]]);
        
        $stmtFeeding = $this->createMockStatement([['count' => 10]]);

        $this->mockPdo->expects($this->exactly(5))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtAnimal, $stmtImpound, $stmtMedical, $stmtAdoption, $stmtFeeding);

        $result = $this->animalModel->findWithRelations(1);
        
        $this->assertIsArray($result);
        $this->assertEquals(5, $result['medical_records_count']);
        $this->assertEquals(2, $result['adoption_requests_count']);
        $this->assertEquals(10, $result['feeding_records_count']);
        $this->assertNotNull($result['impound_record']);
    }

    public function testPaginateReturnsDataAndTotal(): void
    {
        // 1. Count query -> fetch total
        $stmtCount = $this->createMockStatement([['total' => 50]]);
        
        // 2. Data query -> fetchAll items
        $items = [
            ['AnimalID' => 1, 'Name' => 'A'],
            ['AnimalID' => 2, 'Name' => 'B']
        ];
        $stmtData = $this->createMockStatement($items);

        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtCount, $stmtData);

        $result = $this->animalModel->paginate(1, 10);
        
        $this->assertEquals(50, $result['total']);
        $this->assertCount(2, $result['data']);
    }

    public function testCreateInsertsNewAnimal(): void
    {
        $stmt = $this->createMockStatement([], 1); // 1 row affected
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);
            
        $this->mockPdo->method('lastInsertId')
            ->willReturn('100');

        $data = [
            'name' => 'New Dog',
            'type' => 'Dog',
            'intake_status' => 'Stray'
        ];

        $result = $this->animalModel->create($data);
        
        $this->assertEquals(100, $result);
    }

    public function testUpdateModifiesAnimalFields(): void
    {
        $stmt = $this->createMockStatement([], 1);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $data = ['name' => 'Updated Name'];
        $result = $this->animalModel->update(1, $data);
        
        $this->assertTrue($result);
    }

    public function testDeleteSoftDeletesAnimal(): void
    {
        $stmt = $this->createMockStatement([], 1);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SET Is_Deleted = TRUE'))
            ->willReturn($stmt);

        $result = $this->animalModel->delete(1);
        
        $this->assertTrue($result);
    }

    public function testGetStatisticsReturnsAllCounts(): void
    {
        $stats = [
            'total' => 10,
            'available' => 5,
            'dogs' => 8
        ];
        
        $stmt = $this->createMockStatement([$stats]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->animalModel->getStatistics();
        
        $this->assertEquals(10, $result['total']);
        $this->assertEquals(5, $result['available']);
        $this->assertEquals(8, $result['dogs']);
    }

    public function testSearchMatchesNameAndBreed(): void
    {
        $results = [
            ['AnimalID' => 1, 'Name' => 'Retriever']
        ];
        
        $stmt = $this->createMockStatement($results);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('LIKE :query'))
            ->willReturn($stmt);

        $found = $this->animalModel->search('Retriever');
        
        $this->assertCount(1, $found);
        $this->assertEquals('Retriever', $found[0]['Name']);
    }
}
