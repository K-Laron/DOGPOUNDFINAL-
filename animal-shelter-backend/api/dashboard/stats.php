<?php
/**
 * Dashboard Statistics API
 * GET /api/dashboard/stats.php
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
require_once __DIR__ . '/../../models/Animal.php';
require_once __DIR__ . '/../../models/Inventory.php';
require_once __DIR__ . '/../../models/MedicalRecord.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../utils/Response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Method not allowed", 405);
}

$database = new Database();
$db = $database->getConnection();

$auth = new AuthMiddleware($db);
$auth->authenticate();
$auth->requireRole(['Admin', 'Staff', 'Veterinarian']);

$animalModel = new Animal($db);
$inventoryModel = new Inventory($db);
$medicalModel = new MedicalRecord($db);
$activityLog = new ActivityLog($db);

// Gather statistics
$stats = [
    'animals' => $animalModel->getStatistics(),
    'low_stock_count' => count($inventoryModel->getLowStock()),
    'upcoming_vaccinations' => count($medicalModel->getUpcoming(7)),
    'recent_activity' => $activityLog->getRecent(5)
];

// Pending adoption requests count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM Adoption_Requests WHERE Status = 'Pending'");
$stmt->execute();
$stats['pending_adoptions'] = $stmt->fetch()['count'];

// Unpaid invoices count
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(Total_Amount) as total FROM Invoices WHERE Status = 'Unpaid' AND Is_Deleted = FALSE");
$stmt->execute();
$unpaid = $stmt->fetch();
$stats['unpaid_invoices'] = [
    'count' => $unpaid['count'],
    'total_amount' => $unpaid['total'] ?? 0
];

Response::success($stats, "Dashboard statistics retrieved");