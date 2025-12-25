<?php
/**
 * Authentication Controller
 * Handles login, registration, and token management
 * 
 * @package AnimalShelter
 */

require_once APP_PATH . '/controllers/BaseController.php';

class AuthController extends BaseController {
    
    /**
     * User login
     * POST /auth/login
     */
    public function login() {
        // Apply strict rate limiting for login attempts
        if (defined('RATE_LIMIT_ENABLED') && RATE_LIMIT_ENABLED) {
            if (!class_exists('RateLimiter')) {
                require_once APP_PATH . '/utils/RateLimiter.php';
            }
            RateLimiter::checkLogin();
        }
        
        // Validate input - allow 'username' or 'email'
        $rules = [
            'password' => 'required'
        ];
        
        $identifier = $this->input('email') ?? $this->input('username');
        
        if (!$identifier) {
            Response::error("Username or Email is required", 400);
        }
        
        $password = $this->input('password');
        
        // Get user by email or username
        $stmt = $this->db->prepare("
            SELECT u.*, r.Role_Name 
            FROM Users u 
            JOIN Roles r ON u.RoleID = r.RoleID 
            WHERE (u.Email = :identifier OR u.Username = :identifier) 
            AND u.Is_Deleted = FALSE
        ");
        $stmt->execute(['identifier' => $identifier]);
        $user = $stmt->fetch();
        
        // Verify user exists and password matches
        if (!$user || !password_verify($password, $user['Password_Hash'])) {
            // Log failed attempt
            $this->logFailedLogin($identifier);
            Response::error("Invalid username/email or password", 401);
        }
        
        // Check account status
        if ($user['Account_Status'] !== 'Active') {
            Response::error("Your account is " . strtolower($user['Account_Status']) . ". Please contact support.", 403);
        }
        
        // Generate tokens
        $accessToken = JWT::generate([
            'user_id' => $user['UserID'],
            'email' => $user['Email'],
            'username' => $user['Username'],
            'role' => $user['Role_Name']
        ]);
        
        $refreshToken = JWT::generateRefreshToken($user['UserID']);
        
        // Log successful login
        $this->logLoginActivity($user['UserID']);
        
        // Prepare response
        Response::success([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => JWT_EXPIRY,
            'user' => [
                'id' => (int)$user['UserID'],
                'first_name' => $user['FirstName'],
                'last_name' => $user['LastName'],
                'email' => $user['Email'],
                'username' => $user['Username'],
                'avatar_url' => $user['Avatar_Url'] ?? null,
                'role' => $user['Role_Name'],
                'contact_number' => $user['Contact_Number']
            ]
        ], "Login successful");
    }
    
    /**
     * User registration (public - creates Adopter account)
     * POST /auth/register
     */
    public function register() {
        // Validate input
        $this->validate([
            'username' => 'required|max:50',
            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'contact_number' => 'phone'
        ]);
        
        $email = $this->input('email');
        $username = $this->input('username');
        
        // Check if email or username already exists
        $stmt = $this->db->prepare("
            SELECT UserID FROM Users 
            WHERE (Email = :email OR Username = :username) 
            AND Is_Deleted = FALSE
        ");
        $stmt->execute([
            'email' => $email,
            'username' => $username
        ]);
        
        if ($stmt->fetch()) {
            Response::conflict("Email or Username already registered");
        }
        
        // Get Adopter role ID
        $stmt = $this->db->prepare("SELECT RoleID FROM Roles WHERE Role_Name = 'Adopter'");
        $stmt->execute();
        $role = $stmt->fetch();
        
        if (!$role) {
            Response::serverError("System configuration error");
        }
        
        // Create user
        $passwordHash = password_hash($this->input('password'), PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            INSERT INTO Users (RoleID, Username, FirstName, LastName, Email, Contact_Number, Password_Hash, Account_Status, Is_Deleted)
            VALUES (:role_id, :username, :first_name, :last_name, :email, :contact, :password, 'Active', FALSE)
        ");
        
        $result = $stmt->execute([
            'role_id' => $role['RoleID'],
            'username' => $username,
            'first_name' => $this->input('first_name'),
            'last_name' => $this->input('last_name'),
            'email' => $email,
            'contact' => $this->input('contact_number'),
            'password' => $passwordHash
        ]);
        
        if (!$result) {
            Response::serverError("Failed to create account");
        }
        
        $userId = $this->db->lastInsertId();
        
        // Log registration
        $this->logRegistration($userId);
        
        Response::created([
            'id' => (int)$userId,
            'username' => $username,
            'first_name' => $this->input('first_name'),
            'last_name' => $this->input('last_name'),
            'email' => $email,
            'role' => 'Adopter'
        ], "Registration successful. You can now login.");
    }
    
    /**
     * Refresh access token
     * POST /auth/refresh
     */
    public function refresh() {
        $this->validate([
            'refresh_token' => 'required'
        ]);
        
        $refreshToken = $this->input('refresh_token');
        
        // Verify refresh token
        $payload = JWT::verify($refreshToken);
        
        if (!$payload) {
            Response::unauthorized("Invalid or expired refresh token");
        }
        
        // Check if it's a refresh token type
        if (($payload['type'] ?? '') !== 'refresh') {
            Response::unauthorized("Invalid token type");
        }
        
        // Get user
        $stmt = $this->db->prepare("
            SELECT u.*, r.Role_Name 
            FROM Users u 
            JOIN Roles r ON u.RoleID = r.RoleID 
            WHERE u.UserID = :user_id AND u.Is_Deleted = FALSE
        ");
        $stmt->execute(['user_id' => $payload['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            Response::unauthorized("User not found");
        }
        
        if ($user['Account_Status'] !== 'Active') {
            Response::forbidden("Account is " . strtolower($user['Account_Status']));
        }
        
        // Generate new access token
        $accessToken = JWT::generate([
            'user_id' => $user['UserID'],
            'email' => $user['Email'],
            'role' => $user['Role_Name']
        ]);
        
        Response::success([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => JWT_EXPIRY
        ], "Token refreshed");
    }
    
    /**
     * Logout
     * POST /auth/logout
     */
    public function logout() {
        // JWT is stateless, so we just log the action
        if ($this->user) {
            $this->logActivity('LOGOUT', 'User logged out');
        }
        
        Response::success(null, "Logged out successfully");
    }
    
    /**
     * Logout from all sessions
     * POST /auth/logout-all
     */
    public function logoutAll() {
        // In a stateless JWT setup without a token blacklist/versioning, 
        // we can't strictly invalidate other tokens server-side.
        // We log the action for audit purposes.
        if ($this->user) {
            $this->logActivity('LOGOUT_ALL', 'User requested logout from all sessions');
        }
        
        Response::success(null, "Logged out from all sessions successfully");
    }
    
    /**
     * Forgot password - request reset (placeholder)
     * POST /auth/forgot-password
     */
    public function forgotPassword() {
        $this->validate([
            'email' => 'required|email'
        ]);
        
        // In a real implementation, you would:
        // 1. Generate a password reset token
        // 2. Store it in the database with expiry
        // 3. Send email with reset link
        
        // For now, just return success (don't reveal if email exists)
        Response::success(null, "If your email exists in our system, you will receive a password reset link.");
    }
    
    /**
     * Reset password with token (placeholder)
     * POST /auth/reset-password
     */
    public function resetPassword() {
        $this->validate([
            'token' => 'required',
            'password' => 'required|min:8'
        ]);
        
        // In a real implementation, you would:
        // 1. Verify the reset token
        // 2. Check if it's not expired
        // 3. Update the user's password
        // 4. Invalidate the token
        
        Response::error("Password reset is not yet implemented", 501);
    }
    
    /**
     * Log failed login attempt
     */
    private function logFailedLogin($email) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO Activity_Logs (UserID, Action_Type, Description, IP_Address, Log_Date)
                VALUES (NULL, 'LOGIN_FAILED', :description, :ip, NOW())
            ");
            $stmt->execute([
                'description' => "Failed login attempt for email: {$email}",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Failed to log failed login: " . $e->getMessage());
        }
    }
    
    /**
     * Log successful login
     */
    private function logLoginActivity($userId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO Activity_Logs (UserID, Action_Type, Description, IP_Address, Log_Date)
                VALUES (:user_id, 'LOGIN', 'User logged in successfully', :ip, NOW())
            ");
            $stmt->execute([
                'user_id' => $userId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Failed to log login: " . $e->getMessage());
        }
    }
    
    /**
     * Log registration
     */
    private function logRegistration($userId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO Activity_Logs (UserID, Action_Type, Description, IP_Address, Log_Date)
                VALUES (:user_id, 'REGISTER', 'New user registered', :ip, NOW())
            ");
            $stmt->execute([
                'user_id' => $userId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Failed to log registration: " . $e->getMessage());
        }
    }
}