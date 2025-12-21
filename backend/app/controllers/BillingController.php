<?php
/**
 * Billing Controller
 * Handles invoices and payments
 * 
 * @package AnimalShelter
 */

require_once APP_PATH . '/controllers/BaseController.php';

class BillingController extends BaseController {
    
    /**
     * List invoices
     * GET /invoices
     */
    public function indexInvoices() {
        list($page, $perPage) = $this->getPagination();
        
        $where = ["i.Is_Deleted = FALSE"];
        $params = [];
        
        // Adopters can only see their own invoices
        if ($this->user['Role_Name'] === 'Adopter') {
            $where[] = "i.Payer_UserID = :payer_id";
            $params['payer_id'] = $this->user['UserID'];
        } else {
            // Staff/Admin can filter
            if ($this->query('status')) {
                $where[] = "i.Status = :status";
                $params['status'] = $this->query('status');
            }
            
            if ($this->query('type')) {
                $where[] = "i.Transaction_Type = :type";
                $params['type'] = $this->query('type');
            }
            
            if ($this->query('payer_id')) {
                $where[] = "i.Payer_UserID = :payer_id";
                $params['payer_id'] = $this->query('payer_id');
            }
        }
        
        // Date range filter
        if ($this->query('date_from')) {
            $where[] = "DATE(i.Created_At) >= :date_from";
            $params['date_from'] = $this->query('date_from');
        }
        
        if ($this->query('date_to')) {
            $where[] = "DATE(i.Created_At) <= :date_to";
            $params['date_to'] = $this->query('date_to');
        }

        // Search functionality
        if ($this->query('search')) {
            $searchTerm = '%' . trim($this->query('search')) . '%';
            $where[] = "(
                i.InvoiceID LIKE :search
                OR i.Transaction_Type LIKE :search
                OR payer.FirstName LIKE :search 
                OR payer.LastName LIKE :search 
                OR CONCAT(payer.FirstName, ' ', payer.LastName) LIKE :search
                OR staff.FirstName LIKE :search 
                OR staff.LastName LIKE :search
                OR a.Name LIKE :search
            )";
            $params['search'] = $searchTerm;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM Invoices i
            JOIN Users payer ON i.Payer_UserID = payer.UserID
            JOIN Users staff ON i.Issued_By_UserID = staff.UserID
            LEFT JOIN Animals a ON i.Related_AnimalID = a.AnimalID
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get invoices
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("
            SELECT i.*, 
                   payer.FirstName as Payer_FirstName, payer.LastName as Payer_LastName, payer.Email as Payer_Email,
                   staff.FirstName as Staff_FirstName, staff.LastName as Staff_LastName,
                   a.Name as Animal_Name,
                   COALESCE((SELECT SUM(Amount_Paid) FROM Payments WHERE InvoiceID = i.InvoiceID), 0) as Amount_Paid
            FROM Invoices i
            JOIN Users payer ON i.Payer_UserID = payer.UserID
            JOIN Users staff ON i.Issued_By_UserID = staff.UserID
            LEFT JOIN Animals a ON i.Related_AnimalID = a.AnimalID
            WHERE {$whereClause}
            ORDER BY i.Created_At DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $invoices = $stmt->fetchAll();
        
        // Calculate balance for each invoice
        foreach ($invoices as &$invoice) {
            $invoice['Balance'] = $invoice['Total_Amount'] - $invoice['Amount_Paid'];
        }
        
        Response::paginated($invoices, $page, $perPage, $total, "Invoices retrieved");
    }
    
    /**
     * Get invoice statistics
     * GET /invoices/stats/summary
     */
    public function invoiceStatistics() {
        // 1. Get base stats from Invoices (Total Billed)
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_invoices,
                SUM(CASE WHEN Status = 'Unpaid' THEN 1 ELSE 0 END) as unpaid_count,
                SUM(CASE WHEN Status = 'Paid' THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN Status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                SUM(CASE WHEN Status != 'Cancelled' THEN Total_Amount ELSE 0 END) as total_billed,
                SUM(CASE WHEN Transaction_Type = 'Adoption Fee' THEN Total_Amount ELSE 0 END) as adoption_fees_total,
                SUM(CASE WHEN Transaction_Type = 'Reclaim Fee' THEN Total_Amount ELSE 0 END) as reclaim_fees_total
            FROM Invoices
            WHERE Is_Deleted = FALSE
        ");
        $stmt->execute();
        $stats = $stmt->fetch();
        
        // 2. Get total actual collections from Payments
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(p.Amount_Paid), 0) as total_collected
            FROM Payments p
            JOIN Invoices i ON p.InvoiceID = i.InvoiceID
            WHERE i.Is_Deleted = FALSE AND i.Status != 'Cancelled'
        ");
        $stmt->execute();
        $collected = $stmt->fetch()['total_collected'];
        
        // 3. Calculate derived stats
        // Outstanding = (Total Billed) - (Total Collected)
        $stats['total_unpaid'] = $stats['total_billed'] - $collected;
        $stats['total_paid'] = $collected; // Update Total Revenue to reflect actual collections
        
        // 4. This month's stats (Invoices created this month)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as this_month_invoices
            FROM Invoices
            WHERE Is_Deleted = FALSE
            AND MONTH(Created_At) = MONTH(CURRENT_DATE)
            AND YEAR(Created_At) = YEAR(CURRENT_DATE)
        ");
        $stmt->execute();
        $monthlyMeta = $stmt->fetch();
        
        // 5. This month's collections (Payments received this month)
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(Amount_Paid), 0) as this_month_collected
            FROM Payments
            WHERE MONTH(Payment_Date) = MONTH(CURRENT_DATE)
            AND YEAR(Payment_Date) = YEAR(CURRENT_DATE)
        ");
        $stmt->execute();
        $monthlyCollection = $stmt->fetch();
        
        Response::success(array_merge($stats, $monthlyMeta, $monthlyCollection), "Invoice statistics retrieved");
    }
    
    /**
     * Get single invoice with payments
     * GET /invoices/{id}
     */
    public function showInvoice($id) {
        $stmt = $this->db->prepare("
            SELECT i.*, 
                   payer.FirstName as Payer_FirstName, payer.LastName as Payer_LastName, 
                   payer.Email as Payer_Email, payer.Contact_Number as Payer_Contact,
                   staff.FirstName as Staff_FirstName, staff.LastName as Staff_LastName,
                   a.Name as Animal_Name, a.Type as Animal_Type
            FROM Invoices i
            JOIN Users payer ON i.Payer_UserID = payer.UserID
            JOIN Users staff ON i.Issued_By_UserID = staff.UserID
            LEFT JOIN Animals a ON i.Related_AnimalID = a.AnimalID
            WHERE i.InvoiceID = :id AND i.Is_Deleted = FALSE
        ");
        $stmt->execute(['id' => $id]);
        $invoice = $stmt->fetch();
        
        if (!$invoice) {
            Response::notFound("Invoice not found");
        }
        
        // Check access for adopters
        if ($this->user['Role_Name'] === 'Adopter' && $invoice['Payer_UserID'] != $this->user['UserID']) {
            Response::forbidden("Access denied");
        }
        
        // Get payments
        $stmt = $this->db->prepare("
            SELECT p.*, u.FirstName, u.LastName
            FROM Payments p
            JOIN Users u ON p.Received_By_UserID = u.UserID
            WHERE p.InvoiceID = :id
            ORDER BY p.Payment_Date DESC
        ");
        $stmt->execute(['id' => $id]);
        $invoice['payments'] = $stmt->fetchAll();
        
        // Calculate totals
        $invoice['Amount_Paid'] = array_sum(array_column($invoice['payments'], 'Amount_Paid'));
        $invoice['Balance'] = $invoice['Total_Amount'] - $invoice['Amount_Paid'];
        
        Response::success($invoice);
    }
    
    /**
     * Create invoice
     * POST /invoices
     */
    public function createInvoice() {
        $this->validate([
            'payer_user_id' => 'required|integer',
            'transaction_type' => 'required|in:Adoption Fee,Reclaim Fee',
            'total_amount' => 'required|numeric|positive'
        ]);
        
        // Verify payer exists
        $stmt = $this->db->prepare("SELECT UserID FROM Users WHERE UserID = :id AND Is_Deleted = FALSE");
        $stmt->execute(['id' => $this->input('payer_user_id')]);
        
        if (!$stmt->fetch()) {
            Response::error("Payer not found", 400);
        }
        
        // Verify animal if provided
        if ($this->input('animal_id')) {
            $stmt = $this->db->prepare("SELECT AnimalID FROM Animals WHERE AnimalID = :id AND Is_Deleted = FALSE");
            $stmt->execute(['id' => $this->input('animal_id')]);
            
            if (!$stmt->fetch()) {
                Response::error("Animal not found", 400);
            }
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO Invoices (Payer_UserID, Issued_By_UserID, Transaction_Type, Total_Amount, Status, Related_AnimalID, Related_RequestID, Is_Deleted)
            VALUES (:payer_id, :issued_by, :type, :amount, 'Unpaid', :animal_id, :request_id, FALSE)
        ");
        
        $stmt->execute([
            'payer_id' => $this->input('payer_user_id'),
            'issued_by' => $this->user['UserID'],
            'type' => $this->input('transaction_type'),
            'amount' => $this->input('total_amount'),
            'animal_id' => $this->input('animal_id'),
            'request_id' => $this->input('request_id')
        ]);
        
        $invoiceId = $this->db->lastInsertId();
        
        $this->logActivity('CREATE_INVOICE', "Created invoice ID: {$invoiceId} - {$this->input('transaction_type')} - PHP {$this->input('total_amount')}");
        
        // Get created invoice
        $stmt = $this->db->prepare("
            SELECT i.*, payer.FirstName as Payer_FirstName, payer.LastName as Payer_LastName
            FROM Invoices i
            JOIN Users payer ON i.Payer_UserID = payer.UserID
            WHERE i.InvoiceID = :id
        ");
        $stmt->execute(['id' => $invoiceId]);
        
        Response::created($stmt->fetch(), "Invoice created");
    }
    
    /**
     * Cancel invoice
     * PUT /invoices/{id}/cancel
     */
    public function cancelInvoice($id) {
        $stmt = $this->db->prepare("SELECT * FROM Invoices WHERE InvoiceID = :id AND Is_Deleted = FALSE");
        $stmt->execute(['id' => $id]);
        $invoice = $stmt->fetch();
        
        if (!$invoice) {
            Response::notFound("Invoice not found");
        }
        
        if ($invoice['Status'] === 'Paid') {
            Response::error("Cannot cancel a paid invoice", 400);
        }
        
        // Check if there are any payments
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM Payments WHERE InvoiceID = :id");
        $stmt->execute(['id' => $id]);
        
        if ($stmt->fetch()['count'] > 0) {
            Response::error("Cannot cancel invoice with existing payments", 400);
        }
        
        $stmt = $this->db->prepare("UPDATE Invoices SET Status = 'Cancelled', Is_Deleted = TRUE WHERE InvoiceID = :id");
        $stmt->execute(['id' => $id]);
        
        $this->logActivity('CANCEL_INVOICE', "Cancelled invoice ID: {$id}");
        
        Response::success(null, "Invoice cancelled");
    }
    
    /**
     * List all payments
     * GET /payments
     */
    public function indexPayments() {
        list($page, $perPage) = $this->getPagination();
        
        $where = ["1=1"];
        $params = [];
        
        if ($this->query('invoice_id')) {
            $where[] = "p.InvoiceID = :invoice_id";
            $params['invoice_id'] = $this->query('invoice_id');
        }
        
        if ($this->query('payment_method')) {
            $where[] = "p.Payment_Method = :method";
            $params['method'] = $this->query('payment_method');
        }
        
        if ($this->query('date_from')) {
            $where[] = "DATE(p.Payment_Date) >= :date_from";
            $params['date_from'] = $this->query('date_from');
        }
        
        if ($this->query('date_to')) {
            $where[] = "DATE(p.Payment_Date) <= :date_to";
            $params['date_to'] = $this->query('date_to');
        }

        // Search functionality
        if ($this->query('search')) {
            $searchTerm = '%' . trim($this->query('search')) . '%';
            $where[] = "(
                p.PaymentID LIKE :search
                OR p.InvoiceID LIKE :search
                OR p.Reference_Number LIKE :search
                OR p.Payment_Method LIKE :search
                OR payer.FirstName LIKE :search 
                OR payer.LastName LIKE :search 
                OR CONCAT(payer.FirstName, ' ', payer.LastName) LIKE :search
                OR receiver.FirstName LIKE :search 
                OR receiver.LastName LIKE :search
            )";
            $params['search'] = $searchTerm;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM Payments p
            JOIN Invoices i ON p.InvoiceID = i.InvoiceID
            JOIN Users payer ON i.Payer_UserID = payer.UserID
            JOIN Users receiver ON p.Received_By_UserID = receiver.UserID
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get payments
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   i.Transaction_Type, i.Total_Amount as Invoice_Total,
                   payer.FirstName as Payer_FirstName, payer.LastName as Payer_LastName,
                   receiver.FirstName as Receiver_FirstName, receiver.LastName as Receiver_LastName
            FROM Payments p
            JOIN Invoices i ON p.InvoiceID = i.InvoiceID
            JOIN Users payer ON i.Payer_UserID = payer.UserID
            JOIN Users receiver ON p.Received_By_UserID = receiver.UserID
            WHERE {$whereClause}
            ORDER BY p.Payment_Date DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        Response::paginated($stmt->fetchAll(), $page, $perPage, $total, "Payments retrieved");
    }
    
    /**
     * Get single payment
     * GET /payments/{id}
     */
    public function showPayment($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   i.Transaction_Type, i.Total_Amount as Invoice_Total, i.Payer_UserID,
                   payer.FirstName as Payer_FirstName, payer.LastName as Payer_LastName,
                   receiver.FirstName as Receiver_FirstName, receiver.LastName as Receiver_LastName
            FROM Payments p
            JOIN Invoices i ON p.InvoiceID = i.InvoiceID
            JOIN Users payer ON i.Payer_UserID = payer.UserID
            JOIN Users receiver ON p.Received_By_UserID = receiver.UserID
            WHERE p.PaymentID = :id
        ");
        $stmt->execute(['id' => $id]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            Response::notFound("Payment not found");
        }
        
        Response::success($payment);
    }
    
    /**
     * Record payment
     * POST /payments
     */
    public function recordPayment() {
        $this->validate([
            'invoice_id' => 'required|integer',
            'amount_paid' => 'required|numeric|positive',
            'payment_method' => 'required|in:Cash,GCash,Bank Transfer'
        ]);
        
        // Get invoice
        $stmt = $this->db->prepare("
            SELECT i.*, 
                   COALESCE((SELECT SUM(Amount_Paid) FROM Payments WHERE InvoiceID = i.InvoiceID), 0) as Already_Paid
            FROM Invoices i
            WHERE i.InvoiceID = :id AND i.Is_Deleted = FALSE
        ");
        $stmt->execute(['id' => $this->input('invoice_id')]);
        $invoice = $stmt->fetch();
        
        if (!$invoice) {
            Response::notFound("Invoice not found");
        }
        
        if ($invoice['Status'] === 'Paid') {
            Response::error("Invoice is already fully paid", 400);
        }
        
        if ($invoice['Status'] === 'Cancelled') {
            Response::error("Cannot add payment to cancelled invoice", 400);
        }
        
        $balance = $invoice['Total_Amount'] - $invoice['Already_Paid'];
        $amountPaid = (float)$this->input('amount_paid');
        
        // Warn if overpaying (but allow it)
        if ($amountPaid > $balance) {
            // You could either reject or allow overpayment
            // For now, we'll allow it but you might want to change this
        }
        
        $this->db->beginTransaction();
        
        try {
            // Record payment
            $stmt = $this->db->prepare("
                INSERT INTO Payments (InvoiceID, Received_By_UserID, Payment_Date, Amount_Paid, Payment_Method, Reference_Number)
                VALUES (:invoice_id, :received_by, NOW(), :amount, :method, :reference)
            ");
            
            $stmt->execute([
                'invoice_id' => $this->input('invoice_id'),
                'received_by' => $this->user['UserID'],
                'amount' => $amountPaid,
                'method' => $this->input('payment_method'),
                'reference' => $this->input('reference_number')
            ]);
            
            $paymentId = $this->db->lastInsertId();
            
            // Check if invoice is now fully paid
            $totalPaid = $invoice['Already_Paid'] + $amountPaid;
            
            if ($totalPaid >= $invoice['Total_Amount']) {
                $stmt = $this->db->prepare("UPDATE Invoices SET Status = 'Paid' WHERE InvoiceID = :id");
                $stmt->execute(['id' => $this->input('invoice_id')]);
            }
            
            $this->db->commit();
            
            $this->logActivity(
                'RECORD_PAYMENT', 
                "Recorded payment ID: {$paymentId} for invoice ID: {$this->input('invoice_id')} - PHP {$amountPaid} via {$this->input('payment_method')}"
            );
            
            // Get updated invoice
            $stmt = $this->db->prepare("
                SELECT i.*, 
                       COALESCE((SELECT SUM(Amount_Paid) FROM Payments WHERE InvoiceID = i.InvoiceID), 0) as Amount_Paid
                FROM Invoices i
                WHERE i.InvoiceID = :id
            ");
            $stmt->execute(['id' => $this->input('invoice_id')]);
            $updatedInvoice = $stmt->fetch();
            $updatedInvoice['Balance'] = $updatedInvoice['Total_Amount'] - $updatedInvoice['Amount_Paid'];
            
            Response::created([
                'payment_id' => $paymentId,
                'invoice' => $updatedInvoice
            ], "Payment recorded successfully");
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error recording payment: " . $e->getMessage());
            Response::serverError("Failed to record payment");
        }
    }
    
    /**
     * Get financial summary
     * GET /billing/summary
     */
    public function financialSummary() {
        // Overall totals
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(Amount_Paid), 0) as total_collected,
                COUNT(DISTINCT InvoiceID) as invoices_with_payments
            FROM Payments
        ");
        $stmt->execute();
        $overall = $stmt->fetch();
        
        // By transaction type
        $stmt = $this->db->prepare("
            SELECT 
                i.Transaction_Type,
                COUNT(DISTINCT i.InvoiceID) as invoice_count,
                SUM(i.Total_Amount) as total_billed,
                COALESCE(SUM(p.Amount_Paid), 0) as total_collected
            FROM Invoices i
            LEFT JOIN Payments p ON i.InvoiceID = p.InvoiceID
            WHERE i.Is_Deleted = FALSE
            GROUP BY i.Transaction_Type
        ");
        $stmt->execute();
        $byType = $stmt->fetchAll();
        
        // By payment method
        $stmt = $this->db->prepare("
            SELECT 
                Payment_Method,
                COUNT(*) as payment_count,
                SUM(Amount_Paid) as total_amount
            FROM Payments
            GROUP BY Payment_Method
        ");
        $stmt->execute();
        $byMethod = $stmt->fetchAll();
        
        // Monthly trend (last 6 months)
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(Payment_Date, '%Y-%m') as month,
                COUNT(*) as payment_count,
                SUM(Amount_Paid) as total_collected
            FROM Payments
            WHERE Payment_Date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(Payment_Date, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute();
        $monthlyTrend = $stmt->fetchAll();
        
        Response::success([
            'overall' => $overall,
            'by_transaction_type' => $byType,
            'by_payment_method' => $byMethod,
            'monthly_trend' => $monthlyTrend
        ], "Financial summary retrieved");
    }
    
    /**
     * Get financial report by date range
     * GET /billing/report
     */
    public function financialReport() {
        $this->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date'
        ]);
        
        $dateFrom = $this->query('date_from');
        $dateTo = $this->query('date_to');
        
        // Invoices in range
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as invoice_count,
                SUM(Total_Amount) as total_billed,
                SUM(CASE WHEN Status = 'Paid' THEN Total_Amount ELSE 0 END) as total_paid_invoices,
                SUM(CASE WHEN Status = 'Unpaid' THEN Total_Amount ELSE 0 END) as total_unpaid_invoices
            FROM Invoices
            WHERE Is_Deleted = FALSE
            AND DATE(Created_At) BETWEEN :date_from AND :date_to
        ");
        $stmt->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
        $invoiceSummary = $stmt->fetch();
        
        // Payments in range
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as payment_count,
                SUM(Amount_Paid) as total_collected
            FROM Payments
            WHERE DATE(Payment_Date) BETWEEN :date_from AND :date_to
        ");
        $stmt->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
        $paymentSummary = $stmt->fetch();
        
        // Daily breakdown
        $stmt = $this->db->prepare("
            SELECT 
                DATE(Payment_Date) as date,
                COUNT(*) as payment_count,
                SUM(Amount_Paid) as total_collected
            FROM Payments
            WHERE DATE(Payment_Date) BETWEEN :date_from AND :date_to
            GROUP BY DATE(Payment_Date)
            ORDER BY date ASC
        ");
        $stmt->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
        $dailyBreakdown = $stmt->fetchAll();
        
        Response::success([
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ],
            'invoices' => $invoiceSummary,
            'payments' => $paymentSummary,
            'daily_breakdown' => $dailyBreakdown
        ], "Financial report generated");
    }
}