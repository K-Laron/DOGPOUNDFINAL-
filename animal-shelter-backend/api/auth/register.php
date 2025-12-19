<?php
/**
 * User Registration Endpoint
 * POST /api/auth/register.php
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Validator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed", 405);
}

$data = json_decode(file_get_contents("php://input"), true);

// Validate input
$validator = new Validator($data);
$validator->required('first_name')->maxLength('first_name', 50)
          ->required('last_name')->maxLength('last_name', 50)
          ->required('email')->email('email')
          ->required('password')->minLength('password', 8)
          ->required('contact_number');

if (!$validator->passes()) {
    Response::error("Validation failed", 422, $validator->getErrors());
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $userModel = new User($db);
    $activityLog = new ActivityLog($db);
    
    // Check if email exists
    if ($userModel->emailExists($data['email'])) {
        Response::error("Email already registered", 409);
    }
    
    // Set default role to 'Adopter' (public registration)
    $data['role_id'] = 4; // Adopter role
    
    // Create user
    $userId = $userModel->create($data);
    
    if ($userId) {
        $activityLog->log($userId, 'REGISTER', 'New user registered');
        
        $user = $userModel->getById($userId);
        Response::success([
            'id' => $user['UserID'],
            'first_name' => $user['FirstName'],
            'last_name' => $user['LastName'],
            'email' => $user['Email']
        ], "Registration successful", 201);
    } else {
        Response::error("Failed to create account", 500);
    }
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    Response::error("An error occurred during registration", 500);
}