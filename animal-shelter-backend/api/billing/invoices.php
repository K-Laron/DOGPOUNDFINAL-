<?php
/**
 * Invoices API
 * GET /api/billing/invoices.php - List invoices
 * POST /api/billing/invoices.php - Create invoice
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
require_once __DIR__ . '/../../models/Invoice.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Validator.php';

$database = new Database();
$db = $database->getConnection();

$auth = new AuthMiddleware($db);
$user = $auth->authenticate();

$invoiceModel = new Invoice($db);
$activityLog = new ActivityLog($db);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGet($invoiceModel, $auth, $user);
        break;
    case 'POST':
        $auth->requireRole(['Admin', 'Staff']);
        handlePost($invoiceModel, $activityLog, $user);
        break;
    default:
        Response::error("Method not allowed", 405);
}

function handleGet($invoiceModel, $auth, $user) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? min((int)$_GET['per_page'], MAX_PAGE_SIZE) : DEFAULT_PAGE_SIZE;
    
    $filters = [
        'status' => $_GET['status'] ?? null,
        'type' => $_GET['type'] ?? null
    ];
    
    // Adopters can only see their own invoices
    if ($user['Role_Name'] === 'Adopter') {
        $filters['payer_id'] = $user['UserID'];
    }
    
    $result = $invoiceModel->getAll($page, $perPage, $filters);
    Response::paginated($result['data'], $page, $perPage, $result['total']);
}

function handlePost($invoiceModel, $activityLog, $user) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $validator = new Validator($data);
    $validator->required('payer_user_id')->numeric('payer_user_id')
              ->required('transaction_type')->inArray('transaction_type', ['Adoption Fee', 'Reclaim Fee'])
              ->required('total_amount')->numeric('total_amount');
    
    if (!$validator->passes()) {
        Response::error("Validation failed", 422, $validator->getErrors());
    }
    
    $data['issued_by_user_id'] = $user['UserID'];
    
    $invoiceId = $invoiceModel->create($data);
    
    if ($invoiceId) {
        $activityLog->log($user['UserID'], 'CREATE_INVOICE', "Created invoice ID: $invoiceId");
        Response::success($invoiceModel->getById($invoiceId), "Invoice created", 201);
    } else {
        Response::error("Failed to create invoice", 500);
    }
}