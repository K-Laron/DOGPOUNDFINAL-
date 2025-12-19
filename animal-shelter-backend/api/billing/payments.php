<?php
/**
 * Payments API
 * POST /api/billing/payments.php - Record payment
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed", 405);
}

$database = new Database();
$db = $database->getConnection();

$auth = new AuthMiddleware($db);
$user = $auth->authenticate();
$auth->requireRole(['Admin', 'Staff']);

$invoiceModel = new Invoice($db);
$activityLog = new ActivityLog($db);

$data = json_decode(file_get_contents("php://input"), true);

$validator = new Validator($data);
$validator->required('invoice_id')->numeric('invoice_id')
          ->required('amount_paid')->numeric('amount_paid')
          ->required('payment_method')->inArray('payment_method', ['Cash', 'GCash', 'Bank Transfer']);

if (!$validator->passes()) {
    Response::error("Validation failed", 422, $validator->getErrors());
}

$data['received_by_user_id'] = $user['UserID'];

$result = $invoiceModel->recordPayment($data['invoice_id'], $data);

if (isset($result['error'])) {
    Response::error($result['error'], 400);
}

$activityLog->log(
    $user['UserID'], 
    'RECORD_PAYMENT', 
    "Recorded payment ID: {$result['id']} for invoice: {$data['invoice_id']}"
);

Response::success(
    $invoiceModel->getById($data['invoice_id']), 
    "Payment recorded successfully", 
    201
);