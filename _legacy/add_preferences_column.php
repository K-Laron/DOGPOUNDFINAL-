<?php
// Define necessary constants for the application to bootstrap properly
define('BASE_PATH', __DIR__ . '/backend');
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('APP_ENV', 'development');

require_once APP_PATH . '/bootstrap.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if column exists
    $stmt = $db->prepare("SHOW COLUMNS FROM Users LIKE 'Preferences'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $sql = "ALTER TABLE Users ADD COLUMN Preferences JSON DEFAULT NULL AFTER Contact_Number";
        $db->exec($sql);
        echo "Successfully added Preferences column to Users table.\n";
    } else {
        echo "Preferences column already exists.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
