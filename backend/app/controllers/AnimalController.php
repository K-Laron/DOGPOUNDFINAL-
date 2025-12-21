<?php
/**
 * Animal Controller
 * Handles animal management operations
 * 
 * @package AnimalShelter
 */

require_once APP_PATH . '/controllers/BaseController.php';

class AnimalController extends BaseController {
    
    /**
     * List all animals with pagination and filters
     * GET /animals
     */
    public function index() {
        list($page, $perPage) = $this->getPagination();
        
        $where = ["Is_Deleted = FALSE"];
        $params = [];
        
        // Filter by type
        if ($this->query('type')) {
            $where[] = "Type = :type";
            $params['type'] = $this->query('type');
        }
        
        // Filter by status
        if ($this->query('status')) {
            $where[] = "Current_Status = :status";
            $params['status'] = $this->query('status');
        }
        
        // Filter by gender
        if ($this->query('gender')) {
            $where[] = "Gender = :gender";
            $params['gender'] = $this->query('gender');
        }
        
        // Filter by intake status
        if ($this->query('intake_status')) {
            $where[] = "Intake_Status = :intake_status";
            $params['intake_status'] = $this->query('intake_status');
        }
        
        // Search
        if ($this->query('search')) {
            $where[] = "(Name LIKE :search OR Breed LIKE :search)";
            $params['search'] = '%' . $this->query('search') . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM Animals WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get animals
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("
            SELECT * FROM Animals 
            WHERE {$whereClause}
            ORDER BY Intake_Date DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $animals = $stmt->fetchAll();
        
        // Add image URLs
        foreach ($animals as &$animal) {
            $animal['Image_URL'] = $this->getFileUrl($animal['Image_URL']);
        }
        
        Response::paginated($animals, $page, $perPage, $total, "Animals retrieved");
    }
    
    /**
     * Get available animals for adoption (public)
     * GET /animals/available
     */
    public function available() {
        list($page, $perPage) = $this->getPagination();
        
        $where = ["Is_Deleted = FALSE", "Current_Status = 'Available'"];
        $params = [];
        
        if ($this->query('type')) {
            $where[] = "Type = :type";
            $params['type'] = $this->query('type');
        }
        
        if ($this->query('gender')) {
            $where[] = "Gender = :gender";
            $params['gender'] = $this->query('gender');
        }
        
        if ($this->query('search')) {
            $where[] = "(Name LIKE :search OR Breed LIKE :search)";
            $params['search'] = '%' . $this->query('search') . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM Animals WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get animals
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("
            SELECT AnimalID, Name, Type, Breed, Gender, Age_Group, Weight, Image_URL, Intake_Date, Current_Status
            FROM Animals 
            WHERE {$whereClause}
            ORDER BY Intake_Date DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $animals = $stmt->fetchAll();
        
        foreach ($animals as &$animal) {
            $animal['Image_URL'] = $this->getFileUrl($animal['Image_URL']);
        }
        
        Response::paginated($animals, $page, $perPage, $total, "Available animals retrieved");
    }
    
    /**
     * Get animal statistics
     * GET /animals/stats/summary
     */
    public function statistics() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Current_Status = 'Available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN Current_Status = 'Adopted' THEN 1 ELSE 0 END) as adopted,
                SUM(CASE WHEN Current_Status = 'In Treatment' THEN 1 ELSE 0 END) as in_treatment,
                SUM(CASE WHEN Current_Status = 'Quarantine' THEN 1 ELSE 0 END) as quarantine,
                SUM(CASE WHEN Current_Status = 'Deceased' THEN 1 ELSE 0 END) as deceased,
                SUM(CASE WHEN Current_Status = 'Reclaimed' THEN 1 ELSE 0 END) as reclaimed,
                SUM(CASE WHEN Type = 'Dog' THEN 1 ELSE 0 END) as dogs,
                SUM(CASE WHEN Type = 'Cat' THEN 1 ELSE 0 END) as cats,
                SUM(CASE WHEN Type = 'Other' THEN 1 ELSE 0 END) as others
            FROM Animals 
            WHERE Is_Deleted = FALSE
        ");
        $stmt->execute();
        $stats = $stmt->fetch();
        
        // Get intake stats for current month
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as this_month_intake,
                SUM(CASE WHEN Intake_Status = 'Stray' THEN 1 ELSE 0 END) as strays,
                SUM(CASE WHEN Intake_Status = 'Surrendered' THEN 1 ELSE 0 END) as surrendered,
                SUM(CASE WHEN Intake_Status = 'Confiscated' THEN 1 ELSE 0 END) as confiscated
            FROM Animals 
            WHERE Is_Deleted = FALSE 
            AND MONTH(Intake_Date) = MONTH(CURRENT_DATE)
            AND YEAR(Intake_Date) = YEAR(CURRENT_DATE)
        ");
        $stmt->execute();
        $monthlyStats = $stmt->fetch();
        
        Response::success(array_merge($stats, $monthlyStats), "Statistics retrieved");
    }
    
    /**
     * Get single animal with full details
     * GET /animals/{id}
     */
    public function show($id) {
        $stmt = $this->db->prepare("SELECT * FROM Animals WHERE AnimalID = :id AND Is_Deleted = FALSE");
        $stmt->execute(['id' => $id]);
        $animal = $stmt->fetch();
        
        if (!$animal) {
            Response::notFound("Animal not found");
        }
        
        $animal['Image_URL'] = $this->getFileUrl($animal['Image_URL']);
        
        // Get impound record
        $stmt = $this->db->prepare("SELECT * FROM Impound_Records WHERE AnimalID = :id");
        $stmt->execute(['id' => $id]);
        $animal['impound_record'] = $stmt->fetch() ?: null;
        
        // Get medical records count
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM Medical_Records WHERE AnimalID = :id");
        $stmt->execute(['id' => $id]);
        $animal['medical_records_count'] = $stmt->fetch()['count'];
        
        // Get adoption requests count
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM Adoption_Requests WHERE AnimalID = :id");
        $stmt->execute(['id' => $id]);
        $animal['adoption_requests_count'] = $stmt->fetch()['count'];
        
        Response::success($animal);
    }
    
    /**
     * Create new animal
     * POST /animals
     */
    public function store() {
        $this->validate([
            'name' => 'required|max:50',
            'type' => 'required|in:Dog,Cat,Other',
            'intake_status' => 'required|in:Stray,Surrendered,Confiscated,Born in Shelter,Transferred',
            'gender' => 'in:Male,Female,Unknown',
            'weight' => 'numeric'
        ]);
        
        $stmt = $this->db->prepare("
            INSERT INTO Animals (Name, Type, Breed, Gender, Age_Group, Weight, Intake_Date, Intake_Status, Current_Status, Image_URL, Is_Deleted)
            VALUES (:name, :type, :breed, :gender, :age_group, :weight, :intake_date, :intake_status, :current_status, :image_url, FALSE)
        ");
        
        $stmt->execute([
            'name' => $this->input('name'),
            'type' => $this->input('type'),
            'breed' => $this->input('breed'),
            'gender' => $this->input('gender', 'Unknown'),
            'age_group' => $this->input('age_group'),
            'weight' => $this->input('weight'),
            'intake_date' => $this->input('intake_date', date('Y-m-d H:i:s')),
            'intake_status' => $this->input('intake_status'),
            'current_status' => $this->input('current_status', 'Available'),
            'image_url' => $this->input('image_url')
        ]);
        
        $animalId = $this->db->lastInsertId();
        
        $this->logActivity('CREATE_ANIMAL', "Created animal ID: {$animalId} ({$this->input('name')})");
        
        // Get created animal
        $stmt = $this->db->prepare("SELECT * FROM Animals WHERE AnimalID = :id");
        $stmt->execute(['id' => $animalId]);
        $animal = $stmt->fetch();
        $animal['Image_URL'] = $this->getFileUrl($animal['Image_URL']);
        
        Response::created($animal, "Animal record created");
    }
    
    /**
     * Update animal
     * PUT /animals/{id}
     */
    public function update($id) {
        $stmt = $this->db->prepare("SELECT * FROM Animals WHERE AnimalID = :id AND Is_Deleted = FALSE");
        $stmt->execute(['id' => $id]);
        
        if (!$stmt->fetch()) {
            Response::notFound("Animal not found");
        }
        
        $updates = [];
        $params = ['id' => $id];
        
        $fields = [
            'name' => 'Name',
            'type' => 'Type',
            'breed' => 'Breed',
            'gender' => 'Gender',
            'age_group' => 'Age_Group',
            'weight' => 'Weight',
            'current_status' => 'Current_Status',
            'image_url' => 'Image_URL'
        ];
        
        foreach ($fields as $inputKey => $dbField) {
            if ($this->has($inputKey)) {
                $updates[] = "{$dbField} = :{$inputKey}";
                $params[$inputKey] = $this->input($inputKey);
            }
        }
        
        if (empty($updates)) {
            Response::error("No fields to update", 400);
        }
        
        // Validate type if being updated
        if ($this->has('type')) {
            $this->validate(['type' => 'in:Dog,Cat,Other']);
        }
        
        if ($this->has('current_status')) {
            $this->validate(['current_status' => 'in:Available,Adopted,Deceased,In Treatment,Quarantine,Reclaimed']);
        }
        
        $stmt = $this->db->prepare("UPDATE Animals SET " . implode(', ', $updates) . " WHERE AnimalID = :id");
        $stmt->execute($params);
        
        $this->logActivity('UPDATE_ANIMAL', "Updated animal ID: {$id}");
        
        // Get updated animal
        $stmt = $this->db->prepare("SELECT * FROM Animals WHERE AnimalID = :id");
        $stmt->execute(['id' => $id]);
        $animal = $stmt->fetch();
        $animal['Image_URL'] = $this->getFileUrl($animal['Image_URL']);
        
        Response::success($animal, "Animal updated");
    }
    
    /**
     * Delete animal (soft delete)
     * DELETE /animals/{id}
     */
    public function destroy($id) {
        $stmt = $this->db->prepare("SELECT Name FROM Animals WHERE AnimalID = :id AND Is_Deleted = FALSE");
        $stmt->execute(['id' => $id]);
        $animal = $stmt->fetch();
        
        if (!$animal) {
            Response::notFound("Animal not found");
        }
        
        $stmt = $this->db->prepare("UPDATE Animals SET Is_Deleted = TRUE WHERE AnimalID = :id");
        $stmt->execute(['id' => $id]);
        
        $this->logActivity('DELETE_ANIMAL', "Deleted animal ID: {$id} ({$animal['Name']})");
        
        Response::success(null, "Animal record deleted");
    }
    
    /**
     * Update animal status only
     * PATCH /animals/{id}/status
     */
    public function updateStatus($id) {
        $this->validate([
            'status' => 'required|in:Available,Adopted,Deceased,In Treatment,Quarantine,Reclaimed'
        ]);
        
        $stmt = $this->db->prepare("SELECT Name, Current_Status FROM Animals WHERE AnimalID = :id AND Is_Deleted = FALSE");
        $stmt->execute(['id' => $id]);
        $animal = $stmt->fetch();
        
        if (!$animal) {
            Response::notFound("Animal not found");
        }
        
        $newStatus = $this->input('status');
        
        $stmt = $this->db->prepare("UPDATE Animals SET Current_Status = :status WHERE AnimalID = :id");
        $stmt->execute(['status' => $newStatus, 'id' => $id]);
        
        $this->logActivity('UPDATE_ANIMAL_STATUS', "Changed animal ID: {$id} status from {$animal['Current_Status']} to {$newStatus}");
        
        Response::success(['status' => $newStatus], "Animal status updated");
    }
    
    /**
     * Add impound record
     * POST /animals/{id}/impound
     */
    public function addImpoundRecord($id) {
        $stmt = $this->db->prepare("SELECT AnimalID FROM Animals WHERE AnimalID = :id AND Is_Deleted = FALSE");
        $stmt->execute(['id' => $id]);
        
        if (!$stmt->fetch()) {
            Response::notFound("Animal not found");
        }
        
        // Check if impound record already exists
        $stmt = $this->db->prepare("SELECT ImpoundID FROM Impound_Records WHERE AnimalID = :id");
        $stmt->execute(['id' => $id]);
        
        if ($stmt->fetch()) {
            Response::conflict("Impound record already exists for this animal");
        }
        
        $this->validate([
            'capture_date' => 'required',
            'location_found' => 'required|max:255',
            'impounding_officer' => 'required|max:100'
        ]);
        
        $stmt = $this->db->prepare("
            INSERT INTO Impound_Records (AnimalID, Capture_Date, Location_Found, Impounding_Officer, Condition_On_Arrival)
            VALUES (:animal_id, :capture_date, :location, :officer, :condition)
        ");
        
        $stmt->execute([
            'animal_id' => $id,
            'capture_date' => $this->input('capture_date'),
            'location' => $this->input('location_found'),
            'officer' => $this->input('impounding_officer'),
            'condition' => $this->input('condition_on_arrival')
        ]);
        
        $impoundId = $this->db->lastInsertId();
        
        $this->logActivity('CREATE_IMPOUND', "Added impound record for animal ID: {$id}");
        
        $stmt = $this->db->prepare("SELECT * FROM Impound_Records WHERE ImpoundID = :id");
        $stmt->execute(['id' => $impoundId]);
        
        Response::created($stmt->fetch(), "Impound record added");
    }
    
    /**
     * Get impound record
     * GET /animals/{id}/impound
     */
    public function getImpoundRecord($id) {
        $stmt = $this->db->prepare("
            SELECT ir.*, a.Name as Animal_Name
            FROM Impound_Records ir
            JOIN Animals a ON ir.AnimalID = a.AnimalID
            WHERE ir.AnimalID = :id
        ");
        $stmt->execute(['id' => $id]);
        $record = $stmt->fetch();
        
        if (!$record) {
            Response::notFound("Impound record not found");
        }
        
        Response::success($record);
    }
    
    /**
     * Update impound record
     * PUT /animals/{id}/impound
     */
    public function updateImpoundRecord($id) {
        $stmt = $this->db->prepare("SELECT ImpoundID FROM Impound_Records WHERE AnimalID = :id");
        $stmt->execute(['id' => $id]);
        
        if (!$stmt->fetch()) {
            Response::notFound("Impound record not found");
        }
        
        $updates = [];
        $params = ['animal_id' => $id];
        
        if ($this->has('capture_date')) {
            $updates[] = "Capture_Date = :capture_date";
            $params['capture_date'] = $this->input('capture_date');
        }
        
        if ($this->has('location_found')) {
            $updates[] = "Location_Found = :location";
            $params['location'] = $this->input('location_found');
        }
        
        if ($this->has('impounding_officer')) {
            $updates[] = "Impounding_Officer = :officer";
            $params['officer'] = $this->input('impounding_officer');
        }
        
        if ($this->has('condition_on_arrival')) {
            $updates[] = "Condition_On_Arrival = :condition";
            $params['condition'] = $this->input('condition_on_arrival');
        }
        
        if (empty($updates)) {
            Response::error("No fields to update", 400);
        }
        
        $stmt = $this->db->prepare("UPDATE Impound_Records SET " . implode(', ', $updates) . " WHERE AnimalID = :animal_id");
        $stmt->execute($params);
        
        $this->logActivity('UPDATE_IMPOUND', "Updated impound record for animal ID: {$id}");
        
        $stmt = $this->db->prepare("SELECT * FROM Impound_Records WHERE AnimalID = :id");
        $stmt->execute(['id' => $id]);
        
        Response::success($stmt->fetch(), "Impound record updated");
    }
    
    /**
     * Upload animal image
     * POST /animals/{id}/image
     */
    public function uploadImage($id) {
        $stmt = $this->db->prepare("SELECT AnimalID, Image_URL FROM Animals WHERE AnimalID = :id AND Is_Deleted = FALSE");
        $stmt->execute(['id' => $id]);
        $animal = $stmt->fetch();
        
        if (!$animal) {
            Response::notFound("Animal not found");
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            Response::error("No image file uploaded", 400);
        }
        
        // Save file
        $relativePath = $this->saveFile('image', 'animals');
        
        if (!$relativePath) {
            Response::error("Failed to upload image. Check file type and size.", 400);
        }
        
        // Delete old image if exists
        if ($animal['Image_URL']) {
            $this->deleteFile($animal['Image_URL']);
        }
        
        // Update database
        $stmt = $this->db->prepare("UPDATE Animals SET Image_URL = :image WHERE AnimalID = :id");
        $stmt->execute(['image' => $relativePath, 'id' => $id]);
        
        $this->logActivity('UPLOAD_ANIMAL_IMAGE', "Uploaded image for animal ID: {$id}");
        
        Response::success([
            'image_url' => $this->getFileUrl($relativePath)
        ], "Image uploaded successfully");
    }
}