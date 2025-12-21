<?php
/**
 * User Model
 * Handles all user-related database operations
 * 
 * @package AnimalShelter
 */

class User {
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * @var string Table name
     */
    private $table = 'Users';
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Find user by ID
     * 
     * @param int $id User ID
     * @return array|false User data or false
     */
    public function find($id) {
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
                u.Created_At,
                u.Updated_At,
                r.Role_Name
            FROM {$this->table} u
            JOIN Roles r ON u.RoleID = r.RoleID
            WHERE u.UserID = :id AND u.Is_Deleted = FALSE
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Find user by email
     * 
     * @param string $email Email address
     * @return array|false User data or false
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("
            SELECT 
                u.*,
                r.Role_Name
            FROM {$this->table} u
            JOIN Roles r ON u.RoleID = r.RoleID
            WHERE u.Email = :email AND u.Is_Deleted = FALSE
        ");
        $stmt->execute(['email' => strtolower(trim($email))]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all users with pagination
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param array $filters Filter options
     * @return array ['data' => [], 'total' => int]
     */
    public function paginate($page = 1, $perPage = 20, $filters = []) {
        $where = ["u.Is_Deleted = FALSE"];
        $params = [];
        
        // Filter by role
        if (!empty($filters['role_id'])) {
            $where[] = "u.RoleID = :role_id";
            $params['role_id'] = $filters['role_id'];
        }
        
        // Filter by role name
        if (!empty($filters['role'])) {
            $where[] = "r.Role_Name = :role_name";
            $params['role_name'] = $filters['role'];
        }
        
        // Filter by status
        if (!empty($filters['status'])) {
            $where[] = "u.Account_Status = :status";
            $params['status'] = $filters['status'];
        }
        
        // Search
        if (!empty($filters['search'])) {
            $where[] = "(u.FirstName LIKE :search OR u.LastName LIKE :search OR u.Email LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM {$this->table} u
            JOIN Roles r ON u.RoleID = r.RoleID
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];
        
        // Get data
        $stmt = $this->db->prepare("
            SELECT 
                u.UserID,
                u.RoleID,
                u.FirstName,
                u.LastName,
                u.Email,
                u.Contact_Number,
                u.Account_Status,
                u.Created_At,
                u.Updated_At,
                r.Role_Name
            FROM {$this->table} u
            JOIN Roles r ON u.RoleID = r.RoleID
            WHERE {$whereClause}
            ORDER BY u.Created_At DESC
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
     * Create new user
     * 
     * @param array $data User data
     * @return int|false User ID or false on failure
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (
                RoleID, 
                FirstName, 
                LastName, 
                Email, 
                Contact_Number, 
                Password_Hash, 
                Account_Status, 
                Is_Deleted,
                Created_At,
                Updated_At
            ) VALUES (
                :role_id, 
                :first_name, 
                :last_name, 
                :email, 
                :contact, 
                :password, 
                :status, 
                FALSE,
                NOW(),
                NOW()
            )
        ");
        
        $result = $stmt->execute([
            'role_id' => $data['role_id'],
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'email' => strtolower(trim($data['email'])),
            'contact' => $data['contact_number'] ?? null,
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'status' => $data['account_status'] ?? 'Active'
        ]);
        
        return $result ? (int)$this->db->lastInsertId() : false;
    }
    
    /**
     * Update user
     * 
     * @param int $id User ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = [
            'RoleID' => 'role_id',
            'FirstName' => 'first_name',
            'LastName' => 'last_name',
            'Email' => 'email',
            'Contact_Number' => 'contact_number',
            'Account_Status' => 'account_status'
        ];
        
        foreach ($allowedFields as $dbField => $dataKey) {
            if (array_key_exists($dataKey, $data)) {
                $fields[] = "{$dbField} = :{$dataKey}";
                $params[$dataKey] = $data[$dataKey];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $fields[] = "Updated_At = NOW()";
        
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE UserID = :id"
        );
        
        return $stmt->execute($params);
    }
    
    /**
     * Soft delete user
     * 
     * @param int $id User ID
     * @return bool Success status
     */
    public function delete($id) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET Is_Deleted = TRUE, Account_Status = 'Inactive', Updated_At = NOW()
            WHERE UserID = :id
        ");
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Hard delete user (permanent)
     * 
     * @param int $id User ID
     * @return bool Success status
     */
    public function hardDelete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE UserID = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Restore soft deleted user
     * 
     * @param int $id User ID
     * @return bool Success status
     */
    public function restore($id) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET Is_Deleted = FALSE, Account_Status = 'Active', Updated_At = NOW()
            WHERE UserID = :id
        ");
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Verify user password
     * 
     * @param string $email Email address
     * @param string $password Plain text password
     * @return array|false User data if valid, false otherwise
     */
    public function verifyPassword($email, $password) {
        $user = $this->findByEmail($email);
        
        if ($user && password_verify($password, $user['Password_Hash'])) {
            return $user;
        }
        
        return false;
    }
    
    /**
     * Update user password
     * 
     * @param int $id User ID
     * @param string $newPassword New plain text password
     * @return bool Success status
     */
    public function updatePassword($id, $newPassword) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET Password_Hash = :hash, Updated_At = NOW()
            WHERE UserID = :id
        ");
        return $stmt->execute([
            'hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $id
        ]);
    }
    
    /**
     * Check if email exists
     * 
     * @param string $email Email address
     * @param int|null $excludeId User ID to exclude
     * @return bool
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE Email = :email AND Is_Deleted = FALSE";
        $params = ['email' => strtolower(trim($email))];
        
        if ($excludeId) {
            $sql .= " AND UserID != :id";
            $params['id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetch()['count'] > 0;
    }
    
    /**
     * Update account status
     * 
     * @param int $id User ID
     * @param string $status New status (Active, Inactive, Banned)
     * @return bool Success status
     */
    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET Account_Status = :status, Updated_At = NOW()
            WHERE UserID = :id
        ");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }
    
    /**
     * Get users by role
     * 
     * @param string $roleName Role name
     * @return array Users
     */
    public function getByRole($roleName) {
        $stmt = $this->db->prepare("
            SELECT u.*, r.Role_Name
            FROM {$this->table} u
            JOIN Roles r ON u.RoleID = r.RoleID
            WHERE r.Role_Name = :role AND u.Is_Deleted = FALSE AND u.Account_Status = 'Active'
            ORDER BY u.LastName, u.FirstName
        ");
        $stmt->execute(['role' => $roleName]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Count users by role
     * 
     * @return array Role counts
     */
    public function countByRole() {
        $stmt = $this->db->prepare("
            SELECT r.Role_Name, COUNT(u.UserID) as count
            FROM Roles r
            LEFT JOIN {$this->table} u ON r.RoleID = u.RoleID AND u.Is_Deleted = FALSE
            GROUP BY r.RoleID, r.Role_Name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user statistics
     * 
     * @return array Statistics
     */
    public function getStatistics() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Account_Status = 'Active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN Account_Status = 'Inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN Account_Status = 'Banned' THEN 1 ELSE 0 END) as banned,
                SUM(CASE WHEN DATE(Created_At) = CURDATE() THEN 1 ELSE 0 END) as created_today,
                SUM(CASE WHEN YEARWEEK(Created_At) = YEARWEEK(CURDATE()) THEN 1 ELSE 0 END) as created_this_week,
                SUM(CASE WHEN MONTH(Created_At) = MONTH(CURDATE()) AND YEAR(Created_At) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as created_this_month
            FROM {$this->table}
            WHERE Is_Deleted = FALSE
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}