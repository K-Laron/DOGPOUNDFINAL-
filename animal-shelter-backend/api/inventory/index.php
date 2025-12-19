<?php
/**
 * Inventory API
 * GET /api/inventory/ - List items
 * POST /api/inventory/ - Create item
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Inventory.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Validator.php';

$database = new Database();
$db = $database->getConnection();

$auth = new AuthMiddleware($db);
$user = $auth->authenticate();
$auth->requireRole(['Admin', 'Staff']);

$inventoryModel = new Inventory($db);
$activityLog = new ActivityLog($db);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGet($inventoryModel);
        break;
    case 'POST':
        handlePost($inventoryModel, $activityLog, $user);
        break;
    default:
        Response::error("Method not allowed", 405);
}

function handleGet($inventoryModel) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? min((int)$_GET['per_page'], MAX_PAGE_SIZE) : DEFAULT_PAGE_SIZE;
    
    $filters = [
        'category' => $_GET['category'] ?? null,
        'search' => $_GET['search'] ?? null,
        'low_stock' => isset($_GET['low_stock']) && $_GET['low_stock'] === 'true'
    ];
    
    $result = $inventoryModel->getAll($page, $perPage, $filters);
    Response::paginated($result['data'], $page, $perPage, $result['total']);
}

function handlePost($inventoryModel, $activityLog, $user) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $validator = new Validator($data);
    $validator->required('item_name')
              ->required('category')
              ->inArray('category', ['Medical', 'Food', 'Cleaning', 'Supplies']);
    
    if (!$validator->passes()) {
        Response::error("Validation failed", 422, $validator->getErrors());
    }
    
    $itemId = $inventoryModel->create($data);
    
    if ($itemId) {
        $activityLog->log($user['UserID'], 'CREATE_INVENTORY', "Created inventory item ID: $itemId");
        Response::success($inventoryModel->getById($itemId), "Inventory item created", 201);
    } else {
        Response::error("Failed to create item", 500);
    }
}