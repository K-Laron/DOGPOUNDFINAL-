<?php
/**
 * Animal Model
 */

class Animal {
    private $conn;
    private $table = "Animals";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create new animal record
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (Name, Type, Breed, Gender, Age_Group, Weight, Intake_Date, 
                   Intake_Status, Current_Status, Image_URL) 
                  VALUES (:name, :type, :breed, :gender, :age_group, :weight, 
                          :intake_date, :intake_status, :current_status, :image_url)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':breed', $data['breed']);
        $stmt->bindParam(':gender', $data['gender']);
        $stmt->bindParam(':age_group', $data['age_group']);
        $stmt->bindParam(':weight', $data['weight']);
        $stmt->bindValue(':intake_date', $data['intake_date'] ?? date('Y-m-d H:i:s'));
        $stmt->bindParam(':intake_status', $data['intake_status']);
        $stmt->bindValue(':current_status', $data['current_status'] ?? 'Available');
        $stmt->bindParam(':image_url', $data['image_url']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    /**
     * Get animal by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE AnimalID = :id AND Is_Deleted = FALSE";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * Get all animals with pagination and filters
     */
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $where = ["Is_Deleted = FALSE"];
        $params = [];

        // Apply filters
        if (!empty($filters['type'])) {
            $where[] = "Type = :type";
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['status'])) {
            $where[] = "Current_Status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['gender'])) {
            $where[] = "Gender = :gender";
            $params[':gender'] = $filters['gender'];
        }

        if (!empty($filters['intake_status'])) {
            $where[] = "Intake_Status = :intake_status";
            $params[':intake_status'] = $filters['intake_status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(Name LIKE :search OR Breed LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get data
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE $whereClause 
                  ORDER BY Intake_Date DESC 
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
     * Get available animals for adoption
     */
    public function getAvailable($page = 1, $perPage = 20, $type = null) {
        $offset = ($page - 1) * $perPage;
        $where = "Is_Deleted = FALSE AND Current_Status = 'Available'";
        $params = [];

        if ($type) {
            $where .= " AND Type = :type";
            $params[':type'] = $type;
        }

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE $where";
        $countStmt = $this->conn->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get data
        $query = "SELECT AnimalID, Name, Type, Breed, Gender, Age_Group, Weight, Image_URL, Intake_Date
                  FROM " . $this->table . " 
                  WHERE $where 
                  ORDER BY Intake_Date DESC 
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
     * Update animal record
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'Name' => 'name',
            'Type' => 'type',
            'Breed' => 'breed',
            'Gender' => 'gender',
            'Age_Group' => 'age_group',
            'Weight' => 'weight',
            'Current_Status' => 'current_status',
            'Image_URL' => 'image_url'
        ];
        
        foreach ($allowedFields as $dbField => $dataKey) {
            if (isset($data[$dataKey])) {
                $fields[] = "$dbField = :$dataKey";
                $params[":$dataKey"] = $data[$dataKey];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE AnimalID = :id";
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute($params);
    }

    /**
     * Update animal status
     */
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table . " SET Current_Status = :status WHERE AnimalID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Soft delete animal
     */
    public function delete($id) {
        $query = "UPDATE " . $this->table . " SET Is_Deleted = TRUE WHERE AnimalID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Get statistics
     */
    public function getStatistics() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN Current_Status = 'Available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN Current_Status = 'Adopted' THEN 1 ELSE 0 END) as adopted,
                    SUM(CASE WHEN Current_Status = 'In Treatment' THEN 1 ELSE 0 END) as in_treatment,
                    SUM(CASE WHEN Type = 'Dog' THEN 1 ELSE 0 END) as dogs,
                    SUM(CASE WHEN Type = 'Cat' THEN 1 ELSE 0 END) as cats,
                    SUM(CASE WHEN Type = 'Other' THEN 1 ELSE 0 END) as others
                  FROM " . $this->table . " 
                  WHERE Is_Deleted = FALSE";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * Get animal with full details including impound record
     */
    public function getFullDetails($id) {
        $animal = $this->getById($id);
        
        if (!$animal) {
            return null;
        }

        // Get impound record
        $query = "SELECT * FROM Impound_Records WHERE AnimalID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $animal['impound_record'] = $stmt->fetch();

        // Get medical records count
        $query = "SELECT COUNT(*) as count FROM Medical_Records WHERE AnimalID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $animal['medical_records_count'] = $stmt->fetch()['count'];

        return $animal;
    }
}