<?php
// Debug script for AdoptionController

define('BASE_PATH', __DIR__);
define('APP_PATH', __DIR__ . '/backend/app');
define('PUBLIC_PATH', __DIR__ . '/backend/public');
define('APP_ENV', 'development');

require_once APP_PATH . '/bootstrap.php';

try {
    $db = Database::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $searchTerm = '%tico%';
    $whereClause = "1=1 AND (
                a.Name LIKE :search 
                OR a.Breed LIKE :search 
                OR u.FirstName LIKE :search 
                OR u.LastName LIKE :search 
                OR CONCAT(u.FirstName, ' ', u.LastName) LIKE :search
                OR u.Email LIKE :search
                OR a.Type LIKE :search
            )";
    
    $query = "
            SELECT COUNT(*) as total 
            FROM Adoption_Requests ar 
            JOIN Animals a ON ar.AnimalID = a.AnimalID
            JOIN Users u ON ar.Adopter_UserID = u.UserID
            WHERE {$whereClause}
    ";

    echo "Preparing query...\n";
    $stmt = $db->prepare($query);
    
    echo "Executing query...\n";
    $stmt->execute(['search' => $searchTerm]);
    
    $result = $stmt->fetch();
    echo "Total: " . $result['total'] . "\n";
    echo "Success!\n";

} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage() . "\n";
    echo "SQL: " . $query . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
