<?php

/**
 * Application Bootstrap
 * Initializes all core components and handles the application lifecycle
 * 
 * @package AnimalShelter
 */

// ============================================
// LOAD ENVIRONMENT VARIABLES
// ============================================

// Load .env file if it exists (for development)
// .env is in project root (dogpound/.env), APP_PATH is dogpound/backend/app
require_once APP_PATH . '/utils/Env.php';
Env::load(dirname(dirname(APP_PATH)) . '/.env');

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
class App
{
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
    public function __construct()
    {
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
     * 
     * CORS is a security mechanism that restricts cross-origin HTTP requests.
     * Since our frontend (port 3000) and backend (port 8000) run on different
     * origins, we must explicitly allow the frontend to make API requests.
     * 
     * This method also applies global rate limiting to prevent abuse.
     */
    private function handleCors()
    {
        // ====================================================
        // RATE LIMITING
        // Limit API requests per IP to prevent abuse/DDoS
        // ====================================================
        if (defined('RATE_LIMIT_ENABLED') && RATE_LIMIT_ENABLED) {
            require_once APP_PATH . '/utils/RateLimiter.php';
            // Check if this IP has exceeded the request limit (100/min by default)
            // If exceeded, RateLimiter will send a 429 response and exit
            RateLimiter::checkGlobal();
        }

        // ====================================================
        // CORS ORIGIN VALIDATION
        // Only allow requests from trusted frontend origins
        // ====================================================

        // Get the origin of the incoming request
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Check if this origin is in our whitelist (defined in config.php)
        // This prevents malicious sites from making API requests
        if (in_array($origin, ALLOWED_ORIGINS) || in_array('*', ALLOWED_ORIGINS)) {
            // Origin is trusted - echo it back in the response header
            header("Access-Control-Allow-Origin: " . ($origin ?: '*'));
        } else {
            // Unknown origin - default to frontend URL for security
            header("Access-Control-Allow-Origin: " . FRONTEND_URL);
        }

        // ====================================================
        // CORS RESPONSE HEADERS
        // Tell the browser what's allowed in cross-origin requests
        // ====================================================

        // Which HTTP methods can the frontend use?
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");

        // Which headers can the frontend send?
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");

        // Allow cookies/credentials in cross-origin requests
        header("Access-Control-Allow-Credentials: true");

        // Cache preflight response for 24 hours (reduces OPTIONS requests)
        header("Access-Control-Max-Age: 86400");

        // All API responses are JSON
        header("Content-Type: application/json; charset=UTF-8");

        // ====================================================
        // SECURITY HEADERS
        // Protect against common web vulnerabilities
        // ====================================================

        // Prevent MIME type sniffing (stops browsers from guessing content type)
        header("X-Content-Type-Options: nosniff");

        // Prevent clickjacking by disallowing iframe embedding
        header("X-Frame-Options: DENY");

        // Enable browser XSS filter (legacy but still useful)
        header("X-XSS-Protection: 1; mode=block");

        // Control referrer information sent with requests
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // Prevent caching of sensitive data
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");

        // ====================================================
        // PREFLIGHT REQUEST HANDLING
        // Browsers send OPTIONS request before actual request
        // to check if the request is allowed
        // ====================================================
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            // Respond with 200 OK to preflight - no body needed
            http_response_code(200);
            exit;
        }
    }

    /**
     * Initialize database connection
     */
    private function initDatabase()
    {
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
    private function registerRoutes()
    {
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
            'notifications.php',
            'sse.php'
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
    public function run()
    {
        $this->router->dispatch();
    }

    /**
     * Get router instance (for debugging)
     * 
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Get database connection (for debugging)
     * 
     * @return PDO
     */
    public function getDatabase()
    {
        return $this->db;
    }
}
