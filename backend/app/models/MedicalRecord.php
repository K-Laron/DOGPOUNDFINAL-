<?php
/**
 * Medical Record Model
 * Handles medical record database operations
 * 
 * @package AnimalShelter
 */

class MedicalRecord {
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * @var string Table name
     */
    private $table = 'Medical_Records';
    
    /**
     * @var array Valid diagnosis types
     */
    private $validDiagnosisTypes = [
        'Checkup', 
        'Vaccination', 
        'Surgery', 
        'Treatment', 
        'Emergency', 
        'Deworming', 
        'Spay/Neuter'
    ];
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Find medical record by ID
     * 
     * @param int $id Record ID
     * @return array|false Record data or false
     */
    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   a.Name as Animal_Name, 
                   a.Type as Animal_Type,
                   a.Breed as Animal_Breed,
                   a.Current_Status as Animal_Status,
                   u.FirstName as Vet_FirstName, 
                   u.LastName as Vet_LastName,
                   u.Email as Vet_Email,
                   v.License_Number, 
                   v.Specialization
            FROM {$this->table} mr
            JOIN Animals a ON mr.AnimalID = a.AnimalID
            JOIN Veterinarians v ON mr.VetID = v.VetID
            JOIN Users u ON v.UserID = u.UserID
            WHERE mr.RecordID = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get records by animal ID with pagination
     * 
     * @param int $animalId Animal ID
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array ['data' => [], 'total' => int]
     */
    public function getByAnimal($animalId, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM {$this->table} 
            WHERE AnimalID = :animal_id
        ");
        $countStmt->execute(['animal_id' => $animalId]);
        $total = (int)$countStmt->fetch()['total'];
        
        // Get data
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   u.FirstName as Vet_FirstName, 
                   u.LastName as Vet_LastName
            FROM {$this->table} mr
            JOIN Veterinarians v ON mr.VetID = v.VetID
            JOIN Users u ON v.UserID = u.UserID
            WHERE mr.AnimalID = :animal_id
            ORDER BY mr.Date_Performed DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':animal_id', $animalId);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total
        ];
    }
    
    /**
     * Get records by veterinarian with pagination
     * 
     * @param int $vetId Vet ID
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array ['data' => [], 'total' => int]
     */
    public function getByVeterinarian($vetId, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM {$this->table} 
            WHERE VetID = :vet_id
        ");
        $countStmt->execute(['vet_id' => $vetId]);
        $total = (int)$countStmt->fetch()['total'];
        
        // Get data
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   a.Name as Animal_Name, 
                   a.Type as Animal_Type
            FROM {$this->table} mr
            JOIN Animals a ON mr.AnimalID = a.AnimalID
            WHERE mr.VetID = :vet_id
            ORDER BY mr.Date_Performed DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':vet_id', $vetId);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total
        ];
    }
    
    /**
     * Get all records with pagination and filters
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param array $filters Filter options
     * @return array ['data' => [], 'total' => int]
     */
    public function paginate($page = 1, $perPage = 20, $filters = []) {
        $where = ["1=1"];
        $params = [];
        
        // Filter by animal
        if (!empty($filters['animal_id'])) {
            $where[] = "mr.AnimalID = :animal_id";
            $params['animal_id'] = $filters['animal_id'];
        }
        
        // Filter by veterinarian
        if (!empty($filters['vet_id'])) {
            $where[] = "mr.VetID = :vet_id";
            $params['vet_id'] = $filters['vet_id'];
        }
        
        // Filter by diagnosis type
        if (!empty($filters['diagnosis_type'])) {
            $where[] = "mr.Diagnosis_Type = :diagnosis_type";
            $params['diagnosis_type'] = $filters['diagnosis_type'];
        }
        
        // Date range for date performed
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(mr.Date_Performed) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(mr.Date_Performed) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        // Filter by animal type
        if (!empty($filters['animal_type'])) {
            $where[] = "a.Type = :animal_type";
            $params['animal_type'] = $filters['animal_type'];
        }
        
        // Search
        if (!empty($filters['search'])) {
            $where[] = "(a.Name LIKE :search OR mr.Treatment_Notes LIKE :search OR mr.Vaccine_Name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM {$this->table} mr
            JOIN Animals a ON mr.AnimalID = a.AnimalID
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];
        
        // Get data
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   a.Name as Animal_Name, 
                   a.Type as Animal_Type,
                   u.FirstName as Vet_FirstName, 
                   u.LastName as Vet_LastName
            FROM {$this->table} mr
            JOIN Animals a ON mr.AnimalID = a.AnimalID
            JOIN Veterinarians v ON mr.VetID = v.VetID
            JOIN Users u ON v.UserID = u.UserID
            WHERE {$whereClause}
            ORDER BY mr.Date_Performed DESC
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
     * Get upcoming treatments/vaccinations
     * 
     * @param int $days Number of days ahead to look
     * @return array Upcoming records
     */
    public function getUpcoming($days = 7) {
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   a.Name as Animal_Name, 
                   a.Type as Animal_Type, 
                   a.Current_Status,
                   a.Image_URL as Animal_Image,
                   u.FirstName as Vet_FirstName, 
                   u.LastName as Vet_LastName,
                   DATEDIFF(mr.Next_Due_Date, CURDATE()) as Days_Until_Due
            FROM {$this->table} mr
            JOIN Animals a ON mr.AnimalID = a.AnimalID
            JOIN Veterinarians v ON mr.VetID = v.VetID
            JOIN Users u ON v.UserID = u.UserID
            WHERE mr.Next_Due_Date IS NOT NULL 
            AND mr.Next_Due_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
            AND a.Is_Deleted = FALSE 
            AND a.Current_Status NOT IN ('Adopted', 'Deceased', 'Reclaimed')
            ORDER BY mr.Next_Due_Date ASC
        ");
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get overdue treatments
     * 
     * @return array Overdue records
     */
    public function getOverdue() {
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   a.Name as Animal_Name, 
                   a.Type as Animal_Type,
                   a.Current_Status,
                   u.FirstName as Vet_FirstName, 
                   u.LastName as Vet_LastName,
                   DATEDIFF(CURDATE(), mr.Next_Due_Date) as Days_Overdue
            FROM {$this->table} mr
            JOIN Animals a ON mr.AnimalID = a.AnimalID
            JOIN Veterinarians v ON mr.VetID = v.VetID
            JOIN Users u ON v.UserID = u.UserID
            WHERE mr.Next_Due_Date IS NOT NULL 
            AND mr.Next_Due_Date < CURDATE()
            AND a.Is_Deleted = FALSE 
            AND a.Current_Status NOT IN ('Adopted', 'Deceased', 'Reclaimed')
            ORDER BY mr.Next_Due_Date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get today's records
     * 
     * @return array Today's records
     */
    public function getToday() {
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   a.Name as Animal_Name, 
                   a.Type as Animal_Type,
                   u.FirstName as Vet_FirstName, 
                   u.LastName as Vet_LastName
            FROM {$this->table} mr
            JOIN Animals a ON mr.AnimalID = a.AnimalID
            JOIN Veterinarians v ON mr.VetID = v.VetID
            JOIN Users u ON v.UserID = u.UserID
            WHERE DATE(mr.Date_Performed) = CURDATE()
            ORDER BY mr.Date_Performed DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create medical record
     * 
     * @param array $data Record data
     * @return int|false Record ID or false
     */
    public function create($data) {
        // Validate diagnosis type
        if (!in_array($data['diagnosis_type'], $this->validDiagnosisTypes)) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (
                AnimalID,
                VetID,
                Date_Performed,
                Diagnosis_Type,
                Vaccine_Name,
                Treatment_Notes,
                Next_Due_Date,
                Created_At,
                Updated_At
            ) VALUES (
                :animal_id,
                :vet_id,
                :date_performed,
                :diagnosis_type,
                :vaccine_name,
                :treatment_notes,
                :next_due_date,
                NOW(),
                NOW()
            )
        ");
        
        $result = $stmt->execute([
            'animal_id' => $data['animal_id'],
            'vet_id' => $data['vet_id'],
            'date_performed' => $data['date_performed'] ?? date('Y-m-d H:i:s'),
            'diagnosis_type' => $data['diagnosis_type'],
            'vaccine_name' => $data['vaccine_name'] ?? null,
            'treatment_notes' => $data['treatment_notes'],
            'next_due_date' => $data['next_due_date'] ?? null
        ]);
        
        return $result ? (int)$this->db->lastInsertId() : false;
    }
    
    /**
     * Update medical record
     * 
     * @param int $id Record ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = [
            'Diagnosis_Type' => 'diagnosis_type',
            'Vaccine_Name' => 'vaccine_name',
            'Treatment_Notes' => 'treatment_notes',
            'Next_Due_Date' => 'next_due_date',
            'Date_Performed' => 'date_performed'
        ];
        
        foreach ($allowedFields as $dbField => $dataKey) {
            if (array_key_exists($dataKey, $data)) {
                // Validate diagnosis type if being updated
                if ($dataKey === 'diagnosis_type' && !in_array($data[$dataKey], $this->validDiagnosisTypes)) {
                    return false;
                }
                $fields[] = "{$dbField} = :{$dataKey}";
                $params[$dataKey] = $data[$dataKey];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $fields[] = "Updated_At = NOW()";
        
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE RecordID = :id"
        );
        
        return $stmt->execute($params);
    }
    
    /**
     * Delete medical record
     * 
     * @param int $id Record ID
     * @return bool Success status
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE RecordID = :id");
        return $stmt->execute(['id' => $id]);
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
                SUM(CASE WHEN Diagnosis_Type = 'Checkup' THEN 1 ELSE 0 END) as checkups,
                SUM(CASE WHEN Diagnosis_Type = 'Vaccination' THEN 1 ELSE 0 END) as vaccinations,
                SUM(CASE WHEN Diagnosis_Type = 'Surgery' THEN 1 ELSE 0 END) as surgeries,
                SUM(CASE WHEN Diagnosis_Type = 'Treatment' THEN 1 ELSE 0 END) as treatments,
                SUM(CASE WHEN Diagnosis_Type = 'Emergency' THEN 1 ELSE 0 END) as emergencies,
                SUM(CASE WHEN Diagnosis_Type = 'Deworming' THEN 1 ELSE 0 END) as dewormings,
                SUM(CASE WHEN Diagnosis_Type = 'Spay/Neuter' THEN 1 ELSE 0 END) as spay_neuters,
                SUM(CASE WHEN DATE(Date_Performed) = CURDATE() THEN 1 ELSE 0 END) as today_count,
                SUM(CASE WHEN YEARWEEK(Date_Performed) = YEARWEEK(CURDATE()) THEN 1 ELSE 0 END) as this_week,
                SUM(CASE WHEN MONTH(Date_Performed) = MONTH(CURDATE()) AND YEAR(Date_Performed) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as this_month,
                COUNT(DISTINCT AnimalID) as unique_animals,
                (SELECT COUNT(*) FROM {$this->table} WHERE Next_Due_Date IS NOT NULL AND Next_Due_Date < CURDATE()) as overdue_count,
                (SELECT COUNT(*) FROM {$this->table} WHERE Next_Due_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) as upcoming_week
            FROM {$this->table}
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get statistics by diagnosis type
     * 
     * @return array Statistics by type
     */
    public function getStatsByType() {
        $stmt = $this->db->prepare("
            SELECT 
                Diagnosis_Type as type,
                COUNT(*) as count,
                COUNT(DISTINCT AnimalID) as unique_animals,
                MAX(Date_Performed) as last_performed
            FROM {$this->table}
            GROUP BY Diagnosis_Type
            ORDER BY count DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get monthly statistics
     * 
     * @param int $months Number of months
     * @return array Monthly statistics
     */
    public function getMonthlyStats($months = 12) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(Date_Performed, '%Y-%m') as month,
                COUNT(*) as total,
                SUM(CASE WHEN Diagnosis_Type = 'Vaccination' THEN 1 ELSE 0 END) as vaccinations,
                SUM(CASE WHEN Diagnosis_Type = 'Surgery' THEN 1 ELSE 0 END) as surgeries,
                COUNT(DISTINCT AnimalID) as unique_animals,
                COUNT(DISTINCT VetID) as active_vets
            FROM {$this->table}
            WHERE Date_Performed >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY DATE_FORMAT(Date_Performed, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute(['months' => $months]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get vaccination history for animal
     * 
     * @param int $animalId Animal ID
     * @return array Vaccination records
     */
    public function getVaccinationHistory($animalId) {
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   u.FirstName as Vet_FirstName, 
                   u.LastName as Vet_LastName
            FROM {$this->table} mr
            JOIN Veterinarians v ON mr.VetID = v.VetID
            JOIN Users u ON v.UserID = u.UserID
            WHERE mr.AnimalID = :animal_id 
            AND mr.Diagnosis_Type = 'Vaccination'
            ORDER BY mr.Date_Performed DESC
        ");
        $stmt->execute(['animal_id' => $animalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get latest record for animal
     * 
     * @param int $animalId Animal ID
     * @return array|false Latest record or false
     */
    public function getLatestForAnimal($animalId) {
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   u.FirstName as Vet_FirstName, 
                   u.LastName as Vet_LastName
            FROM {$this->table} mr
            JOIN Veterinarians v ON mr.VetID = v.VetID
            JOIN Users u ON v.UserID = u.UserID
            WHERE mr.AnimalID = :animal_id
            ORDER BY mr.Date_Performed DESC
            LIMIT 1
        ");
        $stmt->execute(['animal_id' => $animalId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get valid diagnosis types
     * 
     * @return array Diagnosis types
     */
    public function getDiagnosisTypes() {
        return $this->validDiagnosisTypes;
    }
    
    /**
     * Validate diagnosis type
     * 
     * @param string $type Type to validate
     * @return bool
     */
    public function isValidDiagnosisType($type) {
        return in_array($type, $this->validDiagnosisTypes);
    }
}