# 📚 Database Documentation

## Catarman Dog Pound Management System

This document provides a detailed explanation of the database schema, tables, relationships, and data flow.

---

## 📂 Overview

**Database Name**: `catarman_dog_pound_db`
**Character Set**: `utf8mb4`
**Collation**: `utf8mb4_unicode_ci`
**Engine**: InnoDB (all tables)

---

## 📊 Entity Relationship Diagram

```
┌─────────────┐       ┌─────────────┐
│   Roles     │───────│   Users     │
└─────────────┘       └──────┬──────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
        ▼                    ▼                    ▼
┌───────────────┐    ┌──────────────┐    ┌───────────────┐
│ Veterinarians │    │ Activity_Logs│    │Adoption_Requests│
└───────────────┘    └──────────────┘    └───────┬───────┘
        │                                        │
        ▼                                        ▼
┌──────────────────┐                     ┌─────────────┐
│ Medical_Records  │◄────────────────────│   Animals   │
└──────────────────┘                     └──────┬──────┘
                                                │
        ┌───────────────────────────────────────┤
        │                                       │
        ▼                                       ▼
┌───────────────┐                      ┌─────────────────┐
│ Impound_Records│                      │ Feeding_Records │
└───────────────┘                      └─────────────────┘

┌─────────────┐       ┌─────────────┐
│  Invoices   │───────│  Payments   │
└─────────────┘       └─────────────┘

┌─────────────┐
│  Inventory  │ (standalone)
└─────────────┘
```

---

## 📋 Table Descriptions

### 1. User Management & Security

#### `Roles`
**Purpose**: Define user roles for access control

```
┌─────────────┬─────────────┬───────────────────────┐
│ Column      │ Type        │ Description           │
├─────────────┼─────────────┼───────────────────────┤
│ RoleID      │ INT PK      │ Auto-increment ID     │
│ Role_Name   │ VARCHAR(50) │ Unique role name      │
│ Created_At  │ DATETIME    │ Creation timestamp    │
│ Updated_At  │ DATETIME    │ Last update timestamp │
└─────────────┴─────────────┴───────────────────────┘
```

**Default Roles**:
- Admin
- Staff
- Veterinarian
- Adopter

---

#### `Users`
**Purpose**: Store all system users

```
┌────────────────┬──────────────┬──────────────────────────────────────┐
│ Column         │ Type         │ Description                          │
├────────────────┼──────────────┼──────────────────────────────────────┤
│ UserID         │ INT PK       │ Auto-increment ID                    │
│ RoleID         │ INT FK       │ Reference to Roles                   │
│ FirstName      │ VARCHAR(50)  │ First name                           │
│ LastName       │ VARCHAR(50)  │ Last name                            │
│ Username       │ VARCHAR(50)  │ Unique username                      │
│ Email          │ VARCHAR(100) │ Unique email                         │
│ Contact_Number │ VARCHAR(20)  │ Phone number                         │
│ Address        │ TEXT         │ Full address                         │
│ Avatar_Url     │ VARCHAR(255) │ Profile image URL                    │
│ Password_Hash  │ VARCHAR(255) │ Bcrypt hashed password               │
│ Account_Status │ ENUM         │ 'Active', 'Inactive', 'Banned'       │
│ Preferences    │ JSON         │ User settings (theme, notifications) │
│ Is_Deleted     │ BOOLEAN      │ Soft delete flag                     │
│ Created_At     │ DATETIME     │ Registration date                    │
│ Updated_At     │ DATETIME     │ Last update                          │
└────────────────┴──────────────┴──────────────────────────────────────┘
```

**Indexes**:
- `idx_users_email` on Email
- `idx_users_status` on (Account_Status, Is_Deleted)
- `idx_users_role` on RoleID

---

#### `Veterinarians`
**Purpose**: Additional veterinarian details (extends Users)

```
┌──────────────────┬──────────────┬─────────────────────────────┐
│ Column           │ Type         │ Description                 │
├──────────────────┼──────────────┼─────────────────────────────┤
│ VetID            │ INT PK       │ Auto-increment ID           │
│ UserID           │ INT FK       │ Reference to Users (unique) │
│ License_Number   │ VARCHAR(50)  │ Professional license        │
│ Specialization   │ VARCHAR(100) │ Area of expertise           │
│ Years_Experience │ INT          │ Years practicing            │
│ Clinic_Name      │ VARCHAR(100) │ Associated clinic           │
│ Bio              │ TEXT         │ Professional biography      │
└──────────────────┴──────────────┴─────────────────────────────┘
```

---

### 2. Animal Profiling

#### `Animals`
**Purpose**: Core animal records

```
┌────────────────┬──────────────┬────────────────────────────────────────┐
│ Column         │ Type         │ Description                            │
├────────────────┼──────────────┼────────────────────────────────────────┤
│ AnimalID       │ INT PK       │ Auto-increment ID                      │
│ Name           │ VARCHAR(50)  │ Animal name                            │
│ Type           │ ENUM         │ 'Dog', 'Cat', 'Other'                  │
│ Breed          │ VARCHAR(50)  │ Breed/species                          │
│ Gender         │ ENUM         │ 'Male', 'Female', 'Unknown'            │
│ Age_Group      │ VARCHAR(20)  │ 'Puppy', 'Adult', 'Senior'             │
│ Weight         │ DECIMAL(5,2) │ Weight in kg                           │
│ Intake_Date    │ DATETIME     │ When animal arrived                    │
│ Intake_Status  │ ENUM         │ 'Stray', 'Surrendered', 'Confiscated'  │
│ Current_Status │ ENUM         │ Current status (see below)             │
│ Image_URL      │ VARCHAR(255) │ Photo URL                              │
│ Is_Deleted     │ BOOLEAN      │ Soft delete flag                       │
└────────────────┴──────────────┴────────────────────────────────────────┘
```

**Current Status Values**:
- `Available` - Ready for adoption
- `Adopted` - Already adopted
- `Deceased` - Animal has died
- `In Treatment` - Under medical care
- `Quarantine` - Isolated for health reasons
- `Reclaimed` - Returned to original owner

**Indexes**:
- `idx_animals_status` on (Current_Status, Is_Deleted)
- `idx_animals_type` on Type
- `idx_animals_intake_date` on Intake_Date

---

#### `Impound_Records`
**Purpose**: Details about how animal was received

```
┌──────────────────────┬──────────────┬────────────────────────┐
│ Column               │ Type         │ Description            │
├──────────────────────┼──────────────┼────────────────────────┤
│ ImpoundID            │ INT PK       │ Auto-increment ID      │
│ AnimalID             │ INT FK       │ Reference to Animals   │
│ Capture_Date         │ DATETIME     │ When captured/received │
│ Location_Found       │ VARCHAR(255) │ Where found            │
│ Impounding_Officer   │ VARCHAR(100) │ Officer who processed  │
│ Condition_On_Arrival │ TEXT         │ Health condition notes │
└──────────────────────┴──────────────┴────────────────────────┘
```

---

### 3. Medical & Health

#### `Medical_Records`
**Purpose**: Track all medical procedures and treatments

```
┌──────────────────┬──────────────┬─────────────────────────────┐
│ Column           │ Type         │ Description                 │
├──────────────────┼──────────────┼─────────────────────────────┤
│ RecordID         │ INT PK       │ Auto-increment ID           │
│ AnimalID         │ INT FK       │ Reference to Animals        │
│ VetID            │ INT FK       │ Reference to Veterinarians  │
│ Date_Performed   │ DATETIME     │ When procedure done         │
│ Diagnosis_Type   │ ENUM         │ Type of procedure           │
│ Vaccine_Name     │ VARCHAR(100) │ Vaccine if applicable       │
│ Treatment_Notes  │ TEXT         │ Detailed notes              │
│ Next_Due_Date    │ DATE         │ Follow-up date              │
└──────────────────┴──────────────┴─────────────────────────────┘
```

**Diagnosis Types**:
- Checkup
- Vaccination
- Surgery
- Treatment
- Emergency
- Deworming
- Spay/Neuter

**Indexes**:
- `idx_medical_animal` on AnimalID
- `idx_medical_date` on Date_Performed
- `idx_medical_next_due` on Next_Due_Date

---

#### `Feeding_Records`
**Purpose**: Track animal feeding schedule

```
┌─────────────────┬──────────────┬───────────────────────────┐
│ Column          │ Type         │ Description               │
├─────────────────┼──────────────┼───────────────────────────┤
│ FeedingID       │ INT PK       │ Auto-increment ID         │
│ AnimalID        │ INT FK       │ Reference to Animals      │
│ Fed_By_UserID   │ INT FK       │ Staff who fed             │
│ Feeding_Time    │ DATETIME     │ When fed                  │
│ Food_Type       │ VARCHAR(50)  │ Type of food              │
│ Quantity_Used   │ DECIMAL(5,2) │ Amount in kg/units        │
└─────────────────┴──────────────┴───────────────────────────┘
```

---

### 4. Adoption Module

#### `Adoption_Requests`
**Purpose**: Track adoption applications

```
┌─────────────────────┬──────────┬───────────────────────┐
│ Column              │ Type     │ Description           │
├─────────────────────┼──────────┼───────────────────────┤
│ RequestID           │ INT PK   │ Auto-increment ID     │
│ AnimalID            │ INT FK   │ Animal being adopted  │
│ Adopter_UserID      │ INT FK   │ User requesting       │
│ Request_Date        │ DATETIME │ When submitted        │
│ Status              │ ENUM     │ Current status        │
│ Interview_Date      │ DATETIME │ Scheduled interview   │
│ Staff_Comments      │ TEXT     │ Internal notes        │
│ Processed_By_UserID │ INT FK   │ Staff who processed   │
└─────────────────────┴──────────┴───────────────────────┘
```

**Status Values**:
- `Pending` - Awaiting review
- `Interview Scheduled` - Interview set
- `Approved` - Approved for adoption
- `Rejected` - Application denied
- `Completed` - Adoption finalized
- `Cancelled` - Cancelled by adopter

**Indexes**:
- `idx_adoption_status` on Status
- `idx_adoption_animal` on AnimalID
- `idx_adoption_adopter` on Adopter_UserID

---

### 5. Inventory Management

#### `Inventory`
**Purpose**: Track supplies and materials

```
┌──────────────────┬──────────────┬───────────────────────────────────────────┐
│ Column           │ Type         │ Description                               │
├──────────────────┼──────────────┼───────────────────────────────────────────┤
│ ItemID           │ INT PK       │ Auto-increment ID                         │
│ Item_Name        │ VARCHAR(100) │ Item name                                 │
│ Category         │ ENUM         │ 'Medical', 'Food', 'Cleaning', 'Supplies' │
│ Quantity_On_Hand │ INT          │ Current stock                             │
│ Reorder_Level    │ INT          │ Low stock threshold                       │
│ Expiration_Date  │ DATE         │ If perishable                             │
│ Supplier_Name    │ VARCHAR(100) │ Supplier info                             │
│ Last_Updated     │ DATETIME     │ Last stock update                         │
└──────────────────┴──────────────┴───────────────────────────────────────────┘
```

**Indexes**:
- `idx_inventory_category` on Category
- `idx_inventory_stock` on (Quantity_On_Hand, Reorder_Level)
- `idx_inventory_expiry` on Expiration_Date

---

### 6. Billing & Finance

#### `Invoices`
**Purpose**: Track all charges/fees

```
┌───────────────────┬───────────────┬───────────────────────────────┐
│ Column            │ Type          │ Description                   │
├───────────────────┼───────────────┼───────────────────────────────┤
│ InvoiceID         │ INT PK        │ Auto-increment ID             │
│ Payer_UserID      │ INT FK        │ Customer being charged        │
│ Issued_By_UserID  │ INT FK        │ Staff who issued              │
│ Transaction_Type  │ ENUM          │ 'Adoption Fee', 'Reclaim Fee' │
│ Total_Amount      │ DECIMAL(10,2) │ Invoice amount                │
│ Status            │ ENUM          │ 'Unpaid', 'Paid', 'Cancelled' │
│ Is_Deleted        │ BOOLEAN       │ Soft delete flag              │
│ Related_AnimalID  │ INT FK        │ Related animal                │
│ Related_RequestID │ INT FK        │ Related adoption request      │
└───────────────────┴───────────────┴───────────────────────────────┘
```

**Indexes**:
- `idx_invoices_status` on (Status, Is_Deleted)
- `idx_invoices_payer` on Payer_UserID

---

#### `Payments`
**Purpose**: Track payments against invoices

```
┌────────────────────┬───────────────┬──────────────────────────────────┐
│ Column             │ Type          │ Description                      │
├────────────────────┼───────────────┼──────────────────────────────────┤
│ PaymentID          │ INT PK        │ Auto-increment ID                │
│ InvoiceID          │ INT FK        │ Invoice being paid               │
│ Received_By_UserID │ INT FK        │ Staff who received               │
│ Payment_Date       │ DATETIME      │ When paid                        │
│ Amount_Paid        │ DECIMAL(10,2) │ Amount received                  │
│ Payment_Method     │ ENUM          │ 'Cash', 'GCash', 'Bank Transfer' │
│ Reference_Number   │ VARCHAR(50)   │ Transaction reference            │
└────────────────────┴───────────────┴──────────────────────────────────┘
```

**Indexes**:
- `idx_payments_invoice` on InvoiceID
- `idx_payments_date` on Payment_Date

---

### 7. System Logs

#### `Activity_Logs`
**Purpose**: Audit trail of all user actions

```
┌─────────────┬─────────────┬───────────────────────────┐
│ Column      │ Type        │ Description               │
├─────────────┼─────────────┼───────────────────────────┤
│ LogID       │ INT PK      │ Auto-increment ID         │
│ UserID      │ INT FK      │ User who performed action │
│ Action_Type │ VARCHAR(50) │ Type of action            │
│ Description │ TEXT        │ Detailed description      │
│ IP_Address  │ VARCHAR(45) │ User's IP address         │
│ Log_Date    │ DATETIME    │ When action occurred      │
└─────────────┴─────────────┴───────────────────────────┘
```

**Common Action Types**:
- LOGIN_SUCCESS
- LOGIN_FAILED
- LOGOUT
- USER_CREATE
- USER_UPDATE
- ANIMAL_CREATE
- ANIMAL_UPDATE
- ANIMAL_DELETE
- ADOPTION_SUBMIT
- ADOPTION_APPROVE
- ADOPTION_REJECT
- INVOICE_CREATE
- PAYMENT_RECORD

**Indexes**:
- `idx_activity_user` on UserID
- `idx_activity_date` on Log_Date
- `idx_activity_type` on Action_Type

---

## 🔗 Foreign Key Relationships

```sql
-- Users → Roles
Users.RoleID → Roles.RoleID

-- Veterinarians → Users
Veterinarians.UserID → Users.UserID

-- Impound_Records → Animals
Impound_Records.AnimalID → Animals.AnimalID

-- Medical_Records → Animals, Veterinarians
Medical_Records.AnimalID → Animals.AnimalID
Medical_Records.VetID → Veterinarians.VetID

-- Feeding_Records → Animals, Users
Feeding_Records.AnimalID → Animals.AnimalID
Feeding_Records.Fed_By_UserID → Users.UserID

-- Adoption_Requests → Animals, Users
Adoption_Requests.AnimalID → Animals.AnimalID
Adoption_Requests.Adopter_UserID → Users.UserID
Adoption_Requests.Processed_By_UserID → Users.UserID

-- Invoices → Users, Animals, Adoption_Requests
Invoices.Payer_UserID → Users.UserID
Invoices.Issued_By_UserID → Users.UserID
Invoices.Related_AnimalID → Animals.AnimalID
Invoices.Related_RequestID → Adoption_Requests.RequestID

-- Payments → Invoices, Users
Payments.InvoiceID → Invoices.InvoiceID
Payments.Received_By_UserID → Users.UserID

-- Activity_Logs → Users
Activity_Logs.UserID → Users.UserID
```

---

## 📈 Common Queries

### Get Available Animals
```sql
SELECT * FROM Animals 
WHERE Current_Status = 'Available' 
AND Is_Deleted = FALSE
ORDER BY Intake_Date DESC;
```

### Get Pending Adoptions
```sql
SELECT ar.*, a.Name as Animal_Name, 
       CONCAT(u.FirstName, ' ', u.LastName) as Adopter_Name
FROM Adoption_Requests ar
JOIN Animals a ON ar.AnimalID = a.AnimalID
JOIN Users u ON ar.Adopter_UserID = u.UserID
WHERE ar.Status = 'Pending'
ORDER BY ar.Request_Date ASC;
```

### Get Low Stock Items
```sql
SELECT * FROM Inventory
WHERE Quantity_On_Hand <= Reorder_Level
ORDER BY Quantity_On_Hand ASC;
```

### Get Unpaid Invoices
```sql
SELECT i.*, 
       CONCAT(u.FirstName, ' ', u.LastName) as Customer_Name,
       a.Name as Animal_Name
FROM Invoices i
JOIN Users u ON i.Payer_UserID = u.UserID
LEFT JOIN Animals a ON i.Related_AnimalID = a.AnimalID
WHERE i.Status = 'Unpaid' AND i.Is_Deleted = FALSE
ORDER BY i.Created_At DESC;
```

### Get Dashboard Statistics
```sql
-- Total animals by status
SELECT Current_Status, COUNT(*) as count 
FROM Animals WHERE Is_Deleted = FALSE
GROUP BY Current_Status;

-- Recent activity
SELECT al.*, CONCAT(u.FirstName, ' ', u.LastName) as User_Name
FROM Activity_Logs al
LEFT JOIN Users u ON al.UserID = u.UserID
ORDER BY al.Log_Date DESC
LIMIT 10;

-- Overdue medical treatments
SELECT mr.*, a.Name as Animal_Name
FROM Medical_Records mr
JOIN Animals a ON mr.AnimalID = a.AnimalID
WHERE mr.Next_Due_Date < CURDATE()
AND a.Is_Deleted = FALSE
AND a.Current_Status NOT IN ('Adopted', 'Deceased', 'Reclaimed');
```

---

## 🔐 Security Features

1. **Soft Deletes**: `Is_Deleted` flag instead of actual DELETE
2. **Audit Trail**: All actions logged to `Activity_Logs`
3. **Password Hashing**: Passwords stored as bcrypt hashes
4. **Foreign Key Constraints**: Referential integrity enforced
5. **ON DELETE CASCADE**: Removes related records when parent deleted
6. **Indexes**: Optimized for common query patterns

---

## 📝 Seeder Data

The `seeders.sql` file contains:

1. **Default Roles**:
   - Admin, Staff, Veterinarian, Adopter

2. **Test Users**:
   - admin@dogpound.com (Admin)
   - staff@dogpound.com (Staff)
   - vet@dogpound.com (Veterinarian)
   - adopter@dogpound.com (Adopter)

3. **Sample Animals**:
   - Various dogs and cats with different statuses

4. **Sample Inventory**:
   - Medical supplies, food, cleaning supplies

5. **Sample Medical Records**:
   - Vaccinations, checkups, treatments

---

## 🔄 Migration Notes

When updating the schema:

1. Always backup existing data
2. Use `ALTER TABLE` for column modifications
3. Add new indexes for new query patterns
4. Update foreign keys carefully (may require disabling checks)
5. Test migrations on development first

```sql
-- Example: Add new column
ALTER TABLE Animals ADD COLUMN Microchip_ID VARCHAR(50) AFTER Image_URL;

-- Example: Add index
CREATE INDEX idx_animals_microchip ON Animals(Microchip_ID);
```
