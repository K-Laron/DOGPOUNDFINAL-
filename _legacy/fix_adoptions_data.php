<?php
define('BASE_PATH', __DIR__ . '/backend');
define('APP_PATH', BASE_PATH . '/app');
require_once APP_PATH . '/config/database.php';

try {
    $db = (new Database())->getConnection();
    echo "Starting data fix...\n";

    // 1. Get Adopter Role ID
    $stmt = $db->query("SELECT RoleID FROM Roles WHERE Role_Name = 'Adopter'");
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    $roleId = $role['RoleID'];
    echo "Adopter Role ID: $roleId\n";

    // 2. Create new users
    $newUsers = [
        [
            'first' => 'Juan', 'last' => 'Dela Cruz', 
            'email' => 'juan@example.com', 'contact' => '09123456789'
        ],
        [
            'first' => 'Maria', 'last' => 'Clara', 
            'email' => 'maria@example.com', 'contact' => '09987654321'
        ],
        [
            'first' => 'Sarah', 'last' => 'Geronimo', 
            'email' => 'sarah@example.com', 'contact' => '09112223333'
        ]
    ];

    $userIds = [];

    $insertUser = $db->prepare("
        INSERT INTO Users (RoleID, FirstName, LastName, Email, Contact_Number, Password_Hash, Account_Status)
        VALUES (:role, :first, :last, :email, :contact, :pass, 'Active')
    ");

    $checkUser = $db->prepare("SELECT UserID FROM Users WHERE Email = ?");

    $password = password_hash('password', PASSWORD_DEFAULT);

    foreach ($newUsers as $u) {
        // Check if exists
        $checkUser->execute([$u['email']]);
        $existing = $checkUser->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            echo "User {$u['email']} already exists (ID: {$existing['UserID']})\n";
            $userIds[] = $existing['UserID'];
        } else {
            $insertUser->execute([
                'role' => $roleId,
                'first' => $u['first'],
                'last' => $u['last'],
                'email' => $u['email'],
                'contact' => $u['contact'],
                'pass' => $password
            ]);
            $newId = $db->lastInsertId();
            echo "Created user {$u['first']} (ID: $newId)\n";
            $userIds[] = $newId;
        }
    }

    // 3. Update Adoption Requests
    // We know we have IDs 1, 2, 3, 4 based on debug output.
    // Let's mix them up.
    // ID 1 -> User 0 (Juan)
    // ID 3 -> User 1 (Maria)
    // ID 4 -> User 2 (Sarah)
    // ID 2 -> Leave as Pedro (UserID 4, usually)

    $updates = [
        1 => $userIds[0],
        3 => $userIds[1],
        4 => $userIds[2]
    ];

    $updateStmt = $db->prepare("UPDATE Adoption_Requests SET Adopter_UserID = ? WHERE RequestID = ?");

    foreach ($updates as $reqId => $uid) {
        $updateStmt->execute([$uid, $reqId]);
        echo "Updated Request #$reqId to User ID $uid\n";
    }

    echo "✅ Data diversity fix applied!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
