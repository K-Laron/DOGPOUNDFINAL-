<?php
/**
 * Impound Record Model
 * Handles impound record database operations
 * 
 * @package AnimalShelter
 */

class ImpoundRecord {
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * @var string Table name
     */
    private $table = 'Impound_Records';
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Find impound record by ID
     * 
     * @param int $id Impound ID
     * @return array|false Record data or false
     */
    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT ir.*, a.Name as Animal_Name, a.Type as Animal_Type
            FROM {$this->table} ir
            JOIN Animals a ON ir.AnimalID = a.AnimalID
            WHERE ir.ImpoundID = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Find impound record by animal ID
     * 
     * @param int $animalId Animal ID
     * @return array|false Record data or false
     */
    public function findByAnimal($animalId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE AnimalID = :animal_id");
        $stmt->execute(['animal_id' => $animalId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all impound records with pagination
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param array $filters Filter options
     * @return array ['data' => [], 'total' => int]
     */
    public function paginate($page = 1, $perPage = 20, $filters = []) {
        $where = ["1=1"];
        $params = [];
        
        // Filter by officer
        if (!empty($filters['officer'])) {
            $where[] = "ir.Impounding_Officer LIKE :officer";
            $params['officer'] = '%' . $filters['officer'] . '%';
        }
        
        // Filter by location
        if (!empty($filters['location'])) {
            $where[] = "ir.Location_Found LIKE :location";
            $params['location'] = '%' . $filters['location'] . '%';
        }
        
        // Date range
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(ir.Capture_Date) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(ir.Capture_Date) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        
        // Get total
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM {$this->table} ir 
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];
        
        // Get data
        $stmt = $this->db->prepare("
            SELECT ir.*, a.Name as Animal_Name, a.Type as Animal_Type, a.Current_Status
            FROM {$this->table} ir
            JOIN Animals a ON ir.AnimalID = a.AnimalID
            WHERE {$whereClause}
            ORDER BY ir.Capture_Date DESC
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
     * Create impound record
     * 
     * @param array $data Record data
     * @return int|false Impound ID or false
     */
    public function create($data) {
        // Check if record already exists for this animal
        if ($this->findByAnimal($data['animal_id'])) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (
                AnimalID,
                Capture_Date,
                Location_Found,
                Impounding_Officer,
                Condition_On_Arrival,
                Created_At,
                Updated_At
            ) VALUES (
                :animal_id,
                :capture_date,
                :location,
                :officer,
                :condition,
                NOW(),
                NOW()
            )
        ");
        
        $result = $stmt->execute([
            'animal_id' => $data['animal_id'],
            'capture_date' => $data['capture_date'],
            'location' => trim($data['location_found']),
            'officer' => trim($data['impounding_officer']),
            'condition' => $data['condition_on_arrival'] ?? null
        ]);
        
        return $result ? (int)$this->db->lastInsertId() : false;
    }
    
    /**
     * Update impound record
     * 
     * @param int $id Impound ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = [
            'Capture_Date' => 'capture_date',
            'Location_Found' => 'location_found',
            'Impounding_Officer' => 'impounding_officer',
            'Condition_On_Arrival' => 'condition_on_arrival'
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
            "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE ImpoundID = :id"
        );
        
        return $stmt->execute($params);
    }
    
    /**
     * Update by animal ID
     * 
     * @param int $animalId Animal ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function updateByAnimal($animalId, $data) {
        $record = $this->findByAnimal($animalId);
        if (!$record) {
            return false;
        }
        return $this->update($record['ImpoundID'], $data);
    }
    
    /**
     * Delete impound record
     * 
     * @param int $id Impound ID
     * @return bool Success status
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE ImpoundID = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Get statistics by officer
     * 
     * @return array Officer statistics
     */
    public function getStatsByOfficer() {
        $stmt = $this->db->prepare("
            SELECT 
                Impounding_Officer as officer,
                COUNT(*) as total_captures,
                MAX(Capture_Date) as last_capture
            FROM {$this->table}
            GROUP BY Impounding_Officer
            ORDER BY total_captures DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get statistics by location
     * 
     * @return array Location statistics
     */
    public function getStatsByLocation() {
        $stmt = $this->db->prepare("
            SELECT 
                Location_Found as location,
                COUNT(*) as total_captures
            FROM {$this->table}
            GROUP BY Location_Found
            ORDER BY total_captures DESC
            LIMIT 20
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}