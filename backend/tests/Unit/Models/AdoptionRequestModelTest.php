<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use AdoptionRequest;
use PDO;

class AdoptionRequestModelTest extends TestCase
{
    private $adoptionRequestModel;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        $this->adoptionRequestModel = new AdoptionRequest($this->mockPdo);
    }

    public function testFindReturnsRequestById(): void
    {
        $expectedRequest = ['RequestID' => 1, 'Status' => 'Pending'];
        $stmt = $this->createMockStatement([$expectedRequest]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->adoptionRequestModel->find(1);
        
        $this->assertEquals($expectedRequest, $result);
    }

    public function testCreateInsertsNewRequest(): void
    {
        $stmt = $this->createMockStatement([], 1);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);
            
        $this->mockPdo->method('lastInsertId')
            ->willReturn('10');

        $data = [
            'animal_id' => 1,
            'adopter_user_id' => 100
        ];

        $result = $this->adoptionRequestModel->create($data);
        
        $this->assertEquals(10, $result);
    }

    public function testUpdateStatusModifiesRequest(): void
    {
        $stmt = $this->createMockStatement([], 1);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SET Status = :status'))
            ->willReturn($stmt);

        $result = $this->adoptionRequestModel->updateStatus(1, 'Approved', 5, 'Looks good');
        
        $this->assertTrue($result);
    }

    public function testCompleteAdoptionTransaction(): void
    {
        // 1. find() request
        $requestData = ['RequestID' => 1, 'AnimalID' => 99];
        $stmtFind = $this->createMockStatement([$requestData]);

        // 2. updateStatus (AdoptionRequest)
        $stmtUpdateReq = $this->createMockStatement([], 1);

        // 3. update Animal status
        $stmtUpdateAnimal = $this->createMockStatement([], 1);

        // 4. reject other requests
        $stmtRejectOthers = $this->createMockStatement([], 0);

        $this->mockPdo->expects($this->exactly(4))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtFind, $stmtUpdateReq, $stmtUpdateAnimal, $stmtRejectOthers);

        // Expect transaction methods
        $this->mockPdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $this->mockPdo->expects($this->once())->method('commit')->willReturn(true);

        $result = $this->adoptionRequestModel->complete(1, 5);
        
        $this->assertTrue($result);
    }

    public function testHasActiveRequestReturnsTrueWhenFound(): void
    {
        $stmt = $this->createMockStatement([['count' => 1]]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->adoptionRequestModel->hasActiveRequest(99, 100);
        
        $this->assertTrue($result);
    }
}
