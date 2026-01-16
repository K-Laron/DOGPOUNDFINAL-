<?php

namespace Tests\Unit\Utils;

use Tests\TestCase;
use FeeCalculator;
use PDO;

class FeeCalculatorTest extends TestCase
{
    private $feeCalculator;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        $this->feeCalculator = new FeeCalculator($this->mockPdo);
    }

    public function testGetConfigReturnsDefaultFees(): void
    {
        $config = $this->feeCalculator->getConfig();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('adoption_base_fee', $config);
        $this->assertArrayHasKey('reclaim_base_fee', $config);
        $this->assertEquals(500.00, $config['adoption_base_fee']);
    }

    public function testCalculateAdoptionFeeReturnsErrorForNonexistentAnimal(): void
    {
        // Mock getAnimal returning false (not found - empty result set)
        $stmt = $this->createMockStatement([]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->feeCalculator->calculateAdoptionFee(999);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Animal not found', $result['error']);
    }

    public function testCalculateAdoptionFeeBasicAnimal(): void
    {
        // Mock getAnimal
        $animalData = ['AnimalID' => 1, 'Name' => 'Buddy', 'Type' => 'Dog', 'Current_Status' => 'Available', 'Intake_Date' => '2023-01-01'];
        $stmtAnimal = $this->createMockStatement([$animalData]);

        // Mock isSpayedNeutered (false)
        $stmtSpay = $this->createMockStatement([['count' => 0]]);

        // Mock getVaccinationCount (0)
        $stmtVac = $this->createMockStatement([['count' => 0]]);

        // Configure PDO to return statements in order
        $this->mockPdo->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtAnimal, $stmtSpay, $stmtVac);

        $result = $this->feeCalculator->calculateAdoptionFee(1);
        
        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals(500.00, $result['total']); // Base fee only
        $this->assertEquals('Buddy', $result['animal_name']);
    }

    public function testCalculateAdoptionFeeWithSpayNeuter(): void
    {
        // Mock getAnimal
        $animalData = ['AnimalID' => 1, 'Name' => 'Buddy', 'Type' => 'Dog', 'Current_Status' => 'Available', 'Intake_Date' => '2023-01-01'];
        $stmtAnimal = $this->createMockStatement([$animalData]);

        // Mock isSpayedNeutered (true)
        $stmtSpay = $this->createMockStatement([['count' => 1]]); // > 0

        // Mock getVaccinationCount (0)
        $stmtVac = $this->createMockStatement([['count' => 0]]);

        $this->mockPdo->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtAnimal, $stmtSpay, $stmtVac);

        $result = $this->feeCalculator->calculateAdoptionFee(1);
        
        $expectedTotal = 500.00 + 300.00; // Base + Spay
        $this->assertEquals($expectedTotal, $result['total']);
    }

    public function testCalculateAdoptionFeeWithVaccinations(): void
    {
         // Mock getAnimal
         $animalData = ['AnimalID' => 1, 'Name' => 'Buddy', 'Type' => 'Dog', 'Current_Status' => 'Available', 'Intake_Date' => '2023-01-01'];
         $stmtAnimal = $this->createMockStatement([$animalData]);
 
         // Mock isSpayedNeutered (false)
         $stmtSpay = $this->createMockStatement([['count' => 0]]);
 
         // Mock getVaccinationCount (2)
         $stmtVac = $this->createMockStatement([['count' => 2]]);
 
         $this->mockPdo->expects($this->exactly(3))
             ->method('prepare')
             ->willReturnOnConsecutiveCalls($stmtAnimal, $stmtSpay, $stmtVac);
 
         $result = $this->feeCalculator->calculateAdoptionFee(1);
         
         $expectedTotal = 500.00 + (2 * 200.00); // Base + 2*Vac
         $this->assertEquals($expectedTotal, $result['total']);
    }

    public function testCalculateAdoptionFeeWithTreatmentDiscount(): void
    {
        // Mock getAnimal (In Treatment)
        $animalData = ['AnimalID' => 1, 'Name' => 'Sick Pup', 'Type' => 'Dog', 'Current_Status' => 'In Treatment', 'Intake_Date' => '2023-01-01'];
        $stmtAnimal = $this->createMockStatement([$animalData]);

        // Mock isSpayedNeutered (false)
        $stmtSpay = $this->createMockStatement([['count' => 0]]);

        // Mock getVaccinationCount (0)
        $stmtVac = $this->createMockStatement([['count' => 0]]);

        $this->mockPdo->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtAnimal, $stmtSpay, $stmtVac);

        $result = $this->feeCalculator->calculateAdoptionFee(1);
        
        $expectedTotal = 500.00 - 100.00; // Base - Discount
        $this->assertEquals($expectedTotal, $result['total']);
    }
}
