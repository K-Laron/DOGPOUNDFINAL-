<?php

/**
 * PHPUnit Test Bootstrap
 * Sets up the testing environment
 * 
 * @package AnimalShelter\Tests
 */

// Define paths for testing context
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('PHPUNIT_RUNNING', true);

// Load environment variables
require_once APP_PATH . '/utils/Env.php';
Env::load(dirname(BASE_PATH) . '/.env');

// Load configuration
require_once APP_PATH . '/config/config.php';

// Autoloader for application classes
spl_autoload_register(function ($className) {
    $directories = [
        APP_PATH . '/models/',
        APP_PATH . '/controllers/',
        APP_PATH . '/middleware/',
        APP_PATH . '/utils/',
    ];

    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Suppress error display during testing
ini_set('display_errors', 0);
error_reporting(E_ALL);
