# 🎓 Capstone Defense Guide: Catarman Dog Pound Management System

## 1️⃣ System Overview (Know This by Heart)

**What is it?**
> A web-based information management system for the Catarman Dog Pound that digitizes animal intake, adoption processing, medical records, billing, and inventory management.

**Problem Statement:**
> Manual record-keeping leads to lost records, slow adoption processing, missed medical treatments, and inefficient inventory tracking.

**Solution:**
> A centralized digital system with role-based access that automates workflows, provides real-time dashboards, and generates reports.

---

## 2️⃣ Technical Architecture

### Stack Explanation
```
┌──────────┬────────────────────────┬────────────────────────────────────────────┐
│ Layer    │ Technology             │ Why?                                       │
├──────────┼────────────────────────┼────────────────────────────────────────────┤
│ Frontend │ Vanilla JavaScript SPA │ Lightweight, no dependencies, fast loading │
│ Backend  │ PHP 8.x REST API       │ Industry standard, XAMPP compatible        │
│ Database │ MySQL                  │ Relational data fits well, widely supported│
│ Auth     │ JWT (JSON Web Tokens)  │ Stateless, secure, scalable                │
└──────────┴────────────────────────┴────────────────────────────────────────────┘
```

### Architecture Pattern
- **MVC (Model-View-Controller)** - Separation of concerns
- **REST API** - Standardized HTTP methods (GET, POST, PUT, DELETE)
- **SPA (Single Page Application)** - No page reloads, smoother UX

### System Architecture Diagram
```
+------------------------------------------------------------------+
|                        SYSTEM ARCHITECTURE                        |
+------------------------------------------------------------------+

    +---------------------------------------+
    |         FRONTEND (SPA)                |
    |  +----------+----------+------------+ |
    |  |  HTML5   |   CSS3   | JavaScript | |
    |  +----------+----------+------------+ |
    +-------------------+-------------------+
                        | HTTP/REST (JSON)
                        v
    +---------------------------------------+
    |         BACKEND (PHP 8.x)             |
    |  +----------+------------+----------+ |
    |  |  Router  | Controllers|  Models  | |
    |  +----------+------------+----------+ |
    +-------------------+-------------------+
                        |
                        v
    +---------------------------------------+
    |         SECURITY LAYER                |
    |  +------+-----------+------------+    |
    |  | JWT  | RateLimiter| Sanitizer |    |
    |  +------+-----------+------------+    |
    +-------------------+-------------------+
                        |
                        v
    +---------------------------------------+
    |         MySQL DATABASE                |
    |           [ 12 Tables ]               |
    +---------------------------------------+
```

**Be ready to explain:**
> "The frontend sends HTTP requests to the backend API. The API routes requests to controllers, which interact with models to query the database. Responses are returned as JSON."

---

## 3️⃣ Key Features & How They Work

### 🔐 Authentication Flow
```
+-------------+     +---------------+     +----------------+
|   Browser   |     |   Backend     |     |    Database    |
+------+------+     +-------+-------+     +--------+-------+
       |                    |                      |
       | 1. POST /auth/login                       |
       |  (email, password) |                      |
       +------------------->|                      |
       |                    |                      |
       |    2. Rate Limit   |                      |
       |       Check        |                      |
       |       (10/min)     |                      |
       |                    |                      |
       |                    | 3. Query User        |
       |                    +--------------------->|
       |                    |                      |
       |                    |<---------------------+
       |                    |    User Record       |
       |                    |                      |
       |    4. Verify       |                      |
       |    bcrypt hash     |                      |
       |                    |                      |
       |    5. Generate     |                      |
       |    JWT Token       |                      |
       |    (24hr expiry)   |                      |
       |                    |                      |
       |<-------------------+                      |
       |   6. Return Token  |                      |
       |                    |                      |
       | 7. Store in        |                      |
       |    localStorage    |                      |
       |                    |                      |
       | 8. All future requests include:           |
       |    Authorization: Bearer <token>          |
       |                    |                      |
+------+------+     +-------+-------+     +--------+-------+
```

### 🐕 Animal Management
- **CRUD Operations**: Create, Read, Update, Delete (soft delete)
- **Status Tracking**: Available → Adopted/Deceased/In Treatment/Quarantine/Reclaimed
- **Animal Types**: Dog, Cat, Other
- **Intake Status**: Stray, Surrendered, Confiscated
- **Image Upload**: Stored in `/uploads/animals/` (max 5MB, jpg/png/gif/webp)

### 📋 Adoption Workflow
```
+----------+     +-----------+     +----------+     +-----------+
| PENDING  +---->| INTERVIEW +---->| APPROVED +---->| COMPLETED |
+----+-----+     | SCHEDULED |     +----+-----+     +-----------+
     |           +-----+-----+          |
     |                 |                |
     |                 v                v
     |           +-----------+    +-----------+
     +---------->| CANCELLED |    | REJECTED  |
                 +-----------+    +-----------+

  [Adopter]        [Staff]       [Admin/Staff]     [System]
   Submit          Schedule        Review          Update
   Request         Interview       Request         Status
```
- **All users can submit adoption requests** (Admin, Staff, Vet, Adopter)
- Staff reviews and schedules interviews
- Admin/Staff approves or rejects
- System auto-generates invoice on approval
- Adoption fees: Dog ₱500, Cat ₱300, Other ₱200

### 💊 Medical Records
- **Diagnosis Types**: Checkup, Vaccination, Surgery, Treatment, Emergency, Deworming, Spay/Neuter
- **Overdue Alerts**: System flags treatments past `Next_Due_Date`
- Links to veterinarian who performed procedure
- PDF export for medical history

### 💰 Billing System
- **Invoice Generation**: Auto-created for adoptions/reclaims
- **Payment Tracking**: Partial payments supported
- **Payment Methods**: Cash, GCash, Bank Transfer
- **PDF Reports**: Summary, Detailed, Unpaid reports via jsPDF
- **PDF Preview**: Preview reports before download/print in modal
- **Individual Invoice Print**: Print single invoices with naming format: `ReportType_FirstName_LastName_Date.pdf`
- **Reclaim Fees**: Base ₱200 + ₱50/day

### 📦 Inventory
- **Categories**: Medical, Food, Cleaning, Supplies
- Stock level monitoring with quantity tracking
- **Low Stock Alerts**: When `Quantity_On_Hand ≤ Reorder_Level`
- **Expiring Soon Alerts**: Items expiring within 7 days
- PDF export for inventory reports

### 🔔 Notifications
- User-specific notifications
- Unread count badge
- Mark as read functionality
- Notification history

---

## 4️⃣ Database Design (Common Defense Questions)

### Tables (12 Total)
```
┌───────────────────┬───────────────────────┬──────────────────────────────────────────────────────────────┐
│ Table             │ Purpose               │ Key Fields                                                   │
├───────────────────┼───────────────────────┼──────────────────────────────────────────────────────────────┤
│ Roles             │ Define user permission│ RoleID, Role_Name                                            │
│ Users             │ All system users      │ UserID, RoleID, Username, Email, Password_Hash, Account_Stat │
│ Veterinarians     │ Extra vet info        │ VetID, UserID, License_Number, Specialization                │
│ Animals           │ Core animal records   │ AnimalID, Name, Type, Breed, Current_Status, Image_URL       │
│ Impound_Records   │ How animal received   │ ImpoundID, AnimalID, Capture_Date, Location_Found            │
│ Medical_Records   │ Health treatments     │ RecordID, AnimalID, VetID, Diagnosis_Type, Next_Due_Date     │
│ Feeding_Records   │ Feeding schedule      │ FeedingID, AnimalID, Fed_By_UserID, Food_Type                │
│ Adoption_Requests │ Adoption applications │ RequestID, AnimalID, Adopter_UserID, Status                  │
│ Inventory         │ Supplies tracking     │ ItemID, Item_Name, Category, Quantity_On_Hand                │
│ Invoices          │ Billing records       │ InvoiceID, Payer_UserID, Total_Amount, Status                │
│ Payments          │ Payment transactions  │ PaymentID, InvoiceID, Amount_Paid, Payment_Method            │
│ Activity_Logs     │ Audit trail           │ LogID, UserID, Action_Type, IP_Address                       │
└───────────────────┴───────────────────────┴──────────────────────────────────────────────────────────────┘

### Q: "How do you ensure the User Stats on the profile page are accurate?"
> "We don't just count generic activity logs. For accurate reporting, we query specific tables: `Animals` (via CREATE_ANIMAL logs), `Adoption_Requests` (Processed_By_UserID), and `Invoices` (Issued_By_UserID). This ensures that the stats reflect actual work performed, not just system interactions."

### Q: "How does the 'Overdue' medical tracking work?"
> "The system queries the `Medical_Records` table for any record where `Next_Due_Date` is in the past (`< CURDATE()`) and is not null. We use an optimized query that joins with `Animals` to ensure we only flag overdue treatments for active animals (excluding adopted or deceased ones). This catches ALL missed treatments, not just the most recent one."

### Q: "Why did you give Veterinarians access to Adoptions?"
> "Veterinarians need to see the adoption history of animals to understand their placement timeline. While they cannot process or approve adoptions (SoD - Separation of Duties), read-only access helps them coordinate health checks before an animal is released to an adopter."
```

### Key Relationships (Entity Relationship Diagram)
```
+-------------+       +-----------------+       +------------------+
|   ROLES     |       |     USERS       |       |  VETERINARIANS   |
+-------------+       +-----------------+       +------------------+
| RoleID (PK) |<------| RoleID (FK)     |       | VetID (PK)       |
| Role_Name   |       | UserID (PK)     |<------| UserID (FK)      |
+-------------+       | Username        |       | License_Number   |
                      | Password_Hash   |       | Specialization   |
                      +--------+--------+       +------------------+
                               |
         +---------------------+---------------------+
         |                     |                     |
         v                     v                     v
+---------------+    +-----------------+    +-----------------+
| ACTIVITY_LOGS |    |    ANIMALS      |    |ADOPTION_REQUESTS|
+---------------+    +-----------------+    +-----------------+
| LogID (PK)    |    | AnimalID (PK)   |<---| AnimalID (FK)   |
| UserID (FK)   |    | Name            |    | RequestID (PK)  |
| Action_Type   |    | Type            |    | UserID (FK)     |
| IP_Address    |    | Status          |    | Status          |
+---------------+    +--------+--------+    +-----------------+
                              |
     +------------------------+------------------------+
     |                        |                        |
     v                        v                        v
+----------------+   +-----------------+   +-----------------+
|IMPOUND_RECORDS |   | MEDICAL_RECORDS |   | FEEDING_RECORDS |
+----------------+   +-----------------+   +-----------------+
| ImpoundID (PK) |   | RecordID (PK)   |   | FeedingID (PK)  |
| AnimalID (FK)  |   | AnimalID (FK)   |   | AnimalID (FK)   |
| Capture_Date   |   | VetID (FK)      |   | Fed_By (FK)     |
| Location_Found |   | Diagnosis_Type  |   | Food_Type       |
+----------------+   +-----------------+   +-----------------+

+-----------------+       +-----------------+       +-----------------+
|    INVOICES     |       |    PAYMENTS     |       |   INVENTORY     |
+-----------------+       +-----------------+       +-----------------+
| InvoiceID (PK)  |<------| InvoiceID (FK)  |       | ItemID (PK)     |
| UserID (FK)     |       | PaymentID (PK)  |       | Item_Name       |
| Total_Amount    |       | Amount          |       | Category        |
| Status          |       | Payment_Method  |       | Quantity        |
+-----------------+       +-----------------+       +-----------------+
```

### Database Indexes (Performance)
> "We created indexes on frequently queried columns like `Email`, `Account_Status`, `Current_Status`, `Next_Due_Date`, and `Log_Date` to optimize query performance."

### Why Soft Deletes?
> "We use `Is_Deleted` flags instead of actual DELETE to preserve data integrity and allow recovery. This also maintains referential integrity with related records."

---

## 5️⃣ Security Features (They WILL Ask This)

### Request Security Flow
```
+-----------------------------------------------------------------------------+
|                         REQUEST SECURITY FLOW                                |
+-----------------------------------------------------------------------------+

  HTTP Request
       |
       v
+-----------------------------------------------------------------------------+
|                          SECURITY LAYER                                      |
|                                                                              |
|   +----------+   +----------+   +----------+   +---------+   +--------+     |
|   |   Rate   |-->|  Input   |-->|  Input   |-->|   JWT   |-->|  Role  |     |
|   |  Limiter |   | Sanitizer|   | Validator|   |   Auth  |   |  Check |     |
|   +----------+   +----------+   +----------+   +---------+   +--------+     |
|                                                                              |
+--------------------------------------+--------------------------------------+
                                       |
                                       v
                              +-----------------+
                              |   Controller    |
                              +--------+--------+
                                       |
                                       v
                              +-----------------+
                              |    Database     |
                              | (PDO Prepared)  |
                              +-----------------+
```

```
┌──────────────────────────┬───────────────────────────────────────┬──────────────────────┐
│ Feature                  │ Implementation                        │ File                 │
├──────────────────────────┼───────────────────────────────────────┼──────────────────────┤
│ Password Security        │ bcrypt hashing via password_hash()    │ Built-in PHP         │
│ SQL Injection Prevention │ PDO prepared statements               │ All Models           │
│ Authentication           │ JWT with HS256 signature, 24hr expiry │ JWT.php              │
│ Authorization            │ Role-based middleware checks          │ AuthMiddleware.php   │
│ Rate Limiting            │ 10 login/min, 100 API/min per IP      │ RateLimiter.php      │
│ XSS Prevention           │ Auto-sanitize all input               │ Sanitizer.php        │
│ Input Validation         │ Comprehensive validation rules        │ Validator.php        │
│ CORS Protection          │ Whitelist allowed origins             │ bootstrap.php        │
│ Audit Trail              │ All actions logged with IP address    │ Activity_Logs table  │
└──────────────────────────┴───────────────────────────────────────┴──────────────────────┘
```

### Rate Limiting Explanation
> "We implemented rate limiting to prevent brute force attacks. Login attempts are limited to 10 per minute per IP address. General API requests are limited to 100 per minute. If exceeded, the system returns HTTP 429 with a `Retry-After` header. Rate limit data is stored in file-based storage at `/logs/rate_limits/`."

### Input Sanitization Explanation
> "All user input is automatically sanitized using our `Sanitizer` class in the BaseController. It provides multiple sanitization methods: `string()`, `email()`, `integer()`, `float()`, `boolean()`, `url()`, `filename()`, and `forDatabase()`. HTML entities are escaped, control characters are removed, and dangerous HTML tags are stripped. Password fields are preserved for proper bcrypt hashing."

**Sample Security Answer:**
> "Passwords are never stored in plain text. We use PHP's `password_hash()` with bcrypt, which includes automatic salting. All database queries use prepared statements to prevent SQL injection. All user input passes through our Sanitizer class before processing."

---

## 6️⃣ User Roles & Permissions

### Role Hierarchy
```
                        +------------------+
                        |      ADMIN       |
                        |  (Full Access)   |
                        +--------+---------+
                                 |
              +------------------+------------------+
              |                                     |
     +--------v---------+                  +--------v---------+
     |      STAFF       |                  |   VETERINARIAN   |
     | Animals,         |                  | Animals,         |
     | Adoptions,       |                  | Medical          |
     | Billing,         |                  | Records          |
     | Inventory        |                  +------------------+
     +--------+---------+
              |
     +--------v---------+
     |     ADOPTER      |
     | Browse Animals,  |
     | Submit Adoption  |
     | Requests         |
     +------------------+
```

```
┌──────────────┬───────────┬───────┬───────────┬──────────┬─────────┬─────────┬───────────┐
│ Role         │ Dashboard │ Users │ Animals   │ Adoptions│ Medical │ Billing │ Inventory │
├──────────────┼───────────┼───────┼───────────┼──────────┼─────────┼─────────┼───────────┤
│ Admin        │ ✅        │ ✅    │ ✅        │ ✅       │ ✅      │ ✅      │ ✅        │
│ Staff        │ ✅        │ ❌    │ ✅        │ ✅       │ ✅      │ ✅      │ ✅        │
│ Veterinarian │ ✅        │ ❌    │ ✅        │ 👁️ View   │ ✅      │ ❌      │ ❌        │
│ Adopter      │ ❌        │ ❌    │ 👁️ Browse │ ✅ Own   │ ❌      │ ❌      │ ❌        │
└──────────────┴───────────┴───────┴───────────┴──────────┴─────────┴─────────┴───────────┘
```

**Key Points:**
- All roles can submit adoption requests
- Adopters can only view their own adoption requests
- Admin is the only role that can manage users

---

## 7️⃣ Project Structure (Know the Layout)

```
dogpound/
├── backend/
│   └── app/
│       ├── api/           # 9 API endpoint files
│       ├── config/        # config.php, database.php
│       ├── controllers/   # 10 controller classes
│       ├── middleware/    # AuthMiddleware.php
│       ├── models/        # 12 model classes
│       └── utils/         # JWT, Router, Validator, RateLimiter, Sanitizer, Response
│
├── frontend/
│   └── assets/
│       ├── css/           # 7 stylesheet files (variables, main, components, layouts, etc.)
│       ├── js/
│       │   ├── components/  # 11 reusable UI components
│       │   └── pages/       # 11 page controllers
│       └── images/
│
├── database/
│   ├── schema.sql         # Database structure (12 tables + indexes)
│   └── seeders.sql        # Sample data
│
├── start.bat              # Start servers (hidden windows)
├── stop.bat               # Stop servers
└── Documentation files    # README, IMPLEMENTATION_PLAN, etc.
```

### Backend Components
```
┌─────────────┬───────┬──────────────────────────────────────────────────────────────────┐
│ Type        │ Count │ Examples                                                         │
├─────────────┼───────┼──────────────────────────────────────────────────────────────────┤
│ Controllers │ 10    │ AuthController, UserController, AnimalController, BillingController│
│ Models      │ 12    │ User, Animal, Invoice, Payment, MedicalRecord, Inventory         │
│ Utils       │ 6     │ JWT, Router, Validator, RateLimiter, Sanitizer, Response         │
│ API Files   │ 9     │ auth.php, users.php, animals.php, billing.php, medical.php       │
└─────────────┴───────┴──────────────────────────────────────────────────────────────────┘
```

### Frontend Components
```
┌────────────┬───────┬──────────────────────────────────────────────────────────────────┐
│ Type       │ Count │ Examples                                                         │
├────────────┼───────┼──────────────────────────────────────────────────────────────────┤
│ Pages      │ 11    │ Dashboard, Animals, Adoptions, Billing, Medical, Inventory, Users│
│ Components │ 11    │ DataTable, Modal, Form, Toast, Charts, Sidebar, PDFPreview       │
│ CSS Files  │ 7     │ variables.css, main.css, components.css, layouts.css, animations │
└────────────┴───────┴──────────────────────────────────────────────────────────────────┘
```

---

## 8️⃣ Common Defense Questions & Answers

### Q: "Why did you choose this technology stack?"
> "PHP and MySQL are industry standards, easily deployable on shared hosting, and compatible with XAMPP which is commonly available. Vanilla JavaScript keeps the frontend lightweight without framework overhead, resulting in faster load times and zero dependencies."

### Q: "How does the system handle concurrent users?"
> "Each request is independent with JWT authentication. The database uses InnoDB engine which handles concurrent transactions with row-level locking. We use proper foreign key constraints to maintain data integrity."

### Q: "What happens if the server crashes?"
> "All data is persisted in MySQL. Users would need to log in again (JWT expires after 24 hours), but no data is lost. Activity logs help trace what happened before the crash. Rate limit data uses file-based storage and is automatically cleaned up."

### Q: "How do you ensure data integrity?"
> "Foreign key constraints enforce relationships with ON UPDATE CASCADE. ENUM types restrict valid values for status fields. NOT NULL constraints prevent missing required data. Soft deletes preserve historical records. Database indexes optimize query performance."

### Q: "How do you prevent brute force attacks?"
> "We implemented rate limiting with our `RateLimiter` class. Login attempts are limited to 10 per minute per IP. General API requests are limited to 100 per minute. When limits are exceeded, the server returns HTTP 429 with a Retry-After header. The rate limiter uses file-based storage with automatic cleanup."

### Q: "How do you prevent XSS attacks?"
> "All user input is automatically sanitized through our `Sanitizer` class in the BaseController. HTML entities are escaped using `htmlspecialchars()`, control characters are removed, and dangerous HTML tags like `<script>` are stripped. Password fields are preserved for proper bcrypt hashing."

### Q: "How do you prevent SQL injection?"
> "All database queries use PDO prepared statements with parameterized queries. User input is never directly concatenated into SQL strings. The Sanitizer class provides an additional layer with the `forDatabase()` method."

### Q: "Can this scale to multiple dog pounds?"
> "The current design is for a single facility. For multi-facility support, we'd add a `Facilities` table and add `Facility_ID` foreign keys to relevant tables. The MVC architecture makes this extension straightforward."

### Q: "What's the difference between your system and existing solutions?"
> "This is custom-built for Catarman Dog Pound's specific workflow. Unlike generic pet management software, it includes local requirements like impound records, Philippine-specific adoption workflows, and GCash payment support. The fee structure is also customized."

### Q: "Explain the adoption process in your system."
> "1) Any user browses available animals, 2) Submits adoption request with their info, 3) Staff reviews and schedules interview, 4) Admin/Staff approves or rejects, 5) Upon approval, invoice is auto-generated with correct fee (₱500 Dog, ₱300 Cat, ₱200 Other), 6) Payment is recorded with method (Cash/GCash/Bank Transfer), 7) Animal status changes to 'Adopted', 8) Adoption marked complete."

### Q: "How do you generate PDF reports?"
> "We use jsPDF library on the frontend. The billing module supports three report types: Summary, Detailed, and Unpaid. Users can preview PDFs in a modal before downloading. Individual invoices can also be printed with proper naming convention: `ReportType_FirstName_LastName_Date.pdf`."

### Q: "What validation do you perform on user input?"
> "We have a comprehensive Validator class that checks: required fields, data types (string, integer, email, date), length constraints, format patterns (email, phone), enum values for status fields, and file upload validation (size, type, extension). Invalid requests return HTTP 400 with specific error messages."

### Q: "How do you handle accessibility?"
> "We follow WCAG guidelines with: ARIA labels on all interactive elements, visible focus states for keyboard navigation, screen reader support via aria-hidden on decorative elements, and respecting prefers-reduced-motion for users sensitive to animations. All buttons have title attributes and can be operated via keyboard."

### Q: "What keyboard shortcuts does the system support?"
> "The system supports keyboard shortcuts for power users: `/` or `Ctrl+K` to focus search, `Escape` to close modals and dropdowns, `?` to show help, and navigation chords like `g` then `h` for dashboard or `g` then `a` for animals. These are disabled when typing in input fields."

---

## 9️⃣ Live Demo Tips

### Recommended Flow:
1. **Login as Admin** - Show dashboard statistics and charts
2. **Add an Animal** - Demonstrate CRUD with image upload
3. **Switch to Adopter account** - Submit adoption request
4. **Back to Admin** - Approve request, show auto-generated invoice
5. **Record Payment** - Select GCash payment method
6. **Generate PDF Report** - Preview and download billing report
7. **Check Activity Logs** - Show audit trail with IP addresses
8. **Toggle Dark Mode** - Demonstrate UI theme switching
9. **Resize Window** - Show responsive design

### Features to Highlight:
- ⚡ Real-time dashboard updates with statistics
- 📊 Interactive charts (intake trends, status distribution)
- 🌓 Dark/Light mode toggle with persistent preference
- 📱 Responsive design (mobile card-based tables, touch-friendly)
- 🔔 Toast notifications for all actions
- 📄 PDF preview in modal before download
- 🖨️ Individual invoice printing
- 🔐 Rate limiting (try rapid login attempts)
- 📝 Activity logs with IP tracking
- ♿ Accessibility features (ARIA labels, keyboard navigation)
- ⌨️ Keyboard shortcuts (press `?` to show help)

---

## 🔟 Potential Weaknesses (Be Honest If Asked)

```
┌──────────────────────────┬─────────────────────────┬────────────────────────────┐
│ Limitation               │ Current Workaround      │ Future Improvement         │
├──────────────────────────┼─────────────────────────┼────────────────────────────┤
│ No email notifications   │ Manual follow-ups       │ Integrate PHPMailer        │
│ Single facility only     │ N/A                     │ Add multi-tenant support   │
│ No mobile app            │ Responsive web design   │ Build React Native app     │
│ No backup automation     │ Manual exports          │ Scheduled MySQL dumps      │
│ No SMS alerts            │ N/A                     │ Integrate SMS gateway      │
│ File-based rate limiting │ Works for single server │ Use Redis for scaling      │
└──────────────────────────┴─────────────────────────┴────────────────────────────┘
```

---

## 1️⃣1️⃣ Quick Reference Card

```
Project Name:    Catarman Dog Pound Management System
Version:         1.0.3
Stack:           PHP 8.x + MySQL + Vanilla JS
Architecture:    MVC + REST API + SPA
Auth Method:     JWT (HS256, 24-hour expiry)
Password Hash:   bcrypt with auto-salt
DB Tables:       12 (InnoDB engine)
DB Indexes:      15+ for performance
User Roles:      4 (Admin, Staff, Vet, Adopter)
Controllers:     10
Models:          12
API Endpoints:   9 route files
Frontend Pages:  11
UI Components:   11
Rate Limits:     10 login/min, 100 API/min
Key Features:    Animals, Adoptions, Medical, Billing, Inventory, PDF Reports
Accessibility:   ARIA labels, focus states, keyboard shortcuts
Mobile:          Fully responsive, card-based tables on mobile
Timezone:        Asia/Manila
```

---

## 📚 Related Documentation

```
┌───────────────────────────┬───────────────────────────────────────┐
│ Document                  │ Description                           │
├───────────────────────────┼───────────────────────────────────────┤
│ README.md                 │ Project setup and quick start guide   │
│ CHANGELOG.md              │ Version history and release notes     │
│ IMPLEMENTATION_PLAN.md    │ Complete project implementation plan  │
│ PROJECT_STRUCTURE.md      │ Detailed directory and file structure │
│ SYSTEM_DIAGRAMS.md        │ Architecture, data models, use cases  │
│ BACKEND_DOCUMENTATION.md  │ Backend code explanation              │
│ FRONTEND_DOCUMENTATION.md │ Frontend code explanation             │
│ DATABASE_DOCUMENTATION.md │ Database schema reference             │
└───────────────────────────┴───────────────────────────────────────┘
```

---

Good luck with your defense! 🍀
