<?php
require_once __DIR__ . '/../app/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT 
            u.UserID, 
            u.FirstName, 
            u.LastName, 
            u.Username, 
            u.Email, 
            r.Role_Name 
        FROM Users u 
        JOIN Roles r ON u.RoleID = r.RoleID
        ORDER BY u.UserID
    ");
    
    echo "--- CURRENT DATABASE USERS ---\n";
    printf("%-5s | %-10s | %-15s | %-15s | %-15s | %-20s\n", 'ID', 'Role', 'First Name', 'Last Name', 'Username', 'Email');
    echo str_repeat('-', 90) . "\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf("%-5d | %-10s | %-15s | %-15s | %-15s | %-20s\n", 
            $row['UserID'], 
            substr($row['Role_Name'], 0, 10), 
            substr($row['FirstName'], 0, 15), 
            substr($row['LastName'], 0, 15), 
            substr($row['Username'], 0, 15), 
            $row['Email']
        );
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
