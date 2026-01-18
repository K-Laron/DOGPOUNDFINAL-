<?php

/**
 * Animal Controller
 * Handles animal management operations
 * 
 * @package AnimalShelter
 */

require_once APP_PATH . '/controllers/BaseController.php';

class AnimalController extends BaseController
{

    /**
     * List all animals with pagination and filters
     * GET /animals
     */
    public function index()
    {
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
    public function available()
    {
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
    public function statistics()
    {
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
    public function show($id)
    {
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
    public function store()
    {
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
    public function update($id)
    {
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
    public function destroy($id)
    {
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
     * 
     * Uses database transaction to ensure atomicity when auto-creating invoices
     */
    public function updateStatus($id)
    {
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
        $reclaimingUserId = $this->input('reclaiming_user_id');
        $invoiceCreated = false;
        $invoiceAmount = 0;

        // Start transaction for atomic status update + invoice creation
        $this->db->beginTransaction();

        try {
            // Update animal status
            $stmt = $this->db->prepare("UPDATE Animals SET Current_Status = :status, Updated_At = NOW() WHERE AnimalID = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $id]);

            // AUTO-CREATE INVOICE for reclaim fee when status is changed to 'Reclaimed'
            if ($newStatus === 'Reclaimed' && $reclaimingUserId) {
                // Verify user exists
                $userStmt = $this->db->prepare("SELECT UserID FROM Users WHERE UserID = :id AND Is_Deleted = FALSE");
                $userStmt->execute(['id' => $reclaimingUserId]);

                if ($userStmt->fetch()) {
                    require_once APP_PATH . '/utils/FeeCalculator.php';
                    $calculator = new FeeCalculator($this->db);
                    $feeResult = $calculator->calculateReclaimFee($id);

                    $stmt = $this->db->prepare("
                        INSERT INTO Invoices (
                            Payer_UserID, 
                            Issued_By_UserID, 
                            Transaction_Type, 
                            Total_Amount, 
                            Status, 
                            Related_AnimalID,
                            Is_Deleted
                        ) VALUES (
                            :payer_id, 
                            :issued_by, 
                            'Reclaim Fee', 
                            :amount, 
                            'Unpaid', 
                            :animal_id,
                            FALSE
                        )
                    ");
                    $stmt->execute([
                        'payer_id' => $reclaimingUserId,
                        'issued_by' => $this->user['UserID'],
                        'amount' => $feeResult['total'],
                        'animal_id' => $id
                    ]);

                    $invoiceCreated = true;
                    $invoiceAmount = $feeResult['total'];
                }
            }

            // Commit the transaction
            $this->db->commit();

            // Log activities after successful commit
            $this->logActivity('UPDATE_ANIMAL_STATUS', "Changed animal ID: {$id} status from {$animal['Current_Status']} to {$newStatus}");

            if ($invoiceCreated) {
                $this->logActivity('AUTO_CREATE_INVOICE', "Auto-created reclaim invoice for animal ID: {$id}, Amount: {$invoiceAmount}");
            }

            Response::success([
                'status' => $newStatus,
                'invoice_created' => $invoiceCreated,
                'invoice_amount' => $invoiceCreated ? $invoiceAmount : null
            ], "Animal status updated" . ($invoiceCreated ? " and invoice created" : ""));

        } catch (Exception $e) {
            // Rollback on any error
            $this->db->rollBack();
            error_log("Failed to update animal status: " . $e->getMessage());
            Response::serverError("Failed to update animal status. Please try again.");
        }
    }

    /**
     * Add impound record
     * POST /animals/{id}/impound
     */
    public function addImpoundRecord($id)
    {
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
    public function getImpoundRecord($id)
    {
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
    public function updateImpoundRecord($id)
    {
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
    public function uploadImage($id)
    {
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

    /**
     * Export animals data
     * GET /animals/export
     * 
     * Query params:
     * - format: csv, json, excel (default: csv)
     * - status: filter by status
     * - type: filter by animal type
     * - date_from: filter by intake date from
     * - date_to: filter by intake date to
     */
    public function export()
    {
        try {
            require_once APP_PATH . '/utils/ExportService.php';

            // Clean any previous output buffers for clean export
            while (ob_get_level()) {
                ob_end_clean();
            }

            $format = $this->query('format') ?? 'csv';

            // Validate format
            if (!in_array($format, ['csv', 'json', 'excel'])) {
                Response::error("Invalid export format. Allowed: csv, json, excel", 400);
                return;
            }

            // Build query with filters
            $where = ["a.Is_Deleted = FALSE"];
            $params = [];

            if ($this->query('status')) {
                $where[] = "a.Current_Status = :status";
                $params['status'] = $this->query('status');
            }

            if ($this->query('type')) {
                $where[] = "a.Type = :type";
                $params['type'] = $this->query('type');
            }

            if ($this->query('date_from')) {
                $where[] = "DATE(a.Created_At) >= :date_from";
                $params['date_from'] = $this->query('date_from');
            }

            if ($this->query('date_to')) {
                $where[] = "DATE(a.Created_At) <= :date_to";
                $params['date_to'] = $this->query('date_to');
            }

            $whereClause = implode(' AND ', $where);

            $stmt = $this->db->prepare("
                SELECT 
                    a.AnimalID,
                    a.Name,
                    a.Type,
                    a.Breed,
                    a.Gender,
                    a.Age_Group,
                    a.Weight,
                    a.Current_Status,
                    a.Intake_Status,
                    a.Intake_Date,
                    a.Image_URL,
                    a.Created_At
                FROM Animals a
                WHERE {$whereClause}
                ORDER BY a.Created_At DESC
            ");

            $stmt->execute($params);
            $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Define export headers for better readability
            $headers = [
                'AnimalID' => 'Animal ID',
                'Name' => 'Name',
                'Type' => 'Type',
                'Breed' => 'Breed',
                'Gender' => 'Gender',
                'Age_Group' => 'Age Group',
                'Weight' => 'Weight (Kg)',
                'Current_Status' => 'Status',
                'Intake_Status' => 'Intake Type',
                'Intake_Date' => 'Intake Date',
                'Image_URL' => 'Image URL',
                'Created_At' => 'Created At'
            ];

            $this->logActivity('EXPORT_ANIMALS', "Exported " . count($animals) . " animals to {$format}");

            ExportService::export($animals, $format, 'animals_export', $headers);
        } catch (\Exception $e) {
            // Log the error
            error_log("AnimalController::export failed: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            require_once APP_PATH . '/utils/ErrorHandler.php';
            ErrorHandler::handle($e);
        }
    }

    /**
     * Bulk update animal statuses
     * POST /animals/bulk-status
     * 
     * Request body:
     * - animal_ids: array of animal IDs
     * - status: new status to apply
     */
    public function bulkUpdateStatus()
    {
        $this->validate([
            'animal_ids' => 'required|array',
            'status' => 'required|in:Available,Adopted,Reclaimed,Deceased,Under Treatment'
        ]);

        $animalIds = $this->input('animal_ids');
        $newStatus = $this->input('status');

        if (empty($animalIds)) {
            Response::error("No animals selected", 400);
        }

        if (count($animalIds) > 100) {
            Response::error("Maximum 100 animals can be updated at once", 400);
        }

        $this->db->beginTransaction();

        try {
            $updated = 0;
            $failed = [];

            foreach ($animalIds as $animalId) {
                // Validate each animal exists
                $stmt = $this->db->prepare("
                    SELECT AnimalID, Name, Current_Status 
                    FROM Animals 
                    WHERE AnimalID = :id AND Is_Deleted = FALSE
                ");
                $stmt->execute(['id' => $animalId]);
                $animal = $stmt->fetch();

                if (!$animal) {
                    $failed[] = ['id' => $animalId, 'reason' => 'Animal not found'];
                    continue;
                }

                // Skip if already has the same status
                if ($animal['Current_Status'] === $newStatus) {
                    continue;
                }

                // Update status
                $stmt = $this->db->prepare("
                    UPDATE Animals 
                    SET Current_Status = :status, Updated_At = NOW() 
                    WHERE AnimalID = :id
                ");
                $stmt->execute(['status' => $newStatus, 'id' => $animalId]);
                $updated++;
            }

            $this->db->commit();

            $this->logActivity(
                'BULK_STATUS_UPDATE',
                "Bulk updated {$updated} animals to status: {$newStatus}"
            );

            Response::success([
                'updated_count' => $updated,
                'failed' => $failed,
                'new_status' => $newStatus
            ], "Bulk status update completed. {$updated} animals updated.");

        } catch (Exception $e) {
            if ($e->getMessage() === 'RESPONSE_EXIT') {
                throw $e;
            }
            $this->db->rollBack();
            error_log("Bulk status update error: " . $e->getMessage());
            Response::serverError("Failed to update animal statuses");
        }
    }
}
