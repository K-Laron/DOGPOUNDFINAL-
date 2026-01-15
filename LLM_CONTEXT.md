# Catarman Dog Pound Management System - LLM Context Document

> **Purpose**: This document provides comprehensive context for an LLM to understand the entire system architecture, codebase, and business logic.

---

## System Overview

**Name**: Catarman Dog Pound Management System  
**Version**: 1.2.0  
**Type**: Web-based Information Management System  
**Purpose**: Digitizes animal intake, adoption processing, medical records, billing, and inventory management for the Catarman Dog Pound facility.

---

## Technology Stack

| Layer     | Technology         | Details                                     |
|-----------|--------------------|-----------------------&shy;----------------------|
| Frontend  | Vanilla JavaScript | SPA (Single Page Application), no framework |
| Backend   | PHP 8.x            | Custom MVC framework, RESTful API           |
| Database  | MySQL (InnoDB)     | 12 tables, 15+ indexes                      |
| Auth      | JWT                | HS256, 24-hour expiry, bcrypt passwords     |
| Real-time | SSE                | Server-Sent Events for live updates         |
| Testing   | PHPUnit 10.x       | 92 tests (unit + feature/integration)       |

---

## Architecture Pattern

```text
Frontend (SPA) → HTTP/REST (JSON) → Backend (PHP MVC) → MySQL Database
                                          ↓
                              Security Layer (JWT, Rate Limiter, Sanitizer)
                                          ↓
                              Real-Time Layer (SSE with 3s polling)
```

---

## Project Structure

```text
dogpound/
├── backend/app/
│   ├── api/           # 11 route files (auth, animals, adoptions, billing, sse, system, etc.)
│   ├── controllers/   # 12 controllers (Auth, User, Animal, Adoption, Billing, Medical, Inventory, Dashboard, Notification, SSE, System, Base)
│   ├── models/        # 12 models (User, Animal, Invoice, Payment, MedicalRecord, etc.)
│   ├── middleware/    # AuthMiddleware.php, RequestLogger.php
│   ├── utils/         # JWT, Router (API v1), Validator, RateLimiter, Sanitizer, Response, Env
│   └── tests/         # PHPUnit tests (Unit: Validator, Sanitizer, JWT; Feature: Auth, Animals)
├── frontend/assets/
│   ├── css/           # 7 stylesheets (variables, main, components, layouts, etc.)
│   └── js/
│       ├── components/ # 11 components (DataTable, Modal, Form, Toast, Charts, etc.)
│       └── pages/      # 12 pages (Dashboard, Animals, Adoptions, AdopterRequests, etc.)
├── database/
│   ├── schema.sql     # Database structure
│   └── seeders.sql    # Sample data
└── Documentation files (README, CHANGELOG, DEFENSE_GUIDE, etc.)
```

---

## Database Schema (12 Tables)

### Core Tables

| Table             | Purpose                    | Key Fields                                              |
|-------------------|----------------------------|---------------------------------------------------------|
| Roles             | User permission levels     | RoleID, Role_Name                                       |
| Users             | All system users           | UserID, RoleID, Email, Password_Hash, Account_Status    |
| Veterinarians     | Extended vet info          | VetID, UserID, License_Number, Specialization           |
| Animals           | Animal records             | AnimalID, Name, Type, Breed, Current_Status, Image_URL  |
| Impound_Records   | Intake details             | ImpoundID, AnimalID, Capture_Date, Location_Found       |
| Medical_Records   | Health treatments          | RecordID, AnimalID, VetID, Diagnosis_Type, Next_Due_Date|
| Feeding_Records   | Feeding log                | FeedingID, AnimalID, Fed_By_UserID, Food_Type           |
| Adoption_Requests | Adoption applications      | RequestID, AnimalID, Adopter_UserID, Status             |
| Inventory         | Supply tracking            | ItemID, Item_Name, Category, Quantity_On_Hand           |
| Invoices          | Billing records            | InvoiceID, Payer_UserID, Total_Amount, Status           |
| Payments          | Payment transactions       | PaymentID, InvoiceID, Amount_Paid, Payment_Method       |
| Activity_Logs     | Audit trail                | LogID, UserID, Action_Type, IP_Address                  |

### Key ENUMs

- **Animal Types**: Dog, Cat, Other
- **Animal Status**: Available, Reserved, Adopted, Deceased, In Treatment, Quarantine, Reclaimed
- **Intake Status**: Stray, Surrendered, Confiscated
- **Adoption Status**: Pending, Interview Scheduled, Seminar Scheduled, Approved, Rejected, Completed, Cancelled
- **Diagnosis Types**: Checkup, Vaccination, Surgery, Treatment, Emergency, Deworming, Spay/Neuter
- **Payment Methods**: Cash, GCash, Bank Transfer
- **Invoice Status**: Unpaid, Paid, Cancelled
- **Account Status**: Active, Inactive, Banned

---

## User Roles & Permissions

| Role         | Dashboard | Users | Animals | Adoptions | Medical | Billing | Inventory |
|--------------|-----------|-------|---------|-----------|---------|---------|-----------|
| Admin        | ✅        | ✅    | ✅      | ✅        | ✅      | ✅      | ✅        |
| Staff        | ✅        | ❌    | ✅      | ✅        | ✅      | ✅      | ✅        |
| Veterinarian | ✅        | ❌    | ✅      | View      | ✅      | ❌      | ❌        |
| Adopter      | ❌        | ❌    | Browse  | Own       | ❌      | ❌      | ❌        |

---

## Core Business Workflows

### Adoption Process

```text
PENDING → INTERVIEW SCHEDULED → SEMINAR SCHEDULED → APPROVED → COMPLETED
                                      ↓                 ↓
                                 CANCELLED           REJECTED
```

**Key Rules**:

- All authenticated users can submit adoption requests
- Adopters can cancel their own Pending/Interview Scheduled/Seminar Scheduled requests
- Approving auto-sets animal to "Reserved" and auto-rejects other pending requests
- Completing sets animal to "Adopted"
- System auto-generates invoice on approval
- Fees: Dog ₱500, Cat ₱300, Other ₱200

### Medical Record Tracking

- Treatments linked to veterinarian and animal
- `Next_Due_Date` tracking for follow-ups
- **Overdue alerts**: When `Next_Due_Date < CURDATE()` for active animals
- **Upcoming alerts**: Treatments due within 7 days

### Billing System

- Auto-invoice generation for adoptions/reclaims
- Partial payments supported
- Reclaim fees: Base ₱200 + ₱50/day
- PDF reports: Summary, Detailed, Unpaid (via jsPDF)

### Inventory Management

- Categories: Medical, Food, Cleaning, Supplies
- **Low stock alerts**: When `Quantity_On_Hand <= Reorder_Level`
- **Expiring soon alerts**: Items expiring within 7 days

---

## Security Features

| Feature                | Implementation                           | Location               |
|------------------------|------------------------------------------|------------------------|
| Password Hashing       | bcrypt via `password_hash()`             | Built-in PHP           |
| SQL Injection          | PDO prepared statements                  | All Models             |
| Authentication         | JWT with HS256, 24hr expiry              | JWT.php                |
| Authorization          | Role-based middleware                    | AuthMiddleware.php     |
| Rate Limiting          | 10 login/min, 100 API/min per IP         | RateLimiter.php        |
| XSS Prevention         | Input sanitization + `escapeHTML()`      | Sanitizer.php, utils.js|
| CORS                   | Whitelisted origins                      | bootstrap.php          |
| Security Headers       | X-Frame-Options, X-Content-Type, etc.    | bootstrap.php          |
| File Upload Validation | MIME type + `getimagesize()` verification| BaseController.php     |
| Audit Trail            | All actions logged with IP               | Activity_Logs table    |
| Request Logging        | Structured JSON with timing              | RequestLogger.php      |
| Environment Config     | `.env` for secrets (gitignored)          | Env.php                |

---

## Real-Time Updates (SSE)

- **SSEController** establishes long-lived HTTP connection
- Polls database every 3 seconds for changes
- Monitors: Animals, Adoptions, Inventory, Medical, Billing tables
- Frontend SSE client triggers smart refreshes (avoids interrupting user input)
- Automatic reconnection on timeout

---

## Frontend Architecture

### Pages (12)

| Page          | File               | Purpose                           |
|---------------|--------------------|-----------------------------------|
| Dashboard     | Dashboard.js       | Statistics, charts, activity feed |
| Animals       | Animals.js         | Animal CRUD, grid/table view      |
| Animal Detail | AnimalDetail.js    | Single animal profile             |
| Adoptions     | Adoptions.js       | Adoption request management       |
| My Requests   | AdopterRequests.js | Adopter's own applications        |
| Medical       | Medical.js         | Medical records CRUD              |
| Billing       | Billing.js         | Invoices, payments, PDF reports   |
| Inventory     | Inventory.js       | Stock management                  |
| Users         | Users.js           | User management (Admin only)      |
| Profile       | Profile.js         | User profile settings             |
| Settings      | Settings.js        | System settings                   |
| Login         | Login.js           | Authentication                    |

### Reusable Components (11)

DataTable, Modal, Form, Toast, Charts, Card, Header, Sidebar, Loading, HoverPreview, PDFPreview

### UI Features

- Dark/Light mode with persistent preference
- Responsive design (mobile card-based tables)
- Keyboard shortcuts (press `?` for help)
- ARIA labels, focus states, keyboard navigation
- Predictive prefetching on hover
- Smart caching to reduce API calls

---

## API Endpoints

| Route File       | Base Path            | Purpose                           |
|------------------|----------------------|-----------------------------------|
| auth.php         | /api/v1/auth         | Login, logout, token refresh      |
| users.php        | /api/v1/users        | User CRUD, profile                |
| animals.php      | /api/v1/animals      | Animal CRUD, search, stats        |
| adoptions.php    | /api/v1/adoptions    | Adoption requests, processing     |
| medical.php      | /api/v1/medical      | Medical records CRUD              |
| billing.php      | /api/v1/billing      | Invoices, payments, reports       |
| inventory.php    | /api/v1/inventory    | Stock management                  |
| dashboard.php    | /api/v1/dashboard    | Statistics, charts data           |
| notifications.php| /api/v1/notifications| User notifications                |
| sse.php          | /api/v1/sse          | Server-Sent Events stream         |
| system.php       | /api/v1/health       | Health check & system info        |

---

## Key Code Patterns

### Backend

- All controllers extend `BaseController` (provides `$db`, `$user`, helpers)
- Models use PDO prepared statements exclusively
- Strict PHP 7+ return type declarations
- Soft deletes via `Is_Deleted` flag
- Activity logging for all significant actions

### Frontend

- SPA with hash-based routing (`#/animals`, `#/billing`, etc.)
- `api.js` handles all HTTP requests with caching options
- `Utils.escapeHTML()` for XSS-safe DOM rendering
- Components are class-based with lifecycle methods

---

## Environment Configuration (.env)

```bash
APP_ENV=production
APP_DEBUG=false
JWT_SECRET=your-secret-key
DB_HOST=localhost
DB_PORT=3306
DB_NAME=catarman_dog_pound_db
DB_USER=root
DB_PASS=
TRUSTED_PROXY=false
CORS_ORIGINS=http://localhost
```

---

## Common Query Patterns

```sql
-- Soft delete filter
WHERE Is_Deleted = FALSE

-- Conditional aggregation for stats
SUM(CASE WHEN Status = 'Available' THEN 1 ELSE 0 END) as available

-- Overdue treatments
WHERE Next_Due_Date < CURDATE() AND Current_Status NOT IN ('Adopted', 'Deceased')

-- Date range filtering
WHERE Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
```

---

## Known Limitations

- Single facility only (no multi-tenant)
- No email notifications (manual follow-ups)
- No SMS alerts
- File-based rate limiting (not Redis)

---

## File Naming Conventions

- Controllers: `PascalCaseController.php`
- Models: `PascalCase.php`
- Pages: `PascalCase.js`
- Components: `PascalCase.js`
- CSS: `lowercase.css`
- PDF naming: `ReportType_FirstName_LastName_Date.pdf`

---

This document provides complete context for understanding the Catarman Dog Pound Management System codebase, architecture, and business logic.
