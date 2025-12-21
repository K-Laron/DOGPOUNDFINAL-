<?php
/**
 * Application Bootstrap
 * Initializes all core components and handles the application lifecycle
 * 
 * @package AnimalShelter
 */

// ============================================
// LOAD CONFIGURATION
// ============================================

require_once APP_PATH . '/config/config.php';

// ============================================
// AUTOLOADER
// ============================================

/**
 * Simple autoloader for application classes
 */
spl_autoload_register(function ($className) {
    // Directories to search for classes
    $directories = [
        APP_PATH . '/models/',
        APP_PATH . '/controllers/',
        APP_PATH . '/middleware/',
        APP_PATH . '/utils/',
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ============================================
// LOAD CORE FILES
// ============================================

require_once APP_PATH . '/utils/Response.php';
require_once APP_PATH . '/utils/JWT.php';
require_once APP_PATH . '/utils/Validator.php';
require_once APP_PATH . '/utils/Router.php';
require_once APP_PATH . '/config/database.php';

// ============================================
// MAIN APPLICATION CLASS
// ============================================

/**
 * Main Application Class
 * Handles initialization and request processing
 */
class App {
    /**
     * @var Router
     */
    private $router;
    
    /**
     * @var PDO
     */
    private $db;
    
    /**
     * Constructor - Initialize the application
     */
    public function __construct() {
        // Set up CORS headers first
        $this->handleCors();
        
        // Initialize database connection
        $this->initDatabase();
        
        // Initialize router
        $this->router = new Router($this->db);
        
        // Register all routes
        $this->registerRoutes();
    }
    
    /**
     * Handle CORS (Cross-Origin Resource Sharing) headers
     */
    private function handleCors() {
        // Get the origin header
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Check if origin is allowed
        if (in_array($origin, ALLOWED_ORIGINS) || in_array('*', ALLOWED_ORIGINS)) {
            header("Access-Control-Allow-Origin: " . ($origin ?: '*'));
        } else {
            header("Access-Control-Allow-Origin: " . FRONTEND_URL);
        }
        
        // Set other CORS headers
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400"); // Cache preflight for 24 hours
        
        // Set content type
        header("Content-Type: application/json; charset=UTF-8");
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Initialize database connection
     */
    private function initDatabase() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
        } catch (Exception $e) {
            error_log("Database initialization failed: " . $e->getMessage());
            Response::serverError("Service temporarily unavailable. Please try again later.");
        }
    }
    
    /**
     * Register all application routes
     * Routes are organized by loading separate route files
     */
    private function registerRoutes() {
        // Route files to load
        $routeFiles = [
            'auth.php',
            'users.php',
            'animals.php',
            'medical.php',
            'adoptions.php',
            'inventory.php',
            'billing.php',
            'dashboard.php',
            'notifications.php'
        ];
        
        // Load each route file
        $apiPath = APP_PATH . '/api/';
        $router = $this->router; // Make router available to route files
        
        foreach ($routeFiles as $file) {
            $filePath = $apiPath . $file;
            if (file_exists($filePath)) {
                require_once $filePath;
            } else {
                error_log("Route file not found: {$filePath}");
            }
        }
    }
    
    /**
     * Run the application
     * Dispatches the request to the appropriate handler
     */
    public function run() {
        $this->router->dispatch();
    }
    
    /**
     * Get router instance (for debugging)
     * 
     * @return Router
     */
    public function getRouter() {
        return $this->router;
    }
    
    /**
     * Get database connection (for debugging)
     * 
     * @return PDO
     */
    public function getDatabase() {
        return $this->db;
    }
}