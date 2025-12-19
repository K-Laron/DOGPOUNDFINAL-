<?php
/**
 * Animals API Endpoint
 * GET /api/animals/ - List all animals
 * POST /api/animals/ - Create new animal
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
require_once __DIR__ . '/../../models/Animal.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Validator.php';

$database = new Database();
$db = $database->getConnection();

$auth = new AuthMiddleware($db);
$animalModel = new Animal($db);
$activityLog = new ActivityLog($db);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGet($animalModel);
        break;
    case 'POST':
        $user = $auth->authenticate();
        $auth->requireRole(['Admin', 'Staff']);
        handlePost($animalModel, $activityLog, $user);
        break;
    default:
        Response::error("Method not allowed", 405);
}

function handleGet($animalModel) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? min((int)$_GET['per_page'], MAX_PAGE_SIZE) : DEFAULT_PAGE_SIZE;
    
    $filters = [
        'type' => $_GET['type'] ?? null,
        'status' => $_GET['status'] ?? null,
        'gender' => $_GET['gender'] ?? null,
        'intake_status' => $_GET['intake_status'] ?? null,
        'search' => $_GET['search'] ?? null
    ];
    
    $result = $animalModel->getAll($page, $perPage, $filters);
    Response::paginated($result['data'], $page, $perPage, $result['total']);
}

function handlePost($animalModel, $activityLog, $user) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $validator = new Validator($data);
    $validator->required('name')
              ->required('type')->inArray('type', ['Dog', 'Cat', 'Other'])
              ->required('intake_status')->inArray('intake_status', ['Stray', 'Surrendered', 'Confiscated']);
    
    if (!$validator->passes()) {
        Response::error("Validation failed", 422, $validator->getErrors());
    }
    
    $animalId = $animalModel->create($data);
    
    if ($animalId) {
        $activityLog->log($user['UserID'], 'CREATE_ANIMAL', "Created animal ID: $animalId");
        $animal = $animalModel->getById($animalId);
        Response::success($animal, "Animal record created successfully", 201);
    } else {
        Response::error("Failed to create animal record", 500);
    }
}