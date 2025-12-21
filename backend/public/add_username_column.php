<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('APP_PATH', realpath(__DIR__ . '/../app'));
define('PUBLIC_PATH', __DIR__);
define('ROOT_PATH', dirname(__DIR__));
define('BASE_PATH', ROOT_PATH);

if (file_exists(APP_PATH . '/bootstrap.php')) {
    require_once APP_PATH . '/bootstrap.php';
} else {
    die('Bootstrap not found');
}

try {
    $db = (new Database())->getConnection();
    
    // Add Username column if not exists
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM information_schema.columns 
        WHERE table_schema = DATABASE() 
        AND table_name = 'Users' 
        AND column_name = 'Username'
    ");
    $stmt->execute();
    $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

    if (!$exists) {
        echo "Adding Username column...\n";
        // Add column
        $db->exec("ALTER TABLE Users ADD COLUMN Username VARCHAR(50) AFTER RoleID");
        
        // Populate existing users with a default username (user + ID)
        echo "Populating existing users...\n";
        $db->exec("UPDATE Users SET Username = CONCAT('user', UserID) WHERE Username IS NULL");
        
        // Make it Unique and Not Null (after populating)
        echo "Adding Unique constraint...\n";
        $db->exec("ALTER TABLE Users MODIFY COLUMN Username VARCHAR(50) NOT NULL UNIQUE");
        
        echo "Username column added and populated successfully.\n";
    } else {
        echo "Username column already exists.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
