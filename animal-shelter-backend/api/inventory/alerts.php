<?php
/**
 * Inventory Alerts API
 * GET /api/inventory/alerts.php - Get low stock and expiring items
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Inventory.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../utils/Response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Method not allowed", 405);
}

$database = new Database();
$db = $database->getConnection();

$auth = new AuthMiddleware($db);
$auth->authenticate();
$auth->requireRole(['Admin', 'Staff']);

$inventoryModel = new Inventory($db);

$expiryDays = isset($_GET['expiry_days']) ? (int)$_GET['expiry_days'] : 30;

$alerts = [
    'low_stock' => $inventoryModel->getLowStock(),
    'expiring_soon' => $inventoryModel->getExpiring($expiryDays)
];

Response::success($alerts, "Inventory alerts retrieved");