<?php
/**
 * Activity Log Model
 * Handles activity logging database operations
 * 
 * @package AnimalShelter
 */

class ActivityLog {
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * @var string Table name
     */
    private $table = 'Activity_Logs';
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Find log entry by ID
     * 
     * @param int $id Log ID
     * @return array|false Log data or false
     */
    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT al.*, 
                   u.FirstName, 
                   u.LastName, 
                   u.Email,
                   r.Role_Name
            FROM {$this->table} al
            LEFT JOIN Users u ON al.UserID = u.UserID
            LEFT JOIN Roles r ON u.RoleID = r.RoleID
            WHERE al.LogID = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create log entry
     * 
     * @param int|null $userId User ID (null for system/anonymous)
     * @param string $actionType Action type code
     * @param string|null $description Detailed description
     * @return int|false Log ID or false
     */
    public function log($userId, $actionType, $description = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO {$this->table} (
                    UserID, 
                    Action_Type, 
                    Description, 
                    IP_Address, 
                    Log_Date
                ) VALUES (
                    :user_id, 
                    :action_type, 
                    :description, 
                    :ip, 
                    NOW()
                )
            ");
            
            $result = $stmt->execute([
                'user_id' => $userId,
                'action_type' => $actionType,
                'description' => $description,
                'ip' => $this->getClientIP()
            ]);
            
            return $result ? (int)$this->db->lastInsertId() : false;
            
        } catch (PDOException $e) {
            error_log("Failed to log activity: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all logs with pagination
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param array $filters Filter options
     * @return array ['data' => [], 'total' => int]
     */
    public function paginate($page = 1, $perPage = 20, $filters = []) {
        $where = ["1=1"];
        $params = [];
        
        // Filter by user
        if (!empty($filters['user_id'])) {
            $where[] = "al.UserID = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        
        // Filter by action type
        if (!empty($filters['action_type'])) {
            $where[] = "al.Action_Type = :action_type";
            $params['action_type'] = $filters['action_type'];
        }
        
        // Filter by action type pattern
        if (!empty($filters['action_pattern'])) {
            $where[] = "al.Action_Type LIKE :action_pattern";
            $params['action_pattern'] = '%' . $filters['action_pattern'] . '%';
        }
        
        // Date range
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(al.Log_Date) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(al.Log_Date) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        // Filter by IP
        if (!empty($filters['ip_address'])) {
            $where[] = "al.IP_Address = :ip";
            $params['ip'] = $filters['ip_address'];
        }
        
        // Search in description
        if (!empty($filters['search'])) {
            $where[] = "(al.Description LIKE :search OR al.Action_Type LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM {$this->table} al 
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];
        
        // Get data
        $stmt = $this->db->prepare("
            SELECT al.*, 
                   u.FirstName, 
                   u.LastName, 
                   u.Email,
                   r.Role_Name
            FROM {$this->table} al
            LEFT JOIN Users u ON al.UserID = u.UserID
            LEFT JOIN Roles r ON u.RoleID = r.RoleID
            WHERE {$whereClause}
            ORDER BY al.Log_Date DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total
        ];
    }
    
    /**
     * Get recent logs
     * 
     * @param int $limit Number of logs to retrieve
     * @return array Recent logs
     */
    public function getRecent($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT al.*, 
                   u.FirstName, 
                   u.LastName,
                   r.Role_Name
            FROM {$this->table} al
            LEFT JOIN Users u ON al.UserID = u.UserID
            LEFT JOIN Roles r ON u.RoleID = r.RoleID
            ORDER BY al.Log_Date DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get logs by user
     * 
     * @param int $userId User ID
     * @param int $limit Limit
     * @return array User logs
     */
    public function getByUser($userId, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE UserID = :user_id
            ORDER BY Log_Date DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get logs by action type
     * 
     * @param string $actionType Action type
     * @param int $limit Limit
     * @return array Logs
     */
    public function getByActionType($actionType, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT al.*, 
                   u.FirstName, 
                   u.LastName,
                   r.Role_Name
            FROM {$this->table} al
            LEFT JOIN Users u ON al.UserID = u.UserID
            LEFT JOIN Roles r ON u.RoleID = r.RoleID
            WHERE al.Action_Type = :action_type
            ORDER BY al.Log_Date DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':action_type', $actionType);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get today's logs
     * 
     * @return array Today's logs
     */
    public function getToday() {
        $stmt = $this->db->prepare("
            SELECT al.*, 
                   u.FirstName, 
                   u.LastName,
                   r.Role_Name
            FROM {$this->table} al
            LEFT JOIN Users u ON al.UserID = u.UserID
            LEFT JOIN Roles r ON u.RoleID = r.RoleID
            WHERE DATE(al.Log_Date) = CURDATE()
            ORDER BY al.Log_Date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user's last login
     * 
     * @param int $userId User ID
     * @return array|false Last login log or false
     */
    public function getLastLogin($userId) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE UserID = :user_id AND Action_Type = 'LOGIN'
            ORDER BY Log_Date DESC
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get login history for user
     * 
     * @param int $userId User ID
     * @param int $limit Limit
     * @return array Login history
     */
    public function getLoginHistory($userId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE UserID = :user_id AND Action_Type IN ('LOGIN', 'LOGOUT', 'LOGIN_FAILED')
            ORDER BY Log_Date DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get failed login attempts for IP
     * 
     * @param string $ip IP address
     * @param int $minutes Minutes to look back
     * @return int Number of failed attempts
     */
    public function getFailedLoginAttempts($ip, $minutes = 15) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM {$this->table}
            WHERE IP_Address = :ip 
            AND Action_Type = 'LOGIN_FAILED'
            AND Log_Date >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
        ");
        $stmt->execute(['ip' => $ip, 'minutes' => $minutes]);
        return (int)$stmt->fetch()['count'];
    }
    
    /**
     * Delete old logs
     * 
     * @param int $days Days to keep
     * @return int Number of deleted records
     */
    public function deleteOldLogs($days = 90) {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->table}
            WHERE Log_Date < DATE_SUB(CURDATE(), INTERVAL :days DAY)
        ");
        $stmt->execute(['days' => $days]);
        return $stmt->rowCount();
    }
    
    /**
     * Get statistics
     * 
     * @return array Statistics
     */
    public function getStatistics() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_logs,
                COUNT(DISTINCT UserID) as unique_users,
                COUNT(DISTINCT IP_Address) as unique_ips,
                COUNT(DISTINCT Action_Type) as action_types,
                COUNT(CASE WHEN DATE(Log_Date) = CURDATE() THEN 1 END) as today_count,
                COUNT(CASE WHEN YEARWEEK(Log_Date) = YEARWEEK(CURDATE()) THEN 1 END) as this_week,
                COUNT(CASE WHEN MONTH(Log_Date) = MONTH(CURDATE()) AND YEAR(Log_Date) = YEAR(CURDATE()) THEN 1 END) as this_month,
                MIN(Log_Date) as earliest_log,
                MAX(Log_Date) as latest_log
            FROM {$this->table}
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get statistics by action type
     * 
     * @return array Statistics by action
     */
    public function getStatsByAction() {
        $stmt = $this->db->prepare("
            SELECT 
                Action_Type as action,
                COUNT(*) as count,
                COUNT(DISTINCT UserID) as unique_users,
                MAX(Log_Date) as last_occurrence
            FROM {$this->table}
            GROUP BY Action_Type
            ORDER BY count DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get statistics by user
     * 
     * @param int $limit Limit
     * @return array Statistics by user
     */
    public function getStatsByUser($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                al.UserID,
                u.FirstName,
                u.LastName,
                u.Email,
                COUNT(*) as activity_count,
                MAX(al.Log_Date) as last_activity
            FROM {$this->table} al
            LEFT JOIN Users u ON al.UserID = u.UserID
            WHERE al.UserID IS NOT NULL
            GROUP BY al.UserID
            ORDER BY activity_count DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get daily activity for date range
     * 
     * @param int $days Number of days
     * @return array Daily activity
     */
    public function getDailyActivity($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(Log_Date) as date,
                COUNT(*) as activity_count,
                COUNT(DISTINCT UserID) as unique_users
            FROM {$this->table}
            WHERE Log_Date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            GROUP BY DATE(Log_Date)
            ORDER BY date ASC
        ");
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get hourly activity distribution
     * 
     * @return array Hourly distribution
     */
    public function getHourlyDistribution() {
        $stmt = $this->db->prepare("
            SELECT 
                HOUR(Log_Date) as hour,
                COUNT(*) as activity_count
            FROM {$this->table}
            GROUP BY HOUR(Log_Date)
            ORDER BY hour ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all distinct action types
     * 
     * @return array Action types
     */
    public function getActionTypes() {
        $stmt = $this->db->prepare("
            SELECT DISTINCT Action_Type
            FROM {$this->table}
            ORDER BY Action_Type
        ");
        $stmt->execute();
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Action_Type');
    }
    
    /**
     * Get client IP address
     * 
     * @return string|null IP address
     */
    private function getClientIP() {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated list (proxy case)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return null;
    }
    
    // ==========================================
    // CONVENIENCE LOGGING METHODS
    // ==========================================
    
    /**
     * Log login event
     */
    public function logLogin($userId) {
        return $this->log($userId, 'LOGIN', 'User logged in successfully');
    }
    
    /**
     * Log logout event
     */
    public function logLogout($userId) {
        return $this->log($userId, 'LOGOUT', 'User logged out');
    }
    
    /**
     * Log failed login attempt
     */
    public function logFailedLogin($email) {
        return $this->log(null, 'LOGIN_FAILED', "Failed login attempt for email: {$email}");
    }
    
    /**
     * Log user registration
     */
    public function logRegistration($userId) {
        return $this->log($userId, 'REGISTER', 'New user registered');
    }
    
    /**
     * Log password change
     */
    public function logPasswordChange($userId) {
        return $this->log($userId, 'CHANGE_PASSWORD', 'User changed their password');
    }
    
    /**
     * Log profile update
     */
    public function logProfileUpdate($userId) {
        return $this->log($userId, 'UPDATE_PROFILE', 'User updated their profile');
    }
}