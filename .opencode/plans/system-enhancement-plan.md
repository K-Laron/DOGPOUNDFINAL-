# Catarman Dog Pound - System Enhancement Plan

## Overview

This plan covers comprehensive system enhancements without changing the tech stack:

1. **Security Hardening** (High Priority)
2. **Database Transactions** (High Priority)
3. **Code Refactoring** (Medium Priority)
4. **Data Export Features** (Medium Priority)
5. **API Documentation** (Medium Priority)
6. **Bulk Operations** (Low Priority)
7. **Advanced Reporting** (Low Priority)

---

## Phase 1: Security Hardening

### 1.1 Password Reset Implementation

**Current State:** `AuthController.php:256-291` has placeholder methods that return "not implemented"

**Files to Modify:**
- `database/schema.sql` - Add password_reset_tokens table
- `backend/app/controllers/AuthController.php` - Implement password reset logic
- `backend/app/utils/TokenGenerator.php` - New utility for secure token generation

**Database Migration:**
```sql
CREATE TABLE IF NOT EXISTS Password_Reset_Tokens (
    TokenID INT PRIMARY KEY AUTO_INCREMENT,
    UserID INT NOT NULL,
    Token VARCHAR(64) NOT NULL UNIQUE,
    Expires_At DATETIME NOT NULL,
    Used BOOLEAN DEFAULT FALSE,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE,
    INDEX idx_token (Token),
    INDEX idx_expires (Expires_At)
) ENGINE=InnoDB;
```

**Implementation Steps:**
1. Create `TokenGenerator` utility class
2. Update `forgotPassword()` to:
   - Generate secure random token (32 bytes, hex encoded = 64 chars)
   - Store token with 1-hour expiry
   - Log the action (email sending is optional/future)
3. Update `resetPassword()` to:
   - Validate token exists and not expired
   - Update user password
   - Mark token as used
   - Invalidate all user sessions (token versioning)

**Test Cases:**
- `testForgotPasswordGeneratesToken`
- `testForgotPasswordReturnsSuccessForNonexistentEmail` (security)
- `testResetPasswordWithValidToken`
- `testResetPasswordWithExpiredToken`
- `testResetPasswordWithUsedToken`
- `testResetPasswordInvalidatesOtherSessions`

---

### 1.2 JWT Token Invalidation (Logout Fix)

**Current State:** `AuthController.php:232-253` - logout doesn't actually invalidate tokens

**Files to Modify:**
- `database/schema.sql` - Add token_version column to Users
- `backend/app/utils/JWT.php` - Include token_version in payload/verification
- `backend/app/controllers/AuthController.php` - Increment version on logout

**Database Migration:**
```sql
ALTER TABLE Users ADD COLUMN Token_Version INT DEFAULT 1;
```

**Implementation Steps:**
1. Add `Token_Version` column to Users table
2. Update `JWT::generate()` to include `token_version` in payload
3. Update `JWT::verify()` to check token_version matches database
4. Update `logout()` to increment token_version (invalidates all tokens)
5. Update `logoutAll()` to increment token_version

**Test Cases:**
- `testLogoutInvalidatesToken`
- `testOldTokenRejectedAfterLogout`
- `testLogoutAllInvalidatesAllSessions`
- `testNewTokenWorksAfterLogout`

---

### 1.3 CSRF Protection (Optional - JWT already provides protection)

**Current State:** No CSRF tokens, but JWT in Authorization header provides implicit protection

**Recommendation:** Add CSRF protection for cookie-based sessions if needed in future.
For now, document that the API is protected by JWT Bearer tokens.

---

## Phase 2: Database Transactions

### 2.1 AnimalController::updateStatus() Transaction Fix

**Location:** `backend/app/controllers/AnimalController.php:356-423`

**Issue:** Status update + invoice creation are not atomic. If invoice creation fails, status is already changed.

**Fix:**
```php
public function updateStatus($id)
{
    // ... validation ...
    
    $this->db->beginTransaction();
    
    try {
        // Update animal status
        $stmt = $this->db->prepare("UPDATE Animals SET Current_Status = :status WHERE AnimalID = :id");
        $stmt->execute(['status' => $newStatus, 'id' => $id]);
        
        // Create invoice if reclaimed
        if ($newStatus === 'Reclaimed' && $reclaimingUserId) {
            // ... invoice creation ...
        }
        
        $this->db->commit();
        $this->logActivity('UPDATE_ANIMAL_STATUS', "...");
        
    } catch (Exception $e) {
        $this->db->rollBack();
        Response::serverError("Failed to update status: " . $e->getMessage());
    }
}
```

---

### 2.2 Other Transaction Fixes

**BillingController - Payment Processing:**
- Location: `backend/app/controllers/BillingController.php`
- Issue: Payment creation + invoice status update should be atomic

**InventoryController - Stock Adjustments:**
- Location: `backend/app/controllers/InventoryController.php`
- Issue: Multiple inventory adjustments should be atomic

---

## Phase 3: Code Refactoring

### 3.1 Fix N+1 Query in UserController::listRoles()

**Location:** `backend/app/controllers/UserController.php:989-997`

**Current Code:**
```php
foreach ($roles as &$role) {
    $countStmt = $this->db->prepare("SELECT COUNT(*) FROM Users WHERE RoleID = :role_id");
    $countStmt->execute(['role_id' => $role['RoleID']]);
    $role['user_count'] = $countStmt->fetchColumn();
}
```

**Fixed Code:**
```php
$stmt = $this->db->prepare("
    SELECT r.*, COUNT(u.UserID) as user_count
    FROM Roles r
    LEFT JOIN Users u ON r.RoleID = u.RoleID AND u.Is_Deleted = FALSE
    GROUP BY r.RoleID
    ORDER BY r.Role_Name
");
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

---

### 3.2 Controllers Use Models Consistently

**Issue:** Controllers often bypass models with direct SQL

**Refactoring Pattern:**
```php
// Before (in controller)
$stmt = $this->db->prepare("SELECT * FROM Animals WHERE AnimalID = :id");
$stmt->execute(['id' => $id]);
$animal = $stmt->fetch();

// After (using model)
$animalModel = new Animal($this->db);
$animal = $animalModel->find($id);
```

**Controllers to Refactor:**
- `AdoptionController` - Use `AdoptionRequest` model
- `AnimalController` - Use `Animal` model consistently
- `UserController` - Use `User` model consistently
- `BillingController` - Create `Invoice` and `Payment` model methods

---

## Phase 4: Data Export Features

### 4.1 Create ExportService Utility

**New File:** `backend/app/utils/ExportService.php`

**Features:**
- CSV export for any data array
- Excel export using PhpSpreadsheet (optional, requires composer package)
- PDF export using TCPDF or FPDF (optional, requires composer package)
- Generic interface for all export types

**Implementation:**
```php
<?php
class ExportService {
    /**
     * Export data to CSV
     */
    public static function toCSV(array $data, string $filename, array $headers = []): void {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Write headers
        if (!empty($headers)) {
            fputcsv($output, $headers);
        } elseif (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export data to JSON (for client-side processing)
     */
    public static function toJSON(array $data, string $filename): void {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}
```

---

### 4.2 Add Export Endpoints

**Animals Export:**
```
GET /api/v1/animals/export?format=csv&status=Available
GET /api/v1/animals/export?format=json&type=Dog
```

**Users Export:**
```
GET /api/v1/users/export?format=csv&role=Adopter
```

**Adoptions Export:**
```
GET /api/v1/adoptions/export?format=csv&status=Completed
```

**Invoices/Payments Export:**
```
GET /api/v1/billing/invoices/export?format=csv&from=2024-01-01&to=2024-12-31
GET /api/v1/billing/payments/export?format=csv
```

**Inventory Export:**
```
GET /api/v1/inventory/export?format=csv
```

---

## Phase 5: API Documentation

### 5.1 OpenAPI/Swagger Specification

**New File:** `backend/openapi.yaml`

**Structure:**
```yaml
openapi: 3.0.3
info:
  title: Catarman Dog Pound API
  version: 1.0.0
  description: API for the Catarman Dog Pound Management System

servers:
  - url: http://localhost:8000/api/v1
    description: Development server

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
  
  schemas:
    Animal:
      type: object
      properties:
        AnimalID:
          type: integer
        Name:
          type: string
        Type:
          type: string
          enum: [Dog, Cat, Other]
        # ... more properties

paths:
  /auth/login:
    post:
      summary: User login
      tags: [Authentication]
      # ... request/response definitions

  /animals:
    get:
      summary: List animals
      tags: [Animals]
      security:
        - bearerAuth: []
      # ... request/response definitions
```

**Endpoints to Document:**
- Authentication (6 endpoints)
- Animals (10 endpoints)
- Users (8 endpoints)
- Adoptions (6 endpoints)
- Medical Records (6 endpoints)
- Inventory (6 endpoints)
- Billing (8 endpoints)
- Dashboard (4 endpoints)
- System (2 endpoints)

---

### 5.2 Swagger UI Integration (Optional)

**New File:** `backend/public/docs/index.html`

Serve Swagger UI to visualize the API documentation.

---

## Phase 6: Bulk Operations

### 6.1 Bulk Status Update

**New Endpoint:** `PATCH /api/v1/animals/bulk/status`

**Request:**
```json
{
    "animal_ids": [1, 2, 3, 4, 5],
    "status": "In Treatment"
}
```

**Implementation:**
```php
public function bulkUpdateStatus() {
    $this->validate([
        'animal_ids' => 'required|array',
        'status' => 'required|in:Available,In Treatment,Quarantine'
    ]);
    
    $animalIds = $this->input('animal_ids');
    $status = $this->input('status');
    
    $this->db->beginTransaction();
    
    try {
        $placeholders = implode(',', array_fill(0, count($animalIds), '?'));
        $stmt = $this->db->prepare("
            UPDATE Animals 
            SET Current_Status = ?, Updated_At = NOW()
            WHERE AnimalID IN ({$placeholders}) AND Is_Deleted = FALSE
        ");
        
        $params = array_merge([$status], $animalIds);
        $stmt->execute($params);
        
        $affected = $stmt->rowCount();
        
        $this->db->commit();
        $this->logActivity('BULK_UPDATE_STATUS', "Updated {$affected} animals to status: {$status}");
        
        Response::success(['affected' => $affected], "{$affected} animals updated");
        
    } catch (Exception $e) {
        $this->db->rollBack();
        Response::serverError("Bulk update failed");
    }
}
```

---

### 6.2 Bulk Delete

**New Endpoint:** `DELETE /api/v1/animals/bulk`

**Request:**
```json
{
    "animal_ids": [1, 2, 3]
}
```

---

### 6.3 Bulk Notification (Future)

**New Endpoint:** `POST /api/v1/notifications/bulk`

Send notifications to multiple users.

---

## Phase 7: Advanced Reporting

### 7.1 Enhanced Dashboard Statistics

**Current State:** Basic counts only

**Enhancements:**
- Trend data (last 7 days, 30 days, 12 months)
- Comparison with previous period
- Average processing times
- Conversion rates (adoption requests to completions)

**New Endpoint:** `GET /api/v1/dashboard/trends`

```json
{
    "intake_trends": {
        "daily": [...],
        "weekly": [...],
        "monthly": [...]
    },
    "adoption_trends": {
        "daily": [...],
        "conversion_rate": 0.45
    },
    "revenue_trends": {
        "daily": [...],
        "total_this_month": 15000,
        "comparison": "+12%"
    }
}
```

---

### 7.2 Custom Report Generation

**New Endpoint:** `POST /api/v1/reports/generate`

**Request:**
```json
{
    "report_type": "animals",
    "filters": {
        "status": "Adopted",
        "date_from": "2024-01-01",
        "date_to": "2024-12-31"
    },
    "group_by": "month",
    "format": "csv"
}
```

---

### 7.3 Scheduled Reports (Future)

Store report configurations and run them on schedule (requires cron job).

---

## Implementation Priority

| Phase | Component | Priority | Effort | Risk |
|-------|-----------|----------|--------|------|
| 1.1 | Password Reset | High | Medium | Low |
| 1.2 | Token Invalidation | High | Low | Low |
| 2.1 | Transaction Fixes | High | Low | Low |
| 3.1 | N+1 Query Fixes | Medium | Low | Low |
| 3.2 | Model Refactoring | Medium | High | Low |
| 4.1 | Export Service | Medium | Medium | Low |
| 4.2 | Export Endpoints | Medium | Medium | Low |
| 5.1 | OpenAPI Spec | Medium | High | None |
| 6.1 | Bulk Operations | Low | Medium | Low |
| 7.1 | Advanced Reporting | Low | High | Low |

---

## Recommended Execution Order

### Sprint 1: Security & Data Integrity (Week 1)
1. Password reset implementation
2. Token invalidation (logout fix)
3. Transaction fixes for AnimalController
4. Transaction fixes for BillingController

### Sprint 2: Code Quality (Week 2)
5. Fix N+1 queries
6. Refactor controllers to use models
7. Add missing model methods

### Sprint 3: Export Features (Week 3)
8. Create ExportService utility
9. Add export endpoints to all controllers
10. Test all export formats

### Sprint 4: Documentation (Week 4)
11. Create OpenAPI specification
12. Document all endpoints
13. Add Swagger UI (optional)

### Sprint 5: Bulk Operations & Reporting (Week 5)
14. Implement bulk status update
15. Implement bulk delete
16. Add advanced dashboard statistics
17. Add custom report generation

---

## File Changes Summary

### New Files
| File | Purpose |
|------|---------|
| `backend/app/utils/TokenGenerator.php` | Secure token generation |
| `backend/app/utils/ExportService.php` | CSV/JSON export utility |
| `backend/openapi.yaml` | API documentation |
| `backend/public/docs/index.html` | Swagger UI (optional) |
| `database/migrations/001_password_reset_tokens.sql` | Password reset table |
| `database/migrations/002_token_version.sql` | Token version column |

### Modified Files
| File | Changes |
|------|---------|
| `backend/app/controllers/AuthController.php` | Password reset, logout fix |
| `backend/app/controllers/AnimalController.php` | Transactions, bulk ops, export |
| `backend/app/controllers/UserController.php` | N+1 fix, export |
| `backend/app/controllers/BillingController.php` | Transactions, export |
| `backend/app/controllers/AdoptionController.php` | Export |
| `backend/app/controllers/InventoryController.php` | Transactions, export |
| `backend/app/controllers/DashboardController.php` | Advanced reporting |
| `backend/app/utils/JWT.php` | Token version support |
| `backend/app/api/animals.php` | New routes |
| `backend/app/api/billing.php` | New routes |

---

## Testing Strategy

All enhancements include corresponding tests:

### Unit Tests
- `TokenGeneratorTest.php`
- `ExportServiceTest.php`
- `JWTTokenVersionTest.php`

### Feature Tests
- `PasswordResetTest.php`
- `LogoutInvalidationTest.php`
- `BulkOperationsTest.php`
- `DataExportTest.php`
- `AdvancedReportingTest.php`

---

## Success Metrics

- [ ] Password reset fully functional
- [ ] Logout properly invalidates tokens
- [ ] All multi-step operations use transactions
- [ ] No N+1 queries in controllers
- [ ] Export available for all major entities
- [ ] API documentation covers all endpoints
- [ ] Bulk operations work for admin users
- [ ] Dashboard shows trend data
- [ ] All tests pass
