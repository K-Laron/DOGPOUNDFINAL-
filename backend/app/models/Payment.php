<?php
/**
 * Payment Model
 * Handles payment database operations
 * 
 * @package AnimalShelter
 */

class Payment {
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * @var string Table name
     */
    private $table = 'Payments';
    
    /**
     * @var array Valid payment methods
     */
    private $validMethods = ['Cash', 'GCash', 'Bank Transfer'];
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Find payment by ID
     * 
     * @param int $id Payment ID
     * @return array|false Payment data or false
     */
    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT p.*,
                   i.Transaction_Type, 
                   i.Total_Amount as Invoice_Total,
                   i.Payer_UserID,
                   payer.FirstName as Payer_FirstName, 
                   payer.LastName as Payer_LastName,
                   payer.Email as Payer_Email,
                   receiver.FirstName as Receiver_FirstName, 
                   receiver.LastName as Receiver_LastName
            FROM {$this->table} p
            JOIN Invoices i ON p.InvoiceID = i.InvoiceID
            JOIN Users payer ON i.Payer_UserID = payer.UserID
            JOIN Users receiver ON p.Received_By_UserID = receiver.UserID
            WHERE p.PaymentID = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get payments by invoice
     * 
     * @param int $invoiceId Invoice ID
     * @return array Payments
     */
    public function getByInvoice($invoiceId) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.FirstName, u.LastName
            FROM {$this->table} p
            JOIN Users u ON p.Received_By_UserID = u.UserID
            WHERE p.InvoiceID = :invoice_id
            ORDER BY p.Payment_Date DESC
        ");
        $stmt->execute(['invoice_id' => $invoiceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all payments with pagination
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param array $filters Filter options
     * @return array ['data' => [], 'total' => int]
     */
    public function paginate($page = 1, $perPage = 20, $filters = []) {
        $where = ["1=1"];
        $params = [];
        
        // Filter by invoice
        if (!empty($filters['invoice_id'])) {
            $where[] = "p.InvoiceID = :invoice_id";
            $params['invoice_id'] = $filters['invoice_id'];
        }
        
        // Filter by payment method
        if (!empty($filters['payment_method'])) {
            $where[] = "p.Payment_Method = :method";
            $params['method'] = $filters['payment_method'];
        }
        
        // Filter by receiver
        if (!empty($filters['received_by'])) {
            $where[] = "p.Received_By_UserID = :received_by";
            $params['received_by'] = $filters['received_by'];
        }
        
        // Date range
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(p.Payment_Date) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(p.Payment_Date) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        // Filter by transaction type
        if (!empty($filters['transaction_type'])) {
            $where[] = "i.Transaction_Type = :transaction_type";
            $params['transaction_type'] = $filters['transaction_type'];
        }
        
        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM {$this->table} p
            JOIN Invoices i ON p.InvoiceID = i.InvoiceID
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];
        
        // Get data
        $stmt = $this->db->prepare("
            SELECT p.*,
                   i.Transaction_Type, 
                   i.Total_Amount as Invoice_Total,
                   payer.FirstName as Payer_FirstName, 
                   payer.LastName as Payer_LastName,
                   receiver.FirstName as Receiver_FirstName, 
                   receiver.LastName as Receiver_LastName
            FROM {$this->table} p
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
        
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total
        ];
    }
    
    /**
     * Get today's payments
     * 
     * @return array Today's payments
     */
    public function getToday() {
        $stmt = $this->db->prepare("
            SELECT p.*,
                   i.Transaction_Type,
                   payer.FirstName as Payer_FirstName, 
                   payer.LastName as Payer_LastName,
                   receiver.FirstName as Receiver_FirstName, 
                   receiver.LastName as Receiver_LastName
            FROM {$this->table} p
            JOIN Invoices i ON p.InvoiceID = i.InvoiceID
            JOIN Users payer ON i.Payer_UserID = payer.UserID
            JOIN Users receiver ON p.Received_By_UserID = receiver.UserID
            WHERE DATE(p.Payment_Date) = CURDATE()
            ORDER BY p.Payment_Date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create payment
     * 
     * @param array $data Payment data
     * @return int|false Payment ID or false
     */
    public function create($data) {
        // Validate payment method
        if (!in_array($data['payment_method'], $this->validMethods)) {
            return false;
        }
        
        $this->db->beginTransaction();
        
        try {
            // Create payment record
            $stmt = $this->db->prepare("
                INSERT INTO {$this->table} (
                    InvoiceID,
                    Received_By_UserID,
                    Payment_Date,
                    Amount_Paid,
                    Payment_Method,
                    Reference_Number,
                    Created_At
                ) VALUES (
                    :invoice_id,
                    :received_by,
                    :payment_date,
                    :amount,
                    :method,
                    :reference,
                    NOW()
                )
            ");
            
            $result = $stmt->execute([
                'invoice_id' => $data['invoice_id'],
                'received_by' => $data['received_by_user_id'],
                'payment_date' => $data['payment_date'] ?? date('Y-m-d H:i:s'),
                'amount' => $data['amount_paid'],
                'method' => $data['payment_method'],
                'reference' => $data['reference_number'] ?? null
            ]);
            
            if (!$result) {
                throw new Exception("Failed to create payment record");
            }
            
            $paymentId = (int)$this->db->lastInsertId();
            
            // Check if invoice is now fully paid
            $this->updateInvoiceStatus($data['invoice_id']);
            
            $this->db->commit();
            return $paymentId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating payment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update invoice status based on payments
     * 
     * @param int $invoiceId Invoice ID
     * @return bool Success status
     */
    private function updateInvoiceStatus($invoiceId) {
        // Get invoice total and total paid
        $stmt = $this->db->prepare("
            SELECT i.Total_Amount, COALESCE(SUM(p.Amount_Paid), 0) as Total_Paid
            FROM Invoices i
            LEFT JOIN {$this->table} p ON i.InvoiceID = p.InvoiceID
            WHERE i.InvoiceID = :id
            GROUP BY i.InvoiceID
        ");
        $stmt->execute(['id' => $invoiceId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['Total_Paid'] >= $result['Total_Amount']) {
            $stmt = $this->db->prepare("
                UPDATE Invoices 
                SET Status = 'Paid', Updated_At = NOW() 
                WHERE InvoiceID = :id
            ");
            return $stmt->execute(['id' => $invoiceId]);
        }
        
        return true;
    }
    
    /**
     * Delete payment (use with caution)
     * 
     * @param int $id Payment ID
     * @return bool Success status
     */
    public function delete($id) {
        $payment = $this->find($id);
        
        if (!$payment) {
            return false;
        }
        
        $this->db->beginTransaction();
        
        try {
            // Delete payment
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE PaymentID = :id");
            $stmt->execute(['id' => $id]);
            
            // Update invoice status back to Unpaid if needed
            $stmt = $this->db->prepare("
                UPDATE Invoices 
                SET Status = 'Unpaid', Updated_At = NOW() 
                WHERE InvoiceID = :id AND Status = 'Paid'
            ");
            $stmt->execute(['id' => $payment['InvoiceID']]);
            
            // Recheck if invoice should still be paid
            $this->updateInvoiceStatus($payment['InvoiceID']);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error deleting payment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get total collected for date range
     * 
     * @param string $dateFrom Start date
     * @param string $dateTo End date
     * @return float Total collected
     */
    public function getTotalCollected($dateFrom = null, $dateTo = null) {
        $where = "1=1";
        $params = [];
        
        if ($dateFrom) {
            $where .= " AND DATE(Payment_Date) >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        
        if ($dateTo) {
            $where .= " AND DATE(Payment_Date) <= :date_to";
            $params['date_to'] = $dateTo;
        }
        
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(Amount_Paid), 0) as total
            FROM {$this->table}
            WHERE {$where}
        ");
        $stmt->execute($params);
        return (float)$stmt->fetch()['total'];
    }
    
    /**
     * Get payment statistics
     * 
     * @return array Statistics
     */
    public function getStatistics() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_payments,
                SUM(Amount_Paid) as total_collected,
                AVG(Amount_Paid) as average_payment,
                MAX(Amount_Paid) as largest_payment,
                MIN(Amount_Paid) as smallest_payment,
                SUM(CASE WHEN DATE(Payment_Date) = CURDATE() THEN Amount_Paid ELSE 0 END) as collected_today,
                SUM(CASE WHEN YEARWEEK(Payment_Date) = YEARWEEK(CURDATE()) THEN Amount_Paid ELSE 0 END) as collected_this_week,
                SUM(CASE WHEN MONTH(Payment_Date) = MONTH(CURDATE()) AND YEAR(Payment_Date) = YEAR(CURDATE()) THEN Amount_Paid ELSE 0 END) as collected_this_month
            FROM {$this->table}
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get statistics by payment method
     * 
     * @return array Statistics by method
     */
    public function getStatsByMethod() {
        $stmt = $this->db->prepare("
            SELECT 
                Payment_Method as method,
                COUNT(*) as count,
                SUM(Amount_Paid) as total_amount,
                AVG(Amount_Paid) as average_amount
            FROM {$this->table}
            GROUP BY Payment_Method
            ORDER BY total_amount DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get daily collection for date range
     * 
     * @param int $days Number of days
     * @return array Daily collection
     */
    public function getDailyCollection($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(Payment_Date) as date,
                COUNT(*) as payment_count,
                SUM(Amount_Paid) as total_collected
            FROM {$this->table}
            WHERE Payment_Date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            GROUP BY DATE(Payment_Date)
            ORDER BY date ASC
        ");
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get monthly collection
     * 
     * @param int $months Number of months
     * @return array Monthly collection
     */
    public function getMonthlyCollection($months = 12) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(Payment_Date, '%Y-%m') as month,
                COUNT(*) as payment_count,
                SUM(Amount_Paid) as total_collected,
                SUM(CASE WHEN Payment_Method = 'Cash' THEN Amount_Paid ELSE 0 END) as cash_total,
                SUM(CASE WHEN Payment_Method = 'GCash' THEN Amount_Paid ELSE 0 END) as gcash_total,
                SUM(CASE WHEN Payment_Method = 'Bank Transfer' THEN Amount_Paid ELSE 0 END) as bank_total
            FROM {$this->table}
            WHERE Payment_Date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY DATE_FORMAT(Payment_Date, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute(['months' => $months]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get payments by staff
     * 
     * @param int $userId Staff user ID
     * @param int $limit Limit
     * @return array Payments
     */
    public function getByStaff($userId, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT p.*,
                   i.Transaction_Type,
                   payer.FirstName as Payer_FirstName, 
                   payer.LastName as Payer_LastName
            FROM {$this->table} p
            JOIN Invoices i ON p.InvoiceID = i.InvoiceID
            JOIN Users payer ON i.Payer_UserID = payer.UserID
            WHERE p.Received_By_UserID = :user_id
            ORDER BY p.Payment_Date DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get valid payment methods
     * 
     * @return array Methods
     */
    public function getPaymentMethods() {
        return $this->validMethods;
    }
    
    /**
     * Validate payment method
     * 
     * @param string $method Method to validate
     * @return bool
     */
    public function isValidMethod($method) {
        return in_array($method, $this->validMethods);
    }
}