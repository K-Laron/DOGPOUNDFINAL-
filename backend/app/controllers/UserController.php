<?php
/**
 * User Controller
 * Handles user management, profile, and role operations
 * 
 * @package AnimalShelter
 * @author Your Name
 * @version 1.0.0
 */

require_once APP_PATH . '/controllers/BaseController.php';

class UserController extends BaseController {
    
    /**
     * List all users with pagination and filters
     * GET /users
     * 
     * Query Parameters:
     * - page: Page number (default: 1)
     * - per_page: Items per page (default: 20, max: 100)
     * - role_id: Filter by role ID
     * - status: Filter by account status (Active, Inactive, Banned)
     * - search: Search by name or email
     * 
     * @return void
     */
    public function index() {
        list($page, $perPage) = $this->getPagination();
        
        // Build WHERE clause
        $where = ["u.Is_Deleted = FALSE"];
        $params = [];
        
        // Filter by role
        if ($this->query('role_id')) {
            $where[] = "u.RoleID = :role_id";
            $params['role_id'] = (int)$this->query('role_id');
        }
        
        // Filter by role name
        if ($this->query('role')) {
            $where[] = "r.Role_Name = :role_name";
            $params['role_name'] = $this->query('role');
        }
        
        // Filter by status
        if ($this->query('status')) {
            $allowedStatuses = ['Active', 'Inactive', 'Banned'];
            if (in_array($this->query('status'), $allowedStatuses)) {
                $where[] = "u.Account_Status = :status";
                $params['status'] = $this->query('status');
            }
        }
        
        // Search by name or email
        if ($this->query('search')) {
            $where[] = "(u.FirstName LIKE :search OR u.LastName LIKE :search OR u.Email LIKE :search OR CONCAT(u.FirstName, ' ', u.LastName) LIKE :search)";
            $params['search'] = '%' . trim($this->query('search')) . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countQuery = "
            SELECT COUNT(*) as total 
            FROM Users u 
            JOIN Roles r ON u.RoleID = r.RoleID 
            WHERE {$whereClause}
        ";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];
        
        // Get users with pagination
        $offset = ($page - 1) * $perPage;
        $query = "
            SELECT 
                u.UserID,
                u.RoleID,
                u.FirstName,
                u.LastName,
                u.Username,
                u.Email,
                u.Contact_Number,
                u.Account_Status,
                u.Avatar_Url,
                u.Created_At,
                u.Updated_At,
                r.Role_Name
            FROM Users u
            JOIN Roles r ON u.RoleID = r.RoleID
            WHERE {$whereClause}
            ORDER BY u.Created_At DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $users = $stmt->fetchAll();
        
        // Format response
        $formattedUsers = array_map(function($user) {
            return [
                'id' => (int)$user['UserID'],
                'role_id' => (int)$user['RoleID'],
                'role_name' => $user['Role_Name'],
                'first_name' => $user['FirstName'],
                'last_name' => $user['LastName'],
                'full_name' => $user['FirstName'] . ' ' . $user['LastName'],
                'username' => $user['Username'],
                'email' => $user['Email'],
                'avatar_url' => $user['Avatar_Url'] ?? null,
                'contact_number' => $user['Contact_Number'],
                'account_status' => $user['Account_Status'],
                'preferences' => json_decode($user['Preferences'] ?? '{}', true),
                'created_at' => $user['Created_At'],
                'updated_at' => $user['Updated_At']
            ];
        }, $users);
        
        Response::paginated($formattedUsers, $page, $perPage, $total, "Users retrieved successfully");
    }

    /**
     * Get user statistics summary
     * GET /users/stats/summary
     * 
     * @return void
     */
    public function stats() {
        $query = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Account_Status = 'Active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN Account_Status = 'Inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN Account_Status = 'Banned' THEN 1 ELSE 0 END) as banned,
                SUM(CASE WHEN MONTH(Created_At) = MONTH(CURRENT_DATE) AND YEAR(Created_At) = YEAR(CURRENT_DATE) THEN 1 ELSE 0 END) as created_this_month
            FROM Users
            WHERE Is_Deleted = FALSE
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats = $stmt->fetch();
        
        // Ensure numbers are integers
        $response = [
            'total' => (int)$stats['total'],
            'active' => (int)$stats['active'],
            'inactive' => (int)$stats['inactive'],
            'banned' => (int)$stats['banned'],
            'created_this_month' => (int)$stats['created_this_month']
        ];
        
        Response::success($response, "User statistics retrieved successfully");
    }
    
    /**
     * Get single user by ID
     * GET /users/{id}
     * 
     * @param int $id User ID
     * @return void
     */
    public function show($id) {
        $user = $this->findUserById($id);
        
        if (!$user) {
            Response::notFound("User not found");
        }
        
        // Get additional details based on role
        $response = $this->formatUserResponse($user);
        
        // If user is a veterinarian, get vet details
        if ($user['Role_Name'] === 'Veterinarian') {
            $vetDetails = $this->getVeterinarianDetails($id);
            if ($vetDetails) {
                $response['veterinarian_details'] = [
                    'vet_id' => (int)$vetDetails['VetID'],
                    'license_number' => $vetDetails['License_Number'],
                    'specialization' => $vetDetails['Specialization'],
                    'years_experience' => (int)$vetDetails['Years_Experience']
                ];
            }
        }
        
        // Get activity summary (for admin viewing other users)
        if ($this->isAdmin() && $this->user['UserID'] != $id) {
            $response['activity_summary'] = $this->getUserActivitySummary($id);
        }

        // Get adopter stats
        if ($user['Role_Name'] === 'Adopter') {
            $response = array_merge($response, $this->getAdopterStats($id));
        }
        
        Response::success($response, "User retrieved successfully");
    }
    
    /**
     * Create new user (Admin only)
     * POST /users
     * 
     * Required fields:
     * - role_id: Role ID
     * - first_name: First name
     * - last_name: Last name
     * - email: Email address
     * - password: Password (min 8 characters)
     * 
     * Optional fields:
     * - contact_number: Phone number
     * - account_status: Account status (default: Active)
     * - license_number: Required if role is Veterinarian
     * - specialization: For veterinarians
     * - years_experience: For veterinarians
     * 
     * @return void
     */
    public function store() {
        // Validate required fields
        $this->validate([
            'role_id' => 'required|integer',
            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',
            'email' => 'required|email',
            'password' => 'required|min:8|max:100'
        ]);
        
        // Validate optional fields if present
        if ($this->has('contact_number')) {
            $this->validate(['contact_number' => 'phone']);
        }
        
        if ($this->has('account_status')) {
            $this->validate(['account_status' => 'in:Active,Inactive,Banned']);
        }
        
        $email = strtolower(trim($this->input('email')));
        
        // Check if email already exists
        if ($this->emailExists($email)) {
            Response::conflict("Email address is already registered");
        }
        
        // Verify role exists
        $role = $this->findRoleById($this->input('role_id'));
        if (!$role) {
            Response::error("Invalid role ID", 400);
        }
        
        // If role is Veterinarian, validate vet-specific fields
        if ($role['Role_Name'] === 'Veterinarian') {
            $this->validate([
                'license_number' => 'required|max:50'
            ]);
        }
        
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // Create user
            $passwordHash = password_hash($this->input('password'), PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("
                INSERT INTO Users (
                    RoleID, 
                    FirstName, 
                    LastName, 
                    Email, 
                    Contact_Number, 
                    Password_Hash, 
                    Account_Status, 
                    Is_Deleted,
                    Created_At,
                    Updated_At
                ) VALUES (
                    :role_id, 
                    :first_name, 
                    :last_name, 
                    :email, 
                    :contact, 
                    :password, 
                    :status, 
                    FALSE,
                    NOW(),
                    NOW()
                )
            ");
            
            $stmt->execute([
                'role_id' => (int)$this->input('role_id'),
                'first_name' => trim($this->input('first_name')),
                'last_name' => trim($this->input('last_name')),
                'email' => $email,
                'contact' => $this->input('contact_number'),
                'password' => $passwordHash,
                'status' => $this->input('account_status', 'Active')
            ]);
            
            $userId = (int)$this->db->lastInsertId();
            
            // If veterinarian, create vet profile
            if ($role['Role_Name'] === 'Veterinarian') {
                $stmt = $this->db->prepare("
                    INSERT INTO Veterinarians (
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
                
                $stmt->execute([
                    'user_id' => $userId,
                    'license' => trim($this->input('license_number')),
                    'specialization' => $this->input('specialization'),
                    'experience' => (int)$this->input('years_experience', 0)
                ]);
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Log activity
            $this->logActivity(
                'CREATE_USER', 
                "Created new user: {$this->input('first_name')} {$this->input('last_name')} ({$email}) with role: {$role['Role_Name']}"
            );
            
            // Get and return created user
            $newUser = $this->findUserById($userId);
            $response = $this->formatUserResponse($newUser);
            
            if ($role['Role_Name'] === 'Veterinarian') {
                $vetDetails = $this->getVeterinarianDetails($userId);
                if ($vetDetails) {
                    $response['veterinarian_details'] = [
                        'vet_id' => (int)$vetDetails['VetID'],
                        'license_number' => $vetDetails['License_Number'],
                        'specialization' => $vetDetails['Specialization'],
                        'years_experience' => (int)$vetDetails['Years_Experience']
                    ];
                }
            }
            
            Response::created($response, "User created successfully");
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error creating user: " . $e->getMessage());
            Response::serverError("Failed to create user. Please try again.");
        }
    }
    
    /**
     * Update user
     * PUT /users/{id}
     * 
     * @param int $id User ID
     * @return void
     */
    public function update($id) {
        // Find user
        $user = $this->findUserById($id);
        
        if (!$user) {
            Response::notFound("User not found");
        }
        
        // Build update query dynamically based on provided fields
        $updates = [];
        $params = ['id' => $id];
        
        // Update role
        if ($this->has('role_id')) {
            $role = $this->findRoleById($this->input('role_id'));
            if (!$role) {
                Response::error("Invalid role ID", 400);
            }
            $updates[] = "RoleID = :role_id";
            $params['role_id'] = (int)$this->input('role_id');
        }
        
        // Update first name
        if ($this->has('first_name')) {
            $this->validate(['first_name' => 'max:50']);
            $updates[] = "FirstName = :first_name";
            $params['first_name'] = trim($this->input('first_name'));
        }
        
        // Update last name
        if ($this->has('last_name')) {
            $this->validate(['last_name' => 'max:50']);
            $updates[] = "LastName = :last_name";
            $params['last_name'] = trim($this->input('last_name'));
        }
        
        // Update email
        if ($this->has('email')) {
            $this->validate(['email' => 'email']);
            $newEmail = strtolower(trim($this->input('email')));
            
            // Check if email is different and already exists
            if (strtolower($user['Email']) !== $newEmail && $this->emailExists($newEmail, $id)) {
                Response::conflict("Email address is already registered");
            }
            
            $updates[] = "Email = :email";
            $params['email'] = $newEmail;
        }
        
        // Update contact number
        if ($this->has('contact_number')) {
            if (!empty($this->input('contact_number'))) {
                $this->validate(['contact_number' => 'phone']);
            }
            $updates[] = "Contact_Number = :contact";
            $params['contact'] = $this->input('contact_number');
        }

        // Update username
        if ($this->has('username')) {
            $this->validate(['username' => 'max:50']);
            $newUsername = trim($this->input('username'));
            
            // Check if username differs and exists
            if ($user['Username'] !== $newUsername) {
                // Check uniqueness
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM Users WHERE Username = :username AND UserID != :id");
                $stmt->execute(['username' => $newUsername, 'id' => $id]);
                if ($stmt->fetch()['count'] > 0) {
                    Response::conflict("Username is already taken");
                }
                
                $updates[] = "Username = :username";
                $params['username'] = $newUsername;
            }
        }
        
        // Update account status
        if ($this->has('account_status')) {
            $this->validate(['account_status' => 'in:Active,Inactive,Banned']);
            
            // Prevent admin from deactivating their own account
            if ($this->user['UserID'] == $id && $this->input('account_status') !== 'Active') {
                Response::error("You cannot deactivate your own account", 400);
            }
            
            $updates[] = "Account_Status = :status";
            $params['status'] = $this->input('account_status');
        }
        
        // Update password (optional)
        if ($this->has('password') && !empty($this->input('password'))) {
            $this->validate(['password' => 'min:8|max:100']);
            $updates[] = "Password_Hash = :password";
            $params['password'] = password_hash($this->input('password'), PASSWORD_DEFAULT);
        }
        
        // Check if there's anything to update
        if (empty($updates)) {
            Response::error("No valid fields provided for update", 400);
        }
        
        // Add updated timestamp
        $updates[] = "Updated_At = NOW()";
        
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // Update user
            $query = "UPDATE Users SET " . implode(', ', $updates) . " WHERE UserID = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            // Update veterinarian details if applicable
            if ($user['Role_Name'] === 'Veterinarian' || 
                ($this->has('role_id') && $this->findRoleById($this->input('role_id'))['Role_Name'] === 'Veterinarian')) {
                
                $this->updateVeterinarianDetails($id);
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Log activity
            $this->logActivity(
                'UPDATE_USER', 
                "Updated user ID: {$id} ({$user['Email']})"
            );
            
            // Get and return updated user
            $updatedUser = $this->findUserById($id);
            $response = $this->formatUserResponse($updatedUser);
            
            if ($updatedUser['Role_Name'] === 'Veterinarian') {
                $vetDetails = $this->getVeterinarianDetails($id);
                if ($vetDetails) {
                    $response['veterinarian_details'] = [
                        'vet_id' => (int)$vetDetails['VetID'],
                        'license_number' => $vetDetails['License_Number'],
                        'specialization' => $vetDetails['Specialization'],
                        'years_experience' => (int)$vetDetails['Years_Experience']
                    ];
                }
            }
            
            Response::success($response, "User updated successfully");
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error updating user: " . $e->getMessage());
            Response::serverError("Failed to update user. Please try again.");
        }
    }
    
    /**
     * Delete user (soft delete)
     * DELETE /users/{id}
     * 
     * @param int $id User ID
     * @return void
     */
    public function destroy($id) {
        // Find user
        $user = $this->findUserById($id);
        
        if (!$user) {
            Response::notFound("User not found");
        }
        
        // Prevent self-deletion
        if ($this->user['UserID'] == $id) {
            Response::error("You cannot delete your own account", 400);
        }
        
        // Prevent deletion of last admin
        if ($user['Role_Name'] === 'Admin') {
            $adminCount = $this->getAdminCount();
            if ($adminCount <= 1) {
                Response::error("Cannot delete the last administrator account", 400);
            }
        }
        
        // Check for dependencies (optional - you might want to handle these differently)
        $dependencies = $this->checkUserDependencies($id);
        if (!empty($dependencies)) {
            // Log the dependencies but proceed with soft delete
            $this->logActivity(
                'DELETE_USER_WARNING',
                "User ID: {$id} has dependencies: " . json_encode($dependencies)
            );
        }
        
        // Soft delete
        $stmt = $this->db->prepare("
            UPDATE Users 
            SET Is_Deleted = TRUE, 
                Account_Status = 'Inactive',
                Updated_At = NOW()
            WHERE UserID = :id
        ");
        $stmt->execute(['id' => $id]);
        
        // Log activity
        $this->logActivity(
            'DELETE_USER', 
            "Deleted user ID: {$id} - {$user['FirstName']} {$user['LastName']} ({$user['Email']})"
        );
        
        Response::success(null, "User deleted successfully");
    }
    
    /**
     * Get current user's profile
     * GET /profile
     * 
     * @return void
     */
    public function profile() {
        $user = $this->findUserById($this->user['UserID']);
        
        if (!$user) {
            Response::notFound("User profile not found");
        }

        $response = $this->formatUserResponse($user);
        
        // Add stats based on role
        if ($user['Role_Name'] === 'Adopter') {
            $response['stats'] = $this->getAdopterStats($user['UserID']);
        } else if ($user['Role_Name'] === 'Veterinarian') {
            $response['stats'] = $this->getVetStats($user['UserID']);
        } else {
            $response['stats'] = $this->getUserStats($user['UserID']);
        }

        // Add veterinarian details if applicable
        if ($user['Role_Name'] === 'Veterinarian') {
            $response['veterinarian_details'] = $this->getVeterinarianDetails($user['UserID']);
        }
        
        // Add activity summary for own profile
        $response['activity_summary'] = $this->getUserActivitySummary($this->user['UserID']);

        Response::success($response, "Profile retrieved successfully");
    }
    
    /**
     * Update current user's profile
     * PUT /profile
     * 
     * Allowed fields:
     * - first_name
     * - last_name
     * - contact_number
     * 
     * @return void
     */
    public function updateProfile() {
        $updates = [];
        $params = ['id' => $this->user['UserID']];
        
        // Update first name
        if ($this->has('first_name')) {
            $this->validate(['first_name' => 'required|max:50']);
            $updates[] = "FirstName = :first_name";
            $params['first_name'] = trim($this->input('first_name'));
        }
        
        // Update last name
        if ($this->has('last_name')) {
            $this->validate(['last_name' => 'required|max:50']);
            $updates[] = "LastName = :last_name";
            $params['last_name'] = trim($this->input('last_name'));
        }
        
        // Update contact number
        if ($this->has('contact_number')) {
            if (!empty($this->input('contact_number'))) {
                $this->validate(['contact_number' => 'phone']);
            }
            $updates[] = "Contact_Number = :contact";
            $params['contact'] = $this->input('contact_number');
        }

        // Update username
        if ($this->has('username')) {
            $this->validate(['username' => 'required|max:50']);
            $newUsername = trim($this->input('username'));
            
            // Check if username differs and exists
            if ($this->user['Username'] !== $newUsername) {
                // Check uniqueness
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM Users WHERE Username = :username AND UserID != :id");
                $stmt->execute(['username' => $newUsername, 'id' => $this->user['UserID']]);
                if ($stmt->fetch()['count'] > 0) {
                    Response::conflict("Username is already taken");
                }
                
                $updates[] = "Username = :username";
                $params['username'] = $newUsername;
            }
        }

        // Update address
        if ($this->has('address')) {
            $updates[] = "Address = :address";
            $params['address'] = trim($this->input('address'));
        }

        // Update preferences
        if ($this->has('preferences')) {
            $updates[] = "Preferences = :preferences";
            $params['preferences'] = json_encode($this->input('preferences'));
        }
        
        // Check if vet update is needed
        $isVetUpdate = $this->user['Role_Name'] === 'Veterinarian' && 
                      ($this->has('license_number') || $this->has('specialization') || $this->has('years_experience'));
        
        if (empty($updates) && !$isVetUpdate) {
            Response::error("No valid fields provided for update", 400);
        }
        
        // Add updated timestamp
        $updates[] = "Updated_At = NOW()";
        
        // Update profile if there are user fields to update
        if (count($updates) > 1) { // > 1 because Updated_At is always added
            $query = "UPDATE Users SET " . implode(', ', $updates) . " WHERE UserID = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
        }
        
        // Update veterinarian details if user is a vet and vet fields are provided
        if ($isVetUpdate) {
            $this->updateVeterinarianDetails($this->user['UserID']);
        }
        
        // Log activity
        $this->logActivity('UPDATE_PROFILE', "User updated their profile");
        
        // Get and return updated profile
        $user = $this->findUserById($this->user['UserID']);
        $response = $this->formatUserResponse($user);
        
        if ($user['Role_Name'] === 'Veterinarian') {
            $vetDetails = $this->getVeterinarianDetails($user['UserID']);
            if ($vetDetails) {
                $response['veterinarian_details'] = [
                    'vet_id' => (int)$vetDetails['VetID'],
                    'license_number' => $vetDetails['License_Number'],
                    'specialization' => $vetDetails['Specialization'],
                    'years_experience' => (int)$vetDetails['Years_Experience'],
                    'clinic_name' => $vetDetails['Clinic_Name'] ?? null,
                    'bio' => $vetDetails['Bio'] ?? null
                ];
            }
        }
        
        Response::success($response, "Profile updated successfully");
    }
    
    /**
     * Change password
     * PUT /profile/password
     * 
     * Required fields:
     * - current_password: Current password
     * - new_password: New password (min 8 characters)
     * - new_password_confirmation: Confirm new password (optional but recommended)
     * 
     * @return void
     */
    public function changePassword() {
        // Validate input
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|max:100'
        ]);
        
        // Verify current password
        $stmt = $this->db->prepare("SELECT Password_Hash FROM Users WHERE UserID = :id");
        $stmt->execute(['id' => $this->user['UserID']]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($this->input('current_password'), $user['Password_Hash'])) {
            Response::error("Current password is incorrect", 400);
        }
        
        // Check if new password is same as current
        if (password_verify($this->input('new_password'), $user['Password_Hash'])) {
            Response::error("New password must be different from current password", 400);
        }
        
        // Check password confirmation if provided
        if ($this->has('new_password_confirmation')) {
            if ($this->input('new_password') !== $this->input('new_password_confirmation')) {
                Response::error("Password confirmation does not match", 400);
            }
        }
        
        // Update password
        $newHash = password_hash($this->input('new_password'), PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("
            UPDATE Users 
            SET Password_Hash = :password, Updated_At = NOW() 
            WHERE UserID = :id
        ");
        $stmt->execute([
            'password' => $newHash,
            'id' => $this->user['UserID']
        ]);
        
        // Log activity
        $this->logActivity('CHANGE_PASSWORD', "User changed their password");
        
        Response::success(null, "Password changed successfully");
    }

    /**
     * Delete own account
     * DELETE /profile
     * 
     * @return void
     */
    public function deleteAccount() {
        // Validate password confirmation
        $this->validate(['password' => 'required']);
        
        // Verify password
        $stmt = $this->db->prepare("SELECT Password_Hash FROM Users WHERE UserID = :id");
        $stmt->execute(['id' => $this->user['UserID']]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($this->input('password'), $user['Password_Hash'])) {
            Response::error("Incorrect password", 400);
        }
        
        // Prevent deletion if last admin (though UI shouldn't allow this for admin usually)
        if ($this->user['Role_Name'] === 'Admin') {
            $adminCount = $this->getAdminCount();
            if ($adminCount <= 1) {
                Response::error("Cannot delete the last administrator account", 400);
            }
        }
        
        // Soft delete
        $stmt = $this->db->prepare("
            UPDATE Users 
            SET Is_Deleted = TRUE, 
                Account_Status = 'Inactive',
                Updated_At = NOW()
            WHERE UserID = :id
        ");
        $stmt->execute(['id' => $this->user['UserID']]);
        
        // Log activity (before token invalidation effectively)
        $this->logActivity('DELETE_ACCOUNT', "User deleted their own account");
        
        Response::success(null, "Account deleted successfully");
    }
    
    /**
     * Upload profile avatar
     * POST /profile/avatar
     * 
     * @return void
     */
    public function uploadAvatar() {
        try {
            if (!$this->getFile('avatar')) {
                Response::error("No file uploaded", 400);
            }

            // Use BaseController's saveFile method
            $relativePath = $this->saveFile('avatar', 'avatars');
            
            if ($relativePath) {
                // Delete old avatar
                $this->deleteOldAvatar($this->user['UserID']);
                
                // Get full URL
                $avatarUrl = $this->getFileUrl($relativePath);
                
                // Update database
                $stmt = $this->db->prepare("
                    UPDATE Users 
                    SET Avatar_Url = :url, Updated_At = NOW() 
                    WHERE UserID = :id
                ");
                $stmt->execute([
                    'url' => $avatarUrl,
                    'id' => $this->user['UserID']
                ]);
                
                $this->logActivity('UPDATE_PROFILE', "User uploaded a new profile photo");
                
                Response::success(['avatar_url' => $avatarUrl], "Profile photo updated successfully");
            } else {
                // Failed to save (validation or move failure)
                // Check if there was an upload error code
                $file = $this->getFile('avatar');
                $errorMsg = "Failed to upload file.";
                
                if ($file && $file['error'] !== UPLOAD_ERR_OK) {
                    switch ($file['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                            $errorMsg = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $errorMsg = "The uploaded file exceeds the MAX_FILE_SIZE directive";
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $errorMsg = "The uploaded file was only partially uploaded";
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $errorMsg = "No file was uploaded";
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $errorMsg = "Missing a temporary folder";
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $errorMsg = "Failed to write file to disk";
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $errorMsg = "File upload stopped by extension";
                            break;
                        default:
                            $errorMsg = "Unknown upload error";
                            break;
                    }
                    Response::serverError($errorMsg); // Return as server error or bad request? 500 is fine for sys errors.
                }
                
                Response::error($errorMsg . " Please ensure it is an image (JPG, PNG, GIF, WebP) and under 5MB.", 400);
            }
        } catch (Throwable $e) {
            error_log("Upload Error: " . $e->getMessage());
            Response::serverError("Crash: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        }
    }

    /**
     * Remove profile avatar
     * DELETE /profile/avatar
     * 
     * @return void
     */
    public function removeAvatar() {
        $this->deleteOldAvatar($this->user['UserID']);

        $stmt = $this->db->prepare("
            UPDATE Users 
            SET Avatar_Url = NULL, Updated_At = NOW() 
            WHERE UserID = :id
        ");
        $stmt->execute(['id' => $this->user['UserID']]);
        
        $this->logActivity('UPDATE_PROFILE', "User removed their profile photo");
        
        Response::success(null, "Profile photo removed successfully");
    }

    /**
     * Delete old avatar file
     * 
     * @param int $userId
     */
    private function deleteOldAvatar($userId) {
        $stmt = $this->db->prepare("SELECT Avatar_Url FROM Users WHERE UserID = :id");
        $stmt->execute(['id' => $userId]);
        $current = $stmt->fetch();

        if ($current && !empty($current['Avatar_Url'])) {
            // Extract filename from URL to reconstruct path
            // Assumes structure: .../uploads/avatars/filename.ext
            $filename = basename($current['Avatar_Url']);
            
            // Use BaseController's deleteFile
            // Path relative to uploads/
            $relativePath = 'avatars/' . $filename;
            
            $this->deleteFile($relativePath);
        }
    }

    /**
     * List all roles
     * GET /roles
     * 
     * @return void
     */
    public function listRoles() {
        $stmt = $this->db->prepare("
            SELECT 
                RoleID as id,
                Role_Name as name,
                Created_At as created_at
            FROM Roles 
            ORDER BY RoleID ASC
        ");
        $stmt->execute();
        
        $roles = $stmt->fetchAll();
        
        // Add user count for each role
        foreach ($roles as &$role) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM Users 
                WHERE RoleID = :role_id AND Is_Deleted = FALSE
            ");
            $stmt->execute(['role_id' => $role['id']]);
            $role['user_count'] = (int)$stmt->fetch()['count'];
        }
        
        Response::success($roles, "Roles retrieved successfully");
    }
    
    // ==========================================
    // PRIVATE HELPER METHODS
    // ==========================================
    
    /**
     * Find user by ID
     * 
     * @param int $id User ID
     * @return array|false User data or false
     */
    private function findUserById($id) {
        $stmt = $this->db->prepare("
            SELECT 
                u.UserID,
                u.RoleID,
                u.FirstName,
                u.LastName,
                u.Username,
                u.Email,
                u.Contact_Number,
                u.Address,
                u.Avatar_Url,
                u.Account_Status,
                u.Is_Deleted,
                u.Preferences,
                u.Created_At,
                u.Updated_At,
                r.Role_Name
            FROM Users u
            JOIN Roles r ON u.RoleID = r.RoleID
            WHERE u.UserID = :id AND u.Is_Deleted = FALSE
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Find role by ID
     * 
     * @param int $id Role ID
     * @return array|false Role data or false
     */
    private function findRoleById($id) {
        $stmt = $this->db->prepare("SELECT RoleID, Role_Name FROM Roles WHERE RoleID = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Check if email exists
     * 
     * @param string $email Email address
     * @param int|null $excludeUserId User ID to exclude (for updates)
     * @return bool
     */
    private function emailExists($email, $excludeUserId = null) {
        $query = "SELECT COUNT(*) as count FROM Users WHERE Email = :email AND Is_Deleted = FALSE";
        $params = ['email' => strtolower($email)];
        
        if ($excludeUserId) {
            $query .= " AND UserID != :user_id";
            $params['user_id'] = $excludeUserId;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return (int)$stmt->fetch()['count'] > 0;
    }
    
    /**
     * Get veterinarian details
     * 
     * @param int $userId User ID
     * @return array|false Vet details or false
     */
    private function getVeterinarianDetails($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                VetID as vet_id,
                License_Number as license_number,
                Specialization as specialization,
                Years_Experience as years_experience,
                Clinic_Name as clinic_name,
                Bio as bio,
                Created_At as created_at,
                Updated_At as updated_at
            FROM Veterinarians 
            WHERE UserID = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Update veterinarian details
     * 
     * @param int $userId User ID
     * @return bool
     */
    private function updateVeterinarianDetails($userId) {
        // Check if vet record exists
        $stmt = $this->db->prepare("SELECT VetID FROM Veterinarians WHERE UserID = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $vet = $stmt->fetch();
        
        if ($vet) {
            // Update existing
            $updates = [];
            $params = ['user_id' => $userId];
            
            if ($this->has('license_number')) {
                $updates[] = "License_Number = :license";
                $params['license'] = trim($this->input('license_number'));
            }
            
            if ($this->has('specialization')) {
                $updates[] = "Specialization = :specialization";
                $params['specialization'] = trim($this->input('specialization'));
            }
            
            if ($this->has('years_experience')) {
                $updates[] = "Years_Experience = :experience";
                $params['experience'] = (int)$this->input('years_experience');
            }

            if ($this->has('clinic_name')) {
                $updates[] = "Clinic_Name = :clinic_name";
                $params['clinic_name'] = trim($this->input('clinic_name'));
            }

            if ($this->has('bio')) {
                $updates[] = "Bio = :bio";
                $params['bio'] = trim($this->input('bio'));
            }
            
            if (!empty($updates)) {
                $updates[] = "Updated_At = NOW()";
                $query = "UPDATE Veterinarians SET " . implode(', ', $updates) . " WHERE UserID = :user_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute($params);
            }
            
            return true;
        } else {
            // Create new vet record if license number is provided
            if ($this->has('license_number')) {
                $stmt = $this->db->prepare("
                    INSERT INTO Veterinarians (UserID, License_Number, Specialization, Years_Experience, Created_At, Updated_At)
                    VALUES (:user_id, :license, :specialization, :experience, NOW(), NOW())
                ");
                $stmt->execute([
                    'user_id' => $userId,
                    'license' => trim($this->input('license_number')),
                    'specialization' => $this->input('specialization'),
                    'experience' => (int)$this->input('years_experience', 0)
                ]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Format user response
     * 
     * @param array $user Raw user data
     * @return array Formatted user data
     */
    private function formatUserResponse($user) {
        return [
            'id' => (int)$user['UserID'],
            'role_id' => (int)$user['RoleID'],
            'role_name' => $user['Role_Name'],
            'role' => $user['Role_Name'],
            'first_name' => $user['FirstName'],
            'last_name' => $user['LastName'],
            'full_name' => $user['FirstName'] . ' ' . $user['LastName'],
            'username' => $user['Username'],
            'email' => $user['Email'],
        'avatar_url' => $user['Avatar_Url'] ?? null,
        'address' => $user['Address'] ?? null,
            'contact_number' => $user['Contact_Number'],
            'account_status' => $user['Account_Status'],
            'preferences' => isset($user['Preferences']) ? json_decode($user['Preferences'], true) : [],
            'created_at' => $user['Created_At'],
            'updated_at' => $user['Updated_At']
        ];
    }
    
    /**
     * Get user activity summary
     * 
     * @param int $userId User ID
     * @return array Activity summary
     */
    private function getUserActivitySummary($userId) {
        // Get last login
        $stmt = $this->db->prepare("
            SELECT Log_Date 
            FROM Activity_Logs 
            WHERE UserID = :user_id AND Action_Type = 'LOGIN'
            ORDER BY Log_Date DESC 
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        $lastLogin = $stmt->fetch();
        
        // Get activity count
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Activity_Logs 
            WHERE UserID = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        $activityCount = $stmt->fetch()['count'];
        
        // Get activity count this month
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Activity_Logs 
            WHERE UserID = :user_id
            AND MONTH(Log_Date) = MONTH(CURRENT_DATE)
            AND YEAR(Log_Date) = YEAR(CURRENT_DATE)
        ");
        $stmt->execute(['user_id' => $userId]);
        $activityThisMonth = $stmt->fetch()['count'];
        
        return [
            'last_login' => $lastLogin ? $lastLogin['Log_Date'] : null,
            'total_activities' => (int)$activityCount,
            'activities_this_month' => (int)$activityThisMonth
        ];
    }

    /**
     * Get user statistics
     * 
     * @param int $userId User ID
     * @return array User statistics
     */
    private function getUserStats($userId) {
        // Animals registered by this user (from Activity_Logs since Animals table doesn't track creator)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Activity_Logs 
            WHERE UserID = :user_id AND Action_Type = 'CREATE_ANIMAL'
        ");
        $stmt->execute(['user_id' => $userId]);
        $animalsRegistered = (int)$stmt->fetch()['count'];
        
        // Adoptions processed by this user
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Adoption_Requests 
            WHERE Processed_By_UserID = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        $adoptionsProcessed = (int)$stmt->fetch()['count'];
        
        // Invoices created by this user
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Invoices 
            WHERE Issued_By_UserID = :user_id AND Is_Deleted = FALSE
        ");
        $stmt->execute(['user_id' => $userId]);
        $invoicesCreated = (int)$stmt->fetch()['count'];
        
        return [
            'animals_registered' => $animalsRegistered,
            'adoptions_processed' => $adoptionsProcessed,
            'invoices_created' => $invoicesCreated
        ];
    }
    
    /**
     * Get veterinarian statistics
     * 
     * @param int $userId User ID
     * @return array Veterinarian statistics
     */
    private function getVetStats($userId) {
        // Get VetID for this user
        $stmt = $this->db->prepare("SELECT VetID FROM Veterinarians WHERE UserID = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $vet = $stmt->fetch();
        
        if (!$vet) {
            return [
                'medical_records' => 0,
                'animals_treated' => 0,
                'records_this_month' => 0
            ];
        }
        
        $vetId = $vet['VetID'];
        
        // Total medical records by this vet
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Medical_Records 
            WHERE VetID = :vet_id
        ");
        $stmt->execute(['vet_id' => $vetId]);
        $medicalRecords = (int)$stmt->fetch()['count'];
        
        // Unique animals treated
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT AnimalID) as count 
            FROM Medical_Records 
            WHERE VetID = :vet_id
        ");
        $stmt->execute(['vet_id' => $vetId]);
        $animalsTreated = (int)$stmt->fetch()['count'];
        
        // Records this month
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Medical_Records 
            WHERE VetID = :vet_id
            AND MONTH(Created_At) = MONTH(CURRENT_DATE)
            AND YEAR(Created_At) = YEAR(CURRENT_DATE)
        ");
        $stmt->execute(['vet_id' => $vetId]);
        $recordsThisMonth = (int)$stmt->fetch()['count'];
        
        return [
            'medical_records' => $medicalRecords,
            'animals_treated' => $animalsTreated,
            'records_this_month' => $recordsThisMonth
        ];
    }
    
    /**
     * Get user activity summary
    
    /**
     * Get adopter statistics
     * 
     * @param int $userId User ID
     * @return array Adopter statistics
     */
    private function getAdopterStats($userId) {
        // Total Requests
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Adoption_Requests 
            WHERE Adopter_UserID = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        $total = (int)$stmt->fetch()['count'];
        
        // Completed (Adopted/Approved/Completed)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Adoption_Requests 
            WHERE Adopter_UserID = :user_id AND Status IN ('Approved', 'Completed')
        ");
        $stmt->execute(['user_id' => $userId]);
        $completed = (int)$stmt->fetch()['count'];

        // Pending Requests
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Adoption_Requests 
            WHERE Adopter_UserID = :user_id AND Status IN ('Pending', 'Interview Scheduled')
        ");
        $stmt->execute(['user_id' => $userId]);
        $pending = (int)$stmt->fetch()['count'];
        
        return [
            'adoption_requests' => $total,
            'completed_adoptions' => $completed,
            'pending_requests' => $pending
        ];
    }

    /**
     * Get admin count
     * 
     * @return int Number of admin users
     */
    private function getAdminCount() {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Users u
            JOIN Roles r ON u.RoleID = r.RoleID
            WHERE r.Role_Name = 'Admin' AND u.Is_Deleted = FALSE AND u.Account_Status = 'Active'
        ");
        $stmt->execute();
        return (int)$stmt->fetch()['count'];
    }
    
    /**
     * Check user dependencies before deletion
     * 
     * @param int $userId User ID
     * @return array Dependencies
     */
    private function checkUserDependencies($userId) {
        $dependencies = [];
        
        // Check adoption requests
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Adoption_Requests 
            WHERE Adopter_UserID = :user_id AND Status IN ('Pending', 'Interview Scheduled', 'Approved')
        ");
        $stmt->execute(['user_id' => $userId]);
        $adoptions = (int)$stmt->fetch()['count'];
        if ($adoptions > 0) {
            $dependencies['pending_adoptions'] = $adoptions;
        }
        
        // Check unpaid invoices
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Invoices 
            WHERE Payer_UserID = :user_id AND Status = 'Unpaid' AND Is_Deleted = FALSE
        ");
        $stmt->execute(['user_id' => $userId]);
        $invoices = (int)$stmt->fetch()['count'];
        if ($invoices > 0) {
            $dependencies['unpaid_invoices'] = $invoices;
        }
        
        // Check if veterinarian with medical records
        $stmt = $this->db->prepare("
            SELECT v.VetID FROM Veterinarians v WHERE v.UserID = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        $vet = $stmt->fetch();
        
        if ($vet) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM Medical_Records 
                WHERE VetID = :vet_id
            ");
            $stmt->execute(['vet_id' => $vet['VetID']]);
            $records = (int)$stmt->fetch()['count'];
            if ($records > 0) {
                $dependencies['medical_records'] = $records;
            }
        }
        
        return $dependencies;
    }
}