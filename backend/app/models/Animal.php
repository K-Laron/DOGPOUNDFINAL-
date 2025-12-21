<?php
/**
 * Animal Model
 * Handles all animal-related database operations
 * 
 * @package AnimalShelter
 */

class Animal {
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * @var string Table name
     */
    private $table = 'Animals';
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Find animal by ID
     * 
     * @param int $id Animal ID
     * @return array|false Animal data or false
     */
    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE AnimalID = :id AND Is_Deleted = FALSE
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Find animal with all related data
     * 
     * @param int $id Animal ID
     * @return array|false Animal data with relations or false
     */
    public function findWithRelations($id) {
        $animal = $this->find($id);
        
        if (!$animal) {
            return false;
        }
        
        // Get impound record
        $stmt = $this->db->prepare("SELECT * FROM Impound_Records WHERE AnimalID = :id");
        $stmt->execute(['id' => $id]);
        $animal['impound_record'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        
        // Get medical records count
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM Medical_Records WHERE AnimalID = :id");
        $stmt->execute(['id' => $id]);
        $animal['medical_records_count'] = (int)$stmt->fetch()['count'];
        
        // Get adoption requests count
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM Adoption_Requests WHERE AnimalID = :id");
        $stmt->execute(['id' => $id]);
        $animal['adoption_requests_count'] = (int)$stmt->fetch()['count'];
        
        // Get feeding records count
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM Feeding_Records WHERE AnimalID = :id");
        $stmt->execute(['id' => $id]);
        $animal['feeding_records_count'] = (int)$stmt->fetch()['count'];
        
        return $animal;
    }
    
    /**
     * Get all animals with pagination
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param array $filters Filter options
     * @return array ['data' => [], 'total' => int]
     */
    public function paginate($page = 1, $perPage = 20, $filters = []) {
        $where = ["Is_Deleted = FALSE"];
        $params = [];
        
        // Filter by type
        if (!empty($filters['type'])) {
            $where[] = "Type = :type";
            $params['type'] = $filters['type'];
        }
        
        // Filter by status
        if (!empty($filters['status'])) {
            $where[] = "Current_Status = :status";
            $params['status'] = $filters['status'];
        }
        
        // Filter by gender
        if (!empty($filters['gender'])) {
            $where[] = "Gender = :gender";
            $params['gender'] = $filters['gender'];
        }
        
        // Filter by intake status
        if (!empty($filters['intake_status'])) {
            $where[] = "Intake_Status = :intake_status";
            $params['intake_status'] = $filters['intake_status'];
        }
        
        // Filter by age group
        if (!empty($filters['age_group'])) {
            $where[] = "Age_Group = :age_group";
            $params['age_group'] = $filters['age_group'];
        }
        
        // Search
        if (!empty($filters['search'])) {
            $where[] = "(Name LIKE :search OR Breed LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        // Date range for intake
        if (!empty($filters['intake_from'])) {
            $where[] = "DATE(Intake_Date) >= :intake_from";
            $params['intake_from'] = $filters['intake_from'];
        }
        
        if (!empty($filters['intake_to'])) {
            $where[] = "DATE(Intake_Date) <= :intake_to";
            $params['intake_to'] = $filters['intake_to'];
        }
        
        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM {$this->table} WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];
        
        // Determine sort order
        $orderBy = "Intake_Date DESC";
        if (!empty($filters['sort'])) {
            $allowedSorts = [
                'name_asc' => 'Name ASC',
                'name_desc' => 'Name DESC',
                'date_asc' => 'Intake_Date ASC',
                'date_desc' => 'Intake_Date DESC',
                'type' => 'Type ASC, Name ASC'
            ];
            $orderBy = $allowedSorts[$filters['sort']] ?? $orderBy;
        }
        
        // Get data
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE {$whereClause}
            ORDER BY {$orderBy}
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
     * Get available animals for adoption
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param array $filters Filter options
     * @return array ['data' => [], 'total' => int]
     */
    public function getAvailable($page = 1, $perPage = 20, $filters = []) {
        $filters['status'] = 'Available';
        return $this->paginate($page, $perPage, $filters);
    }
    
    /**
     * Create new animal
     * 
     * @param array $data Animal data
     * @return int|false Animal ID or false
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (
                Name, 
                Type, 
                Breed, 
                Gender, 
                Age_Group, 
                Weight, 
                Intake_Date, 
                Intake_Status, 
                Current_Status, 
                Image_URL, 
                Is_Deleted,
                Created_At,
                Updated_At
            ) VALUES (
                :name, 
                :type, 
                :breed, 
                :gender, 
                :age_group, 
                :weight, 
                :intake_date, 
                :intake_status, 
                :current_status, 
                :image_url, 
                FALSE,
                NOW(),
                NOW()
            )
        ");
        
        $result = $stmt->execute([
            'name' => trim($data['name']),
            'type' => $data['type'],
            'breed' => $data['breed'] ?? null,
            'gender' => $data['gender'] ?? 'Unknown',
            'age_group' => $data['age_group'] ?? null,
            'weight' => $data['weight'] ?? null,
            'intake_date' => $data['intake_date'] ?? date('Y-m-d H:i:s'),
            'intake_status' => $data['intake_status'],
            'current_status' => $data['current_status'] ?? 'Available',
            'image_url' => $data['image_url'] ?? null
        ]);
        
        return $result ? (int)$this->db->lastInsertId() : false;
    }
    
    /**
     * Update animal
     * 
     * @param int $id Animal ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
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
            "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE AnimalID = :id"
        );
        
        return $stmt->execute($params);
    }
    
    /**
     * Soft delete animal
     * 
     * @param int $id Animal ID
     * @return bool Success status
     */
    public function delete($id) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET Is_Deleted = TRUE, Updated_At = NOW()
            WHERE AnimalID = :id
        ");
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Update animal status
     * 
     * @param int $id Animal ID
     * @param string $status New status
     * @return bool Success status
     */
    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET Current_Status = :status, Updated_At = NOW()
            WHERE AnimalID = :id
        ");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }
    
    /**
     * Update animal image
     * 
     * @param int $id Animal ID
     * @param string $imagePath Image path
     * @return bool Success status
     */
    public function updateImage($id, $imagePath) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET Image_URL = :image, Updated_At = NOW()
            WHERE AnimalID = :id
        ");
        return $stmt->execute(['image' => $imagePath, 'id' => $id]);
    }
    
    /**
     * Get animal statistics
     * 
     * @return array Statistics
     */
    public function getStatistics() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Current_Status = 'Available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN Current_Status = 'Adopted' THEN 1 ELSE 0 END) as adopted,
                SUM(CASE WHEN Current_Status = 'Deceased' THEN 1 ELSE 0 END) as deceased,
                SUM(CASE WHEN Current_Status = 'In Treatment' THEN 1 ELSE 0 END) as in_treatment,
                SUM(CASE WHEN Current_Status = 'Quarantine' THEN 1 ELSE 0 END) as quarantine,
                SUM(CASE WHEN Current_Status = 'Reclaimed' THEN 1 ELSE 0 END) as reclaimed,
                SUM(CASE WHEN Type = 'Dog' THEN 1 ELSE 0 END) as dogs,
                SUM(CASE WHEN Type = 'Cat' THEN 1 ELSE 0 END) as cats,
                SUM(CASE WHEN Type = 'Other' THEN 1 ELSE 0 END) as others,
                SUM(CASE WHEN Intake_Status = 'Stray' THEN 1 ELSE 0 END) as strays,
                SUM(CASE WHEN Intake_Status = 'Surrendered' THEN 1 ELSE 0 END) as surrendered,
                SUM(CASE WHEN Intake_Status = 'Confiscated' THEN 1 ELSE 0 END) as confiscated
            FROM {$this->table}
            WHERE Is_Deleted = FALSE
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get monthly intake statistics
     * 
     * @param int $months Number of months to look back
     * @return array Monthly statistics
     */
    public function getMonthlyIntakeStats($months = 12) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(Intake_Date, '%Y-%m') as month,
                COUNT(*) as total,
                SUM(CASE WHEN Type = 'Dog' THEN 1 ELSE 0 END) as dogs,
                SUM(CASE WHEN Type = 'Cat' THEN 1 ELSE 0 END) as cats
            FROM {$this->table}
            WHERE Is_Deleted = FALSE
            AND Intake_Date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY DATE_FORMAT(Intake_Date, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute(['months' => $months]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get animals by status
     * 
     * @param string $status Status to filter
     * @return array Animals
     */
    public function getByStatus($status) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE Current_Status = :status AND Is_Deleted = FALSE
            ORDER BY Name ASC
        ");
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Count animals by status
     * 
     * @return array Status counts
     */
    public function countByStatus() {
        $stmt = $this->db->prepare("
            SELECT Current_Status as status, COUNT(*) as count
            FROM {$this->table}
            WHERE Is_Deleted = FALSE
            GROUP BY Current_Status
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search animals
     * 
     * @param string $query Search query
     * @param int $limit Result limit
     * @return array Animals
     */
    public function search($query, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT AnimalID, Name, Type, Breed, Current_Status, Image_URL
            FROM {$this->table}
            WHERE Is_Deleted = FALSE
            AND (Name LIKE :query OR Breed LIKE :query)
            ORDER BY Name ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':query', '%' . $query . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}