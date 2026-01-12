# System Diagrams

## Catarman Dog Pound Management System

This document contains the System Architecture, Context Diagram, Functional Decomposition, and Data Models.

---

## 1. System Architecture Diagram

Shows the overall system architecture with all layers and components.

```text
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                          SYSTEM ARCHITECTURE DIAGRAM                                 │
│                     Catarman Dog Pound Management System                             │
└─────────────────────────────────────────────────────────────────────────────────────┘

                                  ┌─────────────────┐
                                  │    BROWSER      │
                                  │  (Client-Side)  │
                                  └────────┬────────┘
                                           │
                                           │ HTTP Requests
                                           │
┌──────────────────────────────────────────▼──────────────────────────────────────────┐
│                              PRESENTATION LAYER                                      │
│                                                                                      │
│  ┌─────────────────────────────────────────────────────────────────────────────┐   │
│  │                         VANILLA JS SPA FRONTEND                              │   │
│  │                            (Single Page Application)                         │   │
│  ├─────────────────────────────────────────────────────────────────────────────┤   │
│  │                                                                             │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │   │
│  │  │   Router     │  │  Components   │  │ Performance  │  │   Services   │   │   │
│  │  │  (SPA Nav)   │  │  (UI Parts)   │  │ (Cache/Pref) │  │  (API Calls) │   │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘   │   │
│  │                                                                             │   │
│  │  Components:                                                                │   │
│  │  • Header.js      • DataTable.js    • Card.js        • Modal.js           │   │
│  │  • Sidebar.js     • Pagination.js   • Toast.js       • EmptyState.js      │   │
│  │                                                                             │   │
│  │  Pages:                                                                     │   │
│  │  • Dashboard.js   • Animals.js      • Adoptions.js   • Medical.js          │   │
│  │  • Billing.js     • Inventory.js    • Users.js       • Profile.js          │   │
│  │                                                                             │   │
│  └─────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                      │
│  ┌─────────────────────────────────────────────────────────────────────────────┐   │
│  │                              STATIC ASSETS                                   │   │
│  │  • CSS (main.css, components.css, responsive.css, enhancements.css)         │   │
│  │  • Images (placeholders, logos, icons)                                       │   │
│  └─────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                           │
                                           │ AJAX / Fetch API
                                           │ (JSON over HTTP)
                                           │
┌──────────────────────────────────────────▼──────────────────────────────────────────┐
│                              APPLICATION LAYER                                       │
│                                                                                      │
│  ┌─────────────────────────────────────────────────────────────────────────────┐   │
│  │                         PHP REST API BACKEND                                 │   │
│  │                              (MVC Pattern)                                   │   │
│  ├─────────────────────────────────────────────────────────────────────────────┤   │
│  │                                                                             │   │
│  │  ┌──────────────────────────────────────────────────────────────────────┐  │   │
│  │  │                           ROUTER                                      │  │   │
│  │  │  • Route matching           • Method validation                       │  │   │
│  │  │  • Authentication check     • CORS handling                           │  │   │
│  │  └──────────────────────────────────────────────────────────────────────┘  │   │
│  │                                    │                                        │   │
│  │                                    ▼                                        │   │
│  │  ┌──────────────────────────────────────────────────────────────────────┐  │   │
│  │  │                        CONTROLLERS                                    │  │   │
│  │  │  • UserController         • AnimalController      • AdoptionController│  │   │
│  │  │  • MedicalController      • InventoryController   • BillingController │  │   │
│  │  │  • DashboardController    • NotificationController                    │  │   │
│  │  └──────────────────────────────────────────────────────────────────────┘  │   │
│  │                                    │                                        │   │
│  │                                    ▼                                        │   │
│  │  ┌──────────────────────────────────────────────────────────────────────┐  │   │
│  │  │                          MODELS                                       │  │   │
│  │  │  • User         • Animal       • Adoption      • Medical             │  │   │
│  │  │  • Inventory    • Invoice      • Payment       • ActivityLog         │  │   │
│  │  └──────────────────────────────────────────────────────────────────────┘  │   │
│  │                                                                             │   │
│  │  ┌────────────────────────┐  ┌────────────────────────────────────────┐   │   │
│  │  │       UTILITIES         │  │           MIDDLEWARE                   │   │   │
│  │  │  • JWT.php (Auth)       │  │  • Authentication                      │   │   │
│  │  │  • Response.php         │  │  • Rate Limiter                        │   │   │
│  │  │  • Sanitizer.php        │  │  • Input Validation                    │   │   │
│  │  │  • RateLimiter.php      │  │  • Error Handler                       │   │   │
│  │  │  • Env.php              │  │                                        │   │   │
│  │  └────────────────────────┘  └────────────────────────────────────────┘   │   │
│  │                                                                             │   │
│  └─────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                           │
                                           │ PDO Queries
                                           │ (Prepared Statements)
                                           │
┌──────────────────────────────────────────▼──────────────────────────────────────────┐
│                                DATA LAYER                                            │
│                                                                                      │
│  ┌─────────────────────────────────────────────────────────────────────────────┐   │
│  │                            MySQL DATABASE                                    │   │
│  │                        (catarman_dog_pound_db)                               │   │
│  ├─────────────────────────────────────────────────────────────────────────────┤   │
│  │                                                                             │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐       │   │
│  │  │   Roles     │  │   Users     │  │Veterinarians│  │  Animals    │       │   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘       │   │
│  │                                                                             │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐       │   │
│  │  │  Impound    │  │  Medical    │  │  Feeding    │  │  Adoption   │       │   │
│  │  │  Records    │  │  Records    │  │  Records    │  │  Requests   │       │   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘       │   │
│  │                                                                             │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐       │   │
│  │  │  Inventory  │  │  Invoices   │  │  Payments   │  │ Activity    │       │   │
│  │  │             │  │             │  │             │  │ Logs        │       │   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘       │   │
│  │                                                                             │   │
│  │  Total: 12 Tables  •  Engine: InnoDB  •  Charset: utf8mb4                  │   │
│  │                                                                             │   │
│  └─────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                      │
│  ┌─────────────────────────────────────────────────────────────────────────────┐   │
│  │                           FILE STORAGE                                       │   │
│  │  • /frontend/assets/uploads/animals/   (Animal images)                      │   │
│  │  • /frontend/assets/uploads/avatars/   (User avatars)                       │   │
│  └─────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### Technology Stack Summary

```text
┌───────────────────┬───────────────────────────────────────────────────────────────┐
│ Layer             │ Technologies                                                  │
├───────────────────┼───────────────────────────────────────────────────────────────┤
│ Frontend          │ HTML5, CSS3, Vanilla JavaScript (ES6+), SPA Architecture      │
│ Backend           │ PHP 8.x, MVC Pattern, REST API                                │
│ Database          │ MySQL 8.x, InnoDB Engine, PDO with Prepared Statements        │
│ Authentication    │ JWT (HS256), 24-hour token expiry, HttpOnly cookies           │
│ Security          │ Rate limiting, Input sanitization, CORS, Password hashing     │
│ Development       │ XAMPP, PHP Built-in Server, Live development environment      │
└───────────────────┴───────────────────────────────────────────────────────────────┘
```

---

## 2. Context Diagram (Level 0 DFD)

Shows the system boundaries and external entities.

```text
                                 ┌──────────────────┐
                                 │                  │
                                 │    SITE ADMIN    │
                                 │                  │
                                 └────────┬─────────┘
                                          │ ▲
                                          │ │
                              1,2,3,4,6,9,│ │ 11,12,14
                                        15│ │
                                          │ │
                                          ▼ │
                                 ┌──────────────────┐
                                 │        0         │
    ┌────────────────┐           │                  │           ┌────────────────┐
    │                │ 1,3,8,    │   Catarman Dog   │ 1,5,7,    │                │
    │  VETERINARIAN  ├──────────►│      Pound &     ├──────────►│    ADOPTER     │
    │                │◄──────────┤   Animal Shelter │◄──────────┤                │
    └────────────────┘ 11,12,    │     Management   │ 10,13     └────────────────┘
                       14        │       System     │
                                 │                  │
                                 └────────┬─────────┘
                                          ▲ │
                                          │ │
                              1,3,4,6,9,15│ │ 11,12,14
                                          │ │
                                          │ ▼
                                 ┌──────────────────┐
                                 │                  │
                                 │      STAFF       │
                                 │                  │
                                 └──────────────────┘
```

### Legend

| ID | Function | Actor(s) |
| :---: | :--- | :--- |
| **1** | Login / Logout / Register / Profile | All Actors |
| **2** | Add / Edit / Delete Users | Admin |
| **3** | Add / Edit / Update Animals | Admin, Staff, Veterinarian |
| **4** | Add / Edit Inventory Items | Admin, Staff |
| **5** | Submit / Cancel Adoption Requests | Adopter |
| **6** | Process Adoptions (Approve/Reject) | Admin, Staff |
| **7** | View Adoption Status & History | Adopter |
| **8** | Record Treatments & Medical History | Veterinarian |
| **9** | Create Invoices & Record Payments | Admin, Staff |
| **10** | View Invoices | Adopter |
| **11** | View Dashboard & Statistics | Admin, Staff, Veterinarian |
| **12** | System Alerts (Low Stock / Expiry) | Admin, Staff, Veterinarian |
| **13** | View Available Animals | Adopter |
| **14** | View Medical History | Admin, Staff, Veterinarian |
| **15** | Record Feeding Logs | Admin, Staff |

---

## 3. Functional Decomposition Diagram

Shows the hierarchical breakdown of system functions.

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
│Secure   │ │Impound  │ │Medical  │ │Submit   │   │List /   │    │Create   │ │Dashboard│ │Manage   │
│Login    │ │(Intake) │ │Records  │ │Request  │   │Filter   │    │Invoices │ │Stats    │ │Users    │
└────┬────┘ └────┬────┘ └────┬────┘ └────┬────┘   └────┬────┘    └────┬────┘ └────┬────┘ └────┬────┘
     │           │           │           │             │              │           │           │
┌────▼────┐ ┌────▼────┐ ┌────▼────┐ ┌────▼────┐   ┌────▼────┐    ┌────▼────┐ ┌────▼────┐ ┌────▼────┐
│Adopter  │ │Profile  │ │Upcoming │ │Process  │   │Add New  │    │Record   │ │Financial│ │System   │
│Register │ │Mgmt     │ │Treatment│ │/Approve │   │Items    │    │Payments │ │Overview │ │Health   │
└────┬────┘ └────┬────┘ └────┬────┘ └────┬────┘   └────┬────┘    └────┬────┘ └────┬────┘ └────┬────┘
     │           │           │           │             │              │           │           │
┌────▼────┐ ┌────▼────┐ ┌────▼────┐ ┌────▼────┐   ┌────▼────┐    ┌────▼────┐ ┌────▼────┐ ┌────▼────┐
│Forget   │ │Status   │ │Health   │ │Schedule │   │Stock    │    │Financial│ │Intake   │ │Manage   │
│Reset PW │ │Updates  │ │History  │ │Interview│   │Alerts   │    │Reports  │ │Trends   │ │Vets     │
└────┬────┘ └────┬────┘ └────┬────┘ └────┬────┘   └────┬────┘    └────┬────┘ └────┬────┘ └────┬────┘
     │           │           │           │             │              │           │           │
┌────▼────┐ ┌────▼────┐ ┌────▼────┐ ┌────▼────┐   ┌────▼────┐    ┌────▼────┐ ┌────▼────┐ ┌────▼────┐
│Logout   │ │Adopt    │ │Feeding  │ │Cancel   │   │Stock    │    │Fee      │ │System   │ │Role     │
│         │ │Avail    │ │Logs     │ │Request  │   │Adjust   │    │Calc     │ │Logs     │ │Mgmt     │
└─────────┘ └─────────┘ └─────────┘ └─────────┘   └─────────┘    └─────────┘ └─────────┘ └─────────┘
```

### Detailed Function Breakdown

```text
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                              FUNCTIONAL DECOMPOSITION                                    │
├─────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                         │
│  1.0 ACCESS CONTROL (AUTH)                     5.0 INVENTORY CONTROL                    │
│  ├── 1.1 Secure Login                          ├── 5.1 List/Filter Items                │
│  ├── 1.2 Adopter Registration                  ├── 5.2 Add New Items                    │
│  ├── 1.3 Forgot/Reset Password                 ├── 5.3 Low Stock & Expiry Alerts        │
│  └── 1.4 Logout                                └── 5.4 Stock Adjustment                 │
│                                                                                         │
│  2.0 ANIMAL MANAGEMENT                         6.0 BILLING & FEES                       │
│  ├── 2.1 Impound (Intake)                      ├── 6.1 Create & Browse Invoices         │
│  ├── 2.2 Profile Management                    ├── 6.2 Record Payments                  │
│  ├── 2.3 Status Updates                        ├── 6.3 Financial Reports                │
│  ├── 2.4 Adoption Availability                 └── 6.4 Fee Calculation                  │
│  └── 2.5 Feeding Records (Animal)                                                       │
│                                                7.0 REPORTS & STATISTICS                 │
│  3.0 MEDICAL CARE                              ├── 7.1 Dashboard Stats                  │
│  ├── 3.1 Medical Records List                  ├── 7.2 Income & Financial Overview      │
│  ├── 3.2 Upcoming Treatments                   ├── 7.3 Intake/Adoption Trends           │
│  ├── 3.3 Health/Vaccination History            └── 7.4 System/User Activity Logs        │
│  └── 3.4 Feeding Logs (System)                                                          │
│                                                8.0 ADMIN PANEL (OPS)                    │
│  4.0 ADOPTION SERVICES                         ├── 8.1 User Management                  │
│  ├── 4.1 Submit Request                        ├── 8.2 System Health Check              │
│  ├── 4.2 Process/Approve Request               ├── 8.3 Veterinarian Management          │
│  ├── 4.3 Interview Scheduling                  └── 8.4 Role Management                  │
│  └── 4.4 Cancel Request                                                                 │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Conceptual Data Model (Entity-Relationship Diagram)

Shows the main entities and their relationships.

```text
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           CONCEPTUAL DATA MODEL                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘

                               ┌───────────┐
                               │   ROLE    │
                               ├───────────┤
                               │ RoleID    │
                               │ Role_Name │
                               └─────┬─────┘
                                     │ 1
                                     │
                                     │ has many
                                     │
                                     ▼ *
┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│                              ┌───────────────┐                              │
│                              │     USER      │                              │
│                              ├───────────────┤                              │
│                              │ UserID        │                              │
│                              │ Username      │                              │
│                              │ Email         │                              │
│                              │ Password_Hash │                              │
│                              │ Account_Status│                              │
│                              └───────┬───────┘                              │
│                                      │                                      │
│        ┌─────────────┬───────────────┼───────────────┬─────────────┐        │
│        │             │               │               │             │        │
│        ▼             ▼               ▼               ▼             ▼        │
│  ┌───────────┐ ┌───────────┐  ┌───────────┐  ┌───────────┐  ┌───────────┐  │
│  │   VET     │ │ ACTIVITY  │  │ ADOPTION  │  │  INVOICE  │  │  FEEDING  │  │
│  │  (1:1)    │ │   LOG     │  │  REQUEST  │  │           │  │  RECORD   │  │
│  └───────────┘ │  (1:*)    │  │   (1:*)   │  │   (1:*)   │  │   (1:*)   │  │
│                └───────────┘  └─────┬─────┘  └─────┬─────┘  └───────────┘  │
│                                     │              │                        │
│                                     │              │                        │
└─────────────────────────────────────┼──────────────┼────────────────────────┘
                                      │              │
                                      │              │ has many
                                      │              ▼
                                      │        ┌───────────┐
                                      │        │  PAYMENT  │
                                      │        │   (1:*)   │
                                      │        └───────────┘
                                      │
                                      │ refers to
                                      ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│                              ┌───────────────┐                              │
│                              │    ANIMAL     │                              │
│                              ├───────────────┤                              │
│                              │ AnimalID      │                              │
│                              │ Name          │                              │
│                              │ Type          │                              │
│                              │ Breed         │                              │
│                              │ Current_Status│                              │
│                              │ Image_URL     │                              │
│                              └───────┬───────┘                              │
│                                      │                                      │
│              ┌───────────────────────┼───────────────────────┐              │
│              │                       │                       │              │
│              ▼                       ▼                       ▼              │
│        ┌───────────┐           ┌───────────┐           ┌───────────┐        │
│        │  IMPOUND  │           │  MEDICAL  │           │  FEEDING  │        │
│        │  RECORD   │           │  RECORD   │           │  RECORD   │        │
│        │   (1:*)   │           │   (1:*)   │           │   (1:*)   │        │
│        └───────────┘           └───────────┘           └───────────┘        │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘

┌───────────────────────────────────┐
│           INVENTORY               │
├───────────────────────────────────┤
│ ItemID                            │
│ Item_Name                         │
│ Category                          │
│ Quantity_On_Hand                  │
│ Reorder_Level                     │
│ Expiration_Date                   │
└───────────────────────────────────┘
     (Standalone Entity)
```

### Entity Relationships

```text
┌───────────────────┬─────────────────────┬──────────────┬───────────────────────────────────┐
│ Parent Entity     │ Child Entity        │ Cardinality  │ Description                       │
├───────────────────┼─────────────────────┼──────────────┼───────────────────────────────────┤
│ Role              │ User                │ 1 : *        │ One role has many users           │
│ User              │ Veterinarian        │ 1 : 1        │ One user can be one veterinarian  │
│ User              │ Activity_Log        │ 1 : *        │ One user creates many logs        │
│ User              │ Adoption_Request    │ 1 : *        │ One user submits many requests    │
│ User              │ Invoice             │ 1 : *        │ One user has many invoices        │
│ User              │ Feeding_Record      │ 1 : *        │ One user records many feedings    │
│ Animal            │ Adoption_Request    │ 1 : *        │ One animal has many requests      │
│ Animal            │ Impound_Record      │ 1 : *        │ One animal has impound history    │
│ Animal            │ Medical_Record      │ 1 : *        │ One animal has many treatments    │
│ Animal            │ Feeding_Record      │ 1 : *        │ One animal has many feedings      │
│ Invoice           │ Payment             │ 1 : *        │ One invoice has many payments     │
│ Inventory         │ (Standalone)        │ -            │ Independent supply tracking       │
└───────────────────┴─────────────────────┴──────────────┴───────────────────────────────────┘
```

### Entity Attributes Summary

```text
┌─────────────────────┬────────────────────────────────────────────────────────────────────┐
│ Entity              │ Key Attributes                                                     │
├─────────────────────┼────────────────────────────────────────────────────────────────────┤
│ Role                │ RoleID (PK), Role_Name                                             │
│ User                │ UserID (PK), RoleID (FK), Username, Email, Password_Hash, Status  │
│ Veterinarian        │ VetID (PK), UserID (FK), License_Number, Specialization           │
│ Animal              │ AnimalID (PK), Name, Type, Breed, Status, Image_URL               │
│ Impound_Record      │ ImpoundID (PK), AnimalID (FK), Capture_Date, Location_Found       │
│ Medical_Record      │ RecordID (PK), AnimalID (FK), VetID (FK), Diagnosis, Next_Due     │
│ Feeding_Record      │ FeedingID (PK), AnimalID (FK), UserID (FK), Food_Type, Quantity   │
│ Adoption_Request    │ RequestID (PK), AnimalID (FK), UserID (FK), Status, Request_Date  │
│ Invoice             │ InvoiceID (PK), UserID (FK), Total_Amount, Status, Due_Date       │
│ Payment             │ PaymentID (PK), InvoiceID (FK), Amount, Payment_Method, Date      │
│ Inventory           │ ItemID (PK), Item_Name, Category, Quantity, Reorder_Level         │
│ Activity_Log        │ LogID (PK), UserID (FK), Action_Type, Description, IP_Address     │
└─────────────────────┴────────────────────────────────────────────────────────────────────┘
```

---

## 5. Module Interaction Matrix

```text
┌──────────────┬──────┬────────┬──────────┬─────────┬─────────┬───────────┬───────┐
│              │ Auth │ Animal │ Adoption │ Medical │ Billing │ Inventory │ Users │
├──────────────┼──────┼────────┼──────────┼─────────┼─────────┼───────────┼───────┤
│ Auth         │  -   │   R    │    R     │    R    │    R    │     R     │   R   │
│ Animal       │  -   │   -    │    W     │    W    │    -    │     -     │   -   │
│ Adoption     │  -   │   R    │    -     │    -    │    W    │     -     │   R   │
│ Medical      │  -   │   R    │    -     │    -    │    -    │     R     │   R   │
│ Billing      │  -   │   R    │    R     │    -    │    -    │     -     │   R   │
│ Inventory    │  -   │   -    │    -     │    R    │    -    │     -     │   -   │
│ Users        │  W   │   -    │    -     │    -    │    -    │     -     │   -   │
└──────────────┴──────┴────────┴──────────┴─────────┴─────────┴───────────┴───────┘

Legend: R = Reads from, W = Writes to, - = No direct interaction
```

---

## 6. Use Case Diagram (Previous Manual System)

Shows the actors and use cases for the previous paper-based manual system before computerization.

```text
┌───────────────────────────────────────────────────────────────────────────────────────────────┐
│                      Catarman Dog Pound - Previous Manual System (Paper-Based)                 │
│                                                                                                │
│                                                                                                │
│                                                    ╭────────────────────────────╮              │
│         O                                          │   SIGN VISITOR LOGBOOK     │              │
│        /|\  ─────────────────────────────────────► │       (Paper Log)          │              │
│        / \                                         ╰────────────────────────────╯              │
│                                                                                                │
│   POTENTIAL                                        ╭────────────────────────────╮              │
│    ADOPTER        ─────────────────────────────────│   VIEW ANIMALS IN KENNELS  │              │
│   (Walk-in)                                        │     (In-Person Only)       │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        └──────────────────────────────────────────►│  FILL PAPER ADOPTION FORM  │              │
│                                                    │       (Handwritten)        │              │
│                                                    ╰────────────────────────────╯              │
│                                                                                                │
│                                                    ╭────────────────────────────╮              │
│                   ─────────────────────────────────│    CHECK STATUS BY PHONE   │              │
│                                                    │        OR VISIT            │              │
│                                                    ╰────────────────────────────╯              │
│                                                                                                │
│                                                    ╭────────────────────────────╮              │
│                   ─────────────────────────────────│      PAY CASH / GET        │              │
│                                                    │    HANDWRITTEN RECEIPT     │              │
│                                                    ╰────────────────────────────╯              │
│                                                                                                │
│ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─  │
│                                                                                                │
│                                                    ╭────────────────────────────╮              │
│         O         ────────────────────────────────►│   WRITE ANIMAL PROFILE     │              │
│        /|\                                         │     CARD (Paper)           │              │
│        / \                                         ╰────────────────────────────╯              │
│                                                                                                │
│      STAFF                                         ╭────────────────────────────╮              │
│    (Employee)     ────────────────────────────────►│  HANDWRITE IMPOUND LOG     │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  REVIEW PAPER APPLICATIONS │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  CALCULATE FEES MANUALLY   │              │
│        │                                           │     (Calculator)           │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │   UPDATE STOCK CARDS       │              │
│        │                                           │     (Physical Count)       │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        └─────────────────────────────────────────► │   LOG FEEDING IN LEDGER    │              │
│                                                    ╰────────────────────────────╯              │
│                                                                                                │
│ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─  │
│                                                                                                │
│                                                    ╭────────────────────────────╮              │
│         O         ────────────────────────────────►│   WRITE TREATMENT RECORD   │              │
│        /|\                                         │      (Handwritten)         │              │
│        / \                                         ╰────────────────────────────╯              │
│                                                                                                │
│   VETERINARIAN                                     ╭────────────────────────────╮              │
│   (Visiting Vet)  ────────────────────────────────►│  FILE VACCINATION CARDS    │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        └─────────────────────────────────────────► │  CHECK DUE DATES MANUALLY  │              │
│                                                    │    (Paper Calendar)        │              │
│                                                    ╰────────────────────────────╯              │
│                                                                                                │
│ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─  │
│                                                                                                │
│                                                    ╭────────────────────────────╮              │
│         O         ────────────────────────────────►│   MAINTAIN STAFF ROSTER    │              │
│        /|\                                         │       (Paper List)         │              │
│        / \                                         ╰────────────────────────────╯              │
│                                                                                                │
│      ADMIN                                         ╭────────────────────────────╮              │
│   (Supervisor)    ────────────────────────────────►│   COMPILE MONTHLY STATS    │              │
│        │                                           │   (Manual Calculation)     │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        └─────────────────────────────────────────► │  HANDWRITE SUMMARY REPORTS │              │
│                                                    ╰────────────────────────────╯              │
│                                                                                                │
└───────────────────────────────────────────────────────────────────────────────────────────────┘
```

### Key Limitations of Manual System

```text
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                     LIMITATIONS OF MANUAL SYSTEM                                     │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  ✗ No Remote Access          │ Adopters must visit in person to view animals       │
│  ✗ Paper Records Only        │ Risk of loss, damage, or misfiling                  │
│  ✗ Manual Calculations       │ Prone to human error in billing and inventory       │
│  ✗ No Real-Time Updates      │ Status changes require physical file updates        │
│  ✗ Difficult Record Search   │ Finding specific records is time-consuming          │
│  ✗ No Automated Alerts       │ Staff must manually track due dates and low stock   │
│  ✗ Limited Data Analysis     │ Reports require manual compilation                   │
│  ✗ No Audit Trail            │ Difficult to track who made changes                  │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 7. Use Case Diagram (Current Computerized System)

Shows the actors interacting with the system and their associated use cases.

```text
┌───────────────────────────────────────────────────────────────────────────────────────────────┐
│                   CATARMAN DOG POUND & ANIMAL SHELTER MANAGEMENT SYSTEM                        │
│                                                                                                │
│         O                                          ╭────────────────────────────╮              │
│        /|\  ─────────────────────────────────────► │      LOGIN / LOGOUT        │              │
│        / \                                         ╰────────────────────────────╯              │
│                                                                                                │
│      ADMIN                                         ╭────────────────────────────╮              │
│        │  ────────────────────────────────────────►│ ADD / EDIT / DELETE USERS  │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │ADD / EDIT / DELETE ANIMALS │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  UPLOAD ANIMAL IMAGE       │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  RECORD IMPOUND INFO       │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  UPDATE ANIMAL STATUS      │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │    RECORD FEEDING LOG      │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  APPROVE / REJECT ADOPTION │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │ADD/EDIT/DELETE INVENTORY   │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  ADJUST STOCK QUANTITY     │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  VIEW LOW STOCK ALERTS     │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │CREATE / CANCEL INVOICE     │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │    RECORD PAYMENT          │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  VIEW / PRINT INVOICES     │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  VIEW FINANCIAL REPORTS    │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │    VIEW ACTIVITY LOGS      │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  UPDATE PROFILE / AVATAR   │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │    CHANGE PASSWORD         │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        └─────────────────────────────────────────► │      VIEW DASHBOARD        │              │
│                                                    ╰────────────────────────────╯              │
│                                                                                                │
│ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─  │
│                                                                                                │
│         O                                          ╭────────────────────────────╮              │
│        /|\  ─────────────────────────────────────► │      LOGIN / LOGOUT        │              │
│        / \                                         ╰────────────────────────────╯              │
│                                                                                                │
│      STAFF                                         ╭────────────────────────────╮              │
│        │  ────────────────────────────────────────►│   ADD / EDIT ANIMALS       │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  UPLOAD ANIMAL IMAGE       │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  RECORD IMPOUND INFO       │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  UPDATE ANIMAL STATUS      │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │    RECORD FEEDING LOG      │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  APPROVE / REJECT ADOPTION │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  ADD / EDIT INVENTORY      │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  ADJUST STOCK QUANTITY     │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  VIEW LOW STOCK ALERTS     │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │    CREATE INVOICE          │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │    RECORD PAYMENT          │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  VIEW / PRINT INVOICES     │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  UPDATE PROFILE / AVATAR   │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │    CHANGE PASSWORD         │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        └─────────────────────────────────────────► │      VIEW DASHBOARD        │              │
│                                                    ╰────────────────────────────╯              │
│                                                                                                │
│ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─  │
│                                                                                                │
│         O                                          ╭────────────────────────────╮              │
│        /|\  ─────────────────────────────────────► │      LOGIN / LOGOUT        │              │
│        / \                                         ╰────────────────────────────╯              │
│                                                                                                │
│   VETERINARIAN                                     ╭────────────────────────────╮              │
│        │  ────────────────────────────────────────►│   ADD / EDIT ANIMALS       │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  UPLOAD ANIMAL IMAGE       │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  UPDATE ANIMAL STATUS      │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │ADD/EDIT/DELETE MEDICAL REC │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │   VIEW MEDICAL HISTORY     │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │ VIEW UPCOMING TREATMENTS   │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │ VIEW OVERDUE TREATMENTS    │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │    RECORD FEEDING LOG      │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  APPROVE / REJECT ADOPTION │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  UPDATE PROFILE / AVATAR   │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │    CHANGE PASSWORD         │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        └─────────────────────────────────────────► │      VIEW DASHBOARD        │              │
│                                                    ╰────────────────────────────╯              │
│                                                                                                │
│ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─  │
│                                                                                                │
│         O                                          ╭────────────────────────────╮              │
│        /|\  ─────────────────────────────────────► │    REGISTER / LOGIN        │              │
│        / \                                         ╰────────────────────────────╯              │
│                                                                                                │
│    ADOPTER                                         ╭────────────────────────────╮              │
│        │  ────────────────────────────────────────►│  VIEW AVAILABLE ANIMALS    │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  SUBMIT ADOPTION REQUEST   │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │   VIEW ADOPTION STATUS     │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  CANCEL ADOPTION REQUEST   │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │   VIEW ADOPTION HISTORY    │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │      VIEW INVOICE          │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        ├─────────────────────────────────────────► │  UPDATE PROFILE / AVATAR   │              │
│        │                                           ╰────────────────────────────╯              │
│        │                                                                                       │
│        │                                           ╭────────────────────────────╮              │
│        └─────────────────────────────────────────► │    CHANGE PASSWORD         │              │
│                                                    ╰────────────────────────────╯              │
│                                                                                                │
└───────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 8. Data Flow Summary

```text
┌────────────────────────────────────────────────────────────────────────────────────┐
│                                  DATA FLOW PATHS                                    │
├────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                    │
│  ADOPTER ───► Submit Request ───► ADOPTION ───► Create Invoice ───► BILLING       │
│                     │                  │                                           │
│                     ▼                  ▼                                           │
│               Animal Selection    Update Animal                                    │
│                     │               Status                                         │
│                     ▼                  │                                           │
│                 ANIMAL ◄───────────────┘                                           │
│                     │                                                              │
│                     ▼                                                              │
│              VET adds treatment                                                    │
│                     │                                                              │
│                     ▼                                                              │
│                 MEDICAL ───► Check Inventory ───► INVENTORY                        │
│                                                                                    │
└────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 9. Key-Based Data Model

Shows entity relationships with Primary Keys (PK) and Foreign Keys (FK) only.

```text
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           KEY-BASED DATA MODEL                                       │
└─────────────────────────────────────────────────────────────────────────────────────┘

    ┌─────────────────┐                         ┌─────────────────┐
    │     ROLES       │                         │   INVENTORY     │
    ├─────────────────┤                         ├─────────────────┤
    │ PK: RoleID      │                         │ PK: ItemID      │
    └────────┬────────┘                         └─────────────────┘
             │ 1                                   (Standalone)
             │
             │ *
    ┌────────▼────────┐
    │     USERS       │
    ├─────────────────┤
    │ PK: UserID      │
    │ FK: RoleID      │◄─────────────────────────────────────────────────────────┐
    └────────┬────────┘                                                          │
             │                                                                   │
    ┌────────┼────────────────────┬─────────────────────┬──────────────────┐    │
    │ 1      │ 1                  │ 1                   │ 1                │    │
    │        │                    │                     │                  │    │
    ▼ 1      ▼ *                  ▼ *                   ▼ *                ▼ *  │
┌─────────┐ ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
│ VETS    │ │ ACTIVITY_LOGS   │ │ FEEDING_RECORDS │ │ ADOPT_REQUESTS  │ │   INVOICES      │
├─────────┤ ├─────────────────┤ ├─────────────────┤ ├─────────────────┤ ├─────────────────┤
│PK:VetID │ │ PK: LogID       │ │ PK: FeedingID   │ │ PK: RequestID   │ │ PK: InvoiceID   │
│FK:UserID│ │ FK: UserID      │ │ FK: AnimalID    │ │ FK: AnimalID    │ │ FK: PayerUserID │
└────┬────┘ └─────────────────┘ │ FK: Fed_By      │ │ FK: AdopterID   │ │ FK: IssuedByID  │
     │                          │     UserID      │ │ FK: ProcessedBy │ │ FK: AnimalID    │
     │                          └────────┬────────┘ └────────┬────────┘ │ FK: RequestID   │
     │                                   │                   │          └────────┬────────┘
     │                                   │ *                 │ *                 │ 1
     │                                   │                   │                   │
     │                          ┌────────┴───────────────────┴────────┐          │
     │                          │                                     │          ▼ *
     │                          ▼                                     ▼    ┌─────────────────┐
     │                    ┌─────────────────┐                              │   PAYMENTS      │
     │                    │    ANIMALS      │                              ├─────────────────┤
     │                    │ PK: AnimalID    │                              │ PK: PaymentID   │
     │                    └────────┬────────┘                              │ FK: InvoiceID   │
     │                             │                                       │ FK: ReceivedBy  │
     │          ┌──────────────────┼──────────────────┐                    │     UserID      │
     │          │ 1                │ 1                │ 1                  └─────────────────┘
     │          │                  │                  │
     │          ▼ *                ▼ *                ▼ *
     │    ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
     │    │ IMPOUND_RECORDS │ │ MEDICAL_RECORDS │ │ FEEDING_RECORDS │
     │    ├─────────────────┤ ├─────────────────┤ ├─────────────────┤
     │    │ PK: ImpoundID   │ │ PK: RecordID    │ │ (Same as above) │
     │    │ FK: AnimalID    │ │ FK: AnimalID    │ └─────────────────┘
     │    └─────────────────┘ │ FK: VetID       │
     │                        └────────┬────────┘
     │                                 │
     └─────────────────────────────────┘
```

### Key Reference Table

```text
┌─────────────────────┬───────────────────┬─────────────────────────────────────────────┐
│ Entity              │ Primary Key       │ Foreign Keys                                │
├─────────────────────┼───────────────────┼─────────────────────────────────────────────┤
│ Roles               │ RoleID            │ (none)                                      │
│ Users               │ UserID            │ RoleID → Roles                              │
│ Veterinarians       │ VetID             │ UserID → Users                              │
│ Animals             │ AnimalID          │ (none)                                      │
│ Impound_Records     │ ImpoundID         │ AnimalID → Animals                          │
│ Medical_Records     │ RecordID          │ AnimalID → Animals, VetID → Veterinarians   │
│ Feeding_Records     │ FeedingID         │ AnimalID → Animals, Fed_By_UserID → Users   │
│ Adoption_Requests   │ RequestID         │ AnimalID → Animals, Adopter_UserID → Users, │
│                     │                   │ Processed_By_UserID → Users                 │
│ Inventory           │ ItemID            │ (none - standalone)                         │
│ Invoices            │ InvoiceID         │ Payer_UserID → Users, Issued_By_UserID →    │
│                     │                   │ Users, Related_AnimalID → Animals,          │
│                     │                   │ Related_RequestID → Adoption_Requests       │
│ Payments            │ PaymentID         │ InvoiceID → Invoices, Received_By → Users   │
│ Activity_Logs       │ LogID             │ UserID → Users                              │
└─────────────────────┴───────────────────┴─────────────────────────────────────────────┘
```

---

## 10. Fully Attributed Data Model

Shows all entities with complete attribute lists, data types, and constraints.

```text
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        FULLY ATTRIBUTED DATA MODEL                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 9.1 Roles

```text
┌───────────────────────────────────────────────────────────────────────────┐
│                              ROLES                                        │
├───────────────────┬───────────────┬───────────────────────────────────────┤
│ Attribute         │ Data Type     │ Constraints                           │
├───────────────────┼───────────────┼───────────────────────────────────────┤
│ RoleID            │ INT           │ PK, AUTO_INCREMENT                    │
│ Role_Name         │ VARCHAR(50)   │ NOT NULL, UNIQUE                      │
│ Created_At        │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP             │
│ Updated_At        │ DATETIME      │ ON UPDATE CURRENT_TIMESTAMP           │
└───────────────────┴───────────────┴───────────────────────────────────────┘
```

### 9.2 Users

```text
┌───────────────────────────────────────────────────────────────────────────┐
│                              USERS                                        │
├───────────────────┬───────────────┬───────────────────────────────────────┤
│ Attribute         │ Data Type     │ Constraints                           │
├───────────────────┼───────────────┼───────────────────────────────────────┤
│ UserID            │ INT           │ PK, AUTO_INCREMENT                    │
│ RoleID            │ INT           │ FK → Roles(RoleID), NOT NULL          │
│ FirstName         │ VARCHAR(50)   │ NOT NULL                              │
│ LastName          │ VARCHAR(50)   │ NOT NULL                              │
│ Username          │ VARCHAR(50)   │ NOT NULL, UNIQUE                      │
│ Email             │ VARCHAR(100)  │ NOT NULL, UNIQUE                      │
│ Contact_Number    │ VARCHAR(20)   │ NULLABLE                              │
│ Address           │ TEXT          │ NULLABLE                              │
│ Avatar_Url        │ VARCHAR(255)  │ NULLABLE                              │
│ Password_Hash     │ VARCHAR(255)  │ NOT NULL                              │
│ Account_Status    │ ENUM          │ 'Active', 'Inactive', 'Banned'        │
│ Preferences       │ JSON          │ NULLABLE                              │
│ Is_Deleted        │ BOOLEAN       │ DEFAULT FALSE                         │
│ Created_At        │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP             │
│ Updated_At        │ DATETIME      │ ON UPDATE CURRENT_TIMESTAMP           │
└───────────────────┴───────────────┴───────────────────────────────────────┘
```

### 9.3 Veterinarians

```text
┌───────────────────────────────────────────────────────────────────────────┐
│                           VETERINARIANS                                   │
├───────────────────┬───────────────┬───────────────────────────────────────┤
│ Attribute         │ Data Type     │ Constraints                           │
├───────────────────┼───────────────┼───────────────────────────────────────┤
│ VetID             │ INT           │ PK, AUTO_INCREMENT                    │
│ UserID            │ INT           │ FK → Users(UserID), NOT NULL, UNIQUE  │
│ License_Number    │ VARCHAR(50)   │ NOT NULL                              │
│ Specialization    │ VARCHAR(100)  │ NULLABLE                              │
│ Years_Experience  │ INT           │ DEFAULT 0                             │
│ Clinic_Name       │ VARCHAR(100)  │ NULLABLE                              │
│ Bio               │ TEXT          │ NULLABLE                              │
│ Created_At        │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP             │
│ Updated_At        │ DATETIME      │ ON UPDATE CURRENT_TIMESTAMP           │
└───────────────────┴───────────────┴───────────────────────────────────────┘
```

### 9.4 Animals

```text
┌───────────────────────────────────────────────────────────────────────────┐
│                              ANIMALS                                      │
├───────────────────┬───────────────┬───────────────────────────────────────┤
│ Attribute         │ Data Type     │ Constraints                           │
├───────────────────┼───────────────┼───────────────────────────────────────┤
│ AnimalID          │ INT           │ PK, AUTO_INCREMENT                    │
│ Name              │ VARCHAR(50)   │ NOT NULL                              │
│ Type              │ ENUM          │ 'Dog', 'Cat', 'Other', NOT NULL       │
│ Breed             │ VARCHAR(50)   │ NULLABLE                              │
│ Gender            │ ENUM          │ 'Male', 'Female', 'Unknown'           │
│ Age_Group         │ VARCHAR(20)   │ NULLABLE                              │
│ Weight            │ DECIMAL(5,2)  │ NULLABLE                              │
│ Intake_Date       │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP             │
│ Intake_Status     │ ENUM          │ 'Stray', 'Surrendered', 'Confiscated' │
│ Current_Status    │ ENUM          │ 'Available', 'Adopted', 'Deceased',   │
│                   │               │ 'In Treatment', 'Quarantine',         │
│                   │               │ 'Reclaimed', 'Reserved'               │
│ Image_URL         │ VARCHAR(255)  │ NULLABLE                              │
│ Is_Deleted        │ BOOLEAN       │ DEFAULT FALSE                         │
│ Created_At        │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP             │
│ Updated_At        │ DATETIME      │ ON UPDATE CURRENT_TIMESTAMP           │
└───────────────────┴───────────────┴───────────────────────────────────────┘
```

### 9.5 Impound_Records

```text
┌───────────────────────────────────────────────────────────────────────────┐
│                          IMPOUND_RECORDS                                  │
├─────────────────────┬───────────────┬─────────────────────────────────────┤
│ Attribute           │ Data Type     │ Constraints                         │
├─────────────────────┼───────────────┼─────────────────────────────────────┤
│ ImpoundID           │ INT           │ PK, AUTO_INCREMENT                  │
│ AnimalID            │ INT           │ FK → Animals(AnimalID), NOT NULL    │
│ Capture_Date        │ DATETIME      │ NOT NULL                            │
│ Location_Found      │ VARCHAR(255)  │ NOT NULL                            │
│ Impounding_Officer  │ VARCHAR(100)  │ NOT NULL                            │
│ Condition_On_Arrival│ TEXT          │ NULLABLE                            │
│ Created_At          │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP           │
│ Updated_At          │ DATETIME      │ ON UPDATE CURRENT_TIMESTAMP         │
└─────────────────────┴───────────────┴─────────────────────────────────────┘
```

### 9.6 Medical_Records

```text
┌───────────────────────────────────────────────────────────────────────────┐
│                          MEDICAL_RECORDS                                  │
├───────────────────┬───────────────┬───────────────────────────────────────┤
│ Attribute         │ Data Type     │ Constraints                           │
├───────────────────┼───────────────┼───────────────────────────────────────┤
│ RecordID          │ INT           │ PK, AUTO_INCREMENT                    │
│ AnimalID          │ INT           │ FK → Animals(AnimalID), NOT NULL      │
│ VetID             │ INT           │ FK → Veterinarians(VetID), NOT NULL   │
│ Date_Performed    │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP             │
│ Diagnosis_Type    │ ENUM          │ 'Checkup', 'Vaccination', 'Surgery',  │
│                   │               │ 'Treatment', 'Emergency', 'Deworming',│
│                   │               │ 'Spay/Neuter', NOT NULL               │
│ Vaccine_Name      │ VARCHAR(100)  │ NULLABLE                              │
│ Treatment_Notes   │ TEXT          │ NULLABLE                              │
│ Next_Due_Date     │ DATE          │ NULLABLE                              │
│ Created_At        │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP             │
│ Updated_At        │ DATETIME      │ ON UPDATE CURRENT_TIMESTAMP           │
└───────────────────┴───────────────┴───────────────────────────────────────┘
```

### 9.7 Feeding_Records

```text
┌───────────────────────────────────────────────────────────────────────────┐
│                          FEEDING_RECORDS                                  │
├───────────────────┬───────────────┬───────────────────────────────────────┤
│ Attribute         │ Data Type     │ Constraints                           │
├───────────────────┼───────────────┼───────────────────────────────────────┤
│ FeedingID         │ INT           │ PK, AUTO_INCREMENT                    │
│ AnimalID          │ INT           │ FK → Animals(AnimalID), NOT NULL      │
│ Fed_By_UserID     │ INT           │ FK → Users(UserID), NOT NULL          │
│ Feeding_Time      │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP             │
│ Food_Type         │ VARCHAR(50)   │ NOT NULL                              │
│ Quantity_Used     │ DECIMAL(5,2)  │ NOT NULL                              │
│ Created_At        │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP             │
└───────────────────┴───────────────┴───────────────────────────────────────┘
```

### 9.8 Adoption_Requests

```text
┌───────────────────────────────────────────────────────────────────────────┐
│                         ADOPTION_REQUESTS                                 │
├─────────────────────┬───────────────┬─────────────────────────────────────┤
│ Attribute           │ Data Type     │ Constraints                         │
├─────────────────────┼───────────────┼─────────────────────────────────────┤
│ RequestID           │ INT           │ PK, AUTO_INCREMENT                  │
│ AnimalID            │ INT           │ FK → Animals(AnimalID), NOT NULL    │
│ Adopter_UserID      │ INT           │ FK → Users(UserID), NOT NULL        │
│ Request_Date        │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP           │
│ Status              │ ENUM          │ 'Pending', 'Interview Scheduled',   │
│                     │               │ 'Approved', 'Rejected', 'Completed',│
│                     │               │ 'Cancelled'                         │
│ Interview_Date      │ DATETIME      │ NULLABLE                            │
│ Staff_Comments      │ TEXT          │ NULLABLE                            │
│ Processed_By_UserID │ INT           │ FK → Users(UserID), NULLABLE        │
│ Created_At          │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP           │
│ Updated_At          │ DATETIME      │ ON UPDATE CURRENT_TIMESTAMP         │
└─────────────────────┴───────────────┴─────────────────────────────────────┘
```

### 9.9 Inventory

```text
┌───────────────────────────────────────────────────────────────────────────┐
│                            INVENTORY                                      │
├───────────────────┬───────────────┬───────────────────────────────────────┤
│ Attribute         │ Data Type     │ Constraints                           │
├───────────────────┼───────────────┼───────────────────────────────────────┤
│ ItemID            │ INT           │ PK, AUTO_INCREMENT                    │
│ Item_Name         │ VARCHAR(100)  │ NOT NULL                              │
│ Category          │ ENUM          │ 'Medical', 'Food', 'Cleaning',        │
│                   │               │ 'Supplies', NOT NULL                  │
│ Quantity_On_Hand  │ INT           │ DEFAULT 0                             │
│ Reorder_Level     │ INT           │ DEFAULT 10                            │
│ Expiration_Date   │ DATE          │ NULLABLE                              │
│ Supplier_Name     │ VARCHAR(100)  │ NULLABLE                              │
│ Last_Updated      │ DATETIME      │ ON UPDATE CURRENT_TIMESTAMP           │
│ Created_At        │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP             │
└───────────────────┴───────────────┴───────────────────────────────────────┘
```

### 9.10 Invoices

```text
┌───────────────────────────────────────────────────────────────────────────┐
│                            INVOICES                                       │
├───────────────────┬───────────────┬───────────────────────────────────────┤
│ Attribute         │ Data Type     │ Constraints                           │
├───────────────────┼───────────────┼───────────────────────────────────────┤
│ InvoiceID         │ INT           │ PK, AUTO_INCREMENT                    │
│ Payer_UserID      │ INT           │ FK → Users(UserID), NOT NULL          │
│ Issued_By_UserID  │ INT           │ FK → Users(UserID), NOT NULL          │
│ Transaction_Type  │ ENUM          │ 'Adoption Fee', 'Reclaim Fee'         │
│ Total_Amount      │ DECIMAL(10,2) │ NOT NULL                              │
│ Status            │ ENUM          │ 'Unpaid', 'Paid', 'Cancelled'         │
│ Is_Deleted        │ BOOLEAN       │ DEFAULT FALSE                         │
│ Related_AnimalID  │ INT           │ FK → Animals(AnimalID), NULLABLE      │
│ Related_RequestID │ INT           │ FK → Adoption_Requests, NULLABLE      │
│ Created_At        │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP             │
│ Updated_At        │ DATETIME      │ ON UPDATE CURRENT_TIMESTAMP           │
└───────────────────┴───────────────┴───────────────────────────────────────┘
```

### 9.11 Payments

```text
┌───────────────────────────────────────────────────────────────────────────┐
│                            PAYMENTS                                       │
├─────────────────────┬───────────────┬─────────────────────────────────────┤
│ Attribute           │ Data Type     │ Constraints                         │
├─────────────────────┼───────────────┼─────────────────────────────────────┤
│ PaymentID           │ INT           │ PK, AUTO_INCREMENT                  │
│ InvoiceID           │ INT           │ FK → Invoices(InvoiceID), NOT NULL  │
│ Received_By_UserID  │ INT           │ FK → Users(UserID), NOT NULL        │
│ Payment_Date        │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP           │
│ Amount_Paid         │ DECIMAL(10,2) │ NOT NULL                            │
│ Payment_Method      │ ENUM          │ 'Cash', 'Credit Card', 'Gcash',     │
│                     │               │ 'Bank Transfer'                     │
│ Transaction_Ref     │ VARCHAR(100)  │ NULLABLE                            │
│ Created_At          │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP           │
└─────────────────────┴───────────────┴─────────────────────────────────────┘
```

### 9.12 Activity_Logs

```text
┌───────────────────────────────────────────────────────────────────────────┐
│                          ACTIVITY_LOGS                                    │
├───────────────────┬───────────────┬───────────────────────────────────────┤
│ Attribute         │ Data Type     │ Constraints                           │
├───────────────────┼───────────────┼───────────────────────────────────────┤
│ LogID             │ INT           │ PK, AUTO_INCREMENT                    │
│ UserID            │ INT           │ FK → Users(UserID), NULLABLE          │
│ Action_Type       │ VARCHAR(50)   │ NOT NULL                              │
│ Description       │ TEXT          │ NOT NULL                              │
│ IP_Address        │ VARCHAR(45)   │ NULLABLE                              │
│ User_Agent        │ TEXT          │ NULLABLE                              │
│ Created_At        │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP             │
└───────────────────┴───────────────┴───────────────────────────────────────┘
```
