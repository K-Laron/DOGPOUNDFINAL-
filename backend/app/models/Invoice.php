<?php
/**
 * Invoice Model
 * Handles invoice database operations
 * 
 * @package AnimalShelter
 */

class Invoice {
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * @var string Table name
     */
    private $table = 'Invoices';
    
    /**
     * @var array Valid transaction types
     */
    private $validTypes = ['Adoption Fee', 'Reclaim Fee'];
    
    /**
     * @var array Valid statuses
     */
    private $validStatuses = ['Unpaid', 'Paid', 'Cancelled'];
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Find invoice by ID
     * 
     * @param int $id Invoice ID
     * @return array|false Invoice data or false
     */
    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT i.*,
                   payer.FirstName as Payer_FirstName, 
                   payer.LastName as Payer_LastName,
                   payer.Email as Payer_Email,
                   payer.Contact_Number as Payer_Contact,
                   staff.FirstName as Staff_FirstName, 
                   staff.LastName as Staff_LastName,
                   a.Name as Animal_Name, 
                   a.Type as Animal_Type
            FROM {$this->table} i
            JOIN Users payer ON i.Payer_UserID = payer.UserID
            JOIN Users staff ON i.Issued_By_UserID = staff.UserID
            LEFT JOIN Animals a ON i.Related_AnimalID = a.AnimalID
            WHERE i.InvoiceID = :id AND i.Is_Deleted = FALSE
        ");
        $stmt->execute(['id' => $id]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($invoice) {
            // Calculate amount paid and balance
            $invoice['Amount_Paid'] = $this->getAmountPaid($id);
            $invoice['Balance'] = $invoice['Total_Amount'] - $invoice['Amount_Paid'];
        }
        
        return $invoice;
    }
    
    /**
     * Find invoice with payments
     * 
     * @param int $id Invoice ID
     * @return array|false Invoice with payments or false
     */
    public function findWithPayments($id) {
        $invoice = $this->find($id);
        
        if (!$invoice) {
            return false;
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
        $invoice['payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $invoice;
    }
    
    /**
     * Get all invoices with pagination
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param array $filters Filter options
     * @return array ['data' => [], 'total' => int]
     */
    public function paginate($page = 1, $perPage = 20, $filters = []) {
        $where = ["i.Is_Deleted = FALSE"];
        $params = [];
        
        // Filter by status
        if (!empty($filters['status'])) {
            $where[] = "i.Status = :status";
            $params['status'] = $filters['status'];
        }
        
        // Filter by transaction type
        if (!empty($filters['type'])) {
            $where[] = "i.Transaction_Type = :type";
            $params['type'] = $filters['type'];
        }
        
        // Filter by payer
        if (!empty($filters['payer_id'])) {
            $where[] = "i.Payer_UserID = :payer_id";
            $params['payer_id'] = $filters['payer_id'];
        }
        
        // Filter by animal
        if (!empty($filters['animal_id'])) {
            $where[] = "i.Related_AnimalID = :animal_id";
            $params['animal_id'] = $filters['animal_id'];
        }
        
        // Date range
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(i.Created_At) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(i.Created_At) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        // Search
        if (!empty($filters['search'])) {
            $where[] = "(payer.FirstName LIKE :search OR payer.LastName LIKE :search OR payer.Email LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM {$this->table} i
            JOIN Users payer ON i.Payer_UserID = payer.UserID
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];
        
        // Get data
        $stmt = $this->db->prepare("
            SELECT i.*,
                   payer.FirstName as Payer_FirstName, 
                   payer.LastName as Payer_LastName,
                   payer.Email as Payer_Email,
                   staff.FirstName as Staff_FirstName, 
                   staff.LastName as Staff_LastName,
                   a.Name as Animal_Name,
                   COALESCE((SELECT SUM(Amount_Paid) FROM Payments WHERE InvoiceID = i.InvoiceID), 0) as Amount_Paid
            FROM {$this->table} i
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
        
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate balance for each invoice
        foreach ($invoices as &$invoice) {
            $invoice['Balance'] = $invoice['Total_Amount'] - $invoice['Amount_Paid'];
        }
        
        return [
            'data' => $invoices,
            'total' => $total
        ];
    }
    
    /**
     * Get invoices by payer
     * 
     * @param int $payerId Payer user ID
     * @return array Invoices
     */
    public function getByPayer($payerId) {
        $stmt = $this->db->prepare("
            SELECT i.*,
                   a.Name as Animal_Name,
                   COALESCE((SELECT SUM(Amount_Paid) FROM Payments WHERE InvoiceID = i.InvoiceID), 0) as Amount_Paid
            FROM {$this->table} i
            LEFT JOIN Animals a ON i.Related_AnimalID = a.AnimalID
            WHERE i.Payer_UserID = :payer_id AND i.Is_Deleted = FALSE
            ORDER BY i.Created_At DESC
        ");
        $stmt->execute(['payer_id' => $payerId]);
        
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($invoices as &$invoice) {
            $invoice['Balance'] = $invoice['Total_Amount'] - $invoice['Amount_Paid'];
        }
        
        return $invoices;
    }
    
    /**
     * Get unpaid invoices
     * 
     * @return array Unpaid invoices
     */
    public function getUnpaid() {
        $stmt = $this->db->prepare("
            SELECT i.*,
                   payer.FirstName as Payer_FirstName, 
                   payer.LastName as Payer_LastName,
                   payer.Email as Payer_Email,
                   a.Name as Animal_Name,
                   COALESCE((SELECT SUM(Amount_Paid) FROM Payments WHERE InvoiceID = i.InvoiceID), 0) as Amount_Paid
            FROM {$this->table} i
            JOIN Users payer ON i.Payer_UserID = payer.UserID
            LEFT JOIN Animals a ON i.Related_AnimalID = a.AnimalID
            WHERE i.Status = 'Unpaid' AND i.Is_Deleted = FALSE
            ORDER BY i.Created_At ASC
        ");
        $stmt->execute();
        
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($invoices as &$invoice) {
            $invoice['Balance'] = $invoice['Total_Amount'] - $invoice['Amount_Paid'];
        }
        
        return $invoices;
    }
    
    /**
     * Create invoice
     * 
     * @param array $data Invoice data
     * @return int|false Invoice ID or false
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (
                Payer_UserID,
                Issued_By_UserID,
                Transaction_Type,
                Total_Amount,
                Status,
                Related_AnimalID,
                Related_RequestID,
                Is_Deleted,
                Created_At,
                Updated_At
            ) VALUES (
                :payer_id,
                :issued_by,
                :type,
                :amount,
                'Unpaid',
                :animal_id,
                :request_id,
                FALSE,
                NOW(),
                NOW()
            )
        ");
        
        $result = $stmt->execute([
            'payer_id' => $data['payer_user_id'],
            'issued_by' => $data['issued_by_user_id'],
            'type' => $data['transaction_type'],
            'amount' => $data['total_amount'],
            'animal_id' => $data['animal_id'] ?? null,
            'request_id' => $data['request_id'] ?? null
        ]);
        
        return $result ? (int)$this->db->lastInsertId() : false;
    }
    
    /**
     * Update invoice status
     * 
     * @param int $id Invoice ID
     * @param string $status New status
     * @return bool Success status
     */
    public function updateStatus($id, $status) {
        if (!in_array($status, $this->validStatuses)) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET Status = :status, Updated_At = NOW()
            WHERE InvoiceID = :id
        ");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }
    
    /**
     * Mark invoice as paid
     * 
     * @param int $id Invoice ID
     * @return bool Success status
     */
    public function markAsPaid($id) {
        return $this->updateStatus($id, 'Paid');
    }
    
    /**
     * Cancel invoice
     * 
     * @param int $id Invoice ID
     * @return bool Success status
     */
    public function cancel($id) {
        $invoice = $this->find($id);
        
        if (!$invoice) {
            return false;
        }
        
        // Cannot cancel paid invoice
        if ($invoice['Status'] === 'Paid') {
            return false;
        }
        
        // Check for existing payments
        if ($invoice['Amount_Paid'] > 0) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET Status = 'Cancelled', Is_Deleted = TRUE, Updated_At = NOW()
            WHERE InvoiceID = :id
        ");
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Soft delete invoice
     * 
     * @param int $id Invoice ID
     * @return bool Success status
     */
    public function delete($id) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET Is_Deleted = TRUE, Updated_At = NOW()
            WHERE InvoiceID = :id
        ");
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Get amount paid for invoice
     * 
     * @param int $id Invoice ID
     * @return float Amount paid
     */
    public function getAmountPaid($id) {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(Amount_Paid), 0) as total
            FROM Payments
            WHERE InvoiceID = :id
        ");
        $stmt->execute(['id' => $id]);
        return (float)$stmt->fetch()['total'];
    }
    
    /**
     * Get balance for invoice
     * 
     * @param int $id Invoice ID
     * @return float|false Balance or false
     */
    public function getBalance($id) {
        $invoice = $this->find($id);
        
        if (!$invoice) {
            return false;
        }
        
        return $invoice['Total_Amount'] - $this->getAmountPaid($id);
    }
    
    /**
     * Check if invoice is fully paid
     * 
     * @param int $id Invoice ID
     * @return bool
     */
    public function isFullyPaid($id) {
        $balance = $this->getBalance($id);
        return $balance !== false && $balance <= 0;
    }
    
    /**
     * Update status based on payments
     * 
     * @param int $id Invoice ID
     * @return bool Success status
     */
    public function updateStatusFromPayments($id) {
        $invoice = $this->find($id);
        
        if (!$invoice || $invoice['Status'] === 'Cancelled') {
            return false;
        }
        
        if ($this->isFullyPaid($id)) {
            return $this->markAsPaid($id);
        }
        
        return true;
    }
    
    /**
     * Get invoice statistics
     * 
     * @return array Statistics
     */
    public function getStatistics() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_invoices,
                SUM(CASE WHEN Status = 'Unpaid' THEN 1 ELSE 0 END) as unpaid_count,
                SUM(CASE WHEN Status = 'Paid' THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN Status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                SUM(Total_Amount) as total_billed,
                SUM(CASE WHEN Status = 'Unpaid' THEN Total_Amount ELSE 0 END) as total_unpaid,
                SUM(CASE WHEN Status = 'Paid' THEN Total_Amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN Transaction_Type = 'Adoption Fee' THEN Total_Amount ELSE 0 END) as adoption_fees_total,
                SUM(CASE WHEN Transaction_Type = 'Reclaim Fee' THEN Total_Amount ELSE 0 END) as reclaim_fees_total
            FROM {$this->table}
            WHERE Is_Deleted = FALSE
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get statistics by transaction type
     * 
     * @return array Statistics by type
     */
    public function getStatsByType() {
        $stmt = $this->db->prepare("
            SELECT 
                Transaction_Type as type,
                COUNT(*) as count,
                SUM(Total_Amount) as total_amount,
                SUM(CASE WHEN Status = 'Paid' THEN Total_Amount ELSE 0 END) as paid_amount
            FROM {$this->table}
            WHERE Is_Deleted = FALSE
            GROUP BY Transaction_Type
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get monthly statistics
     * 
     * @param int $months Number of months
     * @return array Monthly statistics
     */
    public function getMonthlyStats($months = 12) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(Created_At, '%Y-%m') as month,
                COUNT(*) as invoice_count,
                SUM(Total_Amount) as total_billed,
                SUM(CASE WHEN Status = 'Paid' THEN Total_Amount ELSE 0 END) as total_paid
            FROM {$this->table}
            WHERE Is_Deleted = FALSE
            AND Created_At >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY DATE_FORMAT(Created_At, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute(['months' => $months]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get valid transaction types
     * 
     * @return array Types
     */
    public function getTransactionTypes() {
        return $this->validTypes;
    }
    
    /**
     * Get valid statuses
     * 
     * @return array Statuses
     */
    public function getStatuses() {
        return $this->validStatuses;
    }
}