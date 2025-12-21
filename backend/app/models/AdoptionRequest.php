<?php
/**
 * Adoption Request Model
 * Handles adoption request database operations
 * 
 * @package AnimalShelter
 */

class AdoptionRequest {
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * @var string Table name
     */
    private $table = 'Adoption_Requests';
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Find request by ID
     * 
     * @param int $id Request ID
     * @return array|false Request data or false
     */
    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT ar.*, 
                   a.Name as Animal_Name, a.Type as Animal_Type, a.Breed, a.Gender, 
                   a.Age_Group, a.Image_URL, a.Current_Status as Animal_Status,
                   adopter.FirstName as Adopter_FirstName, adopter.LastName as Adopter_LastName, 
                   adopter.Email as Adopter_Email, adopter.Contact_Number as Adopter_Contact,
                   staff.FirstName as Staff_FirstName, staff.LastName as Staff_LastName
            FROM {$this->table} ar
            JOIN Animals a ON ar.AnimalID = a.AnimalID
            JOIN Users adopter ON ar.Adopter_UserID = adopter.UserID
            LEFT JOIN Users staff ON ar.Processed_By_UserID = staff.UserID
            WHERE ar.RequestID = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all requests with pagination
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param array $filters Filter options
     * @return array ['data' => [], 'total' => int]
     */
    public function paginate($page = 1, $perPage = 20, $filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = "ar.Status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['adopter_id'])) {
            $where[] = "ar.Adopter_UserID = :adopter_id";
            $params['adopter_id'] = $filters['adopter_id'];
        }
        
        if (!empty($filters['animal_id'])) {
            $where[] = "ar.AnimalID = :animal_id";
            $params['animal_id'] = $filters['animal_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(ar.Request_Date) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(ar.Request_Date) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        
        // Get total
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM {$this->table} ar WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];
        
        // Get data
        $stmt = $this->db->prepare("
            SELECT ar.*, 
                   a.Name as Animal_Name, a.Type as Animal_Type, a.Breed, a.Image_URL,
                   u.FirstName, u.LastName, u.Email, u.Contact_Number
            FROM {$this->table} ar
            JOIN Animals a ON ar.AnimalID = a.AnimalID
            JOIN Users u ON ar.Adopter_UserID = u.UserID
            WHERE {$whereClause}
            ORDER BY ar.Request_Date DESC
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
     * Get requests by adopter
     * 
     * @param int $adopterId Adopter user ID
     * @return array Requests
     */
    public function getByAdopter($adopterId) {
        $stmt = $this->db->prepare("
            SELECT ar.*, 
                   a.Name as Animal_Name, a.Type as Animal_Type, a.Breed, a.Image_URL
            FROM {$this->table} ar
            JOIN Animals a ON ar.AnimalID = a.AnimalID
            WHERE ar.Adopter_UserID = :adopter_id
            ORDER BY ar.Request_Date DESC
        ");
        $stmt->execute(['adopter_id' => $adopterId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get requests by animal
     * 
     * @param int $animalId Animal ID
     * @return array Requests
     */
    public function getByAnimal($animalId) {
        $stmt = $this->db->prepare("
            SELECT ar.*, u.FirstName, u.LastName, u.Email
            FROM {$this->table} ar
            JOIN Users u ON ar.Adopter_UserID = u.UserID
            WHERE ar.AnimalID = :animal_id
            ORDER BY ar.Request_Date DESC
        ");
        $stmt->execute(['animal_id' => $animalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get pending requests
     * 
     * @return array Pending requests
     */
    public function getPending() {
        $stmt = $this->db->prepare("
            SELECT ar.*, 
                   a.Name as Animal_Name, a.Type as Animal_Type,
                   u.FirstName, u.LastName, u.Email
            FROM {$this->table} ar
            JOIN Animals a ON ar.AnimalID = a.AnimalID
            JOIN Users u ON ar.Adopter_UserID = u.UserID
            WHERE ar.Status = 'Pending'
            ORDER BY ar.Request_Date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create adoption request
     * 
     * @param array $data Request data
     * @return int|false Request ID or false
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (
                AnimalID,
                Adopter_UserID,
                Request_Date,
                Status,
                Created_At,
                Updated_At
            ) VALUES (
                :animal_id,
                :adopter_id,
                NOW(),
                'Pending',
                NOW(),
                NOW()
            )
        ");
        
        $result = $stmt->execute([
            'animal_id' => $data['animal_id'],
            'adopter_id' => $data['adopter_user_id']
        ]);
        
        return $result ? (int)$this->db->lastInsertId() : false;
    }
    
    /**
     * Update request status
     * 
     * @param int $id Request ID
     * @param string $status New status
     * @param int|null $staffId Staff user ID who processed
     * @param string|null $comments Staff comments
     * @return bool Success status
     */
    public function updateStatus($id, $status, $staffId = null, $comments = null) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET Status = :status,
                Processed_By_UserID = :staff_id,
                Staff_Comments = :comments,
                Updated_At = NOW()
            WHERE RequestID = :id
        ");
        
        return $stmt->execute([
            'status' => $status,
            'staff_id' => $staffId,
            'comments' => $comments,
            'id' => $id
        ]);
    }
    
    /**
     * Cancel request
     * 
     * @param int $id Request ID
     * @return bool Success status
     */
    public function cancel($id) {
        return $this->updateStatus($id, 'Cancelled');
    }
    
    /**
     * Complete adoption and update animal status
     * 
     * @param int $id Request ID
     * @param int $staffId Staff user ID
     * @return bool Success status
     */
    public function complete($id, $staffId) {
        $request = $this->find($id);
        if (!$request) {
            return false;
        }
        
        $this->db->beginTransaction();
        
        try {
            // Update request status
            $this->updateStatus($id, 'Completed', $staffId);
            
            // Update animal status
            $stmt = $this->db->prepare("UPDATE Animals SET Current_Status = 'Adopted', Updated_At = NOW() WHERE AnimalID = :id");
            $stmt->execute(['id' => $request['AnimalID']]);
            
            // Reject other pending requests for this animal
            $stmt = $this->db->prepare("
                UPDATE {$this->table}
                SET Status = 'Rejected',
                    Staff_Comments = 'Animal has been adopted by another applicant',
                    Processed_By_UserID = :staff_id,
                    Updated_At = NOW()
                WHERE AnimalID = :animal_id
                AND RequestID != :request_id
                AND Status IN ('Pending', 'Interview Scheduled', 'Approved')
            ");
            $stmt->execute([
                'staff_id' => $staffId,
                'animal_id' => $request['AnimalID'],
                'request_id' => $id
            ]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error completing adoption: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user has active request for animal
     * 
     * @param int $animalId Animal ID
     * @param int $userId User ID
     * @return bool
     */
    public function hasActiveRequest($animalId, $userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM {$this->table}
            WHERE AnimalID = :animal_id
            AND Adopter_UserID = :user_id
            AND Status IN ('Pending', 'Interview Scheduled', 'Approved')
        ");
        $stmt->execute([
            'animal_id' => $animalId,
            'user_id' => $userId
        ]);
        
        return (int)$stmt->fetch()['count'] > 0;
    }
    
    /**
     * Get statistics
     * 
     * @return array Statistics
     */
    public function getStatistics() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN Status = 'Interview Scheduled' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN Status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN Status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN Status = 'Completed' AND MONTH(Updated_At) = MONTH(CURDATE()) THEN 1 ELSE 0 END) as completed_this_month
            FROM {$this->table}
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get monthly adoption statistics
     * 
     * @param int $months Number of months
     * @return array Monthly statistics
     */
    public function getMonthlyStats($months = 12) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(Updated_At, '%Y-%m') as month,
                COUNT(*) as total,
                SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) as completed
            FROM {$this->table}
            WHERE Updated_At >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY DATE_FORMAT(Updated_At, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute(['months' => $months]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}