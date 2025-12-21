<?php
/**
 * Base Controller
 * All controllers extend this class
 * Provides common functionality for all controllers
 * 
 * @package AnimalShelter
 */

abstract class BaseController {
    /**
     * @var PDO Database connection
     */
    protected $db;
    
    /**
     * @var array|null Current authenticated user
     */
    protected $user;
    
    /**
     * @var array Request data (JSON body or form data)
     */
    protected $requestData;
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     * @param array|null $user Authenticated user data
     */
    public function __construct($db, $user = null) {
        $this->db = $db;
        $this->user = $user;
        $this->requestData = $this->getRequestData();
    }
    
    /**
     * Get JSON request data or form data
     * 
     * @return array Request data
     */
    protected function getRequestData() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Handle JSON content type
        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            error_log("DEBUG: Raw JSON Input: " . $json);
            $data = json_decode($json, true) ?? [];
            error_log("DEBUG: Decoded Data: " . print_r($data, true));
            return array_merge($_GET, $data);
        }
        
        // Handle form data
        if (strpos($contentType, 'multipart/form-data') !== false) {
            return array_merge($_POST, $_FILES);
        }
        
        // Handle URL encoded form data
        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            return $_POST;
        }
        
        // Default: merge GET and POST
        return array_merge($_GET, $_POST);
    }
    
    /**
     * Get query parameter
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value if not found
     * @return mixed Parameter value
     */
    protected function query($key, $default = null) {
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Get request body parameter
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value if not found
     * @return mixed Parameter value
     */
    protected function input($key, $default = null) {
        return $this->requestData[$key] ?? $default;
    }
    
    /**
     * Get all input data
     * 
     * @return array All request data
     */
    protected function all() {
        return $this->requestData;
    }
    
    /**
     * Get only specified keys from input
     * 
     * @param array $keys Keys to retrieve
     * @return array Filtered data
     */
    protected function only(array $keys) {
        return array_intersect_key($this->requestData, array_flip($keys));
    }
    
    /**
     * Get all input except specified keys
     * 
     * @param array $keys Keys to exclude
     * @return array Filtered data
     */
    protected function except(array $keys) {
        return array_diff_key($this->requestData, array_flip($keys));
    }
    
    /**
     * Check if input has a key
     * 
     * @param string $key Key to check
     * @return bool
     */
    protected function has($key) {
        return isset($this->requestData[$key]);
    }
    
    /**
     * Get pagination parameters
     * 
     * @return array [page, perPage]
     */
    protected function getPagination() {
        $page = max(1, (int)$this->query('page', 1));
        $perPage = min(
            max(1, (int)$this->query('per_page', DEFAULT_PAGE_SIZE)),
            MAX_PAGE_SIZE
        );
        
        return [$page, $perPage];
    }
    
    /**
     * Validate request data using Validator class
     * Sends validation error response if validation fails
     * 
     * @param array $rules Validation rules
     * @return Validator Validator instance
     */
    protected function validate(array $rules) {
        $validator = Validator::make($this->requestData, $rules);
        
        if ($validator->fails()) {
            Response::validationError($validator->getErrors());
        }
        
        return $validator;
    }
    
    /**
     * Log activity to Activity_Logs table
     * 
     * @param string $actionType Action type code
     * @param string|null $description Detailed description
     */
    protected function logActivity($actionType, $description = null) {
        if (!$this->user) {
            return;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO Activity_Logs (UserID, Action_Type, Description, IP_Address, Log_Date)
                VALUES (:user_id, :action_type, :description, :ip_address, NOW())
            ");
            
            $stmt->execute([
                'user_id' => $this->user['UserID'],
                'action_type' => $actionType,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (PDOException $e) {
            // Log error but don't fail the request
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
    
    /**
     * Check if current user owns a resource
     * 
     * @param int $resourceUserId User ID of resource owner
     * @return bool
     */
    protected function isOwner($resourceUserId) {
        return $this->user && (int)$this->user['UserID'] === (int)$resourceUserId;
    }
    
    /**
     * Check if current user has specific role(s)
     * 
     * @param string|array $roles Role(s) to check
     * @return bool
     */
    protected function hasRole($roles) {
        if (!$this->user) {
            return false;
        }
        
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        return in_array($this->user['Role_Name'], $roles);
    }
    
    /**
     * Check if user is Admin
     * 
     * @return bool
     */
    protected function isAdmin() {
        return $this->hasRole('Admin');
    }
    
    /**
     * Check if user is Staff (or Admin)
     * 
     * @return bool
     */
    protected function isStaff() {
        return $this->hasRole(['Admin', 'Staff']);
    }
    
    /**
     * Check if user is Veterinarian
     * 
     * @return bool
     */
    protected function isVeterinarian() {
        return $this->hasRole(['Admin', 'Veterinarian']);
    }
    
    /**
     * Get uploaded file
     * 
     * @param string $key File input name
     * @return array|null File data or null
     */
    protected function getFile($key) {
        return $_FILES[$key] ?? null;
    }
    
    /**
     * Validate and save uploaded file
     * 
     * @param string $key File input name
     * @param string $destination Destination folder (relative to UPLOAD_PATH)
     * @param array $options Upload options
     * @return string|false Relative file path or false on failure
     */
    protected function saveFile($key, $destination, $options = []) {
        $file = $this->getFile($key);
        
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Check file size
        $maxSize = $options['max_size'] ?? MAX_FILE_SIZE;
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        // Check extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = $options['extensions'] ?? ALLOWED_EXTENSIONS;
        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }
        
        // Create destination directory if not exists
        $uploadDir = UPLOAD_PATH . trim($destination, '/') . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return trim($destination, '/') . '/' . $filename;
        }
        
        return false;
    }
    
    /**
     * Delete a file
     * 
     * @param string $relativePath Relative path from UPLOAD_PATH
     * @return bool
     */
    protected function deleteFile($relativePath) {
        if (empty($relativePath)) {
            return false;
        }
        
        $filepath = UPLOAD_PATH . $relativePath;
        
        if (file_exists($filepath) && is_file($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
    
    /**
     * Get full URL for uploaded file
     * 
     * @param string|null $relativePath Relative path
     * @return string|null Full URL or null
     */
    protected function getFileUrl($relativePath) {
        if (empty($relativePath)) {
            return null;
        }
        
        return UPLOAD_URL . $relativePath;
    }
}