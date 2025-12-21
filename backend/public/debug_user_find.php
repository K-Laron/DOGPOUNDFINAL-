<?php
require_once __DIR__ . '/../app/config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->query("SELECT UserID, Username, RoleID, Is_Deleted, Account_Status FROM Users WHERE FirstName LIKE '%Test%' OR LastName LIKE '%Laron%' LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
