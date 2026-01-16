<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Veterinarian;
use PDO;

class VeterinarianModelTest extends TestCase
{
    private $vetModel;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        $this->vetModel = new Veterinarian($this->mockPdo);
    }

    public function testFindReturnsVetById(): void
    {
        $expectedVet = ['VetID' => 1, 'License_Number' => 'LIC123'];
        $stmt = $this->createMockStatement([$expectedVet]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->vetModel->find(1);
        
        $this->assertEquals($expectedVet, $result);
    }

    public function testCreateInsertsNewVet(): void
    {
        // 1. findByUserId (check if exists) -> return false (not found)
        $stmtCheckUser = $this->createMockStatement([]);
        $stmtCheckUser->method('fetch')->willReturn(false);
        
        // 2. licenseExists (check license) -> count 0
        $stmtCheckLicense = $this->createMockStatement([['count' => 0]]);
        
        // 3. insert
        $stmtInsert = $this->createMockStatement([], 1);

        $this->mockPdo->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtCheckUser, $stmtCheckLicense, $stmtInsert);
            
        $this->mockPdo->method('lastInsertId')->willReturn('5');

        $data = [
            'user_id' => 1,
            'license_number' => 'NEW123',
            'specialization' => 'Surgery',
            'years_experience' => 5
        ];

        $result = $this->vetModel->create($data);
        
        $this->assertEquals(5, $result);
    }

    public function testDeletePreventsIfRecordsExist(): void
    {
        // 1. check records count -> returns count > 0
        $stmtCheck = $this->createMockStatement([['count' => 5]]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtCheck);

        $result = $this->vetModel->delete(1);
        
        $this->assertFalse($result);
    }
}
