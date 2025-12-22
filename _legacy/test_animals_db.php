<?php
// Load environment and config
require_once __DIR__ . '/backend/app/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Database connected.\n";

    $stmt = $db->query("SELECT COUNT(*) as total FROM Animals");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total animals: " . $total['total'] . "\n";

    $stmt = $db->query("SELECT * FROM Animals LIMIT 1");
    $animal = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "First animal: " . json_encode($animal) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
