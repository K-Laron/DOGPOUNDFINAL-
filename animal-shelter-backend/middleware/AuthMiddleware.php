<?php
/**
 * Authentication Middleware
 */

require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthMiddleware {
    private $db;
    private $user = null;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Authenticate request
     */
    public function authenticate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            Response::error("Authorization token required", 401);
        }

        $token = $matches[1];
        $payload = JWT::verify($token);

        if (!$payload) {
            Response::error("Invalid or expired token", 401);
        }

        // Verify user exists and is active
        $stmt = $this->db->prepare("
            SELECT u.*, r.Role_Name 
            FROM Users u 
            JOIN Roles r ON u.RoleID = r.RoleID 
            WHERE u.UserID = ? AND u.Account_Status = 'Active' AND u.Is_Deleted = FALSE
        ");
        $stmt->execute([$payload['user_id']]);
        $this->user = $stmt->fetch();

        if (!$this->user) {
            Response::error("User not found or inactive", 401);
        }

        return $this->user;
    }

    /**
     * Check if user has required role
     */
    public function requireRole($allowedRoles) {
        if (!$this->user) {
            $this->authenticate();
        }

        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }

        if (!in_array($this->user['Role_Name'], $allowedRoles)) {
            Response::error("Access denied. Required role: " . implode(' or ', $allowedRoles), 403);
        }

        return true;
    }

    /**
     * Get authenticated user
     */
    public function getUser() {
        return $this->user;
    }
}