# 📁 Project Structure

## Catarman Dog Pound Management System

```
dogpound/
├── 📄 README.md                 # Project documentation
├── 📄 SYSTEM_DESIGN_DOCUMENT.md # Detailed system architecture & flows
├── 📄 PROJECT_STRUCTURE.md      # This file
├── 🚀 start.bat              # Start development servers
├── 🛑 stop.bat               # Stop development servers
│
├── 🗄️ database/
│   ├── schema.sql            # Database structure & tables
│   └── seeders.sql           # Sample/test data
│
├── ⚙️ backend/
│   ├── .htaccess             # Apache URL rewriting
│   │
│   ├── app/
│   │   ├── bootstrap.php     # Application bootstrap
│   │   │
│   │   ├── api/              # API Endpoints
│   │   │   ├── adoptions.php
│   │   │   ├── animals.php
│   │   │   ├── auth.php
│   │   │   ├── billing.php
│   │   │   ├── dashboard.php
│   │   │   ├── inventory.php
│   │   │   ├── medical.php
│   │   │   ├── notifications.php
│   │   │   └── users.php
│   │   │
│   │   ├── config/           # Configuration
│   │   │   ├── config.php    # App settings, JWT, CORS
│   │   │   └── database.php  # Database connection
│   │   │
│   │   ├── controllers/      # Business Logic
│   │   │   ├── BaseController.php
│   │   │   ├── AdoptionController.php # Adoptions (Admin, Staff, Vet access)
│   │   │   ├── AnimalController.php
│   │   │   ├── AuthController.php
│   │   │   ├── BillingController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── InventoryController.php
│   │   │   ├── MedicalController.php  # Medical records & overdue tracking
│   │   │   ├── NotificationController.php
│   │   │   └── UserController.php    # User management & profile stats logic
│   │   │
│   │   ├── middleware/       # Request Middleware
│   │   │   └── AuthMiddleware.php
│   │   │
│   │   ├── models/           # Database Models
│   │   │   ├── ActivityLog.php
│   │   │   ├── AdoptionRequest.php
│   │   │   ├── Animal.php
│   │   │   ├── FeedingRecord.php
│   │   │   ├── ImpoundRecord.php
│   │   │   ├── Inventory.php
│   │   │   ├── Invoice.php
│   │   │   ├── MedicalRecord.php
│   │   │   ├── Payment.php
│   │   │   ├── Role.php
│   │   │   ├── User.php
│   │   │   └── Veterinarian.php
│   │   │
│   │   └── utils/            # Utilities
│   │       ├── JWT.php       # Token handling
│   │       ├── RateLimiter.php # Rate limiting (API & login)
│   │       ├── Response.php  # JSON responses
│   │       ├── Router.php    # URL routing
│   │       ├── Sanitizer.php # Input sanitization (XSS prevention)
│   │       └── Validator.php # Input validation
│   │
│   ├── logs/                 # Error logs (gitignored)
│   │   └── rate_limits/      # Rate limit tracking data
│   │
│   └── public/               # Web entry point
│       ├── .htaccess         # Public URL rewriting
│       ├── index.php         # API router entry
│       └── uploads/          # User uploads (gitignored)
│           ├── animals/      # Animal images
│           └── avatars/      # User avatars
│
└── 🎨 frontend/
    ├── index.html            # SPA entry point
    │
    └── assets/
        ├── css/              # Stylesheets
        │   ├── variables.css # CSS custom properties
        │   ├── main.css      # Core styles
        │   ├── components.css# UI components
        │   ├── layouts.css   # Page layouts
        │   ├── animations.css# Transitions & effects
        │   ├── responsive.css# Media queries & mobile card layouts
        │   └── enhancements.css # Enhanced UI features
        │
        ├── images/           # Static images
        │   ├── favicon.png
        │   ├── favicon.svg
        │   ├── placeholder-cat.png    # Cat-specific placeholder
        │   ├── placeholder-dog.png    # Dog-specific placeholder
        │   └── placeholder-other.png  # Other animals placeholder
        │
        ├── js/               # JavaScript
        │   ├── app.js        # Main application
        │   ├── api.js        # API client
        │   ├── auth.js       # Authentication
        │   ├── router.js     # SPA routing
        │   ├── store.js      # State management
        │   ├── utils.js      # Helper functions
        │   │
        │   ├── components/   # Reusable UI Components
        │   │   ├── Card.js
        │   │   ├── Charts.js
        │   │   ├── DataTable.js
        │   │   ├── Form.js
        │   │   ├── Header.js
        │   │   ├── HoverPreview.js
        │   │   ├── Loading.js
        │   │   ├── Modal.js
        │   │   ├── PDFPreview.js  # PDF preview with print/download
        │   │   ├── Sidebar.js
        │   │   └── Toast.js
        │   │
        │   └── pages/        # Page Controllers
        │       ├── Dashboard.js
        │       ├── Animals.js
        │       ├── AnimalDetail.js
        │       ├── Adoptions.js
        │       ├── Medical.js
        │       ├── Inventory.js
        │       ├── Billing.js
        │       ├── Users.js
        │       ├── Profile.js
        │       ├── Settings.js
        │       └── Login.js
        │
        └── pages/            # HTML Templates
            ├── admin/
            │   └── dashboard.html
            └── auth/
                └── login.html
```

## 🔑 Key Files

```
┌─────────────────────────────────────┬──────────────────────────────────────────┐
│ File                                │ Purpose                                  │
├─────────────────────────────────────┼──────────────────────────────────────────┤
│ start.bat                           │ Starts PHP & frontend servers            │
│ stop.bat                            │ Stops all background servers             │
│ backend/app/config/config.php       │ JWT secret, CORS, rate limits, settings  │
│ backend/app/config/database.php     │ MySQL connection settings                │
│ backend/app/utils/RateLimiter.php   │ Rate limiting for API & login protection │
│ backend/app/utils/Sanitizer.php     │ Input sanitization for XSS prevention    │
│ database/schema.sql                 │ Full database schema                     │
│ frontend/index.html                 │ SPA entry point                          │
│ frontend/assets/js/app.js           │ Main application bootstrap               │
└─────────────────────────────────────┴──────────────────────────────────────────┘
```
