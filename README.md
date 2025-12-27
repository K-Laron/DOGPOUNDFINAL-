# 🐕 Catarman Dog Pound Management System

A comprehensive web-based application designed to streamline the operations of the Catarman Dog Pound. This system handles user management, animal records, adoptions, veterinary data, billing, and inventory with a reactive, user-friendly interface.

## 🚀 Features

*   **User Management**: Role-based access control (Admin, Staff, Veterinarian, Adopter) with secure JWT authentication and profile management.
*   **Animal Management**: Complete lifecycle tracking from intake to adoption, including image uploads and status updates.
*   **Adoption Portal**: All authenticated users can browse and adopt animals, with staff/admin approval workflow.
*   **Medical Records**: Detailed veterinary logs for each animal, with PDF export and preview.
*   **Billing System**: Invoice generation, payment tracking, individual invoice printing, and PDF reports with preview before download.
*   **Inventory System**: Track supplies, monitor stock levels, and receive low-stock alerts with PDF export.
*   **Dashboard**: Real-time statistics, activity logs, and overdue task notifications.
*   **PDF Preview**: Preview all PDF exports before printing or downloading (Medical, Inventory, Billing).
*   **Modern Interface**: Clean, responsive design with dark/light mode support and smooth animations.

## 🛠️ Tech Stack

*   **Frontend**: HTML5, CSS3 (Custom Design System), Vanilla JavaScript (ES6+, SPA Architecture)
*   **Backend**: PHP 8.x (Custom MVC Framework), RESTful API with JWT Authentication
*   **Database**: MySQL with PDO prepared statements (SQL injection protected)
*   **Environment**: XAMPP (Apache/MySQL/PHP)

## 🔐 Security Features

*   JWT-based authentication with token refresh
*   Password hashing using `password_hash()` / `password_verify()`
*   PDO prepared statements for all database queries
*   Role-based access control middleware
*   CORS protection with whitelisted origins
*   **Rate Limiting**: Configurable limits for login attempts (10/min) and API requests (100/min)
*   **Input Sanitization**: Automatic XSS prevention on all incoming request data

## ⚙️ Installation & Setup

### Prerequisites
*   XAMPP (or similar PHP/MySQL stack)
*   PHP 8.0+
*   MySQL 5.7+

### Steps

1.  **Clone the Repository**
    ```bash
    git clone https://github.com/K-Laron/DOGPOUNDFINAL-.git
    cd DOGPOUNDFINAL-
    ```

2.  **Database Setup**
    *   Create a new MySQL database named `catarman_dog_pound_db`.
    *   Import the schema: `database/schema.sql`
    *   Import the seed data: `database/seeders.sql`

3.  **Configuration**
    *   Navigate to `backend/app/config/`
    *   Update `database.php` with your database credentials
    *   For production: Update `config.php` with a unique `JWT_SECRET`

4.  **Running the Application**
    *   Double-click `start.bat` in the root directory
    *   The application will launch in background mode (hidden windows)
    *   The browser will automatically open at `http://localhost:3000`

5.  **Stopping the Application**
    *   Double-click `stop.bat` to gracefully shut down the background servers
    *   **Note**: Closing the browser does NOT stop the servers. You must use `stop.bat`

##  Project Structure

```
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

| Endpoint | Description |
|----------|-------------|
| `/api/auth` | Authentication (login, register, refresh) |
| `/api/users` | User management |
| `/api/animals` | Animal CRUD operations |
| `/api/adoptions` | Adoption requests & processing |
| `/api/medical` | Medical records |
| `/api/inventory` | Inventory management |
| `/api/billing` | Invoices & payments |
| `/api/dashboard` | Statistics & activity logs |
| `/api/notifications` | User notifications |

## 📚 Documentation

| Document | Description |
|----------|-------------|
| [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md) | Complete project implementation plan |
| [PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md) | Detailed directory structure |
| [BACKEND_DOCUMENTATION.md](BACKEND_DOCUMENTATION.md) | Backend code documentation |
| [FRONTEND_DOCUMENTATION.md](FRONTEND_DOCUMENTATION.md) | Frontend code documentation |
| [DATABASE_DOCUMENTATION.md](DATABASE_DOCUMENTATION.md) | Database schema & queries |

## 📄 License

This project is for educational purposes.
