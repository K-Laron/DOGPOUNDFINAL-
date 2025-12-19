<?php
/**
 * User Login Endpoint
 * POST /api/auth/login.php
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Validator.php';
require_once __DIR__ . '/../../utils/JWT.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed", 405);
}

// Get input data
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
$validator = new Validator($data);
$validator->required('email')->email('email')
          ->required('password');

if (!$validator->passes()) {
    Response::error("Validation failed", 422, $validator->getErrors());
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $userModel = new User($db);
    $activityLog = new ActivityLog($db);
    
    // Verify credentials
    $user = $userModel->verifyPassword($data['email'], $data['password']);
    
    if (!$user) {
        Response::error("Invalid email or password", 401);
    }
    
    // Check account status
    if ($user['Account_Status'] !== 'Active') {
        Response::error("Your account is " . strtolower($user['Account_Status']), 403);
    }
    
    // Generate JWT token
    $token = JWT::generate([
        'user_id' => $user['UserID'],
        'email' => $user['Email'],
        'role' => $user['Role_Name']
    ]);
    
    // Log activity
    $activityLog->log($user['UserID'], 'LOGIN', 'User logged in successfully');
    
    // Prepare response
    $response = [
        'token' => $token,
        'user' => [
            'id' => $user['UserID'],
            'first_name' => $user['FirstName'],
            'last_name' => $user['LastName'],
            'email' => $user['Email'],
            'role' => $user['Role_Name']
        ]
    ];
    
    Response::success($response, "Login successful");
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    Response::error("An error occurred during login", 500);
}