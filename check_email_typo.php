<?php
define('BASE_PATH', __DIR__ . '/backend');
define('APP_PATH', BASE_PATH . '/app');
require_once APP_PATH . '/config/database.php';

try {
    $db = (new Database())->getConnection();
    
    $emails = [
        'admin@cataramandogpound.com', // What user likely typed (3 'a's)
        'admin@catarmandogpound.com',  // What check_admin.php used (2 'a's)
        'admin@catarman.com'
    ];

    echo "Checking emails...\n";

    foreach ($emails as $email) {
        $stmt = $db->prepare("SELECT UserID, Email, Password_Hash FROM Users WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo "✅ FOUND: $email (ID: " . $user['UserID'] . ")\n";
            if (password_verify('password', $user['Password_Hash'])) {
                echo "   -> Password is 'password'\n";
            } elseif (password_verify('admin123', $user['Password_Hash'])) {
                echo "   -> Password is 'admin123'\n";
            }
        } else {
            echo "❌ NOT FOUND: $email\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
