<?php
/**
 * SSE Server Entry Point
 * Dedicated server for Server-Sent Events on port 8001
 * 
 * Run with: php -S localhost:8001 -t public public/sse.php
 * 
 * @package AnimalShelter
 */

// Prevent direct access via CLI without proper setup
if (php_sapi_name() === 'cli') {
    die("This file cannot be run from CLI directly.\n");
}

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', __DIR__);

// Load environment and config
require_once APP_PATH . '/utils/Env.php';
Env::load(dirname(dirname(APP_PATH)) . '/.env');
require_once APP_PATH . '/config/config.php';

// Load required utilities
require_once APP_PATH . '/utils/Response.php';
require_once APP_PATH . '/utils/JWT.php';
require_once APP_PATH . '/config/database.php';

// Handle CORS for SSE
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$isPrivateNetwork = preg_match('/^https?:\/\/(localhost|127\.0\.0\.1|10\.\d+\.\d+\.\d+|192\.168\.\d+\.\d+|172\.(1[6-9]|2\d|3[01])\.\d+\.\d+)(:\d+)?$/', $origin);
if (in_array($origin, ALLOWED_ORIGINS) || in_array('*', ALLOWED_ORIGINS) || $isPrivateNetwork) {
    header("Access-Control-Allow-Origin: " . ($origin ?: '*'));
} else {
    header("Access-Control-Allow-Origin: " . FRONTEND_URL);
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, Accept, Last-Event-ID");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Authenticate via token query parameter (EventSource doesn't support headers)
$token = $_GET['token'] ?? null;
$user = null;

if ($token) {
    try {
        $decoded = JWT::decode($token);
        if ($decoded && isset($decoded['user_id'])) {
            // Initialize database and fetch user
            $database = new Database();
            $db = $database->getConnection();
            
            $stmt = $db->prepare("
                SELECT u.*, r.Role_Name 
                FROM Users u 
                JOIN Roles r ON u.RoleID = r.RoleID 
                WHERE u.UserID = :user_id AND u.Is_Deleted = FALSE AND u.Account_Status = 'Active'
            ");
            $stmt->execute(['user_id' => $decoded['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("SSE Auth Error: " . $e->getMessage());
    }
}

// Require authentication
if (!$user) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Load SSE controller
require_once APP_PATH . '/controllers/BaseController.php';
require_once APP_PATH . '/controllers/SSEController.php';

try {
    // Create and run SSE controller with user context
    $controller = new SSEController($db, $user);
    $controller->stream();
    
} catch (Throwable $e) {
    error_log("SSE Error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'SSE connection failed'
    ]);
}
