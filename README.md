# 🐕 Catarman Dog Pound Management System

![Version](https://img.shields.io/badge/version-1.1.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.x-777BB4.svg?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1.svg?logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E.svg?logo=javascript&logoColor=black)
![License](https://img.shields.io/badge/license-Educational-green.svg)

A comprehensive web-based application designed to streamline the operations of the Catarman Dog Pound. This system handles user management, animal records, adoptions, veterinary data, billing, and inventory with a reactive, user-friendly interface.

## 🚀 Features

* **User Management**: Role-based access control (Admin, Staff, Veterinarian, Adopter) with secure JWT authentication and profile management.
* **Animal Management**: Complete lifecycle tracking from intake to adoption, including image uploads, unique type-specific placeholders, and status updates.
* **Adoption Portal**: Automated workflow where approval reserves the animal and completion marks it as adopted. All authenticated users can browse and submit requests.
* **Medical Records**: Detailed veterinary logs for each animal, with PDF export and preview.
* **Billing System**: Invoice generation, payment tracking, individual invoice printing, and PDF reports with preview before download.
* **Inventory System**: Track supplies, monitor stock levels, and receive low-stock alerts with PDF export.
* **Dashboard**: Real-time statistics, activity logs, and overdue task notifications.
* **Performance Optimized**: McMaster-Carr inspired prefetching, smart caching, and Optimistic UI for instant page transitions.
* **PDF Preview**: Preview all PDF exports before printing or downloading (Medical, Inventory, Billing).
* **Modern Interface**: Clean, responsive design with dark/light mode support and smooth animations.
* **Keyboard Shortcuts**: Navigate quickly with shortcuts (`/` for search, `?` for help, `g+h` for home).

## ♿ Accessibility

* ARIA labels on all interactive elements
* Clear focus states for keyboard navigation
* Screen reader support with live regions
* Respects `prefers-reduced-motion` for users sensitive to animations
* High contrast mode support

## 🛠️ Tech Stack

* **Frontend**: HTML5, CSS3 (Custom Design System), Vanilla JavaScript (ES6+, SPA Architecture)
* **Backend**: PHP 8.x (Custom MVC Framework), RESTful API with JWT Authentication
* **Database**: MySQL with PDO prepared statements (SQL injection protected)
* **Environment**: XAMPP (Apache/MySQL/PHP)

## 🔐 Security Features

* JWT-based authentication with token refresh
* Password hashing using `password_hash()` / `password_verify()`
* PDO prepared statements for all database queries
* Role-based access control middleware
* CORS protection with whitelisted origins (configurable via `CORS_ORIGINS`)
* **Rate Limiting**: Configurable limits for login attempts (10/min) and API requests (100/min)
* **Input Sanitization**: Automatic XSS prevention on all incoming request data
* **Security Headers**: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy
* **Environment Configuration**: Secure `.env` file support for secrets management
* **File Upload Validation**: MIME type verification and image validation

## ⚙️ Installation & Setup

### Prerequisites

* XAMPP (or similar PHP/MySQL stack)
* PHP 8.0+
* MySQL 5.7+

### Steps

1. **Clone the Repository**

   ```bash
   git clone https://github.com/K-Laron/DOGPOUNDFINAL-.git
   cd DOGPOUNDFINAL-
   ```

2. **Database Setup**

   * Create a new MySQL database named `catarman_dog_pound_db`.
   * Import the schema: `database/schema.sql`
   * Import the seed data: `database/seeders.sql`

3. **Configuration**

   * Copy `.env.example` to `.env`: `cp .env.example .env`
   * Update `.env` with your database credentials:

     ```env
     DB_HOST=127.0.0.1
     DB_PORT=3307
     DB_NAME=catarman_dog_pound_db
     DB_USER=root
     DB_PASS=
     ```

   * For production: Set a unique `JWT_SECRET` and `APP_ENV=production`

4. **Running the Application**

   * Double-click `start.bat` in the root directory
   * The application will launch in background mode (hidden windows)
   * The browser will automatically open at `http://localhost:3000`

5. **Stopping the Application**

   * Double-click `stop.bat` to gracefully shut down the background servers
   * **Note**: Closing the browser does NOT stop the servers. You must use `stop.bat`

## Project Structure

```text
├── backend/
│   ├── app/
│   │   ├── api/          # API endpoints
│   │   ├── config/       # Configuration files
│   │   ├── controllers/  # Business logic
│   │   ├── middleware/   # Auth middleware
│   │   ├── models/       # Database models
│   │   └── utils/        # JWT, Router, Validator, RateLimiter, Sanitizer
│   ├── logs/             # Error logs & rate limit data
│   └── public/           # Entry point & uploads
├── frontend/
│   ├── assets/
│   │   ├── css/          # Stylesheets
│   │   ├── js/           # JavaScript modules
│   │   └── pages/        # HTML templates
│   └── index.html        # Main SPA entry
├── database/
│   ├── schema.sql        # Database structure
│   └── seeders.sql       # Sample data
├── start.bat             # Start servers
└── stop.bat              # Stop servers
```

## 📝 API Endpoints

```text
┌────────────────────┬───────────────────────────────────────────┐
│ Endpoint           │ Description                               │
├────────────────────┼───────────────────────────────────────────┤
│ /api/auth          │ Authentication (login, register, refresh) │
│ /api/users         │ User management                           │
│ /api/animals       │ Animal CRUD operations                    │
│ /api/adoptions     │ Adoption requests & processing            │
│ /api/medical       │ Medical records                           │
│ /api/inventory     │ Inventory management                      │
│ /api/billing       │ Invoices & payments                       │
│ /api/dashboard     │ Statistics & activity logs                │
│ /api/notifications │ User notifications                        │
└────────────────────┴───────────────────────────────────────────┘
```

## 📚 Documentation

| Document | Location | Description |
| -------- | -------- | ----------- |
| CHANGELOG.md | Root | Version history and release notes |
| IMPLEMENTATION_PLAN.md | Root | Complete project implementation plan |
| PROJECT_STRUCTURE.md | Root | Detailed directory structure |
| DATABASE_DOCUMENTATION.md | Root | Database schema & queries |
| SYSTEM_DIAGRAMS.md | Root | System Context, Architecture, Use Cases, Event Flows (ASCII) |
| DEFENSE_GUIDE.md | Root | Security defenses, Q&A, and threat analysis |
| PIECES_QUESTIONNAIRE.md | Root | Comprehensive system analysis (PIECES framework) |
| BACKEND_DOCUMENTATION.md | `/docs` | Backend code documentation |
| FRONTEND_DOCUMENTATION.md | `/docs` | Frontend code documentation |
| SYSTEM_DESIGN_DOCUMENT.md | `/docs` | System architecture and flowcharts |

## 📄 License

This project is for educational purposes.
