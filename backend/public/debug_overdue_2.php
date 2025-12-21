<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define paths
define('APP_PATH', realpath(__DIR__ . '/../app'));
define('PUBLIC_PATH', __DIR__);
define('ROOT_PATH', dirname(__DIR__));
define('BASE_PATH', ROOT_PATH);

if (!APP_PATH) {
    die("APP_PATH could not be resolved from " . __DIR__ . '/../app');
}

// Load bootstrap - using absolute path relative to this file
if (file_exists(APP_PATH . '/bootstrap.php')) {
    require_once APP_PATH . '/bootstrap.php';
} else {
    // Show what failed
    die('Bootstrap not found at ' . APP_PATH . '/bootstrap.php');
}

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Main logic
try {
    $db = (new Database())->getConnection();

    $stmt = $db->prepare("
        SELECT mr.RecordID, mr.Next_Due_Date,
               DATEDIFF(CURDATE(), mr.Next_Due_Date) as Days_Overdue,
               mr.Diagnosis_Type, mr.AnimalID
        FROM Medical_Records mr
        INNER JOIN (
            SELECT MAX(RecordID) as MaxID
            FROM Medical_Records
            GROUP BY AnimalID, Diagnosis_Type, COALESCE(Vaccine_Name, '')
        ) latest ON mr.RecordID = latest.MaxID
        WHERE mr.Next_Due_Date IS NOT NULL 
        ORDER BY mr.Next_Due_Date ASC
    ");

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true, 
        'count' => count($results),
        'data' => $results
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
