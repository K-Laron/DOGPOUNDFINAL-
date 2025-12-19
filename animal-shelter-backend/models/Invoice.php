<?php
/**
 * Invoice Model
 */

class Invoice {
    private $conn;
    private $table = "Invoices";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create invoice
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (Payer_UserID, Issued_By_UserID, Transaction_Type, Total_Amount, 
                   Status, Related_AnimalID, Related_RequestID) 
                  VALUES (:payer_id, :issued_by, :type, :amount, 'Unpaid', :animal_id, :request_id)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':payer_id', $data['payer_user_id']);
        $stmt->bindParam(':issued_by', $data['issued_by_user_id']);
        $stmt->bindParam(':type', $data['transaction_type']);
        $stmt->bindParam(':amount', $data['total_amount']);
        $stmt->bindParam(':animal_id', $data['animal_id']);
        $stmt->bindParam(':request_id', $data['request_id']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    /**
     * Get invoice by ID
     */
    public function getById($id) {
        $query = "SELECT i.*, 
                         payer.FirstName as Payer_FirstName, payer.LastName as Payer_LastName, payer.Email as Payer_Email,
                         staff.FirstName as Staff_FirstName, staff.LastName as Staff_LastName,
                         a.Name as Animal_Name
                  FROM " . $this->table . " i
                  JOIN Users payer ON i.Payer_UserID = payer.UserID
                  JOIN Users staff ON i.Issued_By_UserID = staff.UserID
                  LEFT JOIN Animals a ON i.Related_AnimalID = a.AnimalID
                  WHERE i.InvoiceID = :id AND i.Is_Deleted = FALSE";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $invoice = $stmt->fetch();
        
        if ($invoice) {
            // Get payments
            $paymentQuery = "SELECT p.*, u.FirstName, u.LastName 
                            FROM Payments p 
                            JOIN Users u ON p.Received_By_UserID = u.UserID
                            WHERE p.InvoiceID = :id";
            $paymentStmt = $this->conn->prepare($paymentQuery);
            $paymentStmt->bindParam(':id', $id);
            $paymentStmt->execute();
            $invoice['payments'] = $paymentStmt->fetchAll();
            
            // Calculate amount paid
            $invoice['amount_paid'] = array_sum(array_column($invoice['payments'], 'Amount_Paid'));
            $invoice['balance'] = $invoice['Total_Amount'] - $invoice['amount_paid'];
        }
        
        return $invoice;
    }

    /**
     * Get all invoices with filters
     */
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $where = ["i.Is_Deleted = FALSE"];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "i.Status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $where[] = "i.Transaction_Type = :type";
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['payer_id'])) {
            $where[] = "i.Payer_UserID = :payer_id";
            $params[':payer_id'] = $filters['payer_id'];
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table . " i WHERE $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get data
        $query = "SELECT i.*, 
                         payer.FirstName as Payer_FirstName, payer.LastName as Payer_LastName,
                         a.Name as Animal_Name
                  FROM " . $this->table . " i
                  JOIN Users payer ON i.Payer_UserID = payer.UserID
                  LEFT JOIN Animals a ON i.Related_AnimalID = a.AnimalID
                  WHERE $whereClause
                  ORDER BY i.Created_At DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total
        ];
    }

    /**
     * Update invoice status
     */
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table . " SET Status = :status WHERE InvoiceID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Soft delete invoice
     */
    public function delete($id) {
        $query = "UPDATE " . $this->table . " SET Is_Deleted = TRUE, Status = 'Cancelled' WHERE InvoiceID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Record payment
     */
    public function recordPayment($invoiceId, $data) {
        $invoice = $this->getById($invoiceId);
        
        if (!$invoice || $invoice['Status'] === 'Paid' || $invoice['Status'] === 'Cancelled') {
            return ['error' => 'Invoice cannot accept payments'];
        }

        $query = "INSERT INTO Payments 
                  (InvoiceID, Received_By_UserID, Amount_Paid, Payment_Method, Reference_Number) 
                  VALUES (:invoice_id, :received_by, :amount, :method, :reference)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':invoice_id', $invoiceId);
        $stmt->bindParam(':received_by', $data['received_by_user_id']);
        $stmt->bindParam(':amount', $data['amount_paid']);
        $stmt->bindParam(':method', $data['payment_method']);
        $stmt->bindParam(':reference', $data['reference_number']);
        
        if ($stmt->execute()) {
            $paymentId = $this->conn->lastInsertId();
            
            // Check if invoice is fully paid
            $totalPaid = $invoice['amount_paid'] + $data['amount_paid'];
            if ($totalPaid >= $invoice['Total_Amount']) {
                $this->updateStatus($invoiceId, 'Paid');
            }
            
            return ['id' => $paymentId];
        }
        
        return ['error' => 'Failed to record payment'];
    }
}