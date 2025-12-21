<?php
/**
 * Role Model
 * Handles role-related database operations
 * 
 * @package AnimalShelter
 */

class Role {
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * @var string Table name
     */
    private $table = 'Roles';
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Find role by ID
     * 
     * @param int $id Role ID
     * @return array|false Role data or false
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE RoleID = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Find role by name
     * 
     * @param string $name Role name
     * @return array|false Role data or false
     */
    public function findByName($name) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE Role_Name = :name");
        $stmt->execute(['name' => $name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all roles
     * 
     * @return array All roles
     */
    public function all() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY RoleID ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all roles with user count
     * 
     * @return array Roles with user counts
     */
    public function allWithUserCount() {
        $stmt = $this->db->prepare("
            SELECT 
                r.*,
                COUNT(u.UserID) as user_count
            FROM {$this->table} r
            LEFT JOIN Users u ON r.RoleID = u.RoleID AND u.Is_Deleted = FALSE
            GROUP BY r.RoleID
            ORDER BY r.RoleID ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new role
     * 
     * @param string $name Role name
     * @return int|false Role ID or false
     */
    public function create($name) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (Role_Name, Created_At, Updated_At)
            VALUES (:name, NOW(), NOW())
        ");
        
        $result = $stmt->execute(['name' => $name]);
        return $result ? (int)$this->db->lastInsertId() : false;
    }
    
    /**
     * Update role
     * 
     * @param int $id Role ID
     * @param string $name New role name
     * @return bool Success status
     */
    public function update($id, $name) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET Role_Name = :name, Updated_At = NOW()
            WHERE RoleID = :id
        ");
        return $stmt->execute(['name' => $name, 'id' => $id]);
    }
    
    /**
     * Delete role
     * 
     * @param int $id Role ID
     * @return bool Success status
     */
    public function delete($id) {
        // Check if role has users
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM Users WHERE RoleID = :id AND Is_Deleted = FALSE");
        $stmt->execute(['id' => $id]);
        
        if ($stmt->fetch()['count'] > 0) {
            return false; // Cannot delete role with users
        }
        
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE RoleID = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Check if role name exists
     * 
     * @param string $name Role name
     * @param int|null $excludeId Role ID to exclude
     * @return bool
     */
    public function nameExists($name, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE Role_Name = :name";
        $params = ['name' => $name];
        
        if ($excludeId) {
            $sql .= " AND RoleID != :id";
            $params['id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetch()['count'] > 0;
    }
}