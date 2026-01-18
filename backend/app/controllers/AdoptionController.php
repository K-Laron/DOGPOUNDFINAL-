<?php

/**
 * Adoption Controller
 * Handles adoption request operations
 * 
 * @package AnimalShelter
 */

require_once APP_PATH . '/controllers/BaseController.php';

class AdoptionController extends BaseController
{

    /**
     * List adoption requests
     * GET /adoptions
     */
    public function index()
    {
        list($page, $perPage) = $this->getPagination();

        $where = ["1=1"];
        $params = [];

        // Adopters can only see their own requests
        if ($this->user['Role_Name'] === 'Adopter') {
            $where[] = "ar.Adopter_UserID = :adopter_id";
            $params['adopter_id'] = $this->user['UserID'];
        } else {
            // Staff/Admin can filter
            if ($this->query('status')) {
                $where[] = "ar.Status = :status";
                $params['status'] = $this->query('status');
            }

            if ($this->query('animal_id')) {
                $where[] = "ar.AnimalID = :animal_id";
                $params['animal_id'] = $this->query('animal_id');
            }

            if ($this->query('adopter_id')) {
                $where[] = "ar.Adopter_UserID = :adopter_id";
                $params['adopter_id'] = $this->query('adopter_id');
            }
        }

        // Search functionality (for all roles)
        if ($this->query('search')) {
            $searchTerm = '%' . trim($this->query('search')) . '%';
            $where[] = "(
                a.Name LIKE :search 
                OR a.Breed LIKE :search 
                OR u.FirstName LIKE :search 
                OR u.LastName LIKE :search 
                OR CONCAT(u.FirstName, ' ', u.LastName) LIKE :search
                OR u.Email LIKE :search
                OR a.Type LIKE :search
            )";
            $params['search'] = $searchTerm;
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM Adoption_Requests ar 
            JOIN Animals a ON ar.AnimalID = a.AnimalID
            JOIN Users u ON ar.Adopter_UserID = u.UserID
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get requests
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("
            SELECT ar.*, 
                   a.Name as Animal_Name, a.Type as Animal_Type, a.Breed, a.Image_URL,
                   u.FirstName, u.LastName, u.Email, u.Contact_Number
            FROM Adoption_Requests ar
            JOIN Animals a ON ar.AnimalID = a.AnimalID
            JOIN Users u ON ar.Adopter_UserID = u.UserID
            WHERE {$whereClause}
            ORDER BY ar.Request_Date DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $requests = $stmt->fetchAll();

        // Add image URLs
        foreach ($requests as &$request) {
            $request['Image_URL'] = $this->getFileUrl($request['Image_URL']);
        }

        Response::paginated($requests, $page, $perPage, $total, "Adoption requests retrieved");
    }

    /**
     * Get single adoption request
     * GET /adoptions/{id}
     */
    public function show($id)
    {
        $stmt = $this->db->prepare("
            SELECT ar.*, 
                   a.Name as Animal_Name, a.Type as Animal_Type, a.Breed, a.Gender, 
                   a.Age_Group, a.Image_URL, a.Current_Status as Animal_Status,
                   u.FirstName as Adopter_FirstName, u.LastName as Adopter_LastName, 
                   u.Email as Adopter_Email, u.Contact_Number as Adopter_Contact,
                   staff.FirstName as Staff_FirstName, staff.LastName as Staff_LastName
            FROM Adoption_Requests ar
            JOIN Animals a ON ar.AnimalID = a.AnimalID
            JOIN Users u ON ar.Adopter_UserID = u.UserID
            LEFT JOIN Users staff ON ar.Processed_By_UserID = staff.UserID
            WHERE ar.RequestID = :id
        ");
        $stmt->execute(['id' => $id]);
        $request = $stmt->fetch();

        if (!$request) {
            Response::notFound("Adoption request not found");
        }

        // Check access for adopters
        if ($this->user['Role_Name'] === 'Adopter' && $request['Adopter_UserID'] != $this->user['UserID']) {
            Response::forbidden("Access denied");
        }

        $request['Image_URL'] = $this->getFileUrl($request['Image_URL']);

        Response::success($request);
    }

    /**
     * Create adoption request
     * POST /adoptions
     */
    public function store()
    {
        $this->validate([
            'animal_id' => 'required|integer'
        ]);

        $animalId = $this->input('animal_id');

        // Check animal exists and is available
        $stmt = $this->db->prepare("SELECT AnimalID, Name, Current_Status FROM Animals WHERE AnimalID = :id AND Is_Deleted = FALSE");
        $stmt->execute(['id' => $animalId]);
        $animal = $stmt->fetch();

        if (!$animal) {
            Response::notFound("Animal not found");
        }

        if ($animal['Current_Status'] !== 'Available') {
            Response::error("This animal is not available for adoption", 400);
        }

        // Check for existing pending request from same user
        $stmt = $this->db->prepare("
            SELECT RequestID FROM Adoption_Requests 
            WHERE AnimalID = :animal_id AND Adopter_UserID = :user_id 
            AND Status IN ('Pending', 'Interview Scheduled', 'Seminar Scheduled', 'Approved')
        ");
        $stmt->execute([
            'animal_id' => $animalId,
            'user_id' => $this->user['UserID']
        ]);

        if ($stmt->fetch()) {
            Response::error("You already have an active request for this animal", 400);
        }

        // Create request
        $stmt = $this->db->prepare("
            INSERT INTO Adoption_Requests (AnimalID, Adopter_UserID, Request_Date, Status)
            VALUES (:animal_id, :user_id, NOW(), 'Pending')
        ");

        $stmt->execute([
            'animal_id' => $animalId,
            'user_id' => $this->user['UserID']
        ]);

        $requestId = $this->db->lastInsertId();

        $this->logActivity('CREATE_ADOPTION_REQUEST', "Submitted adoption request ID: {$requestId} for animal: {$animal['Name']}");

        // Get created request
        $stmt = $this->db->prepare("
            SELECT ar.*, 
                   a.Name as Animal_Name, a.Type as Animal_Type, a.Breed, a.Image_URL,
                   u.FirstName, u.LastName, u.Email
            FROM Adoption_Requests ar
            JOIN Animals a ON ar.AnimalID = a.AnimalID
            JOIN Users u ON ar.Adopter_UserID = u.UserID
            WHERE ar.RequestID = :id
        ");
        $stmt->execute(['id' => $requestId]);
        $request = $stmt->fetch();
        $request['Image_URL'] = $this->getFileUrl($request['Image_URL']);

        Response::created($request, "Adoption request submitted successfully");
    }

    /**
     * Process adoption request
     * PUT /adoptions/{id}/process
     */
    public function process($id)
    {
        $stmt = $this->db->prepare("
            SELECT ar.*, a.Name as Animal_Name, a.AnimalID
            FROM Adoption_Requests ar
            JOIN Animals a ON ar.AnimalID = a.AnimalID
            WHERE ar.RequestID = :id
        ");
        $stmt->execute(['id' => $id]);
        $request = $stmt->fetch();

        if (!$request) {
            Response::notFound("Adoption request not found");
        }

        $this->validate([
            'status' => 'required|in:Interview Scheduled,Seminar Scheduled,Approved,Rejected,Completed',
            'interview_date' => 'nullable|datetime',
            'seminar_date' => 'nullable|datetime'
        ]);

        $newStatus = $this->input('status');
        $comments = $this->input('comments');
        $interviewDate = $this->input('interview_date');
        $seminarDate = $this->input('seminar_date');

        $this->db->beginTransaction();

        try {
            // Update request with interview date if status is Interview Scheduled
            if ($newStatus === 'Interview Scheduled' && $interviewDate) {
                $stmt = $this->db->prepare("
                    UPDATE Adoption_Requests 
                    SET Status = :status, 
                        Staff_Comments = :comments, 
                        Processed_By_UserID = :staff_id,
                        Interview_Date = :interview_date
                    WHERE RequestID = :id
                ");

                $stmt->execute([
                    'status' => $newStatus,
                    'comments' => $comments,
                    'staff_id' => $this->user['UserID'],
                    'interview_date' => $interviewDate,
                    'id' => $id
                ]);
            } elseif ($newStatus === 'Seminar Scheduled' && $seminarDate) {
                // Update request with seminar date
                $stmt = $this->db->prepare("
                    UPDATE Adoption_Requests 
                    SET Status = :status, 
                        Staff_Comments = :comments, 
                        Processed_By_UserID = :staff_id,
                        Seminar_Date = :seminar_date
                    WHERE RequestID = :id
                ");

                $stmt->execute([
                    'status' => $newStatus,
                    'comments' => $comments,
                    'staff_id' => $this->user['UserID'],
                    'seminar_date' => $seminarDate,
                    'id' => $id
                ]);
            } else {
                // Clear dates for other statuses
                $stmt = $this->db->prepare("
                    UPDATE Adoption_Requests 
                    SET Status = :status, 
                        Staff_Comments = :comments, 
                        Processed_By_UserID = :staff_id,
                        Interview_Date = NULL,
                        Seminar_Date = NULL
                    WHERE RequestID = :id
                ");

                $stmt->execute([
                    'status' => $newStatus,
                    'comments' => $comments,
                    'staff_id' => $this->user['UserID'],
                    'id' => $id
                ]);
            }

            // If approved, update animal status to Reserved and reject other pending requests
            if ($newStatus === 'Approved') {
                // Update animal status to Reserved
                $stmt = $this->db->prepare("UPDATE Animals SET Current_Status = 'Reserved' WHERE AnimalID = :id");
                $stmt->execute(['id' => $request['AnimalID']]);

                // Reject other pending requests for this animal
                $stmt = $this->db->prepare("
                    UPDATE Adoption_Requests 
                    SET Status = 'Rejected', 
                        Staff_Comments = 'Another applicant has been approved for this animal',
                        Processed_By_UserID = :staff_id
                    WHERE AnimalID = :animal_id 
                    AND RequestID != :request_id 
                    AND Status IN ('Pending', 'Interview Scheduled', 'Seminar Scheduled')
                ");
                $stmt->execute([
                    'staff_id' => $this->user['UserID'],
                    'animal_id' => $request['AnimalID'],
                    'request_id' => $id
                ]);

                // AUTO-CREATE INVOICE for adoption fee
                require_once APP_PATH . '/utils/FeeCalculator.php';
                $calculator = new FeeCalculator($this->db);
                $feeResult = $calculator->calculateAdoptionFee($request['AnimalID']);

                $stmt = $this->db->prepare("
                    INSERT INTO Invoices (
                        Payer_UserID, 
                        Issued_By_UserID, 
                        Transaction_Type, 
                        Total_Amount, 
                        Status, 
                        Related_AnimalID, 
                        Related_RequestID,
                        Is_Deleted
                    ) VALUES (
                        :payer_id, 
                        :issued_by, 
                        'Adoption Fee', 
                        :amount, 
                        'Unpaid', 
                        :animal_id, 
                        :request_id,
                        FALSE
                    )
                ");
                $stmt->execute([
                    'payer_id' => $request['Adopter_UserID'],
                    'issued_by' => $this->user['UserID'],
                    'amount' => $feeResult['total'],
                    'animal_id' => $request['AnimalID'],
                    'request_id' => $id
                ]);

                $this->logActivity('AUTO_CREATE_INVOICE', "Auto-created adoption invoice for request ID: {$id}, Amount: {$feeResult['total']}");
            }

            // If completed, check invoice is fully paid first, then update animal status
            if ($newStatus === 'Completed') {
                // Check if there's an invoice for this adoption and get payment status
                $stmt = $this->db->prepare("
                    SELECT 
                        i.InvoiceID, 
                        i.Status, 
                        i.Total_Amount,
                        COALESCE((SELECT SUM(Amount_Paid) FROM Payments WHERE InvoiceID = i.InvoiceID), 0) as Amount_Paid
                    FROM Invoices i
                    WHERE i.Related_RequestID = :request_id 
                    AND i.Transaction_Type = 'Adoption Fee'
                    AND i.Is_Deleted = FALSE
                    ORDER BY i.Created_At DESC
                    LIMIT 1
                ");
                $stmt->execute(['request_id' => $id]);
                $invoice = $stmt->fetch();

                // If no invoice exists, block completion
                if (!$invoice) {
                    $this->db->rollBack();
                    Response::error(
                        "Cannot complete adoption. No invoice found for this adoption request. Please contact an administrator.", 
                        400
                    );
                    return;
                }

                // Calculate remaining balance
                $balance = $invoice['Total_Amount'] - $invoice['Amount_Paid'];

                // If not fully paid, block completion with detailed message
                if ($balance > 0) {
                    $this->db->rollBack();
                    $message = sprintf(
                        "Cannot complete adoption. Payment not fully settled. Total: PHP %s | Paid: PHP %s | Balance: PHP %s",
                        number_format($invoice['Total_Amount'], 2),
                        number_format($invoice['Amount_Paid'], 2),
                        number_format($balance, 2)
                    );
                    Response::error($message, 400);
                    return;
                }

                // Update animal status
                $stmt = $this->db->prepare("UPDATE Animals SET Current_Status = 'Adopted' WHERE AnimalID = :id");
                $stmt->execute(['id' => $request['AnimalID']]);

                // Reject other pending requests for this animal (in case any slipped through)
                $stmt = $this->db->prepare("
                    UPDATE Adoption_Requests 
                    SET Status = 'Rejected', 
                        Staff_Comments = 'Animal has been adopted by another applicant',
                        Processed_By_UserID = :staff_id
                    WHERE AnimalID = :animal_id 
                    AND RequestID != :request_id 
                    AND Status IN ('Pending', 'Interview Scheduled', 'Seminar Scheduled', 'Approved')
                ");
                $stmt->execute([
                    'staff_id' => $this->user['UserID'],
                    'animal_id' => $request['AnimalID'],
                    'request_id' => $id
                ]);
            }

            // If rejected and this was the only approved request, set animal back to Available
            if ($newStatus === 'Rejected' && $request['Status'] === 'Approved') {
                // Check if there are any other approved requests
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as count FROM Adoption_Requests 
                    WHERE AnimalID = :animal_id AND Status = 'Approved' AND RequestID != :request_id
                ");
                $stmt->execute(['animal_id' => $request['AnimalID'], 'request_id' => $id]);
                $result = $stmt->fetch();

                if ($result['count'] == 0) {
                    // No other approved requests, set animal back to Available
                    $stmt = $this->db->prepare("UPDATE Animals SET Current_Status = 'Available' WHERE AnimalID = :id");
                    $stmt->execute(['id' => $request['AnimalID']]);
                }
            }

            $this->db->commit();

            $this->logActivity('PROCESS_ADOPTION', "Processed adoption request ID: {$id} - Status: {$newStatus}");

            // Get updated request
            $stmt = $this->db->prepare("
                SELECT ar.*, 
                       a.Name as Animal_Name, a.Type as Animal_Type,
                       u.FirstName, u.LastName, u.Email
                FROM Adoption_Requests ar
                JOIN Animals a ON ar.AnimalID = a.AnimalID
                JOIN Users u ON ar.Adopter_UserID = u.UserID
                WHERE ar.RequestID = :id
            ");
            $stmt->execute(['id' => $id]);

            Response::success($stmt->fetch(), "Adoption request updated");
        } catch (Exception $e) {
            if ($e->getMessage() === 'RESPONSE_EXIT') {
                throw $e;
            }
            $this->db->rollBack();
            error_log("Error processing adoption: " . $e->getMessage());
            Response::serverError("Failed to process request");
        }
    }

    /**
     * Cancel adoption request (by adopter)
     * PUT /adoptions/{id}/cancel
     */
    public function cancel($id)
    {
        $stmt = $this->db->prepare("
            SELECT RequestID, Adopter_UserID, Status 
            FROM Adoption_Requests 
            WHERE RequestID = :id
        ");
        $stmt->execute(['id' => $id]);
        $request = $stmt->fetch();

        if (!$request) {
            Response::notFound("Adoption request not found");
        }

        // Check ownership
        if ($request['Adopter_UserID'] != $this->user['UserID']) {
            Response::forbidden("You can only cancel your own requests");
        }

        // Can only cancel pending requests
        if ($request['Status'] !== 'Pending') {
            Response::error("Only pending requests can be cancelled", 400);
        }

        $stmt = $this->db->prepare("UPDATE Adoption_Requests SET Status = 'Cancelled' WHERE RequestID = :id");
        $stmt->execute(['id' => $id]);

        $this->logActivity('CANCEL_ADOPTION', "Cancelled adoption request ID: {$id}");

        Response::success(null, "Adoption request cancelled");
    }

    /**
     * Get adoption statistics
     * GET /adoptions/stats/summary
     */
    public function statistics()
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Status IN ('Pending', 'Interview Scheduled') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN Status = 'Interview Scheduled' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN Status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN Status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM Adoption_Requests
        ");
        $stmt->execute();
        $stats = $stmt->fetch();

        // This month's stats (based on Updated_At for completion)
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as this_month_total,
                SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) as completed_this_month
            FROM Adoption_Requests
            WHERE MONTH(Updated_At) = MONTH(CURRENT_DATE)
            AND YEAR(Updated_At) = YEAR(CURRENT_DATE)
        ");
        $stmt->execute();
        $monthlyStats = $stmt->fetch();

        Response::success(array_merge($stats, $monthlyStats), "Adoption statistics retrieved");
    }

    /**
     * Get adoption history for an animal
     * GET /adoptions/animal/{animalId}
     */
    public function animalHistory($animalId)
    {
        $stmt = $this->db->prepare("
            SELECT ar.*, u.FirstName, u.LastName, u.Email
            FROM Adoption_Requests ar
            JOIN Users u ON ar.Adopter_UserID = u.UserID
            WHERE ar.AnimalID = :animal_id
            ORDER BY ar.Request_Date DESC
        ");
        $stmt->execute(['animal_id' => $animalId]);

        Response::success($stmt->fetchAll(), "Adoption history retrieved");
    }

    /**
     * Get adoption history for a user
     * GET /adoptions/user/{userId}
     */
    public function userHistory($userId)
    {
        $stmt = $this->db->prepare("
            SELECT ar.*, a.Name as Animal_Name, a.Type as Animal_Type, a.Breed
            FROM Adoption_Requests ar
            JOIN Animals a ON ar.AnimalID = a.AnimalID
            WHERE ar.Adopter_UserID = :user_id
            ORDER BY ar.Request_Date DESC
        ");
        $stmt->execute(['user_id' => $userId]);

        Response::success($stmt->fetchAll(), "User adoption history retrieved");
    }
}
