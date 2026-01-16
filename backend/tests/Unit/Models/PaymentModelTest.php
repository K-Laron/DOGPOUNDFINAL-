<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Payment;
use PDO;

class PaymentModelTest extends TestCase
{
    private $paymentModel;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        $this->paymentModel = new Payment($this->mockPdo);
    }

    public function testFindReturnsPaymentById(): void
    {
        $expectedPayment = ['PaymentID' => 1, 'Amount_Paid' => 100];
        $stmt = $this->createMockStatement([$expectedPayment]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->paymentModel->find(1);
        
        $this->assertEquals($expectedPayment, $result);
    }

    public function testCreateInsertsPaymentAndUpdateInvoice(): void
    {
        // 1. Insert payment
        $stmtInsert = $this->createMockStatement([], 1);
        
        // 2. UpdateInvoiceStatus -> Get invoice total/paid
        $invoiceStats = ['Total_Amount' => 100, 'Total_Paid' => 100];
        $stmtStats = $this->createMockStatement([$invoiceStats]);
        
        // 3. UpdateInvoiceStatus -> Update invoice to Paid
        $stmtUpdate = $this->createMockStatement([], 1);

        $this->mockPdo->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtInsert, $stmtStats, $stmtUpdate);
            
        $this->mockPdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $this->mockPdo->expects($this->once())->method('commit')->willReturn(true);
        $this->mockPdo->method('lastInsertId')->willReturn('10');

        $data = [
            'invoice_id' => 1,
            'received_by_user_id' => 1,
            'amount_paid' => 100,
            'payment_method' => 'Cash'
        ];

        $result = $this->paymentModel->create($data);
        
        $this->assertEquals(10, $result);
    }

    public function testDeletePaymentUpdatesInvoiceStatus(): void
    {
        // 1. Find payment
        $payment = ['PaymentID' => 1, 'InvoiceID' => 100];
        $stmtFind = $this->createMockStatement([$payment]);

        // 2. Delete payment
        $stmtDelete = $this->createMockStatement([], 1);

        // 3. Update invoice to Unpaid (if it was paid)
        $stmtUpdateUnpaid = $this->createMockStatement([], 1);

        // 4. UpdateInvoiceStatus -> Check totals
        // Assume not fully paid after deletion
        $invoiceStats = ['Total_Amount' => 200, 'Total_Paid' => 0];
        $stmtStats = $this->createMockStatement([$invoiceStats]);

        $this->mockPdo->expects($this->exactly(4))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtFind, $stmtDelete, $stmtUpdateUnpaid, $stmtStats);

        $this->mockPdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $this->mockPdo->expects($this->once())->method('commit')->willReturn(true);

        $result = $this->paymentModel->delete(1);
        
        $this->assertTrue($result);
    }

    public function testGetTotalCollectedReturnsSum(): void
    {
        $stmt = $this->createMockStatement([['total' => 5000]]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->paymentModel->getTotalCollected();
        
        $this->assertEquals(5000, $result);
    }
}
