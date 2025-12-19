<?php
/**
 * Medical Record Model
 */

class MedicalRecord {
    private $conn;
    private $table = "Medical_Records";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create new medical record
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (AnimalID, VetID, Date_Performed, Diagnosis_Type, Vaccine_Name, 
                   Treatment_Notes, Next_Due_Date) 
                  VALUES (:animal_id, :vet_id, :date_performed, :diagnosis_type, 
                          :vaccine_name, :treatment_notes, :next_due_date)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':animal_id', $data['animal_id']);
        $stmt->bindParam(':vet_id', $data['vet_id']);
        $stmt->bindValue(':date_performed', $data['date_performed'] ?? date('Y-m-d H:i:s'));
        $stmt->bindParam(':diagnosis_type', $data['diagnosis_type']);
        $stmt->bindParam(':vaccine_name', $data['vaccine_name']);
        $stmt->bindParam(':treatment_notes', $data['treatment_notes']);
        $stmt->bindParam(':next_due_date', $data['next_due_date']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    /**
     * Get record by ID
     */
    public function getById($id) {
        $query = "SELECT mr.*, 
                         a.Name as Animal_Name, a.Type as Animal_Type,
                         u.FirstName as Vet_FirstName, u.LastName as Vet_LastName
                  FROM " . $this->table . " mr
                  JOIN Animals a ON mr.AnimalID = a.AnimalID
                  JOIN Veterinarians v ON mr.VetID = v.VetID
                  JOIN Users u ON v.UserID = u.UserID
                  WHERE mr.RecordID = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * Get all records for an animal
     */
    public function getByAnimal($animalId, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE AnimalID = :animal_id";
        $countStmt = $this->conn->prepare($countQuery);
        $countStmt->bindParam(':animal_id', $animalId);
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];

        // Get data
        $query = "SELECT mr.*, u.FirstName as Vet_FirstName, u.LastName as Vet_LastName
                  FROM " . $this->table . " mr
                  JOIN Veterinarians v ON mr.VetID = v.VetID
                  JOIN Users u ON v.UserID = u.UserID
                  WHERE mr.AnimalID = :animal_id
                  ORDER BY mr.Date_Performed DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':animal_id', $animalId);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total
        ];
    }

    /**
     * Get upcoming vaccinations/treatments
     */
    public function getUpcoming($days = 7) {
        $query = "SELECT mr.*, a.Name as Animal_Name, a.Type as Animal_Type,
                         u.FirstName as Vet_FirstName, u.LastName as Vet_LastName
                  FROM " . $this->table . " mr
                  JOIN Animals a ON mr.AnimalID = a.AnimalID
                  JOIN Veterinarians v ON mr.VetID = v.VetID
                  JOIN Users u ON v.UserID = u.UserID
                  WHERE mr.Next_Due_Date IS NOT NULL 
                  AND mr.Next_Due_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                  AND a.Is_Deleted = FALSE AND a.Current_Status NOT IN ('Adopted', 'Deceased', 'Reclaimed')
                  ORDER BY mr.Next_Due_Date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Update medical record
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'Diagnosis_Type' => 'diagnosis_type',
            'Vaccine_Name' => 'vaccine_name',
            'Treatment_Notes' => 'treatment_notes',
            'Next_Due_Date' => 'next_due_date'
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

        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE RecordID = :id";
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute($params);
    }

    /**
     * Delete medical record
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE RecordID = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
}