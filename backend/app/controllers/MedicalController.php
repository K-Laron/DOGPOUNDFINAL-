<?php
/**
 * Medical Controller
 * Handles medical records and feeding operations
 * 
 * @package AnimalShelter
 */

require_once APP_PATH . '/controllers/BaseController.php';

class MedicalController extends BaseController {
    
    /**
     * List all medical records
     * GET /medical
     */
    public function index() {
        list($page, $perPage) = $this->getPagination();
        
        $where = ["1=1"];
        $params = [];
        
        if ($this->query('animal_id')) {
            $where[] = "mr.AnimalID = :animal_id";
            $params['animal_id'] = $this->query('animal_id');
        }
        
        if ($this->query('diagnosis_type')) {
            $where[] = "mr.Diagnosis_Type = :diagnosis_type";
            $params['diagnosis_type'] = $this->query('diagnosis_type');
        }
        
        if ($this->query('vet_id')) {
            $where[] = "mr.VetID = :vet_id";
            $params['vet_id'] = $this->query('vet_id');
        }

        // Search functionality
        if ($this->query('search')) {
            $searchTerm = '%' . trim($this->query('search')) . '%';
            $where[] = "(
                a.Name LIKE :search 
                OR a.Type LIKE :search
                OR mr.Diagnosis_Type LIKE :search
                OR mr.Vaccine_Name LIKE :search
                OR mr.Treatment_Notes LIKE :search
                OR u.FirstName LIKE :search
                OR u.LastName LIKE :search
                OR CONCAT(u.FirstName, ' ', u.LastName) LIKE :search
            )";
            $params['search'] = $searchTerm;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM Medical_Records mr
            JOIN Animals a ON mr.AnimalID = a.AnimalID
            JOIN Veterinarians v ON mr.VetID = v.VetID
            JOIN Users u ON v.UserID = u.UserID
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get records
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   a.Name as Animal_Name, a.Type as Animal_Type,
                   u.FirstName as Vet_FirstName, u.LastName as Vet_LastName
            FROM Medical_Records mr
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
        
        Response::paginated($stmt->fetchAll(), $page, $perPage, $total, "Medical records retrieved");
    }
    
    /**
     * Get medical records for specific animal
     * GET /medical/animal/{animalId}
     */
    public function byAnimal($animalId) {
        // Verify animal exists
        $stmt = $this->db->prepare("SELECT AnimalID FROM Animals WHERE AnimalID = :id AND Is_Deleted = FALSE");
        $stmt->execute(['id' => $animalId]);
        
        if (!$stmt->fetch()) {
            Response::notFound("Animal not found");
        }
        
        list($page, $perPage) = $this->getPagination();
        
        // Get total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM Medical_Records WHERE AnimalID = :animal_id");
        $countStmt->execute(['animal_id' => $animalId]);
        $total = $countStmt->fetch()['total'];
        
        // Get records
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("
            SELECT mr.*, u.FirstName as Vet_FirstName, u.LastName as Vet_LastName
            FROM Medical_Records mr
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
        
        Response::paginated($stmt->fetchAll(), $page, $perPage, $total, "Medical records retrieved");
    }
    
    /**
     * Get upcoming treatments/vaccinations
     * GET /medical/upcoming
     */
    public function upcoming() {
        $days = (int)$this->query('days', 7);
        
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   a.Name as Animal_Name, a.Type as Animal_Type, a.Current_Status,
                   u.FirstName as Vet_FirstName, u.LastName as Vet_LastName
            FROM Medical_Records mr
            INNER JOIN (
                SELECT MAX(RecordID) as MaxID
                FROM Medical_Records
                GROUP BY AnimalID, Diagnosis_Type, COALESCE(Vaccine_Name, '')
            ) latest ON mr.RecordID = latest.MaxID
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
        
        Response::success($stmt->fetchAll(), "Upcoming treatments retrieved");
    }
    
    /**
     * Get overdue treatments
     * GET /medical/overdue
     */
    public function overdue() {
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   a.Name as Animal_Name, a.Type as Animal_Type,
                   u.FirstName as Vet_FirstName, u.LastName as Vet_LastName,
                   DATEDIFF(CURDATE(), mr.Next_Due_Date) as Days_Overdue
            FROM Medical_Records mr
            JOIN Animals a ON mr.AnimalID = a.AnimalID
            LEFT JOIN Veterinarians v ON mr.VetID = v.VetID
            LEFT JOIN Users u ON v.UserID = u.UserID
            WHERE mr.Next_Due_Date IS NOT NULL 
            AND mr.Next_Due_Date != '0000-00-00'
            AND mr.Next_Due_Date < CURDATE()
            AND a.Is_Deleted = FALSE 
            ORDER BY mr.Next_Due_Date ASC
        ");
        
        $stmt->execute();
        
        Response::success($stmt->fetchAll(), "Overdue treatments retrieved");
    }

    /**
     * Get medical statistics summary
     * GET /medical/stats/summary
     */
    public function stats() {
        // Total records
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM Medical_Records");
        $stmt->execute();
        $total = $stmt->fetch()['total'];
        
        // This month
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as this_month 
            FROM Medical_Records 
            WHERE MONTH(Date_Performed) = MONTH(CURRENT_DATE) 
            AND YEAR(Date_Performed) = YEAR(CURRENT_DATE)
        ");
        $stmt->execute();
        $thisMonth = $stmt->fetch()['this_month'];
        
        Response::success([
            'total' => $total,
            'this_month' => $thisMonth
        ], "Medical statistics retrieved");
    }
    
    /**
     * Get single medical record
     * GET /medical/{id}
     */
    public function show($id) {
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   a.Name as Animal_Name, a.Type as Animal_Type,
                   u.FirstName as Vet_FirstName, u.LastName as Vet_LastName,
                   v.License_Number, v.Specialization
            FROM Medical_Records mr
            JOIN Animals a ON mr.AnimalID = a.AnimalID
            JOIN Veterinarians v ON mr.VetID = v.VetID
            JOIN Users u ON v.UserID = u.UserID
            WHERE mr.RecordID = :id
        ");
        $stmt->execute(['id' => $id]);
        $record = $stmt->fetch();
        
        if (!$record) {
            Response::notFound("Medical record not found");
        }
        
        Response::success($record);
    }
    
    /**
     * Create medical record
     * POST /medical
     */
    public function store() {
        $this->validate([
            'animal_id' => 'required|integer',
            'diagnosis_type' => 'required|in:Checkup,Vaccination,Surgery,Treatment,Emergency,Deworming,Spay/Neuter',
            'treatment_notes' => 'required'
        ]);
        
        // Verify animal exists
        $stmt = $this->db->prepare("SELECT AnimalID FROM Animals WHERE AnimalID = :id AND Is_Deleted = FALSE");
        $stmt->execute(['id' => $this->input('animal_id')]);
        
        if (!$stmt->fetch()) {
            Response::notFound("Animal not found");
        }
        
        // Get vet ID
        $vetId = $this->input('vet_id');
        
        if (!$vetId) {
            // If user is a veterinarian, use their ID
            if ($this->user['Role_Name'] === 'Veterinarian') {
                $stmt = $this->db->prepare("SELECT VetID FROM Veterinarians WHERE UserID = :user_id");
                $stmt->execute(['user_id' => $this->user['UserID']]);
                $vet = $stmt->fetch();
                
                if (!$vet) {
                    Response::error("Veterinarian profile not found", 400);
                }
                
                $vetId = $vet['VetID'];
            } else {
                Response::error("Veterinarian ID is required", 400);
            }
        }
        
        // Verify vet exists
        $stmt = $this->db->prepare("SELECT VetID FROM Veterinarians WHERE VetID = :id");
        $stmt->execute(['id' => $vetId]);
        
        if (!$stmt->fetch()) {
            Response::error("Veterinarian not found", 400);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO Medical_Records (AnimalID, VetID, Date_Performed, Diagnosis_Type, Vaccine_Name, Treatment_Notes, Next_Due_Date)
            VALUES (:animal_id, :vet_id, :date_performed, :diagnosis_type, :vaccine_name, :treatment_notes, :next_due_date)
        ");
        
        $stmt->execute([
            'animal_id' => $this->input('animal_id'),
            'vet_id' => $vetId,
            'date_performed' => $this->input('date_performed', date('Y-m-d H:i:s')),
            'diagnosis_type' => $this->input('diagnosis_type'),
            'vaccine_name' => $this->input('vaccine_name'),
            'treatment_notes' => $this->input('treatment_notes'),
            'next_due_date' => $this->input('next_due_date')
        ]);
        
        $recordId = $this->db->lastInsertId();
        
        $this->logActivity('CREATE_MEDICAL_RECORD', "Created medical record ID: {$recordId} for animal ID: {$this->input('animal_id')}");
        
        // Get created record
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   a.Name as Animal_Name,
                   u.FirstName as Vet_FirstName, u.LastName as Vet_LastName
            FROM Medical_Records mr
            JOIN Animals a ON mr.AnimalID = a.AnimalID
            JOIN Veterinarians v ON mr.VetID = v.VetID
            JOIN Users u ON v.UserID = u.UserID
            WHERE mr.RecordID = :id
        ");
        $stmt->execute(['id' => $recordId]);
        
        Response::created($stmt->fetch(), "Medical record created");
    }
    
    /**
     * Update medical record
     * PUT /medical/{id}
     */
    public function update($id) {
        $stmt = $this->db->prepare("SELECT RecordID FROM Medical_Records WHERE RecordID = :id");
        $stmt->execute(['id' => $id]);
        
        if (!$stmt->fetch()) {
            Response::notFound("Medical record not found");
        }
        
        $updates = [];
        $params = ['id' => $id];
        
        if ($this->has('diagnosis_type')) {
            $this->validate(['diagnosis_type' => 'in:Checkup,Vaccination,Surgery,Treatment,Emergency,Deworming,Spay/Neuter']);
            $updates[] = "Diagnosis_Type = :diagnosis_type";
            $params['diagnosis_type'] = $this->input('diagnosis_type');
        }
        
        if ($this->has('vaccine_name')) {
            $updates[] = "Vaccine_Name = :vaccine_name";
            $params['vaccine_name'] = $this->input('vaccine_name');
        }
        
        if ($this->has('treatment_notes')) {
            $updates[] = "Treatment_Notes = :treatment_notes";
            $params['treatment_notes'] = $this->input('treatment_notes');
        }
        
        if ($this->has('next_due_date')) {
            $updates[] = "Next_Due_Date = :next_due_date";
            $params['next_due_date'] = $this->input('next_due_date');
        }
        
        if (empty($updates)) {
            Response::error("No fields to update", 400);
        }
        
        $stmt = $this->db->prepare("UPDATE Medical_Records SET " . implode(', ', $updates) . " WHERE RecordID = :id");
        $stmt->execute($params);
        
        $this->logActivity('UPDATE_MEDICAL_RECORD', "Updated medical record ID: {$id}");
        
        // Get updated record
        $stmt = $this->db->prepare("
            SELECT mr.*, 
                   a.Name as Animal_Name,
                   u.FirstName as Vet_FirstName, u.LastName as Vet_LastName
            FROM Medical_Records mr
            JOIN Animals a ON mr.AnimalID = a.AnimalID
            JOIN Veterinarians v ON mr.VetID = v.VetID
            JOIN Users u ON v.UserID = u.UserID
            WHERE mr.RecordID = :id
        ");
        $stmt->execute(['id' => $id]);
        
        Response::success($stmt->fetch(), "Medical record updated");
    }
    
    /**
     * Delete medical record
     * DELETE /medical/{id}
     */
    public function destroy($id) {
        $stmt = $this->db->prepare("SELECT RecordID FROM Medical_Records WHERE RecordID = :id");
        $stmt->execute(['id' => $id]);
        
        if (!$stmt->fetch()) {
            Response::notFound("Medical record not found");
        }
        
        $stmt = $this->db->prepare("DELETE FROM Medical_Records WHERE RecordID = :id");
        $stmt->execute(['id' => $id]);
        
        $this->logActivity('DELETE_MEDICAL_RECORD', "Deleted medical record ID: {$id}");
        
        Response::success(null, "Medical record deleted");
    }
    
    /**
     * Get feeding records for animal
     * GET /feeding/animal/{animalId}
     */
    public function feedingByAnimal($animalId) {
        $stmt = $this->db->prepare("SELECT AnimalID FROM Animals WHERE AnimalID = :id AND Is_Deleted = FALSE");
        $stmt->execute(['id' => $animalId]);
        
        if (!$stmt->fetch()) {
            Response::notFound("Animal not found");
        }
        
        list($page, $perPage) = $this->getPagination();
        
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM Feeding_Records WHERE AnimalID = :animal_id");
        $countStmt->execute(['animal_id' => $animalId]);
        $total = $countStmt->fetch()['total'];
        
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("
            SELECT fr.*, u.FirstName, u.LastName
            FROM Feeding_Records fr
            JOIN Users u ON fr.Fed_By_UserID = u.UserID
            WHERE fr.AnimalID = :animal_id
            ORDER BY fr.Feeding_Time DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':animal_id', $animalId);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        Response::paginated($stmt->fetchAll(), $page, $perPage, $total, "Feeding records retrieved");
    }
    
    /**
     * Get today's feeding summary
     * GET /feeding/today
     */
    public function feedingToday() {
        $stmt = $this->db->prepare("
            SELECT fr.*, 
                   a.Name as Animal_Name, a.Type as Animal_Type,
                   u.FirstName, u.LastName
            FROM Feeding_Records fr
            JOIN Animals a ON fr.AnimalID = a.AnimalID
            JOIN Users u ON fr.Fed_By_UserID = u.UserID
            WHERE DATE(fr.Feeding_Time) = CURDATE()
            ORDER BY fr.Feeding_Time DESC
        ");
        $stmt->execute();
        
        $records = $stmt->fetchAll();
        
        // Get summary
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT AnimalID) as animals_fed,
                COUNT(*) as total_feedings,
                SUM(Quantity_Used) as total_quantity
            FROM Feeding_Records
            WHERE DATE(Feeding_Time) = CURDATE()
        ");
        $stmt->execute();
        $summary = $stmt->fetch();
        
        Response::success([
            'summary' => $summary,
            'records' => $records
        ], "Today's feeding summary retrieved");
    }
    
    /**
     * Record feeding
     * POST /feeding
     */
    public function recordFeeding() {
        $this->validate([
            'animal_id' => 'required|integer',
            'food_type' => 'required|max:50',
            'quantity_used' => 'required|numeric|positive'
        ]);
        
        // Verify animal exists
        $stmt = $this->db->prepare("SELECT AnimalID FROM Animals WHERE AnimalID = :id AND Is_Deleted = FALSE");
        $stmt->execute(['id' => $this->input('animal_id')]);
        
        if (!$stmt->fetch()) {
            Response::notFound("Animal not found");
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO Feeding_Records (AnimalID, Fed_By_UserID, Feeding_Time, Food_Type, Quantity_Used)
            VALUES (:animal_id, :user_id, NOW(), :food_type, :quantity)
        ");
        
        $stmt->execute([
            'animal_id' => $this->input('animal_id'),
            'user_id' => $this->user['UserID'],
            'food_type' => $this->input('food_type'),
            'quantity' => $this->input('quantity_used')
        ]);
        
        $feedingId = $this->db->lastInsertId();
        
        $this->logActivity('RECORD_FEEDING', "Recorded feeding for animal ID: {$this->input('animal_id')}");
        
        // Get created record
        $stmt = $this->db->prepare("
            SELECT fr.*, 
                   a.Name as Animal_Name,
                   u.FirstName, u.LastName
            FROM Feeding_Records fr
            JOIN Animals a ON fr.AnimalID = a.AnimalID
            JOIN Users u ON fr.Fed_By_UserID = u.UserID
            WHERE fr.FeedingID = :id
        ");
        $stmt->execute(['id' => $feedingId]);
        
        Response::created($stmt->fetch(), "Feeding recorded");
    }
    
    /**
     * List all veterinarians
     * GET /veterinarians
     */
    public function listVeterinarians() {
        $stmt = $this->db->prepare("
            SELECT v.*, u.FirstName, u.LastName, u.Email, u.Contact_Number, u.Account_Status
            FROM Veterinarians v
            JOIN Users u ON v.UserID = u.UserID
            WHERE u.Is_Deleted = FALSE
            ORDER BY u.LastName, u.FirstName
        ");
        $stmt->execute();
        
        Response::success($stmt->fetchAll(), "Veterinarians retrieved");
    }
    
    /**
     * Get veterinarian details
     * GET /veterinarians/{id}
     */
    public function showVeterinarian($id) {
        $stmt = $this->db->prepare("
            SELECT v.*, u.FirstName, u.LastName, u.Email, u.Contact_Number, u.Account_Status
            FROM Veterinarians v
            JOIN Users u ON v.UserID = u.UserID
            WHERE v.VetID = :id AND u.Is_Deleted = FALSE
        ");
        $stmt->execute(['id' => $id]);
        $vet = $stmt->fetch();
        
        if (!$vet) {
            Response::notFound("Veterinarian not found");
        }
        
        // Get record count
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM Medical_Records WHERE VetID = :id");
        $stmt->execute(['id' => $id]);
        $vet['records_count'] = $stmt->fetch()['count'];
        
        Response::success($vet);
    }
}