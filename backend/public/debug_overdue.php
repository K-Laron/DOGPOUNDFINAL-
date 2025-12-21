<?php
// Define paths
define('APP_PATH', realpath(__DIR__ . '/../app'));
define('PUBLIC_PATH', __DIR__);
define('ROOT_PATH', dirname(__DIR__));

// Load bootstrap
if (file_exists(APP_PATH . '/bootstrap.php')) {
    require_once APP_PATH . '/bootstrap.php';
} else {
    // Basic fallback if bootstrap fails
    header('HTTP/1.1 500 Internal Server Error');
    die('Bootstrap not found');
}

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Main logic
try {
    // Use the App's Database class which is now autoloaded
    $db = (new Database())->getConnection();

    $stmt = $db->prepare("
        SELECT mr.RecordID, mr.Next_Due_Date,
               DATEDIFF(CURDATE(), mr.Next_Due_Date) as Days_Overdue,
               DATEDIFF(CURDATE(), IFNULL(mr.Next_Due_Date, CURDATE())) as Raw_Diff
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
        'data' => $results,
        'info' => 'Debugging overdue calculation'
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
