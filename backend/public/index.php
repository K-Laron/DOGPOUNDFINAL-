<?php
/**
 * Animal Shelter Management System
 * Single Entry Point - All requests flow through here
 * 
 * @author Your Name
 * @version 1.0.0
 */

// Prevent direct access via CLI without proper setup
if (php_sapi_name() === 'cli') {
    die("This file cannot be run from CLI directly.\n");
}

// Handle PHP built-in server (serve static files directly)
if (php_sapi_name() === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', __DIR__);

// Load the application bootstrap
require_once APP_PATH . '/bootstrap.php';

// Initialize and run the application
try {
    $app = new App();
    $app->run();
} catch (Throwable $e) {
    // Use centralized error handler to log and respond safely
    require_once APP_PATH . '/utils/ErrorHandler.php';
    ErrorHandler::handle($e);
}