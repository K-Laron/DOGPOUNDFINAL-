<?php
/**
 * Inventory Model
 */

class Inventory {
    private $conn;
    private $table = "Inventory";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create inventory item
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (Item_Name, Category, Quantity_On_Hand, Reorder_Level, 
                   Expiration_Date, Supplier_Name) 
                  VALUES (:name, :category, :quantity, :reorder_level, 
                          :expiration_date, :supplier)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $data['item_name']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindValue(':quantity', $data['quantity'] ?? 0);
        $stmt->bindValue(':reorder_level', $data['reorder_level'] ?? 10);
        $stmt->bindParam(':expiration_date', $data['expiration_date']);
        $stmt->bindParam(':supplier', $data['supplier_name']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    /**
     * Get item by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE ItemID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * Get all items with filters
     */
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['category'])) {
            $where[] = "Category = :category";
            $params[':category'] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(Item_Name LIKE :search OR Supplier_Name LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $where[] = "Quantity_On_Hand <= Reorder_Level";
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get data
        $query = "SELECT *, 
                         CASE WHEN Quantity_On_Hand <= Reorder_Level THEN 1 ELSE 0 END as Is_Low_Stock
                  FROM " . $this->table . " 
                  WHERE $whereClause 
                  ORDER BY Is_Low_Stock DESC, Item_Name ASC
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
     * Update item
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'Item_Name' => 'item_name',
            'Category' => 'category',
            'Quantity_On_Hand' => 'quantity',
            'Reorder_Level' => 'reorder_level',
            'Expiration_Date' => 'expiration_date',
            'Supplier_Name' => 'supplier_name'
        ];
        
        foreach ($allowedFields as $dbField => $dataKey) {
            if (isset($data[$dataKey])) {
                $fields[] = "$dbField = :$dataKey";
                $params[":$dataKey"] = $data[$dataKey];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE ItemID = :id";
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute($params);
    }

    /**
     * Adjust quantity
     */
    public function adjustQuantity($id, $amount, $operation = 'add') {
        $operator = $operation === 'add' ? '+' : '-';
        $query = "UPDATE " . $this->table . " 
                  SET Quantity_On_Hand = Quantity_On_Hand $operator :amount 
                  WHERE ItemID = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Get low stock items
     */
    public function getLowStock() {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE Quantity_On_Hand <= Reorder_Level 
                  ORDER BY (Quantity_On_Hand / Reorder_Level) ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get expiring items
     */
    public function getExpiring($days = 30) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE Expiration_Date IS NOT NULL 
                  AND Expiration_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                  ORDER BY Expiration_Date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Delete item
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE ItemID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
}