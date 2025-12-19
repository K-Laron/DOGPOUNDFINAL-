<?php
/**
 * Application Configuration
 */

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Timezone
date_default_timezone_set('Asia/Manila');

// JWT Configuration
define('JWT_SECRET', 'your-super-secret-key-change-in-production');
define('JWT_EXPIRY', 86400); // 24 hours

// Application Settings
define('APP_NAME', 'Animal Shelter Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/animal-shelter-backend');

// Upload Settings
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);