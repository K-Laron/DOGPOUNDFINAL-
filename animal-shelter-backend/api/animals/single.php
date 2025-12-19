<?php
/**
 * Single Animal API Endpoint
 * GET /api/animals/single.php?id=X - Get animal details
 * PUT /api/animals/single.php?id=X - Update animal
 * DELETE /api/animals/single.php?id=X - Delete animal
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, PUT, DELETE, OPTIONS");
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

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    Response::error("Animal ID is required", 400);
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGet($animalModel, $id);
        break;
    case 'PUT':
        $user = $auth->authenticate();
        $auth->requireRole(['Admin', 'Staff']);
        handlePut($animalModel, $activityLog, $user, $id);
        break;
    case 'DELETE':
        $user = $auth->authenticate();
        $auth->requireRole(['Admin']);
        handleDelete($animalModel, $activityLog, $user, $id);
        break;
    default:
        Response::error("Method not allowed", 405);
}

function handleGet($animalModel, $id) {
    $animal = $animalModel->getFullDetails($id);
    
    if (!$animal) {
        Response::error("Animal not found", 404);
    }
    
    Response::success($animal);
}

function handlePut($animalModel, $activityLog, $user, $id) {
    $animal = $animalModel->getById($id);
    
    if (!$animal) {
        Response::error("Animal not found", 404);
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['type'])) {
        $validator = new Validator($data);
        $validator->inArray('type', ['Dog', 'Cat', 'Other']);
        if (!$validator->passes()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }
    }
    
    if ($animalModel->update($id, $data)) {
        $activityLog->log($user['UserID'], 'UPDATE_ANIMAL', "Updated animal ID: $id");
        Response::success($animalModel->getById($id), "Animal updated successfully");
    } else {
        Response::error("Failed to update animal", 500);
    }
}

function handleDelete($animalModel, $activityLog, $user, $id) {
    $animal = $animalModel->getById($id);
    
    if (!$animal) {
        Response::error("Animal not found", 404);
    }
    
    if ($animalModel->delete($id)) {
        $activityLog->log($user['UserID'], 'DELETE_ANIMAL', "Deleted animal ID: $id - {$animal['Name']}");
        Response::success(null, "Animal deleted successfully");
    } else {
        Response::error("Failed to delete animal", 500);
    }
}