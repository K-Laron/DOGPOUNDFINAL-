<?php
/**
 * Inventory Controller
 * Handles inventory management operations
 * 
 * @package AnimalShelter
 */

require_once APP_PATH . '/controllers/BaseController.php';

class InventoryController extends BaseController {
    
    /**
     * List all inventory items
     * GET /inventory
     */
    public function index() {
        list($page, $perPage) = $this->getPagination();
        
        $where = ["1=1"];
        $params = [];
        
        // Filter by category
        if ($this->query('category')) {
            $where[] = "Category = :category";
            $params['category'] = $this->query('category');
        }
        
        // Search
        if ($this->query('search')) {
            $where[] = "(Item_Name LIKE :search OR Supplier_Name LIKE :search)";
            $params['search'] = '%' . $this->query('search') . '%';
        }
        
        // Low stock filter
        if ($this->query('low_stock') === 'true') {
            $where[] = "Quantity_On_Hand <= Reorder_Level";
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM Inventory WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get items
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("
            SELECT *,
                   CASE WHEN Quantity_On_Hand <= Reorder_Level THEN 1 ELSE 0 END as Is_Low_Stock,
                   CASE WHEN Expiration_Date IS NOT NULL AND Expiration_Date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END as Is_Expiring_Soon
            FROM Inventory 
            WHERE {$whereClause}
            ORDER BY Is_Low_Stock DESC, Item_Name ASC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        Response::paginated($stmt->fetchAll(), $page, $perPage, $total, "Inventory items retrieved");
    }
    
    /**
     * Get inventory alerts (low stock & expiring)
     * GET /inventory/alerts
     */
    public function alerts() {
        $expiryDays = (int)$this->query('expiry_days', 30);
        
        // Out of stock items
        $stmt = $this->db->prepare("
            SELECT *
            FROM Inventory 
            WHERE Quantity_On_Hand <= 0
            ORDER BY Item_Name ASC
        ");
        $stmt->execute();
        $outOfStock = $stmt->fetchAll();

        // Low stock items
        $stmt = $this->db->prepare("
            SELECT *, 
                   (Reorder_Level - Quantity_On_Hand) as Shortage
            FROM Inventory 
            WHERE Quantity_On_Hand <= Reorder_Level
            ORDER BY (Quantity_On_Hand / NULLIF(Reorder_Level, 0)) ASC
        ");
        $stmt->execute();
        $lowStock = $stmt->fetchAll();
        
        // Expiring items
        $stmt = $this->db->prepare("
            SELECT *,
                    DATEDIFF(Expiration_Date, CURDATE()) as Days_Until_Expiry
            FROM Inventory 
            WHERE Expiration_Date IS NOT NULL 
            AND Expiration_Date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
            AND Expiration_Date >= CURDATE()
            ORDER BY Expiration_Date ASC
        ");
        $stmt->execute(['days' => $expiryDays]);
        $expiring = $stmt->fetchAll();
        
        // Already expired
        $stmt = $this->db->prepare("
            SELECT *,
                    DATEDIFF(CURDATE(), Expiration_Date) as Days_Expired
            FROM Inventory 
            WHERE Expiration_Date IS NOT NULL 
            AND Expiration_Date < CURDATE()
            ORDER BY Expiration_Date ASC
        ");
        $stmt->execute();
        $expired = $stmt->fetchAll();
        
        Response::success([
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
            'expiring_soon' => $expiring,
            'expired' => $expired,
            'summary' => [
                'out_of_stock_count' => count($outOfStock),
                'low_stock_count' => count($lowStock),
                'expiring_count' => count($expiring),
                'expired_count' => count($expired)
            ]
        ], "Inventory alerts retrieved");
    }
    
    /**
     * Get low stock items only
     * GET /inventory/low-stock
     */
    public function lowStock() {
        $stmt = $this->db->prepare("
            SELECT *, 
                   (Reorder_Level - Quantity_On_Hand) as Shortage,
                   ROUND((Quantity_On_Hand / NULLIF(Reorder_Level, 0)) * 100, 1) as Stock_Percentage
            FROM Inventory 
            WHERE Quantity_On_Hand > 0 AND Quantity_On_Hand <= Reorder_Level
            ORDER BY Stock_Percentage ASC
        ");
        $stmt->execute();
        
        Response::success($stmt->fetchAll(), "Low stock items retrieved");
    }
    
    /**
     * Get expiring items only
     * GET /inventory/expiring
     */
    public function expiring() {
        $days = (int)$this->query('days', 30);
        
        $stmt = $this->db->prepare("
            SELECT *,
                   DATEDIFF(Expiration_Date, CURDATE()) as Days_Until_Expiry
            FROM Inventory 
            WHERE Expiration_Date IS NOT NULL 
            AND Expiration_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
            ORDER BY Expiration_Date ASC
        ");
        $stmt->execute(['days' => $days]);
        
        Response::success($stmt->fetchAll(), "Expiring items retrieved");
    }
    
    /**
     * Get inventory statistics
     * GET /inventory/stats/summary
     */
    public function statistics() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_items,
                SUM(Quantity_On_Hand) as total_quantity,
                SUM(CASE WHEN Quantity_On_Hand > 0 AND Quantity_On_Hand <= Reorder_Level THEN 1 ELSE 0 END) as low_stock_count,
                SUM(CASE WHEN Quantity_On_Hand <= 0 THEN 1 ELSE 0 END) as out_of_stock_count,
                SUM(CASE WHEN Expiration_Date IS NOT NULL AND Expiration_Date < CURDATE() THEN 1 ELSE 0 END) as expired_count,
                SUM(CASE WHEN Expiration_Date IS NOT NULL AND Expiration_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon_count,
                SUM(CASE WHEN Category = 'Medical' THEN 1 ELSE 0 END) as medical_items,
                SUM(CASE WHEN Category = 'Food' THEN 1 ELSE 0 END) as food_items,
                SUM(CASE WHEN Category = 'Cleaning' THEN 1 ELSE 0 END) as cleaning_items,
                SUM(CASE WHEN Category = 'Supplies' THEN 1 ELSE 0 END) as supply_items
            FROM Inventory
        ");
        $stmt->execute();
        
        Response::success($stmt->fetch(), "Inventory statistics retrieved");
    }
    
    /**
     * Get single inventory item
     * GET /inventory/{id}
     */
    public function show($id) {
        $stmt = $this->db->prepare("
            SELECT *,
                   CASE WHEN Quantity_On_Hand <= Reorder_Level THEN 1 ELSE 0 END as Is_Low_Stock,
                   CASE WHEN Expiration_Date IS NOT NULL AND Expiration_Date < CURDATE() THEN 1 ELSE 0 END as Is_Expired
            FROM Inventory 
            WHERE ItemID = :id
        ");
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            Response::notFound("Inventory item not found");
        }
        
        Response::success($item);
    }
    
    /**
     * Create inventory item
     * POST /inventory
     */
    public function store() {
        $this->validate([
            'item_name' => 'required|max:100',
            'category' => 'required|in:Medical,Food,Cleaning,Supplies',
            'quantity_on_hand' => 'integer|nonNegative',
            'reorder_level' => 'integer|nonNegative'
        ]);
        
        $stmt = $this->db->prepare("
            INSERT INTO Inventory (Item_Name, Category, Quantity_On_Hand, Reorder_Level, Expiration_Date, Supplier_Name, Last_Updated)
            VALUES (:name, :category, :quantity, :reorder_level, :expiration, :supplier, NOW())
        ");
        
        $stmt->execute([
            'name' => $this->input('item_name'),
            'category' => $this->input('category'),
            'quantity' => $this->input('quantity_on_hand', 0),
            'reorder_level' => $this->input('reorder_level', 10),
            'expiration' => $this->input('expiration_date'),
            'supplier' => $this->input('supplier_name')
        ]);
        
        $itemId = $this->db->lastInsertId();
        
        $this->logActivity('CREATE_INVENTORY', "Created inventory item ID: {$itemId} ({$this->input('item_name')})");
        
        $stmt = $this->db->prepare("SELECT * FROM Inventory WHERE ItemID = :id");
        $stmt->execute(['id' => $itemId]);
        
        Response::created($stmt->fetch(), "Inventory item created");
    }
    
    /**
     * Update inventory item
     * PUT /inventory/{id}
     */
    public function update($id) {
        $stmt = $this->db->prepare("SELECT ItemID FROM Inventory WHERE ItemID = :id");
        $stmt->execute(['id' => $id]);
        
        if (!$stmt->fetch()) {
            Response::notFound("Inventory item not found");
        }
        
        $updates = [];
        $params = ['id' => $id];
        
        $fields = [
            'item_name' => 'Item_Name',
            'category' => 'Category',
            'quantity_on_hand' => 'Quantity_On_Hand',
            'reorder_level' => 'Reorder_Level',
            'expiration_date' => 'Expiration_Date',
            'supplier_name' => 'Supplier_Name'
        ];
        
        foreach ($fields as $inputKey => $dbField) {
            if ($this->has($inputKey)) {
                $updates[] = "{$dbField} = :{$inputKey}";
                $params[$inputKey] = $this->input($inputKey);
            }
        }
        
        if (empty($updates)) {
            Response::error("No fields to update", 400);
        }
        
        // Validate category if being updated
        if ($this->has('category')) {
            $this->validate(['category' => 'in:Medical,Food,Cleaning,Supplies']);
        }
        
        $updates[] = "Last_Updated = NOW()";
        
        $stmt = $this->db->prepare("UPDATE Inventory SET " . implode(', ', $updates) . " WHERE ItemID = :id");
        $stmt->execute($params);
        
        $this->logActivity('UPDATE_INVENTORY', "Updated inventory item ID: {$id}");
        
        $stmt = $this->db->prepare("SELECT * FROM Inventory WHERE ItemID = :id");
        $stmt->execute(['id' => $id]);
        
        Response::success($stmt->fetch(), "Inventory item updated");
    }
    
    /**
     * Adjust stock quantity
     * PATCH /inventory/{id}/adjust
     */
    public function adjustStock($id) {
        $stmt = $this->db->prepare("SELECT * FROM Inventory WHERE ItemID = :id");
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            Response::notFound("Inventory item not found");
        }
        
        $this->validate([
            'amount' => 'required|integer',
            'operation' => 'required|in:add,subtract'
        ]);
        
        $amount = abs((int)$this->input('amount'));
        $operation = $this->input('operation');
        $reason = $this->input('reason', '');
        
        // Prevent negative stock
        if ($operation === 'subtract' && $amount > $item['Quantity_On_Hand']) {
            Response::error("Cannot subtract more than current stock ({$item['Quantity_On_Hand']})", 400);
        }
        
        $operator = $operation === 'add' ? '+' : '-';
        
        $stmt = $this->db->prepare("
            UPDATE Inventory 
            SET Quantity_On_Hand = Quantity_On_Hand {$operator} :amount, Last_Updated = NOW()
            WHERE ItemID = :id
        ");
        $stmt->execute(['amount' => $amount, 'id' => $id]);
        
        $newQuantity = $operation === 'add' 
            ? $item['Quantity_On_Hand'] + $amount 
            : $item['Quantity_On_Hand'] - $amount;
        
        $this->logActivity(
            'ADJUST_INVENTORY', 
            "Adjusted {$item['Item_Name']}: {$operation} {$amount} (was: {$item['Quantity_On_Hand']}, now: {$newQuantity}). Reason: {$reason}"
        );
        
        $stmt = $this->db->prepare("SELECT * FROM Inventory WHERE ItemID = :id");
        $stmt->execute(['id' => $id]);
        
        Response::success($stmt->fetch(), "Stock adjusted successfully");
    }
    
    /**
     * Delete inventory item
     * DELETE /inventory/{id}
     */
    public function destroy($id) {
        $stmt = $this->db->prepare("SELECT Item_Name FROM Inventory WHERE ItemID = :id");
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            Response::notFound("Inventory item not found");
        }
        
        $stmt = $this->db->prepare("DELETE FROM Inventory WHERE ItemID = :id");
        $stmt->execute(['id' => $id]);
        
        $this->logActivity('DELETE_INVENTORY', "Deleted inventory item: {$item['Item_Name']}");
        
        Response::success(null, "Inventory item deleted");
    }
    
    /**
     * Get items by category
     * GET /inventory/category/{category}
     */
    public function byCategory($category) {
        // Validate category
        $validCategories = ['Medical', 'Food', 'Cleaning', 'Supplies'];
        if (!in_array($category, $validCategories)) {
            Response::error("Invalid category. Must be one of: " . implode(', ', $validCategories), 400);
        }
        
        list($page, $perPage) = $this->getPagination();
        
        // Get total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM Inventory WHERE Category = :category");
        $countStmt->execute(['category' => $category]);
        $total = $countStmt->fetch()['total'];
        
        // Get items
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("
            SELECT *,
                   CASE WHEN Quantity_On_Hand <= Reorder_Level THEN 1 ELSE 0 END as Is_Low_Stock
            FROM Inventory 
            WHERE Category = :category
            ORDER BY Item_Name ASC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        Response::paginated($stmt->fetchAll(), $page, $perPage, $total, "{$category} items retrieved");
    }
}