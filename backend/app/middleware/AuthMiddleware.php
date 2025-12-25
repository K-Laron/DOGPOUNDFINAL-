<?php
/**
 * Authentication Middleware
 * Handles JWT authentication and role-based authorization
 * 
 * @package AnimalShelter
 */

require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthMiddleware {
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * @var array|null Authenticated user data
     */
    private $user = null;
    
    /**
     * @var array|null JWT payload
     */
    private $payload = null;

    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Authenticate request using JWT token
     * 
     * @return array Authenticated user data
     */
    public function authenticate() {
        $token = $this->getBearerToken();

        if (!$token) {
            Response::error("Authorization token required", 401);
        }

        // Verify JWT token
        $this->payload = JWT::verify($token);

        if (!$this->payload) {
            Response::error("Invalid or expired token", 401);
        }

        // Check if user_id exists in payload
        if (!isset($this->payload['user_id'])) {
            Response::error("Invalid token payload", 401);
        }

        // Verify user exists and is active
        $stmt = $this->db->prepare("
            SELECT 
                u.UserID,
                u.RoleID,
                u.FirstName,
                u.LastName,
                u.Email,
                u.Contact_Number,
                u.Account_Status,
                u.Is_Deleted,
                r.Role_Name 
            FROM Users u 
            JOIN Roles r ON u.RoleID = r.RoleID 
            WHERE u.UserID = :user_id AND u.Is_Deleted = FALSE
        ");
        $stmt->execute(['user_id' => $this->payload['user_id']]);
        $this->user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$this->user) {
            Response::error("User not found", 401);
        }

        // Check account status
        if ($this->user['Account_Status'] !== 'Active') {
            $status = strtolower($this->user['Account_Status']);
            Response::error("Your account is {$status}. Please contact support.", 403);
        }

        return $this->user;
    }

    /**
     * Check if user has required role(s)
     * 
     * @param string|array $allowedRoles Single role or array of roles
     * @return bool True if authorized
     */
    public function requireRole($allowedRoles) {
        // Ensure user is authenticated first
        if (!$this->user) {
            $this->authenticate();
        }

        // Convert to array if string
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }

        // Check for wildcard (any authenticated user)
        if (in_array('*', $allowedRoles)) {
            return true;
        }

        // Check if user's role is in allowed roles
        if (!in_array($this->user['Role_Name'], $allowedRoles)) {
            Response::error(
                "Access denied. Required role: " . implode(' or ', $allowedRoles), 
                403
            );
        }

        return true;
    }

    /**
     * Check if user has any of the specified roles (without throwing error)
     * 
     * @param string|array $roles Role(s) to check
     * @return bool True if user has role
     */
    public function hasRole($roles) {
        if (!$this->user) {
            return false;
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        return in_array($this->user['Role_Name'], $roles);
    }

    /**
     * Check if current user is Admin
     * 
     * @return bool
     */
    public function isAdmin() {
        return $this->hasRole('Admin');
    }

    /**
     * Check if current user is Staff or Admin
     * 
     * @return bool
     */
    public function isStaff() {
        return $this->hasRole(['Admin', 'Staff']);
    }

    /**
     * Check if current user is Veterinarian
     * 
     * @return bool
     */
    public function isVeterinarian() {
        return $this->hasRole(['Admin', 'Veterinarian']);
    }

    /**
     * Check if current user owns a resource
     * 
     * @param int $resourceUserId User ID of resource owner
     * @return bool
     */
    public function isOwner($resourceUserId) {
        if (!$this->user) {
            return false;
        }
        return (int)$this->user['UserID'] === (int)$resourceUserId;
    }

    /**
     * Check if user can access resource (owner or has role)
     * 
     * @param int $resourceUserId Resource owner's user ID
     * @param array $allowedRoles Roles that can access regardless of ownership
     * @return bool
     */
    public function canAccess($resourceUserId, $allowedRoles = ['Admin']) {
        return $this->isOwner($resourceUserId) || $this->hasRole($allowedRoles);
    }

    /**
     * Get authenticated user
     * 
     * @return array|null User data or null
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Get user ID
     * 
     * @return int|null User ID or null
     */
    public function getUserId() {
        return $this->user ? (int)$this->user['UserID'] : null;
    }

    /**
     * Get user role
     * 
     * @return string|null Role name or null
     */
    public function getUserRole() {
        return $this->user ? $this->user['Role_Name'] : null;
    }

    /**
     * Get JWT payload
     * 
     * @return array|null JWT payload or null
     */
    public function getPayload() {
        return $this->payload;
    }

    /**
     * Get Bearer token from Authorization header
     * Handles different server configurations
     * 
     * @return string|null Token or null
     */
    private function getBearerToken() {
        $authHeader = $this->getAuthorizationHeader();

        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Get Authorization header
     * Works across different server configurations (Apache, Nginx, etc.)
     * 
     * @return string|null Header value or null
     */
    private function getAuthorizationHeader() {
        $headers = null;

        // Method 1: Check $_SERVER
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } 
        // Method 2: Check HTTP_AUTHORIZATION
        elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } 
        // Method 3: Check REDIRECT_HTTP_AUTHORIZATION (for some CGI/FastCGI setups)
        elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        }
        // Method 4: Use getallheaders() function
        elseif (function_exists('getallheaders')) {
            $requestHeaders = getallheaders();
            
            // Handle case-insensitive header names
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );

            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        // Method 5: Use apache_request_headers() function
        elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            
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
     * Optional authentication - doesn't fail if no token
     * Useful for endpoints that work differently for authenticated vs anonymous users
     * 
     * @return array|null User data or null
     */
    public function optionalAuth() {
        try {
            $token = $this->getBearerToken();
            
            if (!$token) {
                return null;
            }

            $this->payload = JWT::verify($token);
            
            if (!$this->payload || !isset($this->payload['user_id'])) {
                return null;
            }

            $stmt = $this->db->prepare("
                SELECT u.*, r.Role_Name 
                FROM Users u 
                JOIN Roles r ON u.RoleID = r.RoleID 
                WHERE u.UserID = :user_id 
                AND u.Account_Status = 'Active' 
                AND u.Is_Deleted = FALSE
            ");
            $stmt->execute(['user_id' => $this->payload['user_id']]);
            $this->user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $this->user;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Require owner or specific roles
     * 
     * @param int $resourceUserId Owner's user ID
     * @param array $allowedRoles Roles that can access
     * @return bool
     */
    public function requireOwnerOrRole($resourceUserId, $allowedRoles = ['Admin']): bool {
        if (!$this->user) {
            $this->authenticate();
        }

        if ($this->isOwner($resourceUserId)) {
            return true;
        }

        if ($this->hasRole($allowedRoles)) {
            return true;
        }

        Response::error("Access denied. You can only access your own resources.", 403);
        return false;
    }
}