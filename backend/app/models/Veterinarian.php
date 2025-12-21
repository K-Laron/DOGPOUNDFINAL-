<?php
/**
 * Veterinarian Model
 * Handles veterinarian-related database operations
 * 
 * @package AnimalShelter
 */

class Veterinarian {
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * @var string Table name
     */
    private $table = 'Veterinarians';
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Find veterinarian by ID
     * 
     * @param int $id Vet ID
     * @return array|false Vet data or false
     */
    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT v.*, 
                   u.UserID,
                   u.FirstName, 
                   u.LastName, 
                   u.Email, 
                   u.Contact_Number, 
                   u.Account_Status,
                   u.Created_At as User_Created_At
            FROM {$this->table} v
            JOIN Users u ON v.UserID = u.UserID
            WHERE v.VetID = :id AND u.Is_Deleted = FALSE
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Find veterinarian by user ID
     * 
     * @param int $userId User ID
     * @return array|false Vet data or false
     */
    public function findByUserId($userId) {
        $stmt = $this->db->prepare("
            SELECT v.*, 
                   u.FirstName, 
                   u.LastName, 
                   u.Email, 
                   u.Contact_Number, 
                   u.Account_Status
            FROM {$this->table} v
            JOIN Users u ON v.UserID = u.UserID
            WHERE v.UserID = :user_id AND u.Is_Deleted = FALSE
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Find veterinarian by license number
     * 
     * @param string $licenseNumber License number
     * @return array|false Vet data or false
     */
    public function findByLicense($licenseNumber) {
        $stmt = $this->db->prepare("
            SELECT v.*, 
                   u.FirstName, 
                   u.LastName, 
                   u.Email
            FROM {$this->table} v
            JOIN Users u ON v.UserID = u.UserID
            WHERE v.License_Number = :license AND u.Is_Deleted = FALSE
        ");
        $stmt->execute(['license' => $licenseNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all veterinarians
     * 
     * @param bool $activeOnly Get only active vets
     * @return array Veterinarians
     */
    public function all($activeOnly = true) {
        $where = "u.Is_Deleted = FALSE";
        if ($activeOnly) {
            $where .= " AND u.Account_Status = 'Active'";
        }
        
        $stmt = $this->db->prepare("
            SELECT v.*, 
                   u.FirstName, 
                   u.LastName, 
                   u.Email, 
                   u.Contact_Number, 
                   u.Account_Status
            FROM {$this->table} v
            JOIN Users u ON v.UserID = u.UserID
            WHERE {$where}
            ORDER BY u.LastName, u.FirstName
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all veterinarians with record counts
     * 
     * @return array Vets with counts
     */
    public function allWithRecordCounts() {
        $stmt = $this->db->prepare("
            SELECT v.*, 
                   u.FirstName, 
                   u.LastName, 
                   u.Email, 
                   u.Contact_Number, 
                   u.Account_Status,
                   COUNT(mr.RecordID) as total_records,
                   COUNT(DISTINCT mr.AnimalID) as unique_animals,
                   MAX(mr.Date_Performed) as last_record_date
            FROM {$this->table} v
            JOIN Users u ON v.UserID = u.UserID
            LEFT JOIN Medical_Records mr ON v.VetID = mr.VetID
            WHERE u.Is_Deleted = FALSE
            GROUP BY v.VetID
            ORDER BY u.LastName, u.FirstName
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get veterinarians with pagination
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param array $filters Filter options
     * @return array ['data' => [], 'total' => int]
     */
    public function paginate($page = 1, $perPage = 20, $filters = []) {
        $where = ["u.Is_Deleted = FALSE"];
        $params = [];
        
        // Filter by status
        if (!empty($filters['status'])) {
            $where[] = "u.Account_Status = :status";
            $params['status'] = $filters['status'];
        }
        
        // Filter by specialization
        if (!empty($filters['specialization'])) {
            $where[] = "v.Specialization LIKE :specialization";
            $params['specialization'] = '%' . $filters['specialization'] . '%';
        }
        
        // Search
        if (!empty($filters['search'])) {
            $where[] = "(u.FirstName LIKE :search OR u.LastName LIKE :search OR u.Email LIKE :search OR v.License_Number LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM {$this->table} v
            JOIN Users u ON v.UserID = u.UserID
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];
        
        // Get data
        $stmt = $this->db->prepare("
            SELECT v.*, 
                   u.FirstName, 
                   u.LastName, 
                   u.Email, 
                   u.Contact_Number, 
                   u.Account_Status
            FROM {$this->table} v
            JOIN Users u ON v.UserID = u.UserID
            WHERE {$whereClause}
            ORDER BY u.LastName, u.FirstName
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
     * Create veterinarian profile
     * 
     * @param array $data Vet data
     * @return int|false Vet ID or false
     */
    public function create($data) {
        // Check if user already has vet profile
        if ($this->findByUserId($data['user_id'])) {
            return false;
        }
        
        // Check if license number already exists
        if ($this->licenseExists($data['license_number'])) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (
                UserID,
                License_Number,
                Specialization,
                Years_Experience,
                Created_At,
                Updated_At
            ) VALUES (
                :user_id,
                :license,
                :specialization,
                :experience,
                NOW(),
                NOW()
            )
        ");
        
        $result = $stmt->execute([
            'user_id' => $data['user_id'],
            'license' => trim($data['license_number']),
            'specialization' => $data['specialization'] ?? null,
            'experience' => (int)($data['years_experience'] ?? 0)
        ]);
        
        return $result ? (int)$this->db->lastInsertId() : false;
    }
    
    /**
     * Update veterinarian profile
     * 
     * @param int $id Vet ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = [
            'License_Number' => 'license_number',
            'Specialization' => 'specialization',
            'Years_Experience' => 'years_experience'
        ];
        
        foreach ($allowedFields as $dbField => $dataKey) {
            if (array_key_exists($dataKey, $data)) {
                // Check license uniqueness if being updated
                if ($dataKey === 'license_number') {
                    if ($this->licenseExists($data[$dataKey], $id)) {
                        return false;
                    }
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
            "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE VetID = :id"
        );
        
        return $stmt->execute($params);
    }
    
    /**
     * Update by user ID
     * 
     * @param int $userId User ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function updateByUserId($userId, $data) {
        $vet = $this->findByUserId($userId);
        if (!$vet) {
            return false;
        }
        return $this->update($vet['VetID'], $data);
    }
    
    /**
     * Delete veterinarian profile
     * 
     * @param int $id Vet ID
     * @return bool Success status
     */
    public function delete($id) {
        // Check for existing medical records
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM Medical_Records WHERE VetID = :id");
        $stmt->execute(['id' => $id]);
        
        if ($stmt->fetch()['count'] > 0) {
            return false; // Cannot delete vet with records
        }
        
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE VetID = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Get medical records for veterinarian
     * 
     * @param int $vetId Vet ID
     * @param int $limit Limit
     * @return array Records
     */
    public function getMedicalRecords($vetId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   a.Name as Animal_Name, 
                   a.Type as Animal_Type,
                   a.Breed as Animal_Breed
            FROM Medical_Records mr
            JOIN Animals a ON mr.AnimalID = a.AnimalID
            WHERE mr.VetID = :vet_id
            ORDER BY mr.Date_Performed DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':vet_id', $vetId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get statistics for veterinarian
     * 
     * @param int $vetId Vet ID
     * @return array Statistics
     */
    public function getStatistics($vetId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_records,
                COUNT(DISTINCT AnimalID) as unique_animals,
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
                MIN(Date_Performed) as first_record_date,
                MAX(Date_Performed) as last_record_date
            FROM Medical_Records
            WHERE VetID = :vet_id
        ");
        $stmt->execute(['vet_id' => $vetId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get monthly activity for veterinarian
     * 
     * @param int $vetId Vet ID
     * @param int $months Number of months
     * @return array Monthly activity
     */
    public function getMonthlyActivity($vetId, $months = 12) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(Date_Performed, '%Y-%m') as month,
                COUNT(*) as record_count,
                COUNT(DISTINCT AnimalID) as unique_animals
            FROM Medical_Records
            WHERE VetID = :vet_id
            AND Date_Performed >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY DATE_FORMAT(Date_Performed, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute(['vet_id' => $vetId, 'months' => $months]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get upcoming treatments assigned to veterinarian
     * 
     * @param int $vetId Vet ID
     * @param int $days Days ahead
     * @return array Upcoming treatments
     */
    public function getUpcomingTreatments($vetId, $days = 7) {
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   a.Name as Animal_Name, 
                   a.Type as Animal_Type,
                   a.Current_Status,
                   DATEDIFF(mr.Next_Due_Date, CURDATE()) as Days_Until_Due
            FROM Medical_Records mr
            JOIN Animals a ON mr.AnimalID = a.AnimalID
            WHERE mr.VetID = :vet_id
            AND mr.Next_Due_Date IS NOT NULL
            AND mr.Next_Due_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
            AND a.Is_Deleted = FALSE
            AND a.Current_Status NOT IN ('Adopted', 'Deceased', 'Reclaimed')
            ORDER BY mr.Next_Due_Date ASC
        ");
        $stmt->execute(['vet_id' => $vetId, 'days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if license number exists
     * 
     * @param string $licenseNumber License number
     * @param int|null $excludeId Vet ID to exclude
     * @return bool
     */
    public function licenseExists($licenseNumber, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE License_Number = :license";
        $params = ['license' => trim($licenseNumber)];
        
        if ($excludeId) {
            $sql .= " AND VetID != :id";
            $params['id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetch()['count'] > 0;
    }
    
    /**
     * Get all specializations in use
     * 
     * @return array Specializations
     */
    public function getSpecializations() {
        $stmt = $this->db->prepare("
            SELECT DISTINCT Specialization
            FROM {$this->table}
            WHERE Specialization IS NOT NULL AND Specialization != ''
            ORDER BY Specialization
        ");
        $stmt->execute();
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Specialization');
    }
    
    /**
     * Get veterinarians by specialization
     * 
     * @param string $specialization Specialization
     * @return array Veterinarians
     */
    public function getBySpecialization($specialization) {
        $stmt = $this->db->prepare("
            SELECT v.*, 
                   u.FirstName, 
                   u.LastName, 
                   u.Email, 
                   u.Contact_Number
            FROM {$this->table} v
            JOIN Users u ON v.UserID = u.UserID
            WHERE v.Specialization LIKE :specialization
            AND u.Is_Deleted = FALSE
            AND u.Account_Status = 'Active'
            ORDER BY u.LastName, u.FirstName
        ");
        $stmt->execute(['specialization' => '%' . $specialization . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get overall veterinarian statistics
     * 
     * @return array Overall statistics
     */
    public function getOverallStatistics() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_veterinarians,
                COUNT(CASE WHEN u.Account_Status = 'Active' THEN 1 END) as active_count,
                AVG(v.Years_Experience) as avg_experience,
                MAX(v.Years_Experience) as max_experience,
                COUNT(DISTINCT v.Specialization) as specialization_count
            FROM {$this->table} v
            JOIN Users u ON v.UserID = u.UserID
            WHERE u.Is_Deleted = FALSE
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}