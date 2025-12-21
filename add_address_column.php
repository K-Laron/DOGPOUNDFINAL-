<?php
// Script to add Address column to Users table
define('BASE_PATH', __DIR__ . '/backend');
define('APP_PATH', __DIR__ . '/backend/app');
define('PUBLIC_PATH', __DIR__ . '/frontend');

require_once APP_PATH . '/bootstrap.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if column exists
    $stmt = $conn->prepare("SHOW COLUMNS FROM Users LIKE 'Address'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $sql = "ALTER TABLE Users ADD COLUMN Address TEXT AFTER Contact_Number";
        $conn->exec($sql);
        echo "Successfully added Address column to Users table.\n";
    } else {
        echo "Address column already exists.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
