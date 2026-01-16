<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Invoice;
use PDO;

class InvoiceModelTest extends TestCase
{
    private $invoiceModel;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        $this->invoiceModel = new Invoice($this->mockPdo);
    }

    public function testFindReturnsInvoiceWithBalance(): void
    {
        // 1. fetch invoice
        $invoice = ['InvoiceID' => 1, 'Total_Amount' => 100];
        $stmtInvoice = $this->createMockStatement([$invoice]);
        
        // 2. getAmountPaid -> fetch sum
        $stmtPayment = $this->createMockStatement([['total' => 20]]);

        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtInvoice, $stmtPayment);

        $result = $this->invoiceModel->find(1);
        
        $this->assertEquals(100, $result['Total_Amount']);
        $this->assertEquals(20, $result['Amount_Paid']);
        $this->assertEquals(80, $result['Balance']);
    }

    public function testCreateInsertsInvoice(): void
    {
        $stmt = $this->createMockStatement([], 1);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);
            
        $this->mockPdo->method('lastInsertId')
            ->willReturn('15');

        $data = [
            'payer_user_id' => 1,
            'issued_by_user_id' => 2,
            'transaction_type' => 'Adoption Fee',
            'total_amount' => 100
        ];

        $result = $this->invoiceModel->create($data);
        
        $this->assertEquals(15, $result);
    }

    public function testIsFullyPaidReturnsTrueMatchedAmount(): void
    {
        // isFullyPaid calls getBalance calls find
        // 1. find (invoice)
        $invoice = ['InvoiceID' => 1, 'Total_Amount' => 100];
        $stmtFind = $this->createMockStatement([$invoice]);
        
        // 2. find calls getAmountPaid
        $stmtPay1 = $this->createMockStatement([['total' => 100]]);
        
        // 3. getBalance ALSO calls getAmountPaid again (based on method structure)
        // Let's re-read getBalance:
        // public function getBalance($id) {
        //   $invoice = $this->find($id); // calls getAmountPaid internally
        //   return $invoice['Total_Amount'] - $this->getAmountPaid($id); // calls getAmountPaid AGAIN
        // }
        // So we expect: find -> getAmountPaid, then getAmountPaid again.
        
        $stmtPay2 = $this->createMockStatement([['total' => 100]]);

        $this->mockPdo->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtFind, $stmtPay1, $stmtPay2);

        $result = $this->invoiceModel->isFullyPaid(1);
        
        $this->assertTrue($result);
    }

    public function testCancelPreventsPaidInvoiceCancellation(): void
    {
        // 1. find
        $invoice = ['InvoiceID' => 1, 'Status' => 'Paid', 'Total_Amount' => 100];
        // find calls getAmountPaid
        $stmtFind = $this->createMockStatement([$invoice]);
        $stmtPay = $this->createMockStatement([['total' => 100]]);

        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtFind, $stmtPay);

        $result = $this->invoiceModel->cancel(1);
        
        $this->assertFalse($result);
    }
}
