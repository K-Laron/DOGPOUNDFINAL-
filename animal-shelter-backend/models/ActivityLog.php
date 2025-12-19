<?php
/**
 * Activity Log Model
 */

class ActivityLog {
    private $conn;
    private $table = "Activity_Logs";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create log entry
     */
    public function log($userId, $actionType, $description = null) {
        $query = "INSERT INTO " . $this->table . " 
                  (UserID, Action_Type, Description, IP_Address, Log_Date) 
                  VALUES (:user_id, :action_type, :description, :ip, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action_type', $actionType);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':ip', $ip);
        
        return $stmt->execute();
    }

    /**
     * Get logs with filters
     */
    public function getLogs($page = 1, $perPage = 50, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = "al.UserID = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (!empty($filters['action_type'])) {
            $where[] = "al.Action_Type = :action_type";
            $params[':action_type'] = $filters['action_type'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(al.Log_Date) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "DATE(al.Log_Date) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table . " al WHERE $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get data
        $query = "SELECT al.*, u.FirstName, u.LastName, u.Email
                  FROM " . $this->table . " al
                  LEFT JOIN Users u ON al.UserID = u.UserID
                  WHERE $whereClause
                  ORDER BY al.Log_Date DESC
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
     * Get recent activity for dashboard
     */
    public function getRecent($limit = 10) {
        $query = "SELECT al.*, u.FirstName, u.LastName
                  FROM " . $this->table . " al
                  LEFT JOIN Users u ON al.UserID = u.UserID
                  ORDER BY al.Log_Date DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}