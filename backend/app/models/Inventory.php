<?php
/**
 * Inventory Model
 * Handles inventory item database operations
 * 
 * @package AnimalShelter
 */

class Inventory {
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * @var string Table name
     */
    private $table = 'Inventory';
    
    /**
     * @var array Valid categories
     */
    private $validCategories = ['Medical', 'Food', 'Cleaning', 'Supplies'];
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Find inventory item by ID
     * 
     * @param int $id Item ID
     * @return array|false Item data or false
     */
    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT *,
                   CASE WHEN Quantity_On_Hand <= Reorder_Level THEN 1 ELSE 0 END as Is_Low_Stock,
                   CASE WHEN Expiration_Date IS NOT NULL AND Expiration_Date < CURDATE() THEN 1 ELSE 0 END as Is_Expired,
                   CASE WHEN Expiration_Date IS NOT NULL AND Expiration_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END as Is_Expiring_Soon
            FROM {$this->table} 
            WHERE ItemID = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Find item by name
     * 
     * @param string $name Item name
     * @return array|false Item data or false
     */
    public function findByName($name) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE Item_Name = :name");
        $stmt->execute(['name' => trim($name)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all items with pagination
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param array $filters Filter options
     * @return array ['data' => [], 'total' => int]
     */
    public function paginate($page = 1, $perPage = 20, $filters = []) {
        $where = ["1=1"];
        $params = [];
        
        // Filter by category
        if (!empty($filters['category'])) {
            $where[] = "Category = :category";
            $params['category'] = $filters['category'];
        }
        
        // Filter by low stock
        if (!empty($filters['low_stock']) && $filters['low_stock'] === true) {
            $where[] = "Quantity_On_Hand <= Reorder_Level";
        }
        
        // Filter by expired
        if (!empty($filters['expired']) && $filters['expired'] === true) {
            $where[] = "Expiration_Date IS NOT NULL AND Expiration_Date < CURDATE()";
        }
        
        // Filter by expiring soon
        if (!empty($filters['expiring_soon']) && $filters['expiring_soon'] === true) {
            $days = $filters['expiry_days'] ?? 30;
            $where[] = "Expiration_Date IS NOT NULL AND Expiration_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :expiry_days DAY)";
            $params['expiry_days'] = $days;
        }
        
        // Search by name or supplier
        if (!empty($filters['search'])) {
            $where[] = "(Item_Name LIKE :search OR Supplier_Name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        
        // Determine sort order
        $orderBy = "Item_Name ASC";
        if (!empty($filters['sort'])) {
            $allowedSorts = [
                'name_asc' => 'Item_Name ASC',
                'name_desc' => 'Item_Name DESC',
                'quantity_asc' => 'Quantity_On_Hand ASC',
                'quantity_desc' => 'Quantity_On_Hand DESC',
                'expiry_asc' => 'Expiration_Date ASC',
                'expiry_desc' => 'Expiration_Date DESC',
                'low_stock' => 'CASE WHEN Quantity_On_Hand <= Reorder_Level THEN 0 ELSE 1 END, Quantity_On_Hand ASC',
                'category' => 'Category ASC, Item_Name ASC'
            ];
            $orderBy = $allowedSorts[$filters['sort']] ?? $orderBy;
        }
        
        // Get total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM {$this->table} WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];
        
        // Get data
        $stmt = $this->db->prepare("
            SELECT *,
                   CASE WHEN Quantity_On_Hand <= Reorder_Level THEN 1 ELSE 0 END as Is_Low_Stock,
                   CASE WHEN Expiration_Date IS NOT NULL AND Expiration_Date < CURDATE() THEN 1 ELSE 0 END as Is_Expired,
                   CASE WHEN Expiration_Date IS NOT NULL AND Expiration_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END as Is_Expiring_Soon
            FROM {$this->table}
            WHERE {$whereClause}
            ORDER BY {$orderBy}
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
     * Get items by category
     * 
     * @param string $category Category name
     * @return array Items
     */
    public function getByCategory($category) {
        $stmt = $this->db->prepare("
            SELECT *,
                   CASE WHEN Quantity_On_Hand <= Reorder_Level THEN 1 ELSE 0 END as Is_Low_Stock
            FROM {$this->table}
            WHERE Category = :category
            ORDER BY Item_Name ASC
        ");
        $stmt->execute(['category' => $category]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get low stock items
     * 
     * @return array Low stock items
     */
    public function getLowStock() {
        $stmt = $this->db->prepare("
            SELECT *,
                   (Reorder_Level - Quantity_On_Hand) as Shortage,
                   ROUND((Quantity_On_Hand / NULLIF(Reorder_Level, 0)) * 100, 1) as Stock_Percentage
            FROM {$this->table}
            WHERE Quantity_On_Hand <= Reorder_Level
            ORDER BY Stock_Percentage ASC, Item_Name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get expiring items
     * 
     * @param int $days Days until expiry
     * @return array Expiring items
     */
    public function getExpiring($days = 30) {
        $stmt = $this->db->prepare("
            SELECT *,
                   DATEDIFF(Expiration_Date, CURDATE()) as Days_Until_Expiry
            FROM {$this->table}
            WHERE Expiration_Date IS NOT NULL
            AND Expiration_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
            ORDER BY Expiration_Date ASC
        ");
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get expired items
     * 
     * @return array Expired items
     */
    public function getExpired() {
        $stmt = $this->db->prepare("
            SELECT *,
                   DATEDIFF(CURDATE(), Expiration_Date) as Days_Expired
            FROM {$this->table}
            WHERE Expiration_Date IS NOT NULL
            AND Expiration_Date < CURDATE()
            ORDER BY Expiration_Date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all alerts (low stock + expiring + expired)
     * 
     * @param int $expiryDays Days for expiring soon threshold
     * @return array Alerts grouped by type
     */
    public function getAlerts($expiryDays = 30) {
        return [
            'low_stock' => $this->getLowStock(),
            'expiring_soon' => $this->getExpiring($expiryDays),
            'expired' => $this->getExpired(),
            'summary' => [
                'low_stock_count' => count($this->getLowStock()),
                'expiring_count' => count($this->getExpiring($expiryDays)),
                'expired_count' => count($this->getExpired())
            ]
        ];
    }
    
    /**
     * Create inventory item
     * 
     * @param array $data Item data
     * @return int|false Item ID or false
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (
                Item_Name,
                Category,
                Quantity_On_Hand,
                Reorder_Level,
                Expiration_Date,
                Supplier_Name,
                Last_Updated,
                Created_At
            ) VALUES (
                :name,
                :category,
                :quantity,
                :reorder_level,
                :expiration,
                :supplier,
                NOW(),
                NOW()
            )
        ");
        
        $result = $stmt->execute([
            'name' => trim($data['item_name']),
            'category' => $data['category'],
            'quantity' => $data['quantity_on_hand'] ?? 0,
            'reorder_level' => $data['reorder_level'] ?? 10,
            'expiration' => $data['expiration_date'] ?? null,
            'supplier' => $data['supplier_name'] ?? null
        ]);
        
        return $result ? (int)$this->db->lastInsertId() : false;
    }
    
    /**
     * Update inventory item
     * 
     * @param int $id Item ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = [
            'Item_Name' => 'item_name',
            'Category' => 'category',
            'Quantity_On_Hand' => 'quantity_on_hand',
            'Reorder_Level' => 'reorder_level',
            'Expiration_Date' => 'expiration_date',
            'Supplier_Name' => 'supplier_name'
        ];
        
        foreach ($allowedFields as $dbField => $dataKey) {
            if (array_key_exists($dataKey, $data)) {
                $fields[] = "{$dbField} = :{$dataKey}";
                $params[$dataKey] = $data[$dataKey];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $fields[] = "Last_Updated = NOW()";
        
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE ItemID = :id"
        );
        
        return $stmt->execute($params);
    }
    
    /**
     * Adjust stock quantity (add or subtract)
     * 
     * @param int $id Item ID
     * @param int $amount Amount to adjust (positive or negative)
     * @param string $operation 'add' or 'subtract'
     * @return bool Success status
     */
    public function adjustStock($id, $amount, $operation = 'add') {
        $item = $this->find($id);
        if (!$item) {
            return false;
        }
        
        $amount = abs((int)$amount);
        
        // Prevent negative stock
        if ($operation === 'subtract' && $amount > $item['Quantity_On_Hand']) {
            return false;
        }
        
        $operator = $operation === 'add' ? '+' : '-';
        
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET Quantity_On_Hand = Quantity_On_Hand {$operator} :amount,
                Last_Updated = NOW()
            WHERE ItemID = :id
        ");
        
        return $stmt->execute(['amount' => $amount, 'id' => $id]);
    }
    
    /**
     * Add stock
     * 
     * @param int $id Item ID
     * @param int $amount Amount to add
     * @return bool Success status
     */
    public function addStock($id, $amount) {
        return $this->adjustStock($id, $amount, 'add');
    }
    
    /**
     * Subtract stock
     * 
     * @param int $id Item ID
     * @param int $amount Amount to subtract
     * @return bool Success status
     */
    public function subtractStock($id, $amount) {
        return $this->adjustStock($id, $amount, 'subtract');
    }
    
    /**
     * Set stock quantity directly
     * 
     * @param int $id Item ID
     * @param int $quantity New quantity
     * @return bool Success status
     */
    public function setStock($id, $quantity) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET Quantity_On_Hand = :quantity, Last_Updated = NOW()
            WHERE ItemID = :id
        ");
        return $stmt->execute(['quantity' => max(0, (int)$quantity), 'id' => $id]);
    }
    
    /**
     * Delete inventory item
     * 
     * @param int $id Item ID
     * @return bool Success status
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE ItemID = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Get inventory statistics
     * 
     * @return array Statistics
     */
    public function getStatistics() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_items,
                SUM(Quantity_On_Hand) as total_quantity,
                SUM(CASE WHEN Quantity_On_Hand <= Reorder_Level THEN 1 ELSE 0 END) as low_stock_count,
                SUM(CASE WHEN Quantity_On_Hand = 0 THEN 1 ELSE 0 END) as out_of_stock_count,
                SUM(CASE WHEN Expiration_Date IS NOT NULL AND Expiration_Date < CURDATE() THEN 1 ELSE 0 END) as expired_count,
                SUM(CASE WHEN Expiration_Date IS NOT NULL AND Expiration_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon_count,
                SUM(CASE WHEN Category = 'Medical' THEN 1 ELSE 0 END) as medical_items,
                SUM(CASE WHEN Category = 'Food' THEN 1 ELSE 0 END) as food_items,
                SUM(CASE WHEN Category = 'Cleaning' THEN 1 ELSE 0 END) as cleaning_items,
                SUM(CASE WHEN Category = 'Supplies' THEN 1 ELSE 0 END) as supply_items
            FROM {$this->table}
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get statistics by category
     * 
     * @return array Category statistics
     */
    public function getStatsByCategory() {
        $stmt = $this->db->prepare("
            SELECT 
                Category,
                COUNT(*) as item_count,
                SUM(Quantity_On_Hand) as total_quantity,
                SUM(CASE WHEN Quantity_On_Hand <= Reorder_Level THEN 1 ELSE 0 END) as low_stock_count
            FROM {$this->table}
            GROUP BY Category
            ORDER BY Category
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search items
     * 
     * @param string $query Search query
     * @param int $limit Result limit
     * @return array Items
     */
    public function search($query, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT ItemID, Item_Name, Category, Quantity_On_Hand, Reorder_Level
            FROM {$this->table}
            WHERE Item_Name LIKE :query OR Supplier_Name LIKE :query
            ORDER BY Item_Name ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':query', '%' . $query . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get valid categories
     * 
     * @return array Categories
     */
    public function getCategories() {
        return $this->validCategories;
    }
    
    /**
     * Check if category is valid
     * 
     * @param string $category Category to check
     * @return bool
     */
    public function isValidCategory($category) {
        return in_array($category, $this->validCategories);
    }
    
    /**
     * Get items needing reorder
     * 
     * @return array Items grouped by supplier
     */
    public function getReorderList() {
        $stmt = $this->db->prepare("
            SELECT 
                Supplier_Name,
                Item_Name,
                Category,
                Quantity_On_Hand,
                Reorder_Level,
                (Reorder_Level - Quantity_On_Hand) as Suggested_Order_Quantity
            FROM {$this->table}
            WHERE Quantity_On_Hand <= Reorder_Level
            ORDER BY Supplier_Name, Item_Name
        ");
        $stmt->execute();
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by supplier
        $grouped = [];
        foreach ($items as $item) {
            $supplier = $item['Supplier_Name'] ?: 'Unknown Supplier';
            if (!isset($grouped[$supplier])) {
                $grouped[$supplier] = [];
            }
            $grouped[$supplier][] = $item;
        }
        
        return $grouped;
    }
}