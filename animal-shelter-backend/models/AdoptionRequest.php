<?php
/**
 * Adoption Request Model
 */

class AdoptionRequest {
    private $conn;
    private $table = "Adoption_Requests";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create adoption request
     */
    public function create($data) {
        // Check if animal is available
        $checkQuery = "SELECT Current_Status FROM Animals WHERE AnimalID = :animal_id AND Is_Deleted = FALSE";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':animal_id', $data['animal_id']);
        $checkStmt->execute();
        $animal = $checkStmt->fetch();

        if (!$animal || $animal['Current_Status'] !== 'Available') {
            return ['error' => 'Animal is not available for adoption'];
        }

        // Check for existing pending request from same user
        $existQuery = "SELECT RequestID FROM " . $this->table . " 
                       WHERE AnimalID = :animal_id AND Adopter_UserID = :user_id 
                       AND Status IN ('Pending', 'Interview Scheduled')";
        $existStmt = $this->conn->prepare($existQuery);
        $existStmt->bindParam(':animal_id', $data['animal_id']);
        $existStmt->bindParam(':user_id', $data['adopter_user_id']);
        $existStmt->execute();
        
        if ($existStmt->fetch()) {
            return ['error' => 'You already have a pending request for this animal'];
        }

        $query = "INSERT INTO " . $this->table . " 
                  (AnimalID, Adopter_UserID, Request_Date, Status) 
                  VALUES (:animal_id, :user_id, NOW(), 'Pending')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':animal_id', $data['animal_id']);
        $stmt->bindParam(':user_id', $data['adopter_user_id']);
        
        if ($stmt->execute()) {
            return ['id' => $this->conn->lastInsertId()];
        }
        
        return ['error' => 'Failed to create request'];
    }

    /**
     * Get request by ID
     */
    public function getById($id) {
        $query = "SELECT ar.*, 
                         a.Name as Animal_Name, a.Type as Animal_Type, a.Breed, a.Image_URL,
                         u.FirstName, u.LastName, u.Email, u.Contact_Number
                  FROM " . $this->table . " ar
                  JOIN Animals a ON ar.AnimalID = a.AnimalID
                  JOIN Users u ON ar.Adopter_UserID = u.UserID
                  WHERE ar.RequestID = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * Get all requests with filters
     */
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "ar.Status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['animal_id'])) {
            $where[] = "ar.AnimalID = :animal_id";
            $params[':animal_id'] = $filters['animal_id'];
        }

        if (!empty($filters['adopter_id'])) {
            $where[] = "ar.Adopter_UserID = :adopter_id";
            $params[':adopter_id'] = $filters['adopter_id'];
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table . " ar WHERE $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get data
        $query = "SELECT ar.*, 
                         a.Name as Animal_Name, a.Type as Animal_Type,
                         u.FirstName, u.LastName, u.Email
                  FROM " . $this->table . " ar
                  JOIN Animals a ON ar.AnimalID = a.AnimalID
                  JOIN Users u ON ar.Adopter_UserID = u.UserID
                  WHERE $whereClause
                  ORDER BY ar.Request_Date DESC
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
     * Update request status
     */
    public function updateStatus($id, $status, $staffUserId, $comments = null) {
        $query = "UPDATE " . $this->table . " 
                  SET Status = :status, Staff_Comments = :comments, Processed_By_UserID = :staff_id 
                  WHERE RequestID = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':comments', $comments);
        $stmt->bindParam(':staff_id', $staffUserId);
        $stmt->bindParam(':id', $id);
        
        $result = $stmt->execute();

        // If approved and completed, update animal status
        if ($result && $status === 'Completed') {
            $request = $this->getById($id);
            if ($request) {
                $updateAnimal = "UPDATE Animals SET Current_Status = 'Adopted' WHERE AnimalID = :animal_id";
                $animalStmt = $this->conn->prepare($updateAnimal);
                $animalStmt->bindParam(':animal_id', $request['AnimalID']);
                $animalStmt->execute();
            }
        }

        return $result;
    }

    /**
     * Get requests by user
     */
    public function getByUser($userId, $page = 1, $perPage = 20) {
        return $this->getAll($page, $perPage, ['adopter_id' => $userId]);
    }

    /**
     * Cancel request (by adopter)
     */
    public function cancel($id, $userId) {
        $query = "UPDATE " . $this->table . " 
                  SET Status = 'Cancelled' 
                  WHERE RequestID = :id AND Adopter_UserID = :user_id AND Status = 'Pending'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute() && $stmt->rowCount() > 0;
    }
}