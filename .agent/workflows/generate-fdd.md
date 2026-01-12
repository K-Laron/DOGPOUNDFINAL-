---
description: Generate or update the Functional Decomposition Diagram by scanning the entire codebase
---

# LLM Prompt: Functional Decomposition Diagram Generation from Codebase Analysis

> **Purpose:** Instruct an LLM to systematically scan the entire codebase and produce an accurate, up-to-date Functional Decomposition Diagram (FDD) for the **Catarman Dog Pound & Animal Shelter Management System**.

---

## Phase 1: Project Structure Discovery

**Objective:** Understand the high-level architecture and identify key directories.

### Phase 1 Steps

1. **List the root directory contents:**

   ```text
   list_dir c:\Users\TESS LARON\Desktop\dogpound
   ```

2. **Identify the following key areas:**
   - **Backend Controllers:** `backend/app/controllers/`
   - **Backend Models:** `backend/app/models/`
   - **Frontend Pages/Components:** `frontend/assets/js/` or `frontend/src/`
   - **Database Schema:** `database/schema.sql`
   - **Routes:** `backend/routes/api.php`

3. **Document the project structure** in a temporary note before proceeding.

---

## Phase 2: Module Identification

**Objective:** Identify all major functional modules in the system.

### Phase 2 Steps

1. **Scan all controllers** in `backend/app/controllers/`:

   ```text
   find_by_name -d backend/app/controllers -e php
   ```

2. **Map each controller to a functional module:**

   | Controller | Functional Module |
   | --- | --- |
   | `AuthController.php` | 1.0 Access Control |
   | `AnimalController.php` | 2.0 Animal Management |
   | `MedicalController.php` | 3.0 Medical Care |
   | `AdoptionController.php` | 4.0 Adoption Services |
   | `InventoryController.php` | 5.0 Inventory Control |
   | `BillingController.php` | 6.0 Billing & Fees |
   | `DashboardController.php` | 7.0 Reports & Statistics |
   | `UserController.php` | 8.0 Admin Panel (Ops) |
   | `NotificationController.php` | 7.0 Reports & Statistics |

3. **Verify against the Use Case Diagram** in `SYSTEM_DIAGRAMS.md`:
   - Cross-check every actor's use cases map to at least one module.
   - Ensure all modules have corresponding use cases.

---

## Phase 3: Function Extraction per Module

**Objective:** Extract all functions (sub-features) for each module by scanning controller methods.

### Phase 3 Steps

1. **For each controller, list all public methods:**

   ```php
   // Example: In AuthController.php, look for:
   public function login() { ... }
   public function register() { ... }
   public function logout() { ... }
   public function resetPassword() { ... }
   ```

2. **Use the view_file_outline tool** to quickly extract method names:

   ```text
   view_file_outline backend/app/controllers/AuthController.php
   ```

3. **Map methods to sub-functions using this template:**

   | Module | Function ID | Function Name | Source Method |
   | --- | --- | --- | --- |
   | 1.0 Access Control | 1.1 | Secure Login | `AuthController::login()` |
   | 1.0 Access Control | 1.2 | Adopter Registration | `AuthController::register()` |
   | 1.0 Access Control | 1.3 | Change/Reset Password | `AuthController::resetPassword()` |
   | 1.0 Access Control | 1.4 | Logout | `AuthController::logout()` |
   | 2.0 Animal Management | 2.1 | Impound/Intake | `AnimalController::store()` |
   | 2.0 Animal Management | 2.2 | Update Animal Profiles | `AnimalController::update()` |
   | 2.0 Animal Management | 2.3 | Track Adoption Status | `AnimalController::show()` |
   | 2.0 Animal Management | 2.4 | Daily Feeding Records | `AnimalController::recordFeeding()` |
   | ... | ... | ... | ... |

4. **Repeat for all controllers:**
   - `MedicalController.php` → 3.0 Medical Care
   - `AdoptionController.php` → 4.0 Adoption Services
   - `InventoryController.php` → 5.0 Inventory Control
   - `BillingController.php` → 6.0 Billing & Fees
   - `DashboardController.php` → 7.0 Reports & Statistics
   - `UserController.php` → 8.0 Admin Panel

---

## Phase 4: Hierarchy Construction

**Objective:** Build the hierarchical structure of modules and sub-functions.

### Phase 4 Steps

1. **Use this standard hierarchy format:**

   ```text
   SYSTEM
   ├── 1.0 MODULE
   │   ├── 1.1 Sub-function
   │   ├── 1.2 Sub-function
   │   └── 1.3 Sub-function
   ├── 2.0 MODULE
   │   ├── 2.1 Sub-function
   │   └── ...
   └── ...
   ```

2. **Ensure each module has 3-5 sub-functions** for clarity.

3. **Validate against database tables:**
   - Each module should interact with at least one table in `schema.sql`.
   - Example: `4.0 Adoption Services` → `Adoption_Requests` table.

---

## Phase 5: ASCII Diagram Generation

**Objective:** Produce the final ASCII Functional Decomposition Diagram.

### Phase 5 Steps

1. **Use this template for the main diagram:**

   ```text
                                           ┌──────────────────────────────────────────────────┐
                                           │  CATARMAN DOG POUND & ANIMAL SHELTER MANAGEMENT  │
                                           │                      SYSTEM                      │
                                           └─────────────────────────┬────────────────────────┘
                                                                     │
           ┌───────────┬───────────┬───────────┬─────────────┬───────┴──────┬───────────┬───────────┐
           │           │           │           │             │              │           │           │
           ▼           ▼           ▼           ▼             ▼              ▼           ▼           ▼
      ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐   ┌─────────┐    ┌─────────┐ ┌─────────┐ ┌─────────┐
      │   1.0   │ │   2.0   │ │   3.0   │ │   4.0   │   │   5.0   │    │   6.0   │ │   7.0   │ │   8.0   │
      │ ACCESS  │ │ ANIMAL  │ │ MEDICAL │ │ADOPTION │   │INVENTORY│    │ BILLING │ │ REPORTS │ │  ADMIN  │
      │ CONTROL │ │  MGMT   │ │  CARE   │ │SERVICES │   │ CONTROL │    │ & FEES  │ │ & STATS │ │PANEL/OPS│
      └────┬────┘ └────┬────┘ └────┬────┘ └────┬────┘   └────┬────┘    └────┬────┘ └────┬────┘ └────┬────┘
           │           │           │           │             │              │           │           │
      [Sub-functions for each module below]
   ```

2. **Add sub-function rows** (up to 4 levels deep):

   ```text
      ┌────▼────┐ ┌────▼────┐ ┌────▼────┐ ...
      │ 1.1     │ │ 2.1     │ │ 3.1     │
      │ Login   │ │ Impound │ │ Record  │
      └────┬────┘ └────┬────┘ └────┬────┘
   ```

3. **Create a detailed breakdown table:**

   ```text
   ┌─────────────────────────────────────────────────────────────────────────────────────────┐
   │                              FUNCTIONAL DECOMPOSITION                                    │
   ├─────────────────────────────────────────────────────────────────────────────────────────┤
   │                                                                                         │
   │  1.0 ACCESS CONTROL (AUTH)                     5.0 INVENTORY CONTROL                    │
   │  ├── 1.1 Secure Login (Role-based)             ├── 5.1 Add New Items/Supplies           │
   │  ├── 1.2 Adopter Registration                  ├── 5.2 Update Stock Quantities          │
   │  ├── 1.3 Change / Reset Password               ├── 5.3 Expiry Date Tracking             │
   │  └── 1.4 Logout                                └── 5.4 Low Stock Notifications          │
   │  ...                                           ...                                       │
   └─────────────────────────────────────────────────────────────────────────────────────────┘
   ```

---

## Phase 6: Validation & Cross-Reference

**Objective:** Ensure accuracy and consistency with Use Case Diagram.

### Phase 6 Steps

1. **Cross-check with Use Case Diagram** in `SYSTEM_DIAGRAMS.md`:
   - Every use case should map to at least one function in the FDD.
   - Every actor should have functions in at least one module.

2. **Validate with the following checklist:**

   | Use Case | Mapped to FDD Function |
   | --- | --- |
   | Register / Login | 1.1, 1.2, 1.4 |
   | View Available Animals | 2.3 |
   | Submit Adoption Request | 4.1 |
   | Record Treatment | 3.1 |
   | Create Invoice | 6.1 |
   | Add / Edit Users | 8.1 |
   | ... | ... |

3. **Verify against frontend pages:**

   ```text
   list_dir frontend/assets/js
   ```

   - Each page should correspond to at least one module.

4. **Output any discrepancies** for manual review.

---

## Phase 7: Output & Storage

**Objective:** Save the final Functional Decomposition Diagram to `SYSTEM_DIAGRAMS.md`.

### Phase 7 Steps

1. **Create a temporary file** with the new FDD content:
   - Write the ASCII diagram and detailed breakdown to `fdd_update.md` in the project root.

2. **Identify the current FDD location** in `SYSTEM_DIAGRAMS.md`:
   - Find the line number of `## 3. Functional Decomposition Diagram`.
   - Find the line number of the next section header (e.g., `## 4. Conceptual Data Model`).

   ```powershell
   # Example: Get line numbers
   (Get-Content "SYSTEM_DIAGRAMS.md" | Select-String "## 3. Functional Decomposition").LineNumber
   (Get-Content "SYSTEM_DIAGRAMS.md" | Select-String "## 4. Conceptual Data Model").LineNumber
   ```

3. **Splice the content into `SYSTEM_DIAGRAMS.md`** using PowerShell:

   ```powershell
   # Adjust indices based on actual line numbers (0-indexed)
   $p = "SYSTEM_DIAGRAMS.md"
   $c = Get-Content $p
   $startIndex = 216  # Line before FDD header (0-indexed)
   $endIndex = 290    # Line before next section (0-indexed)
   $n = $c[0..$startIndex] + (Get-Content "fdd_update.md") + $c[$endIndex..($c.Count-1)]
   $n | Set-Content $p -Encoding utf8
   ```

   > **Note:** Always verify line numbers before splicing!

4. **Verify the update** by viewing the updated section:

   ```powershell
   Get-Content "SYSTEM_DIAGRAMS.md" | Select-Object -Index (216..290)
   ```

5. **Delete the temporary file:**

   ```powershell
   Remove-Item "fdd_update.md"
   ```

6. **Fix any lint errors** (e.g., MD058 - blank lines around tables).

7. **Do NOT commit** `SYSTEM_DIAGRAMS.md` if it is in `.gitignore`.

---

## Example Output

### ASCII Diagram (Compact)

```text
                                           ┌──────────────────────────────────────────────────┐
                                           │  CATARMAN DOG POUND & ANIMAL SHELTER MANAGEMENT  │
                                           │                      SYSTEM                      │
                                           └─────────────────────────┬────────────────────────┘
                                                                     │
           ┌───────────┬───────────┬───────────┬─────────────┬───────┴──────┬───────────┬───────────┐
           │           │           │           │             │              │           │           │
           ▼           ▼           ▼           ▼             ▼              ▼           ▼           ▼
      ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐   ┌─────────┐    ┌─────────┐ ┌─────────┐ ┌─────────┐
      │   1.0   │ │   2.0   │ │   3.0   │ │   4.0   │   │   5.0   │    │   6.0   │ │   7.0   │ │   8.0   │
      │ ACCESS  │ │ ANIMAL  │ │ MEDICAL │ │ADOPTION │   │INVENTORY│    │ BILLING │ │ REPORTS │ │  ADMIN  │
      │ CONTROL │ │  MGMT   │ │  CARE   │ │SERVICES │   │ CONTROL │    │ & FEES  │ │ & STATS │ │PANEL/OPS│
      └────┬────┘ └────┬────┘ └────┬────┘ └────┬────┘   └────┬────┘    └────┬────┘ └────┬────┘ └────┬────┘
           │           │           │           │             │              │           │           │
      ┌────▼────┐ ┌────▼────┐ ┌────▼────┐ ┌────▼────┐   ┌────▼────┐    ┌────▼────┐ ┌────▼────┐ ┌────▼────┐
      │ User    │ │Impound  │ │Record   │ │Submit   │   │ Add     │    │Create   │ │Inventory│ │Add/Edit │
      │ Login   │ │(Intake) │ │Treatment│ │Request  │   │ Item    │    │Invoice  │ │Summary  │ │ Users   │
      └────┬────┘ └────┬────┘ └────┬────┘ └────┬────┘   └────┬────┘    └────┬────┘ └────┬────┘ └────┬────┘
           │           │           │           │             │              │           │           │
      ┌────▼────┐ ┌────▼────┐ ┌────▼────┐ ┌────▼────┐   ┌────▼────┐    ┌────▼────┐ ┌────▼────┐ ┌────▼────┐
      │Register │ │Update   │ │Vaccin-  │ │Schedule │   │Update   │    │Record   │ │Health   │ │Activity │
      │Adopter  │ │Status   │ │ation    │ │Interview│   │Stock    │    │Payment  │ │Report   │ │Logs     │
      └────┬────┘ └────┬────┘ └────┬────┘ └────┬────┘   └────┬────┘    └────┬────┘ └────┬────┘ └────┬────┘
           │           │           │           │             │              │           │           │
      ┌────▼────┐ ┌────▼────┐ ┌────▼────┐ ┌────▼────┐   ┌────▼────┐    ┌────▼────┐ ┌────▼────┐ ┌────▼────┐
      │Reset    │ │Daily    │ │Medical  │ │Approve/ │   │Track    │    │Export   │ │Adoption │ │System   │
      │Password │ │Feeding  │ │History  │ │Reject   │   │Expiry   │    │Receipt  │ │Trends   │ │Health   │
      └────┬────┘ └────┬────┘ └────┬────┘ └────┬────┘   └────┬────┘    └────┬────┘ └────┬────┘ └─────────┘
           │           │           │           │             │              │           │
      ┌────▼────┐ ┌────▼────┐ ┌────▼────┐ ┌────▼────┐   ┌────▼────┐    ┌────▼────┐ ┌────▼────┐
      │Logout   │ │Upload   │ │Health   │ │Cancel   │   │Low Stock│    │Track    │ │Financial│
      │         │ │Photo    │ │Report   │ │Request  │   │Alerts   │    │Balances │ │Overview │
      └─────────┘ └─────────┘ └─────────┘ └─────────┘   └─────────┘    └─────────┘ └─────────┘
```

### Detailed Breakdown (Table)

```text
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                              FUNCTIONAL DECOMPOSITION                                    │
├─────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                         │
│  1.0 ACCESS CONTROL (AUTH)                     5.0 INVENTORY CONTROL                    │
│  ├── 1.1 Secure Login (Role-based)             ├── 5.1 Add New Items/Supplies           │
│  ├── 1.2 Adopter Registration                  ├── 5.2 Update Stock Quantities          │
│  ├── 1.3 Change / Reset Password               ├── 5.3 Expiry Date Tracking             │
│  └── 1.4 Logout                                └── 5.4 Low Stock Notifications          │
│                                                                                         │
│  2.0 ANIMAL MANAGEMENT                         6.0 BILLING & FEES                       │
│  ├── 2.1 Impound/Intake (Add Animal)           ├── 6.1 Generate Adoption Invoice        │
│  ├── 2.2 Update Animal Profiles                ├── 6.2 Record Payments                  │
│  ├── 2.3 Track Adoption Status                 ├── 6.3 Receipt Generation (PDF)         │
│  ├── 2.4 Daily Feeding Records                 └── 6.4 Outstanding Balance Tracking     │
│  └── 2.5 Photo Upload & Gallery                                                         │
│                                                7.0 REPORTS & STATISTICS                 │
│  3.0 MEDICAL CARE                              ├── 7.1 Inventory Summary Report         │
│  ├── 3.1 Record Treatments/Surgeries           ├── 7.2 Medical/Health Report            │
│  ├── 3.2 Vaccination Logging                   ├── 7.3 Adoption Trends Analysis         │
│  ├── 3.3 Animal Medical History View           └── 7.4 Income & Financial Overview      │
│  └── 3.4 Health Monitoring                                                              │
│                                                8.0 ADMIN PANEL (OPS)                    │
│  4.0 ADOPTION SERVICES                         ├── 8.1 Add / Edit / Delete Users        │
│  ├── 4.1 Submit Adoption Request               ├── 8.2 Role Assignment                  │
│  ├── 4.2 Interview Scheduling                  ├── 8.3 System Activity Logs             │
│  ├── 4.3 Approval/Rejection Workflow           └── 8.4 System Health Monitor            │
│  └── 4.4 Request Cancellation                                                           │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## Notes for LLM Execution

- **Be thorough:** Scan every controller method and model to identify all functions.
- **Be consistent:** Use the same module numbering (1.0-8.0) as existing documentation.
- **Prioritize accuracy:** If unsure about a function mapping, flag it for human review.
- **Respect constraints:** Do not add functions that are not supported by the codebase.
- **Match Use Cases:** Every function should ideally map to a use case in the Use Case Diagram.
