<?php
/**
 * Router Class
 * Handles URL routing and dispatching to controllers
 * 
 * @package AnimalShelter
 */

class Router {
    /**
     * @var array Registered routes
     */
    private $routes = [];
    
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * @var array|null Current authenticated user
     */
    private $currentUser = null;
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Register a GET route
     * 
     * @param string $path Route path
     * @param string $handler Controller@method
     * @param array|null $roles Required roles (null = no auth required)
     */
    public function get($path, $handler, $roles = null) {
        $this->addRoute('GET', $path, $handler, $roles);
    }
    
    /**
     * Register a POST route
     * 
     * @param string $path Route path
     * @param string $handler Controller@method
     * @param array|null $roles Required roles
     */
    public function post($path, $handler, $roles = null) {
        $this->addRoute('POST', $path, $handler, $roles);
    }
    
    /**
     * Register a PUT route
     * 
     * @param string $path Route path
     * @param string $handler Controller@method
     * @param array|null $roles Required roles
     */
    public function put($path, $handler, $roles = null) {
        $this->addRoute('PUT', $path, $handler, $roles);
    }
    
    /**
     * Register a DELETE route
     * 
     * @param string $path Route path
     * @param string $handler Controller@method
     * @param array|null $roles Required roles
     */
    public function delete($path, $handler, $roles = null) {
        $this->addRoute('DELETE', $path, $handler, $roles);
    }
    
    /**
     * Register a PATCH route
     * 
     * @param string $path Route path
     * @param string $handler Controller@method
     * @param array|null $roles Required roles
     */
    public function patch($path, $handler, $roles = null) {
        $this->addRoute('PATCH', $path, $handler, $roles);
    }
    
    /**
     * Add route to collection
     * 
     * @param string $method HTTP method
     * @param string $path Route path
     * @param string $handler Controller@method
     * @param array|null $roles Required roles
     */
    private function addRoute($method, $path, $handler, $roles) {
        // Convert path parameters to regex pattern
        // {id} becomes (?P<id>[^/]+)
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'roles' => $roles
        ];
    }
    
    /**
     * Dispatch the request to appropriate controller
     */
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getUri();
        
        // Find matching route
        foreach ($this->routes as $route) {
            // Check method matches
            if ($route['method'] !== $method) {
                continue;
            }
            
            // Check path matches
            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters (filter out numeric keys)
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Check authentication and authorization if required
                if ($route['roles'] !== null) {
                    $this->authenticate();
                    
                    // '*' means any authenticated user
                    if (!in_array('*', $route['roles'])) {
                        $this->authorize($route['roles']);
                    }
                }
                
                // Call the controller method
                $this->callHandler($route['handler'], $params);
                return;
            }
        }
        
        // No route matched
        Response::notFound("Endpoint not found: {$method} {$uri}");
    }
    
    /**
     * Get the request URI (cleaned)
     * 
     * @return string Cleaned URI path
     */
    private function getUri() {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        if (strpos($uri, '?') !== false) {
            $uri = strstr($uri, '?', true);
        }
        
        // Remove base path if running in subdirectory
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = dirname($scriptName);
        
        if ($basePath !== '/' && $basePath !== '\\') {
            // Make sure basePath starts correctly
            if (strpos($uri, $basePath) === 0) {
                $uri = substr($uri, strlen($basePath));
            }
        }
        
        // Clean the URI
        $uri = '/' . trim($uri, '/');
        
        // URL decode
        $uri = urldecode($uri);
        
        return $uri;
    }
    
    /**
     * Authenticate the request
     * 
     * @throws void Sends error response if authentication fails
     */
    private function authenticate() {
        $token = $this->getBearerToken();
        
        if (!$token) {
            Response::unauthorized("Authorization token required");
        }
        
        // Verify JWT token
        $payload = JWT::verify($token);
        
        if (!$payload) {
            Response::unauthorized("Invalid or expired token");
        }
        
        // Check if user_id exists in payload
        if (!isset($payload['user_id'])) {
            Response::unauthorized("Invalid token payload");
        }
        
        // Verify user exists and is active
        $stmt = $this->db->prepare("
            SELECT u.*, r.Role_Name 
            FROM Users u 
            JOIN Roles r ON u.RoleID = r.RoleID 
            WHERE u.UserID = :user_id 
            AND u.Is_Deleted = FALSE
        ");
        $stmt->execute(['user_id' => $payload['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            Response::unauthorized("User not found");
        }
        
        if ($user['Account_Status'] !== 'Active') {
            Response::forbidden("Account is " . strtolower($user['Account_Status']));
        }
        
        // Store user for later use
        $this->currentUser = $user;
    }
    
    /**
     * Authorize based on roles
     * 
     * @param array $allowedRoles Roles allowed to access the route
     */
    private function authorize($allowedRoles) {
        if (!$this->currentUser) {
            Response::unauthorized("Authentication required");
        }
        
        if (!in_array($this->currentUser['Role_Name'], $allowedRoles)) {
            Response::forbidden(
                "Access denied. Required role: " . implode(' or ', $allowedRoles)
            );
        }
    }
    
    /**
     * Get Bearer token from Authorization header
     * 
     * @return string|null Token or null if not found
     */
    private function getBearerToken() {
        $header = $this->getAuthorizationHeader();
        
        if (!empty($header) && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }
    
    /**
     * Get Authorization header
     * Handles different server configurations
     * 
     * @return string|null Header value or null
     */
    private function getAuthorizationHeader() {
        $headers = null;
        
        // Check various sources for the authorization header
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            
            // Make keys case-insensitive
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        return $headers;
    }
    
    /**
     * Call the controller handler
     * 
     * @param string $handler Handler string (Controller@method)
     * @param array $params Route parameters
     */
    private function callHandler($handler, $params) {
        // Parse handler string
        if (strpos($handler, '@') === false) {
            Response::serverError("Invalid route handler format");
        }
        
        list($controllerName, $method) = explode('@', $handler);
        
        // Build controller file path
        $controllerFile = APP_PATH . '/controllers/' . $controllerName . '.php';
        
        // Check if controller file exists
        if (!file_exists($controllerFile)) {
            error_log("Controller file not found: {$controllerFile}");
            Response::serverError("Controller not found");
        }
        
        // Include controller file
        require_once $controllerFile;
        
        // Check if class exists
        if (!class_exists($controllerName)) {
            error_log("Controller class not found: {$controllerName}");
            Response::serverError("Controller class not found");
        }
        
        // Instantiate controller
        $controller = new $controllerName($this->db, $this->currentUser);
        
        // Check if method exists
        if (!method_exists($controller, $method)) {
            error_log("Method not found: {$controllerName}@{$method}");
            Response::serverError("Controller method not found");
        }
        
        // Call the method with parameters
        call_user_func_array([$controller, $method], array_values($params));
    }
    
    /**
     * Get current authenticated user
     * 
     * @return array|null User data or null
     */
    public function getCurrentUser() {
        return $this->currentUser;
    }
    
    /**
     * Get all registered routes (for debugging)
     * 
     * @return array
     */
    public function getRoutes() {
        return array_map(function($route) {
            return [
                'method' => $route['method'],
                'path' => $route['path'],
                'handler' => $route['handler'],
                'auth_required' => $route['roles'] !== null,
                'roles' => $route['roles']
            ];
        }, $this->routes);
    }
}