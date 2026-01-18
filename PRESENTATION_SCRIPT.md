# 🎤 Presentation Script: Catarman Dog Pound & Animal Shelter Management System

> **Capstone Defense Presentation Script**  
> **Version**: 1.2.0  
> **Team**: Tess Laron & Team

---

## 📋 Presentation Outline

| Slide | Topic | Est. Time |
|-------|-------|-----------|
| 1 | Title Slide | 30 sec |
| 2-3 | Introduction & Problem Statement | 2 min |
| 4-5 | Objectives & Scope | 2 min |
| 6-7 | Technology Stack & Architecture | 3 min |
| 8-10 | Key Features & Modules | 5 min |
| 11-12 | Database Design | 2 min |
| 13-14 | Security Implementation | 2 min |
| 15-16 | System Demo Highlights | 3 min |
| 17 | Testing & Quality Assurance | 1 min |
| 18-19 | Conclusions & Recommendations | 2 min |
| 20 | Q&A | 5+ min |

**Total Estimated Time**: 25-30 minutes

---

## 🎬 SLIDE 1: Title Slide

### Script:

> "Good [morning/afternoon], members of the panel, faculty, and guests. We are here to present our capstone project entitled **'Catarman Dog Pound and Animal Shelter Management System.'**
>
> I am [Speaker Name], together with my groupmates [Names]. Our adviser is [Adviser Name].
>
> This system was developed for the Catarman Dog Pound facility in Northern Samar to address the challenges they face with manual record-keeping and operational management."

---

## 🎬 SLIDE 2: Introduction

### Script:

> "The Catarman Dog Pound is a local government facility responsible for managing stray, surrendered, and confiscated animals. Currently, they rely on **paper-based records** for tracking animals, processing adoptions, managing medical treatments, and handling billing transactions.
>
> This manual approach leads to several operational challenges that our system aims to address."

---

## 🎬 SLIDE 3: Problem Statement

### Script:

> "Through our initial assessment using the **PIECES Framework**, we identified the following problems:
>
> **1. Performance Issues** — Manual record retrieval takes considerable time, and there's no real-time visibility of inventory levels or animal statuses.
>
> **2. Information Problems** — Records are prone to being lost, damaged, or incomplete. There's no centralized database for quick lookups.
>
> **3. Economics Issues** — Staff spend excessive time on paperwork instead of animal care. Duplicate records waste resources.
>
> **4. Control Weaknesses** — No audit trail exists for who modified records or when. Anyone can access sensitive files.
>
> **5. Efficiency Gaps** — Adoption processing involves multiple manual steps that could be automated. Medical follow-up reminders are easily forgotten.
>
> **6. Service Limitations** — Adopters have no way to check application status online. There's no self-service option for browsing available animals."

---

## 🎬 SLIDE 4: Project Objectives

### Script:

> "Our project objectives are divided into **General** and **Specific** objectives:
>
> **General Objective:**  
> To develop a web-based management system that digitizes and streamlines the operations of the Catarman Dog Pound.
>
> **Specific Objectives:**
>
> 1. To create a centralized database for animal records, including intake, medical history, and adoption status.
>
> 2. To implement a structured adoption workflow with interview scheduling, seminar scheduling, and approval processing.
>
> 3. To develop a billing module that auto-generates invoices and tracks payments with multiple payment methods including GCash.
>
> 4. To provide inventory management with low-stock alerts and expiration tracking.
>
> 5. To implement role-based access control ensuring proper authorization for different user types.
>
> 6. To enable real-time updates so staff can see changes immediately without refreshing the page."

---

## 🎬 SLIDE 5: Scope and Limitations

### Script:

> "**Scope — The system includes:**
> - Animal intake and records management (dogs, cats, and other animals)
> - Complete adoption workflow from request to completion
> - Medical records tracking with overdue treatment alerts
> - Billing with PDF report generation
> - Inventory management with stock level monitoring
> - User management with four distinct roles
> - Real-time updates using Server-Sent Events
>
> **Limitations:**
> - The system is designed for a single facility only
> - There is no integrated email or SMS notification system
> - Payment processing is recorded manually (no payment gateway integration)
> - Requires internet connection to access"

---

## 🎬 SLIDE 6: Technology Stack

### Script:

> "Let me walk you through our technology choices:
>
> **Frontend:**  
> We used **Vanilla JavaScript** with a Single Page Application architecture. We chose this over frameworks like React or Vue because it's lightweight, has zero dependencies, and results in faster loading times. The frontend is fully responsive, working on both desktop and mobile devices.
>
> **Backend:**  
> We implemented our API using **PHP 8.x** with a custom MVC framework. PHP is an industry standard, easily deployable on shared hosting, and compatible with XAMPP which the facility already uses.
>
> **Database:**  
> **MySQL** with the InnoDB engine handles our data storage. We have 12 tables with proper foreign key relationships and over 15 indexes for query optimization.
>
> **Authentication:**  
> We use **JWT (JSON Web Tokens)** with HS256 signature and 24-hour expiry for stateless, secure authentication.
>
> **Testing:**  
> We implemented **PHPUnit 10.x** with 92 automated tests covering validation, sanitization, authentication, and core features."

---

## 🎬 SLIDE 7: System Architecture

### Script:

> "Here's our three-tier architecture:
>
> The **Frontend** is a Single Page Application that communicates with the backend via REST API calls using JSON format.
>
> The **Backend** follows the MVC pattern. When a request comes in, it passes through our **Security Layer** which includes:
> - Rate limiting (10 login attempts per minute, 100 API calls per minute)
> - Input sanitization to prevent XSS attacks
> - JWT authentication and role-based authorization
>
> Only after passing these security checks does the request reach our **Controllers**, which interact with **Models** to query the **MySQL Database**.
>
> We also implemented **Server-Sent Events** for real-time updates. The system polls for changes every 3 seconds and pushes updates to connected clients, so data stays synchronized across multiple users."

---

## 🎬 SLIDE 8: Key Features — Animal Management

### Script:

> "Our first major module is **Animal Management**.
>
> Staff can register new animals with details like name, type (dog, cat, or other), breed, age group, gender, and weight. Each animal is assigned a status that tracks their journey through the facility.
>
> **Status Options Include:**
> - Available — Ready for adoption
> - Reserved — An adoption has been approved
> - Adopted — Successfully adopted
> - In Treatment — Under medical care
> - Quarantine — Isolated for health reasons
> - Reclaimed — Returned to original owner
> - Deceased — No longer living
>
> Staff can also record intake information including capture date, location found, and condition on arrival. The system supports **image uploads** with validation for file size and type."

---

## 🎬 SLIDE 9: Key Features — Adoption Workflow

### Script:

> "The **Adoption Module** is the heart of our system. Let me explain the workflow:
>
> 1. **Pending** — An adopter browses available animals and submits a request with their information and reason for adoption.
>
> 2. **Interview Scheduled** — Staff reviews the request and schedules an interview date.
>
> 3. **Seminar Scheduled** — After the interview, staff schedules an Animal Welfare Seminar to educate the adopter about proper pet care.
>
> 4. **Approved** — Once approved, the system automatically:
>    - Changes the animal's status to **Reserved**
>    - Rejects all other pending requests for that same animal
>    - Generates an invoice for the adoption fee
>
> 5. **Completed** — After payment, the adoption is finalized and the animal status changes to **Adopted**.
>
> Adoption fees are structured as: Dogs at ₱500, Cats at ₱300, and Other animals at ₱200.
>
> Adopters have a dedicated **My Requests** page where they can track their applications and even cancel pending requests."

---

## 🎬 SLIDE 10: Key Features — Medical, Billing & Inventory

### Script:

> "We have three additional core modules:
>
> **Medical Records:**
> - Veterinarians can log treatments with diagnosis types including Checkup, Vaccination, Surgery, Treatment, Emergency, Deworming, Spay/Neuter, and **Euthanasia**.
> - **Euthanasia Feature**: When a Euthanasia record is created, the system automatically updates the animal's status to 'Deceased'.
> - Each diagnosis type has a distinct color badge for quick visual identification.
> - The system tracks **Next Due Date** for follow-up treatments.
> - Dashboard alerts show **overdue treatments** and **upcoming treatments** within 7 days.
>
> **Billing Module:**
> - Invoices are automatically generated for adoptions and reclaims.
> - Reclaim fees are calculated as ₱200 base plus ₱50 per day.
> - Staff can record partial payments with multiple payment methods: Cash, GCash, or Bank Transfer.
> - The system generates **PDF reports** for summary, detailed, and unpaid invoices using jsPDF.
>
> **Inventory Management:**
> - Tracks supplies across categories: Medical, Food, Cleaning, and General Supplies.
> - Provides **Low Stock Alerts** when quantity falls to or below reorder level.
> - Shows **Expiring Soon Alerts** for items expiring within 7 days."

---

## 🎬 SLIDE 11: Database Design

### Script:

> "Our database consists of **12 tables** with proper normalization:
>
> **Core Tables:**
> - `Roles` and `Users` — For authentication and authorization
> - `Veterinarians` — Extended information for vet users
> - `Animals` — Core animal records
> - `Impound_Records` — Intake details linked to animals
> - `Medical_Records` — Health treatments with vet linkage
> - `Feeding_Records` — Daily feeding logs
> - `Adoption_Requests` — Application processing
> - `Inventory` — Supply tracking
> - `Invoices` and `Payments` — Financial transactions
> - `Activity_Logs` — Complete audit trail
>
> We use **soft deletes** via an `Is_Deleted` flag to preserve historical data while hiding records from normal views. All sensitive columns are indexed for query performance."

---

## 🎬 SLIDE 12: Entity Relationships

### Script:

> "Here are the key relationships:
>
> - A **User** belongs to one **Role** (Admin, Staff, Veterinarian, or Adopter)
> - An **Animal** can have multiple **Medical Records**, **Feeding Records**, and **Adoption Requests**
> - An **Invoice** can have multiple **Payments** for partial payment support
> - All significant actions are logged in **Activity_Logs** with timestamps and IP addresses
>
> We enforce referential integrity with foreign key constraints using `ON UPDATE CASCADE` to maintain data consistency."

---

## 🎬 SLIDE 13: Security Implementation

### Script:

> "Security was a top priority. Here's what we implemented:
>
> **Authentication & Authorization:**
> - Passwords are hashed using **bcrypt** via PHP's `password_hash()` function
> - JWT tokens with HS256 signature provide stateless authentication
> - Role-based access control restricts features by user type
>
> **Attack Prevention:**
> - All database queries use **PDO prepared statements** to prevent SQL injection
> - Input is automatically sanitized through our Sanitizer class to prevent XSS
> - We also use `Utils.escapeHTML()` on the frontend for a second layer of XSS defense
>
> **Rate Limiting:**
> - Login attempts limited to 10 per minute per IP address
> - General API calls limited to 100 per minute
> - Excess requests receive HTTP 429 with a Retry-After header
>
> **Audit Trail:**
> - Every significant action is logged with user ID, action type, affected record, IP address, and timestamp
> - This provides accountability and helps investigate any issues"

---

## 🎬 SLIDE 14: Additional Security Features

### Script:

> "We also implemented:
>
> **Security Headers:**
> - `X-Content-Type-Options: nosniff` prevents MIME sniffing attacks
> - `X-Frame-Options: DENY` prevents clickjacking
> - `X-XSS-Protection: 1; mode=block` enables browser XSS filtering
>
> **File Upload Security:**
> - Extension whitelist check
> - MIME type verification
> - Image validation using `getimagesize()`
> - Random filenames to prevent guessing attacks
>
> **Environment Configuration:**
> - Sensitive credentials stored in `.env` file which is gitignored
> - Production mode disables debug output
> - Trusted proxy configuration for deployment behind load balancers"

---

## 🎬 SLIDE 15: User Roles & Permissions

### Script:

> "We have four user roles with different access levels:
>
> **Admin** — Full system access including user management
>
> **Staff** — Can manage animals, adoptions, billing, and inventory, but cannot manage other users
>
> **Veterinarian** — Has access to animals and medical records, can view adoptions (read-only), but no access to billing or inventory
>
> **Adopter** — Can browse available animals, submit adoption requests, track their own applications via the My Requests page, and cancel pending requests
>
> This separation ensures proper **Separation of Duties** — for example, veterinarians can see adoption history for health check coordination but cannot approve adoptions themselves."

---

## 🎬 SLIDE 16: System Demo Highlights

### Script:

> "For our demo, we'll walk through:
>
> 1. **Admin Login** — View dashboard with statistics and charts showing intake trends and status distribution
>
> 2. **Add an Animal** — Demonstrate CRUD operations with image upload
>
> 3. **Adopter Experience** — Switch accounts to show how an adopter browses animals and submits a request
>
> 4. **Process Adoption** — Back to admin to approve the request, showing automatic invoice generation
>
> 5. **Record Payment** — Select GCash as payment method and mark invoice as paid
>
> 6. **PDF Report** — Generate and preview a billing report before download
>
> 7. **Real-Time Updates** — Show data synchronization across browser tabs
>
> 8. **Responsive Design** — Resize window to demonstrate mobile layout
>
> 9. **Dark Mode** — Toggle theme to show the system adapts to user preferences"

---

## 🎬 SLIDE 17: Testing & Quality Assurance

### Script:

> "To ensure system reliability, we implemented:
>
> **Automated Testing with PHPUnit 10.x:**
> - 92 total tests covering our codebase
> - **68 Unit Tests** for Validator, Sanitizer, and JWT utilities
> - **24 Feature Tests** for Auth and Animals API endpoints
>
> **What We Test:**
> - Input validation rules (email format, required fields, data types)
> - XSS prevention in sanitizer
> - JWT token generation and verification
> - API authentication flows
> - CRUD operations on animals
>
> **Code Quality:**
> - Strict PHP return type declarations throughout the backend
> - Consistent coding standards and patterns
> - Comprehensive documentation for all modules"

---

## 🎬 SLIDE 18: Conclusions

### Script:

> "In conclusion:
>
> We successfully developed a **web-based management system** that addresses the operational challenges of the Catarman Dog Pound.
>
> **Key Achievements:**
> - ✅ Centralized digital record-keeping replacing paper-based systems
> - ✅ Structured adoption workflow with interview and seminar scheduling
> - ✅ Automated invoice generation and payment tracking
> - ✅ Real-time updates keeping all users synchronized
> - ✅ Proper security measures including authentication, authorization, and audit trails
> - ✅ Mobile-responsive design for accessibility on any device
>
> The system is ready for deployment and can immediately improve the efficiency of the Catarman Dog Pound operations."

---

## 🎬 SLIDE 19: Recommendations for Future Development

### Script:

> "For future enhancements, we recommend:
>
> 1. **Email Notifications** — Integrate PHPMailer to notify adopters of status changes automatically
>
> 2. **SMS Alerts** — Add SMS gateway integration for urgent notifications like overdue treatments
>
> 3. **Multi-Facility Support** — Extend the database to support multiple dog pound facilities with shared or separate data
>
> 4. **Mobile Application** — Develop a dedicated mobile app using React Native for an improved mobile experience
>
> 5. **Automated Backups** — Implement scheduled MySQL database backups
>
> 6. **Payment Gateway Integration** — Connect with GCash or Maya APIs for direct online payment processing
>
> These enhancements would further improve the system's capabilities and user experience."

---

## 🎬 SLIDE 20: Thank You & Q&A

### Script:

> "That concludes our presentation of the **Catarman Dog Pound and Animal Shelter Management System**.
>
> We would like to thank our adviser [Adviser Name] for the guidance, the Catarman Dog Pound staff for their cooperation during requirements gathering, and the panel for taking the time to evaluate our work.
>
> We are now ready to answer any questions you may have."

---

## 🔑 Quick Reference for Q&A

### Common Questions & Answers:

| Question | Key Points to Mention |
|----------|----------------------|
| "Why PHP/MySQL?" | Industry standard, XAMPP compatible, easy deployment on shared hosting |
| "How do you prevent SQL injection?" | PDO prepared statements, never concatenate user input into queries |
| "How do you prevent XSS?" | Sanitizer class on backend, escapeHTML() on frontend |
| "How do you handle concurrent users?" | JWT is stateless, InnoDB row-level locking handles database concurrency |
| "What happens if server crashes?" | Data persists in MySQL, users re-login (JWT 24hr expiry), activity logs help trace issues |
| "Can this scale to multiple facilities?" | Current design is single-facility; would add Facilities table with foreign keys |
| "Why no React/Vue?" | Vanilla JS is lighter, zero dependencies, faster loading |
| "How does real-time work?" | SSE polls database every 3 seconds, pushes changes to connected clients |
| "What's the Seminar step?" | Animal Welfare education before adoption approval, ensures responsible ownership |
| "How does Euthanasia work?" | Recording Euthanasia auto-updates animal status to 'Deceased', logs the action |
| "What happens to inactive users?" | Adopters inactive 30+ days auto-set to Inactive when Admin loads Dashboard |

---

## 📊 Technical Quick Reference

```text
Project Name:    Catarman Dog Pound Management System
Version:         1.2.0
Stack:           PHP 8.x + MySQL + Vanilla JS
Architecture:    MVC + REST API (/api/v1/) + SPA + SSE
Auth Method:     JWT (HS256, 24-hour expiry)
Password Hash:   bcrypt with auto-salt
DB Tables:       12 (InnoDB engine)
DB Indexes:      15+ for performance
DB Migrations:   4 migration files
User Roles:      4 (Admin, Staff, Vet, Adopter)
Controllers:     12 (incl. SSE, System, Notification)
Models:          12
Utils:           13 (JWT, Router, Validator, Sanitizer, etc.)
Middleware:      3 (Auth, CSRF, RequestLogger)
API Routes:      11 route files
Frontend Pages:  12 (incl. AdopterRequests)
UI Components:   12 (incl. HoverPreview, MobileNav)
Core JS:         8 (api, app, auth, router, utils, constants, icons, store)
Rate Limits:     10 login/min, 100 API/min
Testing:         PHPUnit 10.x - 92 tests
Key Features:    Euthanasia auto-status, Auto-inactive users, SSE real-time
```

---

**Good luck with your defense! 🍀**
