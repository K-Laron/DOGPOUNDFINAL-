---
description: Generate or update a Context Diagram by scanning the entire codebase
---

# LLM Prompt: Context Diagram Generation from Codebase Analysis

> **Purpose:** Instruct an LLM to systematically scan the entire codebase and produce an accurate, up-to-date Context Diagram (Level 0 DFD) for the **Catarman Dog Pound & Animal Shelter Management System**.

---

## Phase 1: Project Structure Discovery

**Objective:** Understand the high-level architecture and identify key directories.

### Instructions

1. List the root directory contents:

   ```text
   list_dir c:\Users\TESS LARON\Desktop\dogpound
   ```

2. Identify the following key areas:
   - **Backend:** `backend/app/controllers/`, `backend/app/models/`
   - **Frontend:** `frontend/src/pages/`, `frontend/src/components/`
   - **Database:** `backend/database/` or `*.sql` files
   - **Routes:** `backend/routes/` or `api.php`

3. Document the project structure in a temporary note before proceeding.

---

## Phase 2: Actor Identification

**Objective:** Identify all external entities (actors) that interact with the system.

### Steps

1. **Scan the Roles table or Role model:**

   ```php
   // Look for role definitions in:
   // backend/app/models/Role.php
   // backend/database/migrations/*roles*
   // Example pattern:
   $roles = ['Admin', 'Staff', 'Veterinarian', 'Adopter'];
   ```

2. **Scan authentication controllers for role-based logic:**

   ```php
   // In AuthController.php, look for:
   $user->role_id
   $role->name
   ```

3. **Scan frontend route guards for actor-specific pages:**

   ```javascript
   // In frontend/src/App.js or routes, look for:
   allowedRoles: ['Admin', 'Staff']
   ```

4. **Output:** List all unique actors found (e.g., Admin, Staff, Veterinarian, Adopter).

---

## Phase 3: Data Flow Identification

**Objective:** Map all interactions between actors and the system.

### Phase 3 Steps

1. **Scan all controllers** in `backend/app/controllers/`:

   ```text
   find_by_name -d backend/app/controllers -e php
   ```

2. **For each controller, identify:**
   - **Inbound flows:** What data does the actor send TO the system?
   - **Outbound flows:** What data does the system return TO the actor?

3. **Use this mapping template per controller:**

   | Controller | Actor(s) | Inbound Data | Outbound Data |
   | --- | --- | --- | --- |
   | `AuthController` | All | Credentials | Token, User Info |
   | `AnimalController` | Staff, Admin | Animal Data | Animal List |
   | `AdoptionController` | Adopter | Request Data | Status Updates |
   | `MedicalController` | Veterinarian | Treatment Data | Medical History |
   | `InventoryController` | Staff, Admin | Item Data | Stock Alerts |
   | `BillingController` | Staff, Admin | Invoice Data | Payment Records |
   | `UserController` | Admin | User Data | User List |
   | `DashboardController` | Admin, Staff, Vet | - | Stats, Alerts |

4. **Scan route files** to confirm endpoint-to-role mappings:

   ```php
   // In backend/routes/api.php or web.php, look for:
   Route::middleware(['auth', 'role:Admin'])->group(...);
   ```

---

## Phase 4: Flow Numbering & Legend Creation

**Objective:** Assign unique IDs to each data flow and create a legend.

### Phase 4 Steps

1. **Assign sequential IDs** to each unique flow identified in Phase 3.

2. **Use this format:**

   | ID | Function | Actor(s) |
   | --- | --- | --- |
   | 1 | Login / Logout / Register | All Actors |
   | 2 | Add / Edit / Delete Users | Admin |
   | 3 | Add / Edit / Update Animals | Staff, Admin, Vet |
   | 4 | Add / Edit Inventory Items | Staff, Admin |
   | 5 | Process Adoptions | Staff, Admin, Vet |
   | 6 | Create Invoices & Record Payments | Staff, Admin |
   | 7 | Record Treatments & Medical History | Veterinarian |
   | ... | ... | ... |

3. **Group flows by direction:**
   - **Inbound (Actor → System):** Submission, Creation, Updates
   - **Outbound (System → Actor):** Views, Alerts, Reports

---

## Phase 5: Context Diagram ASCII Generation

**Objective:** Produce the final ASCII Context Diagram.

### Phase 5 Steps

1. **Use this template structure:**

```text
                                 ┌──────────────────┐
                                 │                  │
                                 │    ACTOR NAME    │
                                 │                  │
                                 └────────┬─────────┘
                                          │ ▲
                                          │ │
                              [Inbound IDs]│ │[Outbound IDs]
                                          │ │
                                          ▼ │
                                 ┌──────────────────┐
                                 │        0         │
                                 │                  │
                                 │   SYSTEM NAME    │
                                 │                  │
                                 └──────────────────┘
```

1. **Position actors around the central system box:**
   - **Top:** Admin (highest privilege)
   - **Left:** Veterinarian (specialized)
   - **Bottom:** Staff (operational)
   - **Right:** Adopter (external user)

2. **Label arrows with flow IDs** from the Legend.

---

## Phase 6: Validation & Cross-Reference

**Objective:** Ensure accuracy and consistency.

### Phase 6 Steps

1. **Cross-check with Use Case Diagram** (if exists):
   - Every use case should map to at least one flow.
   - Every actor in Use Case Diagram must appear in Context Diagram.

2. **Verify against frontend routes:**

   ```javascript
   // Check which pages/components each role can access
   // in frontend/src/App.js or navigation components
   ```

3. **Validate against database schema:**

   ```sql
   -- Ensure all entities referenced in flows have corresponding tables
   SELECT table_name FROM information_schema.tables;
   ```

4. **Output any discrepancies** for manual review.

---

## Phase 7: Output & Storage

**Objective:** Save the final Context Diagram to `SYSTEM_DIAGRAMS.md`.

### Phase 7 Steps

1. **Create a temporary file** with the new Context Diagram content:
   - Write the ASCII diagram and Legend to `context_diagram_update.md` in the project root.

2. **Splice the content into `SYSTEM_DIAGRAMS.md`** using PowerShell:

   ```powershell
   # Read the file, replace lines 157-220 (Context Diagram section) with new content
   $p = "SYSTEM_DIAGRAMS.md"
   $c = Get-Content $p
   $n = $c[0..155] + (Get-Content "context_diagram_update.md") + $c[220..($c.Count-1)]
   $n | Set-Content $p -Encoding utf8
   ```

   > **Note:** Adjust line numbers `[0..155]` and `[220..]` based on the actual location of the Context Diagram section in the file.

3. **Verify the update** by viewing lines 157-220 of `SYSTEM_DIAGRAMS.md`:

   ```powershell
   Get-Content "SYSTEM_DIAGRAMS.md" | Select-Object -Index (156..219)
   ```

4. **Delete the temporary file:**

   ```powershell
   Remove-Item "context_diagram_update.md"
   ```

5. **Do NOT commit** `SYSTEM_DIAGRAMS.md` to version control if it is in `.gitignore`.

---

## Example Output

```text
                                 ┌──────────────────┐
                                 │    SITE ADMIN    │
                                 └────────┬─────────┘
                                          │ ▲
                              1,2,3,4,5,6,│ │ 10,11,12,
                                     8,9,13│ │ 14,15
                                          ▼ │
                                 ┌──────────────────┐
                                 │        0         │
    ┌────────────────┐           │   Catarman Dog   │           ┌────────────────┐
    │  VETERINARIAN  ├──────────►│      Pound &     ├──────────►│    ADOPTER     │
    │                │◄──────────┤   Animal Shelter │◄──────────┤                │
    └────────────────┘           │     Management   │           └────────────────┘
                                 │       System     │
                                 └────────┬─────────┘
                                          ▲ │
                              1,3,4,5,6,13│ │ 10,11,14,15
                                          │ ▼
                                 ┌──────────────────┐
                                 │      STAFF       │
                                 └──────────────────┘
```

---

## Notes for LLM Execution

- **Be thorough:** Scan every controller, model, and route file.
- **Be consistent:** Use the same terminology as the existing documentation.
- **Prioritize accuracy:** If unsure about a flow, flag it for human review rather than guessing.
- **Respect constraints:** Do not add flows that are not supported by the codebase.
