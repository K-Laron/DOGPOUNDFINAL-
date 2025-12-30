# System Diagrams

## Catarman Dog Pound Management System

This document contains the System Architecture, Context Diagram, Functional Decomposition, and Data Models.

---

## 1. System Architecture Diagram

Shows the overall system architecture with all layers and components.

```
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
│  │  │   Router     │  │  Components   │  │   Stores     │  │   Services   │   │   │
│  │  │  (SPA Nav)   │  │  (UI Parts)   │  │   (State)    │  │  (API Calls) │   │   │
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
│  │  │  • DashboardController    • PaymentController                         │  │   │
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

```
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

```
                              ┌─────────────────────────────────────┐
                              │                                     │
     ┌──────────────┐         │      CATARMAN DOG POUND             │         ┌──────────────┐
     │              │ Login   │      MANAGEMENT SYSTEM              │ Manage  │              │
     │   ADOPTER    │────────►│                                     │◄───────►│    ADMIN     │
     │              │ Browse  │  ┌─────────────────────────────┐    │ Users   │              │
     │  (Public)    │────────►│  │                             │    │         │ (Full Access)│
     │              │         │  │    ┌─────────────────┐      │    │         │              │
     │              │ Submit  │  │    │                 │      │    │         │              │
     │              │ Request │  │    │   APPLICATION   │      │    │         │              │
     │              │────────►│  │    │     LOGIC       │      │    │         └──────────────┘
     │              │         │  │    │                 │      │    │
     │              │◄────────│  │    └────────┬────────┘      │    │
     │              │ Status  │  │             │               │    │
     └──────────────┘         │  │    ┌────────▼────────┐      │    │
                              │  │    │                 │      │    │
     ┌──────────────┐         │  │    │    REST API     │      │    │         ┌──────────────┐
     │              │ Animals │  │    │                 │      │    │ Animals │              │
     │    STAFF     │◄───────►│  │    └─────────────────┘      │    │◄───────►│VETERINARIAN  │
     │              │         │  │                             │    │         │              │
     │  (Employee)  │ Billing │  └─────────────────────────────┘    │ Medical │   (Vet)      │
     │              │◄───────►│                                     │◄───────►│              │
     │              │         │                                     │         │              │
     │              │Inventory│                                     │ Records │              │
     │              │◄───────►│                                     │◄───────►│              │
     └──────────────┘         │                                     │         └──────────────┘
                              │                                     │
                              └──────────────────┬──────────────────┘
                                                 │
                              ┌──────────────────┴──────────────────┐
                              │                                     │
                              ▼                                     ▼
                     ┌─────────────────┐                   ┌─────────────────┐
                     │                 │                   │                 │
                     │     MySQL       │                   │      File       │
                     │    DATABASE     │                   │    STORAGE      │
                     │                 │                   │                 │
                     │  (12 Tables)    │                   │   (Uploads)     │
                     │                 │                   │                 │
                     └─────────────────┘                   └─────────────────┘
```

### External Entities

```
┌──────────────┬──────────────┬─────────────────────────────────────────────────┐
│ Entity       │ Role         │ Primary Interactions                            │
├──────────────┼──────────────┼─────────────────────────────────────────────────┤
│ Adopter      │ Public user  │ Browse animals, submit adoption requests        │
│ Staff        │ Employee     │ Manage animals, billing, inventory, adoptions   │
│ Veterinarian │ Licensed vet │ Medical records, animal health                  │
│ Admin        │ Superuser    │ User management, full system access             │
└──────────────┴──────────────┴─────────────────────────────────────────────────┘
```

### Data Stores

```
┌─────────────────┬────────────┬─────────────────────────────────────────────────┐
│ Store           │ Type       │ Contents                                        │
├─────────────────┼────────────┼─────────────────────────────────────────────────┤
│ MySQL Database  │ Relational │ Users, Animals, Adoptions, Medical, Billing     │
│ File Storage    │ Filesystem │ Animal images, User avatars                     │
└─────────────────┴────────────┴─────────────────────────────────────────────────┘
```

---

## 3. Functional Decomposition Diagram

Shows the hierarchical breakdown of system functions.

```
                            ┌───────────────────────────────────┐
                            │   CATARMAN DOG POUND MANAGEMENT   │
                            │              SYSTEM               │
                            └─────────────────┬─────────────────┘
                                              │
        ┌─────────────┬─────────────┬─────────┴─────────┬─────────────┬─────────────┐
        │             │             │                   │             │             │
        ▼             ▼             ▼                   ▼             ▼             ▼
   ┌─────────┐   ┌─────────┐   ┌─────────┐       ┌─────────┐   ┌─────────┐   ┌─────────┐
   │  1.0    │   │  2.0    │   │  3.0    │       │  4.0    │   │  5.0    │   │  6.0    │
   │  AUTH   │   │ ANIMAL  │   │ADOPTION │       │ MEDICAL │   │ BILLING │   │INVENTORY│
   └────┬────┘   └────┬────┘   └────┬────┘       └────┬────┘   └────┬────┘   └────┬────┘
        │             │             │                 │             │             │
   ┌────┴────┐   ┌────┴────┐   ┌────┴────┐       ┌────┴────┐   ┌────┴────┐   ┌────┴────┐
   │         │   │         │   │         │       │         │   │         │   │         │
   ▼         ▼   ▼         ▼   ▼         ▼       ▼         ▼   ▼         ▼   ▼         ▼
┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐
│ 1.1  │ │ 1.2  │ │ 2.1  │ │ 2.2  │ │ 3.1  │ │ 3.2  │ │ 4.1  │ │ 4.2  │ │ 5.1  │ │ 5.2  │ │ 6.1  │ │ 6.2  │
│Login │ │Regis-│ │Add   │ │Update│ │Submit│ │Proces│ │Add   │ │Track │ │Create│ │Record│ │Track │ │Stock │
│      │ │ ter  │ │Animal│ │Status│ │Requst│ │Requst│ │Record│ │Overdu│ │Invoic│ │Paymt │ │Stock │ │Alert │
└──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘
```

### Detailed Function Breakdown

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              FUNCTIONAL DECOMPOSITION                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                     │
│  1.0 AUTHENTICATION                    4.0 MEDICAL RECORDS                          │
│  ├── 1.1 User Login                    ├── 4.1 Add Treatment Record                 │
│  ├── 1.2 User Registration             ├── 4.2 Track Due Dates                      │
│  ├── 1.3 Token Refresh                 ├── 4.3 Flag Overdue Treatments              │
│  ├── 1.4 Password Change               ├── 4.4 Export Medical History               │
│  └── 1.5 Logout                        └── 4.5 Assign Veterinarian                  │
│                                                                                     │
│  2.0 ANIMAL MANAGEMENT                 5.0 BILLING                                   │
│  ├── 2.1 Add Animal                    ├── 5.1 Create Invoice                       │
│  ├── 2.2 Update Animal Info            ├── 5.2 Record Payment                       │
│  ├── 2.3 Update Status                 ├── 5.3 Generate Reports                     │
│  ├── 2.4 Upload Image                  ├── 5.4 PDF Export                           │
│  ├── 2.5 Add Impound Record            └── 5.5 Track Outstanding                    │
│  └── 2.6 Record Feeding                                                             │
│                                        6.0 INVENTORY                                 │
│  3.0 ADOPTION                          ├── 6.1 Add Item                             │
│  ├── 3.1 Submit Request                ├── 6.2 Update Quantity                      │
│  ├── 3.2 Review Request                ├── 6.3 Low Stock Alerts                     │
│  ├── 3.3 Schedule Interview            ├── 6.4 Expiration Tracking                  │
│  ├── 3.4 Approve/Reject                └── 6.5 Export Report                        │
│  ├── 3.5 Complete Adoption                                                          │
│  └── 3.6 Calculate Fees                7.0 USER MANAGEMENT (Admin)                   │
│                                        ├── 7.1 Create User                          │
│                                        ├── 7.2 Assign Role                          │
│                                        ├── 7.3 Activate/Deactivate                  │
│                                        └── 7.4 View Activity Logs                    │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Conceptual Data Model (Entity-Relationship Diagram)

Shows the main entities and their relationships.

```
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

```
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

```
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

```
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

## 6. Data Flow Summary

```
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

## 7. Key-Based Data Model

Shows entity relationships with Primary Keys (PK) and Foreign Keys (FK) only.

```
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
     │ 1                                 │ *                 │ *                 │ 1
     │                                   │                   │                   │
     │                          ┌────────┴───────────────────┴────────┐          │
     │                          │                                     │          ▼ *
     │                          ▼                                     ▼    ┌─────────────────┐
     │                    ┌─────────────────┐                              │   PAYMENTS      │
     │                    │    ANIMALS      │                              ├─────────────────┤
     │                    ├─────────────────┤                              │ PK: PaymentID   │
     │                    │ PK: AnimalID    │                              │ FK: InvoiceID   │
     │                    └────────┬────────┘                              │ FK: ReceivedBy  │
     │                             │                                       │     UserID      │
     │          ┌──────────────────┼──────────────────┐                    └─────────────────┘
     │          │ 1                │ 1                │ 1
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

```
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

## 8. Fully Attributed Data Model

Shows all entities with complete attribute lists, data types, and constraints.

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        FULLY ATTRIBUTED DATA MODEL                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 7.1 Roles

```
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

### 7.2 Users

```
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

### 7.3 Veterinarians

```
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

### 7.4 Animals

```
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
│                   │               │ 'Reclaimed'                           │
│ Image_URL         │ VARCHAR(255)  │ NULLABLE                              │
│ Is_Deleted        │ BOOLEAN       │ DEFAULT FALSE                         │
│ Created_At        │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP             │
│ Updated_At        │ DATETIME      │ ON UPDATE CURRENT_TIMESTAMP           │
└───────────────────┴───────────────┴───────────────────────────────────────┘
```

### 7.5 Impound_Records

```
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

### 7.6 Medical_Records

```
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

### 7.7 Feeding_Records

```
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

### 7.8 Adoption_Requests

```
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

### 7.9 Inventory

```
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

### 7.10 Invoices

```
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

### 7.11 Payments

```
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
│ Payment_Method      │ ENUM          │ 'Cash', 'GCash', 'Bank Transfer'    │
│ Reference_Number    │ VARCHAR(50)   │ NULLABLE                            │
│ Created_At          │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP           │
└─────────────────────┴───────────────┴─────────────────────────────────────┘
```

### 7.12 Activity_Logs

```
┌───────────────────────────────────────────────────────────────────────────┐
│                          ACTIVITY_LOGS                                    │
├───────────────────┬───────────────┬───────────────────────────────────────┤
│ Attribute         │ Data Type     │ Constraints                           │
├───────────────────┼───────────────┼───────────────────────────────────────┤
│ LogID             │ INT           │ PK, AUTO_INCREMENT                    │
│ UserID            │ INT           │ FK → Users(UserID), ON DELETE SET NULL│
│ Action_Type       │ VARCHAR(50)   │ NOT NULL                              │
│ Description       │ TEXT          │ NULLABLE                              │
│ IP_Address        │ VARCHAR(45)   │ NULLABLE                              │
│ Log_Date          │ DATETIME      │ DEFAULT CURRENT_TIMESTAMP             │
└───────────────────┴───────────────┴───────────────────────────────────────┘
```

---

## Summary Statistics

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        DATABASE STATISTICS                               │
├───────────────────────────────┬─────────────────────────────────────────┤
│ Total Entities                │ 12                                      │
│ Total Relationships           │ 15                                      │
│ Standalone Entities           │ 1 (Inventory)                           │
│ Central Entities              │ Users, Animals                          │
│ Highest FK Count              │ Invoices (4 FKs)                        │
│ Database Engine               │ InnoDB                                  │
│ Character Set                 │ utf8mb4_unicode_ci                      │
└───────────────────────────────┴─────────────────────────────────────────┘
```

---

## 9. Use Case Diagram

Shows the system use cases for all actors.

### 9.0 Complete System Use Case Diagram (All Actors)

```
+=====================================================================================+
|                      CATARMAN DOG POUND MANAGEMENT SYSTEM                           |
|                            << System Boundary >>                                    |
+=====================================================================================+
|                                                                                     |
|     AUTHENTICATION MODULE                                                           |
|     .-----------.  .-----------.  .-----------.  .--------------.                  |
|     (   Login   )  (  Logout   )  ( Register  )  ( Token Refresh)                  |
|     '-----------'  '-----------'  '-----------'  '--------------'                  |
|           |              |              |               |                           |
|           |              |              |               |                           |
+-----------|--------------|--------------|-----------+   |   +-----------------------+
            |              |              |           |   |   |                       |
    .---.   |      .---.   |      .---.   |   .---.   |   |   |   .---.               |
    | O |<--+      | O |<--+      | O |<--+   | O |<--+---+   +-->| O |               |
   /|\+/|\        /|\+/|\        /|\+/|\     /|\+/|\              /|\+/|\              |
    /\            /\             /\          /\                   /\                  |
  ADMIN         STAFF        ADOPTER     VETERINARIAN          (ALL)                 |
    |              |              |           |                                       |
    |              |              |           |                                       |
    |              |              |           |   MEDICAL RECORDS MODULE              |
    |              |              |           |   .-------------------.               |
    |              |              |           +-->( Create Med Record )               |
    |              |              |           |   '-------------------'               |
    |              |              |           |   .-------------------.               |
    |              |              |           +-->( Record Vaccination)               |
    |              |              |           |   '-------------------'               |
    |              |              |           |   .-------------------.               |
    |              |              |           +-->( Record Surgery    )               |
    |              |              |           |   '-------------------'               |
    |              |              |           |   .-------------------.               |
    |              |              |           +-->( Track Treatments  )               |
    |              |              |               '-------------------'               |
    |              |              |                                                   |
    |              |              |   ADOPTION MODULE                                 |
    |              |              |   .-------------------.                           |
    |              |              +-->( Submit Adoption   )                           |
    |              |              |   '-------------------'                           |
    |              |              |   .-------------------.                           |
    |              |              +-->( View Request Status)                          |
    |              |                  '-------------------'                           |
    |              |                                                                  |
    |              |   .-------------------.  .-------------------.                   |
    |              +-->( Schedule Interview)  ( Approve/Reject    )                   |
    |              |   '-------------------'  '-------------------'                   |
    |              |   .-------------------.                                          |
    |              +-->( Complete Adoption )                                          |
    |              |   '-------------------'                                          |
    |              |                                                                  |
    |              |   ANIMAL MANAGEMENT MODULE                                       |
    |              |   .-------------.  .-------------.  .-------------.             |
    +--------------|-->( Add Animal  )  ( Update Animal)  ( Delete Animal)            |
    |              +-->| (Staff/Admin)|  |             |  | (Admin Only) |            |
    |              |   '-------------'  '-------------'  '-------------'             |
    |              |   .-------------.  .-------------.  .-------------.             |
    |              +-->( View Animals)  (Record Impound)  (Update Status)             |
    |              |   '-------------'  '-------------'  '-------------'             |
    |              |   .-------------.                                                |
    |              +-->(Upload Image )                                                |
    |              |   '-------------'                                                |
    |              |                                                                  |
    |              |   INVENTORY MODULE                                               |
    |              |   .-------------.  .-------------.  .-------------.             |
    +--------------|-->(  Add Item   )  ( Update Stock)  ( Delete Item )              |
    |              +-->|             |  |             |  | (Admin Only) |             |
    |              |   '-------------'  '-------------'  '-------------'             |
    |              |   .-------------.  .-------------.                               |
    |              +-->( View Alerts )  ( Track Expiry)                               |
    |              |   '-------------'  '-------------'                               |
    |              |                                                                  |
    |              |   BILLING MODULE                                                 |
    |              |   .---------------.  .---------------.  .---------------.       |
    +--------------|-->(Create Invoice )  (Record Payment )  (Cancel Invoice )        |
    |              +-->|               |  |               |  | (Admin Only)  |        |
    |              |   '---------------'  '---------------'  '---------------'       |
    |              |   .---------------.  .---------------.                           |
    |              +-->( Print Invoice )  ( Track Balance )                           |
    |              |   '---------------'  '---------------'                           |
    |              |                                                                  |
    |              |   FEEDING MODULE                                                 |
    |              |   .---------------.  .---------------.  .---------------.       |
    +--------------|-->(Record Feeding )  (View Feed Hist )  (Track Schedule )        |
    |              +-->|               |  |               |  | (Admin Only)  |        |
    |                  '---------------'  '---------------'  '---------------'       |
    |                                                                                 |
    |   USER MANAGEMENT MODULE (Admin Only)                                           |
    |   .-------------.  .-------------.  .-------------.  .---------------.         |
    +-->( Create User )  ( Update User )  ( Delete User )  ( Assign Roles  )          |
    |   '-------------'  '-------------'  '-------------'  '---------------'         |
    |   .--------------------.                                                        |
    +-->(Activate/Deactivate )                                                        |
    |   '--------------------'                                                        |
    |                                                                                 |
    |   DASHBOARD & REPORTING (All Users)                                             |
    |   .-----------------.  .-------------------.  .-------------------.             |
    +-->(View Statistics  )  (View Notifications )  (View Activity Logs )             |
        '-----------------'  '-------------------'  '-------------------'             |
        (All Users)          (All Users)            (Admin Only)                      |
                                                                                      |
+=====================================================================================+

                         ACTOR PERMISSION MATRIX

    +===========+========+========+========+=========+
    | MODULE    | ADMIN  | STAFF  |  VET   | ADOPTER |
    +===========+========+========+========+=========+
    | Auth      |   ✓    |   ✓    |   ✓    |    ✓    |
    | Users     |   ✓    |   ✗    |   ✗    |    ✗    |
    | Animals   |  FULL  |  FULL  |  VIEW  |   VIEW  |
    | Medical   |  VIEW  |  VIEW  |  FULL  |    ✗    |
    | Adoptions |  FULL  |  FULL  |   ✗    | REQUEST |
    | Inventory |  FULL  |  EDIT  |   ✗    |    ✗    |
    | Billing   |  FULL  |  EDIT  |   ✗    |   VIEW  |
    | Feeding   |  FULL  |  FULL  |   ✗    |    ✗    |
    | Dashboard |  FULL  |  VIEW  |  VIEW  |   VIEW  |
    +===========+========+========+========+=========+
```

### 9.1 Admin Use Cases (Detailed)

```
                                         +==================================================+
                                         |     CATARMAN DOG POUND MANAGEMENT SYSTEM         |
                                         |              << System Boundary >>               |
                                         +==================================================+
                                         |                                                  |
                                         |   AUTHENTICATION                                 |
                                         |   .---------------.      .---------------.      |
    .---.                                |   (    Login      )      (    Logout     )      |
    | O |                                |   '---------------'      '---------------'      |
   /|\+/|\                               |   .---------------.      .---------------.      |
    /\                                   |   ( Token Refresh )      ( Manage Profile)      |
   ADMIN-------------------------------->|   '---------------'      '---------------'      |
    |                                    |                                                  |
    |                                    |   USER MANAGEMENT                                |
    |                                    |   .---------------.      .---------------.      |
    +---------------------------------->|   ( Create User   )      ( Update User   )      |
    |                                    |   '---------------'      '---------------'      |
    |                                    |   .---------------.      .---------------.      |
    +---------------------------------->|   ( Delete User   )      ( Assign Roles  )      |
    |                                    |   '---------------'      '---------------'      |
    |                                    |   .--------------------.                        |
    +---------------------------------->|   ( Activate/Deactivate)                        |
    |                                    |   '--------------------'                        |
    |                                    |                                                  |
    |                                    |   ANIMAL MANAGEMENT                              |
    |                                    |   .---------------.      .---------------.      |
    +---------------------------------->|   (  Add Animal   )      ( Update Animal )      |
    |                                    |   '---------------'      '---------------'      |
    |                                    |   .---------------.      .---------------.      |
    +---------------------------------->|   ( Delete Animal )      ( View Animals  )      |
    |                                    |   '---------------'      '---------------'      |
    |                                    |                                                  |
    |                                    |   ADOPTION, INVENTORY, BILLING, FEEDING         |
    +---------------------------------->|   (Full Access to All Modules)                  |
    |                                    |                                                  |
    |                                    |   DASHBOARD                                      |
    +---------------------------------->|   ( View Statistics )  ( View Activity Logs)    |
                                         |                                                  |
                                         +==================================================+
```

### 9.2 Staff Use Cases

```
                                         +==================================================+
                                         |     CATARMAN DOG POUND MANAGEMENT SYSTEM         |
                                         +==================================================+
                                         |                                                  |
    .---.                                |   AUTHENTICATION                                 |
    | O |                                |   .---------------.      .---------------.      |
   /|\+/|\                               |   (    Login      )      (    Logout     )      |
    /\                                   |   '---------------'      '---------------'      |
   STAFF-------------------------------->|   .---------------.      .---------------.      |
    |                                    |   ( Token Refresh )      ( Manage Profile)      |
    |                                    |   '---------------'      '---------------'      |
    |                                    |                                                  |
    |                                    |   ANIMAL MANAGEMENT                              |
    +---------------------------------->|   ( Add/Update/Delete Animal )                  |
    |                                    |   ( Record Impound )  ( Update Status )        |
    |                                    |                                                  |
    |                                    |   ADOPTION PROCESS                               |
    +---------------------------------->|   ( Schedule Interview )( Approve/Reject )      |
    |                                    |   ( Complete Adoption )                         |
    |                                    |                                                  |
    |                                    |   INVENTORY MANAGEMENT                           |
    +---------------------------------->|   ( Add/Update/Delete Item )                    |
    |                                    |   ( View Alerts )  ( Track Expiry )            |
    |                                    |                                                  |
    |                                    |   BILLING                                        |
    +---------------------------------->|   ( Create/Cancel Invoice )                     |
    |                                    |   ( Record Payment ) ( Print Invoice )         |
    |                                    |                                                  |
    |                                    |   FEEDING                                        |
    +---------------------------------->|   ( Record Feeding ) ( View History )           |
                                         |                                                  |
                                         +==================================================+
```

### 9.3 Veterinarian Use Cases

```
                                         +==================================================+
                                         |     CATARMAN DOG POUND MANAGEMENT SYSTEM         |
                                         +==================================================+
                                         |                                                  |
    .---.                                |   AUTHENTICATION                                 |
    | O |                                |   .---------------.      .---------------.      |
   /|\+/|\                               |   (    Login      )      (    Logout     )      |
    /\                                   |   '---------------'      '---------------'      |
VETERINARIAN--------------------------->|   .---------------.      .---------------.      |
    |                                    |   ( Token Refresh )      ( Manage Profile)      |
    |                                    |   '---------------'      '---------------'      |
    |                                    |                                                  |
    |                                    |   ANIMAL MANAGEMENT                              |
    +---------------------------------->|   ( View Animals  )                             |
    |                                    |                                                  |
    |                                    |   MEDICAL RECORDS                                |
    +---------------------------------->|   ( Create Med Record )( View Records   )       |
    |                                    |   ( Record Vaccination)( Record Surgery )       |
    +---------------------------------->|   ( Track Treatments  )( Schedule Follow-up)    |
    |                                    |   ( Export PDF )                                |
    |                                    |                                                  |
    |                                    |   DASHBOARD                                      |
    +---------------------------------->|   ( View Statistics ) ( View Notifications)     |
                                         |                                                  |
                                         +==================================================+
```

### 9.4 Adopter Use Cases

```
                                         +==================================================+
                                         |     CATARMAN DOG POUND MANAGEMENT SYSTEM         |
                                         +==================================================+
                                         |                                                  |
    .---.                                |   AUTHENTICATION                                 |
    | O |                                |   .---------------.      .---------------.      |
   /|\+/|\                               |   (   Register    )      (    Login      )      |
    /\                                   |   '---------------'      '---------------'      |
  ADOPTER------------------------------>|   .---------------.      .---------------.      |
    |                                    |   (    Logout     )      ( Manage Profile)      |
    |                                    |   '---------------'      '---------------'      |
    |                                    |                                                  |
    |                                    |   ANIMAL MANAGEMENT                              |
    +---------------------------------->|   ( Browse Available  )                         |
    |                                    |   ( View Animals  )                             |
    |                                    |                                                  |
    |                                    |   ADOPTION PROCESS                               |
    +---------------------------------->|   ( Submit Adoption   )(View Request Status)    |
    |                                    |                                                  |
    |                                    |   BILLING                                        |
    +---------------------------------->|   ( View My Invoices  )                         |
    |                                    |                                                  |
    |                                    |   DASHBOARD                                      |
    +---------------------------------->|   ( View Statistics ) ( View Notifications)     |
                                         |                                                  |
                                         +==================================================+
```

### Actor Summary

```
┌────────────────┬────────────────────────┬──────────────────────────────────────────────┐
│ ACTOR          │ ROLE                   │ PRIMARY ACCESS                               │
├────────────────┼────────────────────────┼──────────────────────────────────────────────┤
│ Admin          │ System Administrator   │ Full access to all modules                   │
│ Staff          │ Dog Pound Employee     │ Animal, Adoption, Inventory, Billing ops     │
│ Veterinarian   │ Licensed Vet           │ Medical records and treatment management     │
│ Adopter        │ Public User            │ Browse animals, adoption requests, own data  │
└────────────────┴────────────────────────┴──────────────────────────────────────────────┘
```

### Use Case Diagram Legend

```
┌──────────────────┬────────────────────────────────────────────────┐
│ SYMBOL           │ MEANING                                        │
├──────────────────┼────────────────────────────────────────────────┤
│     .---.        │                                                │
│     | O |        │ Actor (Stick Figure)                           │
│    /|\+/|\       │                                                │
│      /\          │                                                │
├──────────────────┼────────────────────────────────────────────────┤
│  .-----------.   │                                                │
│  ( Use Case  )   │ Use Case (Oval)                                │
│  '-----------'   │                                                │
├──────────────────┼────────────────────────────────────────────────┤
│ ------>          │ Association (Actor to Use Case)                │
├──────────────────┼────────────────────────────────────────────────┤
│ +============+   │                                                │
│ | System     |   │ System Boundary                                │
│ +============+   │                                                │
└──────────────────┴────────────────────────────────────────────────┘
```

---

## 10. Event Diagrams

Shows the sequence of events and system responses for key processes.

### 10.1 User Authentication Event Flow

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                          USER AUTHENTICATION EVENT FLOW                              │
└─────────────────────────────────────────────────────────────────────────────────────┘

    EVENT                         PROCESS                           RESPONSE
    ─────                         ───────                           ────────

    ┌─────────────┐
    │ User opens  │
    │ login page  │
    └──────┬──────┘
           │
           ▼
    ┌─────────────┐         ┌─────────────────────┐         ┌─────────────────┐
    │ Submit      │────────►│ Validate credentials│────────►│ Return JWT token│
    │ credentials │         │ against database    │         │ + user data     │
    └─────────────┘         └──────────┬──────────┘         └─────────────────┘
                                       │
                            ┌──────────┴──────────┐
                            │                     │
                       [Valid]                [Invalid]
                            │                     │
                            ▼                     ▼
                   ┌─────────────────┐   ┌─────────────────┐
                   │ Store token in  │   │ Show error msg  │
                   │ localStorage    │   │ "Invalid creds" │
                   └────────┬────────┘   └─────────────────┘
                            │
                            ▼
                   ┌─────────────────┐
                   │ Log activity    │
                   │ (IP, timestamp) │
                   └────────┬────────┘
                            │
                            ▼
                   ┌─────────────────┐
                   │ Redirect to     │
                   │ Dashboard       │
                   └─────────────────┘

    Token Refresh Event:
    ┌─────────────┐         ┌─────────────────────┐         ┌─────────────────┐
    │ Token near  │────────►│ Call /auth/refresh  │────────►│ Issue new token │
    │ expiry      │         │ with current token  │         │ (24hr validity) │
    └─────────────┘         └─────────────────────┘         └─────────────────┘
```

### 10.2 Adoption Process Event Flow

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                          ADOPTION PROCESS EVENT FLOW                                 │
└─────────────────────────────────────────────────────────────────────────────────────┘

  ADOPTER                    STAFF                     SYSTEM                DATABASE
    │                          │                          │                      │
    │  Browse Available        │                          │                      │
    │  Animals                 │                          │                      │
    ├─────────────────────────────────────────────────────►                      │
    │                          │                          │   Query Available    │
    │                          │                          ├─────────────────────►│
    │                          │                          │   Return Animals     │
    │                          │                          │◄─────────────────────┤
    │  Display Animals         │                          │                      │
    │◄─────────────────────────────────────────────────────                      │
    │                          │                          │                      │
    │  Select Animal &         │                          │                      │
    │  Submit Request          │                          │                      │
    ├─────────────────────────────────────────────────────►                      │
    │                          │                          │   Create Request     │
    │                          │                          │   (Status: Pending)  │
    │                          │                          ├─────────────────────►│
    │                          │                          │                      │
    │                          │  Notification: New       │                      │
    │                          │  Adoption Request        │                      │
    │                          │◄─────────────────────────┤                      │
    │                          │                          │                      │
    │                          │  Review Request &        │                      │
    │                          │  Schedule Interview      │                      │
    │                          ├─────────────────────────►│                      │
    │                          │                          │   Update Status:     │
    │                          │                          │   Interview Scheduled│
    │                          │                          ├─────────────────────►│
    │                          │                          │                      │
    │  Notification:           │                          │                      │
    │  Interview Scheduled     │                          │                      │
    │◄─────────────────────────────────────────────────────                      │
    │                          │                          │                      │
    │                          │  Conduct Interview       │                      │
    │                          │  & Make Decision         │                      │
    │                          ├─────────────────────────►│                      │
    │                          │                          │                      │
    │                          │                    ┌─────┴─────┐                │
    │                          │                    │           │                │
    │                          │               [Approve]   [Reject]              │
    │                          │                    │           │                │
    │                          │                    ▼           ▼                │
    │                          │           ┌────────────┐ ┌────────────┐         │
    │                          │           │Create      │ │Update      │         │
    │                          │           │Invoice     │ │Status:     │         │
    │                          │           │for fees    │ │Rejected    │         │
    │                          │           └─────┬──────┘ └────────────┘         │
    │                          │                 │                               │
    │                          │                 ▼                               │
    │                          │           ┌────────────┐                        │
    │  Notification:           │           │Process     │                        │
    │  Approved / Rejected     │           │Payment     │                        │
    │◄─────────────────────────────────────│            │                        │
    │                          │           └─────┬──────┘                        │
    │                          │                 │                               │
    │                          │                 ▼                               │
    │                          │           ┌────────────┐  ┌────────────┐        │
    │                          │           │Update      │  │Update      │        │
    │                          │           │Animal:     │──│Request:    │        │
    │                          │           │Adopted     │  │Completed   │        │
    │                          │           └────────────┘  └────────────┘        │
    │                          │                          ├─────────────────────►│
```

### 10.3 Medical Record Event Flow

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                         MEDICAL RECORD EVENT FLOW                                    │
└─────────────────────────────────────────────────────────────────────────────────────┘

  VETERINARIAN                  SYSTEM                          DATABASE
       │                          │                                │
       │  Select Animal           │                                │
       ├─────────────────────────►│                                │
       │                          │  Fetch animal + history        │
       │                          ├───────────────────────────────►│
       │                          │  Return data                   │
       │                          │◄───────────────────────────────┤
       │  Display animal info     │                                │
       │◄─────────────────────────┤                                │
       │                          │                                │
       │  Create Medical Record   │                                │
       │  (Diagnosis, Treatment)  │                                │
       ├─────────────────────────►│                                │
       │                          │  Validate input                │
       │                          │  Set VetID from token          │
       │                          ├───────────────────────────────►│
       │                          │                                │
       │                          │  ┌─────────────────────────┐   │
       │                          │  │ Check Diagnosis_Type:    │   │
       │                          │  │ - Vaccination?           │   │
       │                          │  │ - Treatment?             │   │
       │                          │  │ - Surgery?               │   │
       │                          │  └───────────┬─────────────┘   │
       │                          │              │                 │
       │                          │              ▼                 │
       │                          │  ┌─────────────────────────┐   │
       │                          │  │ If has Next_Due_Date:   │   │
       │                          │  │ Schedule follow-up flag │   │
       │                          │  └───────────┬─────────────┘   │
       │                          │              │                 │
       │                          │              ▼                 │
       │                          │  Insert Medical_Record         │
       │                          ├───────────────────────────────►│
       │                          │                                │
       │  Success confirmation    │                                │
       │◄─────────────────────────┤                                │
       │                          │                                │
       │                          │                                │
       │  ════════════════════════════════════════════════════════ │
       │           OVERDUE TREATMENT CHECK (Daily Cron)            │
       │  ════════════════════════════════════════════════════════ │
       │                          │                                │
       │                          │  Query: Next_Due_Date < TODAY  │
       │                          ├───────────────────────────────►│
       │                          │  Return overdue records        │
       │                          │◄───────────────────────────────┤
       │                          │                                │
       │  Dashboard Alert:        │                                │
       │  "X overdue treatments"  │                                │
       │◄─────────────────────────┤                                │
```

### 10.4 Inventory Management Event Flow

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        INVENTORY MANAGEMENT EVENT FLOW                               │
└─────────────────────────────────────────────────────────────────────────────────────┘

  STAFF/ADMIN                    SYSTEM                          DATABASE
       │                          │                                │
       │  Add New Item            │                                │
       │  (Name, Category, Qty)   │                                │
       ├─────────────────────────►│                                │
       │                          │  Validate & Sanitize input     │
       │                          │  Set default Reorder_Level=10  │
       │                          ├───────────────────────────────►│
       │                          │                                │
       │  Success + Item ID       │                                │
       │◄─────────────────────────┤                                │
       │                          │                                │
       │  ══════════════════════════════════════════════════════   │
       │            STOCK UPDATE EVENT                              │
       │  ══════════════════════════════════════════════════════   │
       │                          │                                │
       │  Update Quantity         │                                │
       │  (+50 or -10)            │                                │
       ├─────────────────────────►│                                │
       │                          │  Calculate new quantity        │
       │                          ├───────────────────────────────►│
       │                          │                                │
       │                          │  ┌─────────────────────────┐   │
       │                          │  │ Check: Qty <= Reorder?  │   │
       │                          │  └───────────┬─────────────┘   │
       │                          │              │                 │
       │                          │     ┌────────┴────────┐        │
       │                          │   [YES]              [NO]      │
       │                          │     │                 │        │
       │                          │     ▼                 ▼        │
       │                          │  ┌──────────┐    ┌──────────┐  │
       │                          │  │ Create   │    │ Normal   │  │
       │                          │  │ LOW STOCK│    │ Update   │  │
       │                          │  │ Alert    │    │ Only     │  │
       │                          │  └────┬─────┘    └──────────┘  │
       │                          │       │                        │
       │  Notification:           │       │                        │
       │  "Low Stock Alert"       │◄──────┘                        │
       │◄─────────────────────────┤                                │
       │                          │                                │
       │  ══════════════════════════════════════════════════════   │
       │            EXPIRATION CHECK EVENT (Daily)                  │
       │  ══════════════════════════════════════════════════════   │
       │                          │                                │
       │                          │  Query: Expiration <= 7 days   │
       │                          ├───────────────────────────────►│
       │                          │  Return expiring items         │
       │                          │◄───────────────────────────────┤
       │                          │                                │
       │  Dashboard Alert:        │                                │
       │  "X items expiring soon" │                                │
       │◄─────────────────────────┤                                │
```

### 10.5 Billing & Payment Event Flow

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                         BILLING & PAYMENT EVENT FLOW                                 │
└─────────────────────────────────────────────────────────────────────────────────────┘

  STAFF                        SYSTEM                          DATABASE
    │                            │                                │
    │  Create Invoice            │                                │
    │  (Type, Amount, Payer)     │                                │
    ├───────────────────────────►│                                │
    │                            │  Validate input                │
    │                            │  Set Issued_By from token      │
    │                            │  Status = 'Unpaid'             │
    │                            ├───────────────────────────────►│
    │                            │                                │
    │  Invoice #INV-XXXX         │                                │
    │◄───────────────────────────┤                                │
    │                            │                                │
    │  ══════════════════════════════════════════════════════     │
    │            PAYMENT RECEIPT EVENT                             │
    │  ══════════════════════════════════════════════════════     │
    │                            │                                │
    │  Record Payment            │                                │
    │  (Amount, Method, Ref#)    │                                │
    ├───────────────────────────►│                                │
    │                            │                                │
    │                            │  ┌─────────────────────────┐   │
    │                            │  │ Create Payment record   │   │
    │                            │  │ Link to Invoice         │   │
    │                            │  └───────────┬─────────────┘   │
    │                            │              │                 │
    │                            │              ▼                 │
    │                            │  ┌─────────────────────────┐   │
    │                            │  │ Sum all payments for    │   │
    │                            │  │ this invoice            │   │
    │                            │  └───────────┬─────────────┘   │
    │                            │              │                 │
    │                            │     ┌────────┴────────┐        │
    │                            │  [Total >= Invoice]  [Partial] │
    │                            │     │                 │        │
    │                            │     ▼                 ▼        │
    │                            │  ┌──────────┐    ┌──────────┐  │
    │                            │  │ Update   │    │ Keep as  │  │
    │                            │  │ Status:  │    │ 'Unpaid' │  │
    │                            │  │ 'Paid'   │    │ + Balance│  │
    │                            │  └──────────┘    └──────────┘  │
    │                            ├───────────────────────────────►│
    │                            │                                │
    │  Payment recorded          │                                │
    │  + Updated status          │                                │
    │◄───────────────────────────┤                                │
    │                            │                                │
    │  Print/Export PDF          │                                │
    ├───────────────────────────►│                                │
    │                            │  Generate PDF with             │
    │                            │  invoice + payment details     │
    │  Download PDF              │                                │
    │◄───────────────────────────┤                                │
```

### 10.6 Animal Intake Event Flow

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                          ANIMAL INTAKE EVENT FLOW                                    │
└─────────────────────────────────────────────────────────────────────────────────────┘

  STAFF                        SYSTEM                          DATABASE
    │                            │                                │
    │  Add New Animal            │                                │
    │  (Name, Type, Breed...)    │                                │
    ├───────────────────────────►│                                │
    │                            │  Validate input                │
    │                            │  Set Intake_Date = NOW         │
    │                            │  Status = 'Available'          │
    │                            ├───────────────────────────────►│
    │                            │                                │
    │  Animal #A-XXXX created    │                                │
    │◄───────────────────────────┤                                │
    │                            │                                │
    │  Upload Image              │                                │
    │  (JPEG/PNG file)           │                                │
    ├───────────────────────────►│                                │
    │                            │  ┌─────────────────────────┐   │
    │                            │  │ Validate file type      │   │
    │                            │  │ Max size: 5MB           │   │
    │                            │  │ Generate unique name    │   │
    │                            │  └───────────┬─────────────┘   │
    │                            │              │                 │
    │                            │              ▼                 │
    │                            │  Save to /uploads/animals/     │
    │                            │  Update Image_URL in DB        │
    │                            ├───────────────────────────────►│
    │                            │                                │
    │  Image uploaded            │                                │
    │◄───────────────────────────┤                                │
    │                            │                                │
    │  Record Impound Details    │                                │
    │  (Location, Officer, Date) │                                │
    ├───────────────────────────►│                                │
    │                            │  Create Impound_Record         │
    │                            │  Link to Animal                │
    │                            ├───────────────────────────────►│
    │                            │                                │
    │  Impound recorded          │                                │
    │◄───────────────────────────┤                                │
    │                            │                                │
    │                            │  ┌─────────────────────────┐   │
    │                            │  │ Trigger: Update         │   │
    │                            │  │ Dashboard Statistics    │   │
    │                            │  │ - Total Animals +1      │   │
    │                            │  │ - Available Animals +1  │   │
    │                            │  └─────────────────────────┘   │
```

### Event Diagram Legend

```
┌─────────────────────┬───────────────────────────────────────────────────────────────┐
│ Symbol              │ Meaning                                                       │
├─────────────────────┼───────────────────────────────────────────────────────────────┤
│ ─────────────────►  │ Event/Action flow direction                                   │
│ ◄─────────────────  │ Response/Return flow direction                                │
│ ┌───────────────┐   │ Process/Action box                                            │
│ │               │   │                                                               │
│ └───────────────┘   │                                                               │
├─────────────────────┼───────────────────────────────────────────────────────────────┤
│ [Condition]         │ Decision branch                                               │
│     │               │                                                               │
│ ┌───┴───┐           │                                                               │
│ A       B           │                                                               │
├─────────────────────┼───────────────────────────────────────────────────────────────┤
│ ════════════════    │ Section separator (different event type)                      │
├─────────────────────┼───────────────────────────────────────────────────────────────┤
│ ACTOR               │ Column representing an actor/component                        │
│   │                 │                                                               │
└─────────────────────┴───────────────────────────────────────────────────────────────┘
```

### Event Summary Table

```
┌───────────────────────┬────────────────────────────┬────────────────────────────────┐
│ Event Category        │ Trigger Events             │ System Response                │
├───────────────────────┼────────────────────────────┼────────────────────────────────┤
│ Authentication        │ Login, Logout, Refresh     │ JWT token management           │
│ Animal Intake         │ Add, Upload Image          │ Create records, update stats   │
│ Adoption Process      │ Submit, Approve, Complete  │ Status updates, invoicing      │
│ Medical Records       │ Create record, Schedule    │ Track treatments, alerts       │
│ Inventory             │ Add, Update qty            │ Stock alerts, expiry tracking  │
│ Billing               │ Create invoice, Payment    │ Payment tracking, PDF export   │
│ System Monitoring     │ Daily cron jobs            │ Overdue alerts, expiry alerts  │
└───────────────────────┴────────────────────────────┴────────────────────────────────┘
```
