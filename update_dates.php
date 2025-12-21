<?php
define('BASE_PATH', __DIR__ . '/backend');
define('APP_PATH', BASE_PATH . '/app');
require_once APP_PATH . '/config/database.php';

try {
    $db = (new Database())->getConnection();
    echo "Updating dates for testing statistics...\n";

    // 1. Update 'Completed' requests to be Updated_At NOW (to show in 'Completed This Month')
    $stmt = $db->prepare("UPDATE Adoption_Requests SET Updated_At = NOW() WHERE Status = 'Completed'");
    $stmt->execute();
    echo "✅ Set Updated_At to NOW for Completed requests.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
