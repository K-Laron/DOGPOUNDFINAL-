<?php
/**
 * User Model
 */

class User {
    private $conn;
    private $table = "Users";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create new user
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (RoleID, FirstName, LastName, Email, Contact_Number, Password_Hash, Account_Status) 
                  VALUES (:role_id, :first_name, :last_name, :email, :contact, :password, :status)";
        
        $stmt = $this->conn->prepare($query);
        
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt->bindParam(':role_id', $data['role_id']);
        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':contact', $data['contact_number']);
        $stmt->bindParam(':password', $passwordHash);
        $stmt->bindValue(':status', $data['status'] ?? 'Active');
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    /**
     * Get user by ID
     */
    public function getById($id) {
        $query = "SELECT u.UserID, u.RoleID, u.FirstName, u.LastName, u.Email, 
                         u.Contact_Number, u.Account_Status, u.Created_At, u.Updated_At,
                         r.Role_Name
                  FROM " . $this->table . " u
                  JOIN Roles r ON u.RoleID = r.RoleID
                  WHERE u.UserID = :id AND u.Is_Deleted = FALSE";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $query = "SELECT u.*, r.Role_Name 
                  FROM " . $this->table . " u
                  JOIN Roles r ON u.RoleID = r.RoleID
                  WHERE u.Email = :email AND u.Is_Deleted = FALSE";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * Get all users with pagination
     */
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $where = ["u.Is_Deleted = FALSE"];
        $params = [];

        if (!empty($filters['role_id'])) {
            $where[] = "u.RoleID = :role_id";
            $params[':role_id'] = $filters['role_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = "u.Account_Status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(u.FirstName LIKE :search OR u.LastName LIKE :search OR u.Email LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table . " u WHERE $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get data
        $query = "SELECT u.UserID, u.RoleID, u.FirstName, u.LastName, u.Email, 
                         u.Contact_Number, u.Account_Status, u.Created_At, r.Role_Name
                  FROM " . $this->table . " u
                  JOIN Roles r ON u.RoleID = r.RoleID
                  WHERE $whereClause
                  ORDER BY u.Created_At DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total
        ];
    }

    /**
     * Update user
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = ['RoleID', 'FirstName', 'LastName', 'Email', 'Contact_Number', 'Account_Status'];
        
        foreach ($allowedFields as $field) {
            $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $field));
            if (isset($data[$key])) {
                $fields[] = "$field = :$key";
                $params[":$key"] = $data[$key];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE UserID = :id";
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute($params);
    }

    /**
     * Soft delete user
     */
    public function delete($id) {
        $query = "UPDATE " . $this->table . " SET Is_Deleted = TRUE WHERE UserID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Verify password
     */
    public function verifyPassword($email, $password) {
        $user = $this->getByEmail($email);
        
        if ($user && password_verify($password, $user['Password_Hash'])) {
            return $user;
        }
        
        return false;
    }

    /**
     * Update password
     */
    public function updatePassword($id, $newPassword) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $query = "UPDATE " . $this->table . " SET Password_Hash = :hash WHERE UserID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE Email = :email AND Is_Deleted = FALSE";
        $params = [':email' => $email];
        
        if ($excludeId) {
            $query .= " AND UserID != :id";
            $params[':id'] = $excludeId;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch()['count'] > 0;
    }
}