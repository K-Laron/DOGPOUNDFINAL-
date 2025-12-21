<?php
// Load bootstrap (minimal)
define('APP_PATH', __DIR__ . '/app');
require_once APP_PATH . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h1>Debug Notifications</h1>";
    
    // 1. Low Stock
    echo "<h2>Low Stock</h2>";
    $stmt = $db->query("SELECT * FROM Inventory WHERE Quantity_On_Hand <= Reorder_Level");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($items, true) . "</pre>";
    
    // 2. Expiring
    echo "<h2>Expiring</h2>";
    $stmt = $db->query("SELECT * FROM Inventory WHERE Expiration_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($items, true) . "</pre>";

    // 3. Unpaid Invoices (Strict)
    echo "<h2>Unpaid Invoices (Strict > 30 days)</h2>";
    $stmt = $db->query("SELECT * FROM Invoices WHERE Status = 'Unpaid' AND Created_At < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($items, true) . "</pre>";

    // 4. Unpaid Invoices (All)
    echo "<h2>Unpaid Invoices (All)</h2>";
    $stmt = $db->query("SELECT * FROM Invoices WHERE Status = 'Unpaid'");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($items, true) . "</pre>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
