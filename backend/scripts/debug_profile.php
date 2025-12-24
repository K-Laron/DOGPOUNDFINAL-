<?php
// Debug User Profile Script
// Simulates UserController::profile logic to find errors

require_once __DIR__ . '/../app/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connected.\n";

    // 1. Test Admin Profile
    echo "\n--- Testing ADMIN Profile ---\n";
    $stmt = $db->prepare("SELECT UserID FROM Users WHERE Email = 'admin@catarmandogpound.com'");
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin) {
        $id = $admin['UserID'];
        echo "Found Admin ID: $id\n";
        testProfileLogic($db, $id);
    } else {
        echo "❌ Admin user not found.\n";
    }

    // 2. Test Vet Profile
    echo "\n--- Testing VET Profile ---\n";
    $stmt = $db->prepare("SELECT UserID FROM Users WHERE Email LIKE 'vet%' LIMIT 1");
    $stmt->execute();
    $vet = $stmt->fetch();

    if ($vet) {
        $id = $vet['UserID'];
        echo "Found Vet ID: $id\n";
        testProfileLogic($db, $id);
    } else {
        echo "⚠️ Vet user not found.\n";
    }

} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

function testProfileLogic($db, $id) {
    try {
        // 1. Find User (Query from UserController)
        $query = "
            SELECT 
                u.UserID,
                u.RoleID,
                u.FirstName,
                u.LastName,
                u.Username,
                u.Email,
                u.Contact_Number,
                u.Address,
                u.Avatar_Url,
                u.Account_Status,
                u.Is_Deleted,
                u.Preferences,
                u.Created_At,
                u.Updated_At,
                r.Role_Name
            FROM Users u
            JOIN Roles r ON u.RoleID = r.RoleID
            WHERE u.UserID = :id AND u.Is_Deleted = FALSE
        ";
        $stmt = $db->prepare($query);
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        if (!$user) {
            echo "❌ User query returned empty.\n";
            return;
        }
        echo "✅ User basic query successful.\n";
        echo "Role: " . $user['Role_Name'] . "\n";

        // 2. Format Response
        $response = [
            'id' => (int)$user['UserID'],
            'username' => $user['Username'],
            'preferences' => isset($user['Preferences']) ? json_decode($user['Preferences'], true) : [],
        ];

        // 3. Get Stats (if Admin/Staff)
        if ($user['Role_Name'] === 'Staff' || $user['Role_Name'] === 'Admin') {
            echo "Fetching Admin/Staff stats...\n";
            // Mock getUserStats
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM Activity_Logs WHERE UserID = :id");
            $stmt->execute(['id' => $id]);
            echo "✅ Stats query successful.\n";
        }

        // 4. Get Vet Details (if Vet)
        if ($user['Role_Name'] === 'Veterinarian') {
            echo "Fetching Vet details...\n";
            $query = "
                SELECT 
                    VetID,
                    License_Number,
                    Specialization,
                    Years_Experience,
                    Clinic_Name,
                    Bio,
                    Created_At,
                    Updated_At
                FROM Veterinarians 
                WHERE UserID = :user_id
            ";
            $stmt = $db->prepare($query);
            $stmt->execute(['user_id' => $id]);
            $details = $stmt->fetch();
            if ($details) {
                echo "✅ Vet details found.\n";
            } else {
                echo "⚠️ Vet details NOT found (might be optional but query worked).\n";
            }
        }
        
        echo "✅ Profile logic check passed for User $id.\n";

    } catch (PDOException $e) {
        echo "❌ SQL ERROR in testProfileLogic: " . $e->getMessage() . "\n";
    } catch (Exception $e) {
        echo "❌ ERROR in testProfileLogic: " . $e->getMessage() . "\n";
    }
}
