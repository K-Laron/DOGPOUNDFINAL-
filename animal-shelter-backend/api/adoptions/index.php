<?php
/**
 * Adoption Requests API
 * GET /api/adoptions/ - List requests
 * POST /api/adoptions/ - Create request
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
require_once __DIR__ . '/../../models/AdoptionRequest.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Validator.php';

$database = new Database();
$db = $database->getConnection();

$auth = new AuthMiddleware($db);
$user = $auth->authenticate();
$adoptionModel = new AdoptionRequest($db);
$activityLog = new ActivityLog($db);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGet($adoptionModel, $auth, $user);
        break;
    case 'POST':
        handlePost($adoptionModel, $activityLog, $user);
        break;
    default:
        Response::error("Method not allowed", 405);
}

function handleGet($adoptionModel, $auth, $user) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? min((int)$_GET['per_page'], MAX_PAGE_SIZE) : DEFAULT_PAGE_SIZE;
    
    // If user is Adopter, only show their requests
    if ($user['Role_Name'] === 'Adopter') {
        $result = $adoptionModel->getByUser($user['UserID'], $page, $perPage);
    } else {
        $filters = [
            'status' => $_GET['status'] ?? null,
            'animal_id' => $_GET['animal_id'] ?? null
        ];
        $result = $adoptionModel->getAll($page, $perPage, $filters);
    }
    
    Response::paginated($result['data'], $page, $perPage, $result['total']);
}

function handlePost($adoptionModel, $activityLog, $user) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $validator = new Validator($data);
    $validator->required('animal_id')->numeric('animal_id');
    
    if (!$validator->passes()) {
        Response::error("Validation failed", 422, $validator->getErrors());
    }
    
    $data['adopter_user_id'] = $user['UserID'];
    $result = $adoptionModel->create($data);
    
    if (isset($result['error'])) {
        Response::error($result['error'], 400);
    }
    
    $activityLog->log($user['UserID'], 'CREATE_ADOPTION_REQUEST', "Created adoption request ID: {$result['id']}");
    $request = $adoptionModel->getById($result['id']);
    Response::success($request, "Adoption request submitted successfully", 201);
}