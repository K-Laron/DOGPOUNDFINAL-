<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/UserController.php';

// Mock the database connection inside a helper class or just use the controller if possible
// Since UserController extends BaseController which initializes DB, we can try to instantiate it.
// However, BaseController constructor might expect things. 
// Let's just create a raw script that copies the logic of UserController::profile for User 11.

$db = (new Database())->getConnection();

function findUserById($db, $id) {
    echo "Finding user $id...\n";
    $stmt = $db->prepare("
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
        WHERE u.UserID = :id
    ");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAdopterStats($db, $userId) {
    echo "Getting stats for $userId...\n";
    try {
        $stats = [
            'adoption_requests' => 0,
            'completed_adoptions' => 0,
            'pending_requests' => 0
        ];

        // This is where it might fail if table names are wrong
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM Adoptions WHERE AdopterID = :id");
        $stmt->execute(['id' => $userId]);
        $stats['adoption_requests'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $db->prepare("SELECT COUNT(*) as count FROM Adoptions WHERE AdopterID = :id AND Status = 'Approved'");
        $stmt->execute(['id' => $userId]);
        $stats['completed_adoptions'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $db->prepare("SELECT COUNT(*) as count FROM Adoptions WHERE AdopterID = :id AND Status = 'Pending'");
        $stmt->execute(['id' => $userId]);
        $stats['pending_requests'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return ['stats' => $stats];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

$userId = 11;
$user = findUserById($db, $userId);

if (!$user) {
    die("User not found via raw query.\n");
}

print_r($user);

if ($user['Role_Name'] === 'Adopter') {
    $stats = getAdopterStats($db, $userId);
    print_r($stats);
} else {
    echo "User is not an adopter ({$user['Role_Name']})\n";
}
