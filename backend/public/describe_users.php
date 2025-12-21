<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('APP_PATH', realpath(__DIR__ . '/../app'));
define('PUBLIC_PATH', __DIR__);
define('ROOT_PATH', dirname(__DIR__));
define('BASE_PATH', ROOT_PATH);

if (file_exists(APP_PATH . '/bootstrap.php')) {
    require_once APP_PATH . '/bootstrap.php';
} else {
    die('Bootstrap not found');
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("DESCRIBE Users");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($columns, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
