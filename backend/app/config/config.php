<?php
/**
 * Application Configuration
 * 
 * @package AnimalShelter
 */

// ============================================
// ENVIRONMENT SETTINGS
// ============================================

// Environment: 'development' or 'production'
define('APP_ENV', 'development');

// Application Info
define('APP_NAME', 'Catarman Dog Pound Management System');
define('APP_VERSION', '1.0.0');

// ============================================
// URL CONFIGURATION
// ============================================

// Base URL for the API (update for your environment)
define('BASE_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000'));

// Frontend URL (for CORS)
define('FRONTEND_URL', 'http://localhost:3000');

// ============================================
// JWT CONFIGURATION
// ============================================

// Secret key for JWT signing (CHANGE THIS IN PRODUCTION!)
define('JWT_SECRET', 'your-super-secret-key-change-this-in-production-minimum-32-characters');

// Token expiry times (in seconds)
define('JWT_EXPIRY', 86400);           // 24 hours
define('JWT_REFRESH_EXPIRY', 604800);  // 7 days

// ============================================
// CORS CONFIGURATION
// ============================================

// Allowed origins for CORS
define('ALLOWED_ORIGINS', [
    'http://localhost',
    'http://localhost:3000',
    'http://localhost:5173',
    'http://localhost:8080',
    'http://127.0.0.1',
    'http://127.0.0.1:5500',
    FRONTEND_URL
    // In production, add your actual domain here and remove localhost entries
]);

// ============================================
// FILE UPLOAD SETTINGS
// ============================================

// Upload directory path
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads/');

// Upload URL
define('UPLOAD_URL', BASE_URL . '/uploads/');

// Maximum file size (5MB)
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// Allowed file extensions
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Allowed MIME types
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png', 
    'image/gif',
    'image/webp'
]);

// ============================================
// PAGINATION SETTINGS
// ============================================

define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// ============================================
// SECURITY SETTINGS
// ============================================

define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes in seconds

// ============================================
// FEE CONFIGURATION
// ============================================

define('ADOPTION_FEE_DOG', 500.00);
define('ADOPTION_FEE_CAT', 300.00);
define('ADOPTION_FEE_OTHER', 200.00);
define('RECLAIM_FEE_BASE', 200.00);
define('RECLAIM_FEE_PER_DAY', 50.00);

// ============================================
// TIMEZONE
// ============================================

date_default_timezone_set('Asia/Manila');

// ============================================
// ERROR HANDLING
// ============================================

$isDev = APP_ENV === 'development';
error_reporting($isDev ? E_ALL : 0);
ini_set('display_errors', 0); // Don't display errors directly

ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/error.log');