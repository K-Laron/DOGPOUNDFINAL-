<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // add Token_Version column if it doesn't exist
    $sql = "
        SELECT COUNT(*) 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = :dbname 
        AND TABLE_NAME = 'Users' 
        AND COLUMN_NAME = 'Token_Version'
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['dbname' => DB_NAME]);
    
    if ($stmt->fetchColumn() == 0) {
        $conn->exec("ALTER TABLE Users ADD COLUMN Token_Version INT DEFAULT 1");
        echo "Successfully added Token_Version column.\n";
    } else {
        echo "Token_Version column already exists.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
