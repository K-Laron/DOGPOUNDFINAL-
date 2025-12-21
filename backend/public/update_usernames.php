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
    
    // Get all users
    $stmt = $db->query("SELECT UserID, FirstName FROM Users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($users) . " users. Updating usernames...\n";
    
    $updateStmt = $db->prepare("UPDATE Users SET Username = :username WHERE UserID = :id");
    
    foreach ($users as $user) {
        $baseUsername = strtolower(str_replace(' ', '', trim($user['FirstName'])));
        
        // Ensure not empty
        if (empty($baseUsername)) {
            $baseUsername = 'user' . $user['UserID'];
        }
        
        $username = $baseUsername;
        $attempt = 0;
        
        // Loop until unique
        while (true) {
            try {
                // Try to update directly
                // We check uniqueness 'on the fly' by catching the error? 
                // Or better, checking beforehand. 
                // Since this is a one-off script, checking beforehand is safer/cleaner logic-wise but slower.
                // Or we can just try to update and catch integrity violation.
                
                // Let's check if another user has this username ALREADY (excluding self)
                $check = $db->prepare("SELECT UserID FROM Users WHERE Username = :username AND UserID != :id");
                $check->execute(['username' => $username, 'id' => $user['UserID']]);
                if ($check->fetch()) {
                    // Exists, modify
                    $username = $baseUsername . $user['UserID']; // Fallback to appending ID immediately to ensure uniqueness
                    // Or could append sequential numbers, but ID is guaranteed unique.
                } else {
                    // Safe to update
                    $updateStmt->execute(['username' => $username, 'id' => $user['UserID']]);
                    echo "Updated User {$user['UserID']} ({$user['FirstName']}) -> {$username}\n";
                    break;
                }
                
            } catch (PDOException $e) {
                // Handle constraint violation if race condition or I missed something
                if ($e->getCode() == 23000) {
                     $username = $baseUsername . $user['UserID'];
                     continue;
                } else {
                    echo "Error updating User {$user['UserID']}: " . $e->getMessage() . "\n";
                    break;
                }
            }
        }
    }
    
    echo "Done.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
