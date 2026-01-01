<?php
/**
 * Environment Variable Loader
 * Loads variables from .env file into PHP environment
 * 
 * @package AnimalShelter
 */

class Env {
    /**
     * Load environment variables from .env file
     * 
     * @param string $path Path to .env file
     * @return bool True if file was loaded
     */
    public static function load($path) {
        if (!file_exists($path)) {
            return false;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Parse KEY=value
            if (strpos($line, '=') === false) {
                continue;
            }
            
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove surrounding quotes if present
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            
            // Only set if not already defined (system env takes priority)
            if (!getenv($key)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
        
        return true;
    }
    
    /**
     * Get environment variable with optional default
     * 
     * @param string $key Variable name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get($key, $default = null) {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Convert string booleans
        $lower = strtolower($value);
        if ($lower === 'true' || $lower === '(true)') return true;
        if ($lower === 'false' || $lower === '(false)') return false;
        if ($lower === 'null' || $lower === '(null)') return null;
        if ($lower === 'empty' || $lower === '(empty)') return '';
        
        return $value;
    }
    
    /**
     * Check if environment variable exists
     * 
     * @param string $key Variable name
     * @return bool
     */
    public static function has($key) {
        return getenv($key) !== false;
    }
    
    /**
     * Get required environment variable (throws if missing)
     * 
     * @param string $key Variable name
     * @return mixed
     * @throws Exception if variable is not set
     */
    public static function require($key) {
        $value = self::get($key);
        
        if ($value === null) {
            throw new Exception("Required environment variable '{$key}' is not set.");
        }
        
        return $value;
    }
}
