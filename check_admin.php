<?php
// Define base paths consistent with the app
define('BASE_PATH', __DIR__ . '/backend');
define('APP_PATH', BASE_PATH . '/app');

// Mock config constants if needed
define('APP_ENV', 'development');

require_once APP_PATH . '/config/database.php';

try {
    $db = (new Database())->getConnection();
    echo "Connected to database successfully.\n";

    $email = 'admin@catarmandogpound.com';
    $password = 'admin123';

    echo "Checking user: $email\n";

    $stmt = $db->prepare("SELECT * FROM Users WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "❌ User found: NO\n";
    } else {
        echo "✅ User found: YES (ID: " . $user['UserID'] . ")\n";
        echo "Stored Hash: " . $user['Password_Hash'] . "\n";
        
        $match = password_verify($password, $user['Password_Hash']);
        if ($match) {
            echo "✅ Password verify: MATCHES 'admin123'\n";
        } else {
            echo "❌ Password verify: DOES NOT MATCH 'admin123'\n";
            
            // Test common defaults
            if (password_verify('password', $user['Password_Hash'])) {
                echo "💡 IT MATCHES 'password' INSTEAD!\n";
            }
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
