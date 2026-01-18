<?php
/**
 * Authentication Controller
 * Handles login, registration, and token management
 * 
 * @package AnimalShelter
 */

require_once APP_PATH . '/controllers/BaseController.php';
require_once APP_PATH . '/utils/TokenGenerator.php';
require_once APP_PATH . '/utils/CsrfToken.php';

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
        
        // Generate tokens with token version for invalidation support
        $tokenVersion = (int)($user['Token_Version'] ?? 1);
        
        $accessToken = JWT::generateWithVersion([
            'user_id' => $user['UserID'],
            'email' => $user['Email'],
            'username' => $user['Username'],
            'role' => $user['Role_Name']
        ], $tokenVersion);
        
        $refreshToken = JWT::generateRefreshToken($user['UserID'], $tokenVersion);
        
        // Log successful login
        $this->logLoginActivity($user['UserID']);
        
        // Prepare response
        Response::success([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'csrf_token' => CsrfToken::createWithExpiry(),
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
        
        // Generate new access token with current token version
        $tokenVersion = (int)($user['Token_Version'] ?? 1);
        
        $accessToken = JWT::generateWithVersion([
            'user_id' => $user['UserID'],
            'email' => $user['Email'],
            'role' => $user['Role_Name']
        ], $tokenVersion);
        
        Response::success([
            'access_token' => $accessToken,
            'csrf_token' => CsrfToken::createWithExpiry(),
            'token_type' => 'Bearer',
            'expires_in' => JWT_EXPIRY
        ], "Token refreshed");
    }
    
    /**
     * Logout
     * POST /auth/logout
     * 
     * Invalidates all tokens for the current user by incrementing token version
     */
    public function logout() {
        if ($this->user) {
            // Increment token version to invalidate all existing tokens
            JWT::incrementTokenVersion($this->db, $this->user['UserID']);
            $this->logActivity('LOGOUT', 'User logged out - all tokens invalidated');
        }
        
        Response::success(null, "Logged out successfully. All active sessions have been invalidated.");
    }
    
    /**
     * Logout from all sessions
     * POST /auth/logout-all
     * 
     * Invalidates all tokens by incrementing token version (same as logout)
     */
    public function logoutAll() {
        if ($this->user) {
            // Increment token version to invalidate all existing tokens
            JWT::incrementTokenVersion($this->db, $this->user['UserID']);
            $this->logActivity('LOGOUT_ALL', 'User logged out from all sessions - all tokens invalidated');
        }
        
        Response::success(null, "Logged out from all sessions successfully. All tokens have been invalidated.");
    }
    
    /**
     * Forgot password - request reset
     * POST /auth/forgot-password
     * 
     * Generates a password reset token and stores it in the database.
     * In production, this would send an email with the reset link.
     */
    public function forgotPassword() {
        $this->validate([
            'email' => 'required|email'
        ]);
        
        $email = $this->input('email');
        
        // Find user by email (but don't reveal if email exists for security)
        $stmt = $this->db->prepare("
            SELECT UserID, FirstName, Email 
            FROM Users 
            WHERE Email = :email AND Is_Deleted = FALSE AND Account_Status = 'Active'
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        if ($user) {
            try {
                // Generate password reset token (expires in 1 hour)
                $token = TokenGenerator::createPasswordResetToken($this->db, $user['UserID'], 3600);
                
                // Log the action
                $this->logPasswordResetRequest($user['UserID']);
                
                // In production, send email here:
                // EmailService::sendPasswordReset($user['Email'], $user['FirstName'], $token);
                
                // For development/testing, log the token (remove in production!)
                if (defined('APP_ENV') && APP_ENV === 'development') {
                    error_log("Password reset token for {$email}: {$token}");
                }
                
            } catch (Exception $e) {
                error_log("Failed to create password reset token: " . $e->getMessage());
                // Don't reveal the error to the user
            }
        }
        
        // Always return success to prevent email enumeration attacks
        Response::success([
            'message' => 'If your email exists in our system, you will receive a password reset link.',
            'expires_in' => 3600 // 1 hour
        ], "Password reset request processed");
    }
    
    /**
     * Reset password with token
     * POST /auth/reset-password
     * 
     * Validates the reset token and updates the user's password.
     * Also invalidates all existing sessions.
     */
    public function resetPassword() {
        $this->validate([
            'token' => 'required',
            'password' => 'required|min:8'
        ]);
        
        $token = $this->input('token');
        $newPassword = $this->input('password');
        
        // Validate the token
        $tokenRecord = TokenGenerator::validatePasswordResetToken($this->db, $token);
        
        if (!$tokenRecord) {
            Response::error("Invalid or expired reset token. Please request a new password reset.", 400);
        }
        
        $this->db->beginTransaction();
        
        try {
            // Hash the new password
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update user's password and increment token version (invalidates all sessions)
            $stmt = $this->db->prepare("
                UPDATE Users 
                SET Password_Hash = :password, 
                    Token_Version = Token_Version + 1,
                    Updated_At = NOW()
                WHERE UserID = :user_id
            ");
            $stmt->execute([
                'password' => $passwordHash,
                'user_id' => $tokenRecord['UserID']
            ]);
            
            // Mark the token as used
            TokenGenerator::markTokenUsed($this->db, $tokenRecord['TokenID']);
            
            // Log the action
            $this->logPasswordReset($tokenRecord['UserID']);
            
            $this->db->commit();
            
            Response::success([
                'message' => 'Password has been reset successfully. Please login with your new password.',
                'sessions_invalidated' => true
            ], "Password reset successful");
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Password reset failed: " . $e->getMessage());
            Response::serverError("Failed to reset password. Please try again.");
        }
    }
    
    /**
     * Validate reset token (check if valid without using it)
     * POST /auth/validate-reset-token
     */
    public function validateResetToken() {
        $this->validate([
            'token' => 'required'
        ]);
        
        $token = $this->input('token');
        $tokenRecord = TokenGenerator::validatePasswordResetToken($this->db, $token);
        
        if (!$tokenRecord) {
            Response::error("Invalid or expired reset token", 400);
        }
        
        Response::success([
            'valid' => true,
            'email' => $this->maskEmail($tokenRecord['Email']),
            'expires_at' => $tokenRecord['Expires_At']
        ], "Token is valid");
    }
    
    /**
     * Mask email for privacy (show first 2 chars and domain)
     */
    private function maskEmail(string $email): string {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***';
        }
        
        $local = $parts[0];
        $domain = $parts[1];
        
        $maskedLocal = substr($local, 0, 2) . str_repeat('*', max(0, strlen($local) - 2));
        
        return $maskedLocal . '@' . $domain;
    }
    
    /**
     * Log password reset request
     */
    private function logPasswordResetRequest($userId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO Activity_Logs (UserID, Action_Type, Description, IP_Address, Log_Date)
                VALUES (:user_id, 'PASSWORD_RESET_REQUEST', 'Password reset requested', :ip, NOW())
            ");
            $stmt->execute([
                'user_id' => $userId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Failed to log password reset request: " . $e->getMessage());
        }
    }
    
    /**
     * Log password reset completion
     */
    private function logPasswordReset($userId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO Activity_Logs (UserID, Action_Type, Description, IP_Address, Log_Date)
                VALUES (:user_id, 'PASSWORD_RESET', 'Password was reset successfully', :ip, NOW())
            ");
            $stmt->execute([
                'user_id' => $userId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Failed to log password reset: " . $e->getMessage());
        }
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