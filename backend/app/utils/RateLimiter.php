<?php
/**
 * Rate Limiter Utility
 * Provides rate limiting functionality using file-based storage
 * 
 * @package AnimalShelter
 */

class RateLimiter {
    /**
     * @var string Storage directory for rate limit data
     */
    private static $storageDir = null;
    
    /**
     * Initialize storage directory
     * 
     * @return string Storage directory path
     */
    private static function getStorageDir() {
        if (self::$storageDir === null) {
            self::$storageDir = defined('RATE_LIMIT_STORAGE') 
                ? RATE_LIMIT_STORAGE 
                : (defined('BASE_PATH') ? BASE_PATH . '/logs/rate_limits/' : sys_get_temp_dir() . '/rate_limits/');
        }
        
        // Create directory if it doesn't exist
        if (!is_dir(self::$storageDir)) {
            mkdir(self::$storageDir, 0755, true);
        }
        
        return self::$storageDir;
    }
    
    /**
     * Check and enforce rate limit
     * 
     * @param string $type Type of limit (e.g., 'login', 'api', 'global')
     * @param string $identifier Unique identifier (IP address or user ID)
     * @param int $maxRequests Maximum allowed requests
     * @param int $windowSeconds Time window in seconds
     * @return bool True if request is allowed
     */
    public static function check($type, $identifier, $maxRequests, $windowSeconds) {
        // Skip if rate limiting is disabled
        if (defined('RATE_LIMIT_ENABLED') && !RATE_LIMIT_ENABLED) {
            return true;
        }
        
        $key = self::generateKey($type, $identifier);
        $data = self::getData($key);
        $now = time();
        
        // Clean old entries outside the window
        $data = array_filter($data, function($timestamp) use ($now, $windowSeconds) {
            return ($now - $timestamp) < $windowSeconds;
        });
        
        // Check if limit exceeded
        if (count($data) >= $maxRequests) {
            $retryAfter = min($data) + $windowSeconds - $now;
            self::sendRateLimitResponse($retryAfter, $type);
            return false;
        }
        
        // Add current request timestamp
        $data[] = $now;
        self::saveData($key, $data);
        
        return true;
    }
    
    /**
     * Apply global rate limiting (called on every request)
     * Uses default API limits from config
     */
    public static function checkGlobal() {
        // Skip if rate limiting is disabled
        if (defined('RATE_LIMIT_ENABLED') && !RATE_LIMIT_ENABLED) {
            return true;
        }
        
        $ip = self::getClientIP();
        $maxRequests = defined('RATE_LIMIT_API_MAX') ? RATE_LIMIT_API_MAX : 100;
        $windowSeconds = defined('RATE_LIMIT_API_WINDOW') ? RATE_LIMIT_API_WINDOW : 60;
        
        return self::check('global', $ip, $maxRequests, $windowSeconds);
    }
    
    /**
     * Check login-specific rate limit
     * 
     * @param string|null $identifier Optional identifier (defaults to IP)
     * @return bool True if allowed
     */
    public static function checkLogin($identifier = null) {
        $identifier = $identifier ?? self::getClientIP();
        $maxRequests = defined('RATE_LIMIT_LOGIN_MAX') ? RATE_LIMIT_LOGIN_MAX : 10;
        $windowSeconds = defined('RATE_LIMIT_LOGIN_WINDOW') ? RATE_LIMIT_LOGIN_WINDOW : 60;
        
        return self::check('login', $identifier, $maxRequests, $windowSeconds);
    }
    
    /**
     * Get remaining attempts for a given limit
     * 
     * @param string $type Type of limit
     * @param string $identifier Unique identifier
     * @param int $maxRequests Maximum allowed requests
     * @param int $windowSeconds Time window in seconds
     * @return int Remaining attempts
     */
    public static function getRemaining($type, $identifier, $maxRequests, $windowSeconds) {
        $key = self::generateKey($type, $identifier);
        $data = self::getData($key);
        $now = time();
        
        // Clean old entries
        $data = array_filter($data, function($timestamp) use ($now, $windowSeconds) {
            return ($now - $timestamp) < $windowSeconds;
        });
        
        return max(0, $maxRequests - count($data));
    }
    
    /**
     * Reset rate limit for a specific identifier
     * 
     * @param string $type Type of limit
     * @param string $identifier Unique identifier
     */
    public static function reset($type, $identifier) {
        $key = self::generateKey($type, $identifier);
        $file = self::getStorageDir() . $key . '.json';
        
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    /**
     * Clear all expired rate limit data (cleanup task)
     * 
     * @param int $maxAge Maximum age in seconds (default 1 hour)
     */
    public static function cleanup($maxAge = 3600) {
        $dir = self::getStorageDir();
        $now = time();
        
        if (!is_dir($dir)) {
            return;
        }
        
        $files = glob($dir . '*.json');
        
        foreach ($files as $file) {
            if (($now - filemtime($file)) > $maxAge) {
                unlink($file);
            }
        }
    }
    
    /**
     * Generate a safe key for storage
     * 
     * @param string $type Limit type
     * @param string $identifier Identifier
     * @return string Safe key string
     */
    private static function generateKey($type, $identifier) {
        // Sanitize identifier (remove special characters, hash if too long)
        $safeIdentifier = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $identifier);
        
        if (strlen($safeIdentifier) > 32) {
            $safeIdentifier = md5($identifier);
        }
        
        return $type . '_' . $safeIdentifier;
    }
    
    /**
     * Get rate limit data from storage
     * 
     * @param string $key Storage key
     * @return array Array of timestamps
     */
    private static function getData($key) {
        $file = self::getStorageDir() . $key . '.json';
        
        if (!file_exists($file)) {
            return [];
        }
        
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        return is_array($data) ? $data : [];
    }
    
    /**
     * Save rate limit data to storage
     * 
     * @param string $key Storage key
     * @param array $data Array of timestamps
     */
    private static function saveData($key, $data) {
        $file = self::getStorageDir() . $key . '.json';
        file_put_contents($file, json_encode(array_values($data)), LOCK_EX);
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP
     */
    private static function getClientIP() {
        // Check for forwarded IP (behind proxy/load balancer)
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Standard proxy header
            'HTTP_X_REAL_IP',            // Nginx proxy
            'HTTP_CLIENT_IP',            // General
            'REMOTE_ADDR'                // Direct connection
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                // X-Forwarded-For can contain multiple IPs, take the first
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Send rate limit exceeded response
     * 
     * @param int $retryAfter Seconds until limit resets
     * @param string $type Type of limit that was exceeded
     */
    private static function sendRateLimitResponse($retryAfter, $type) {
        // Set rate limit headers
        header('Retry-After: ' . max(1, $retryAfter));
        header('X-RateLimit-Reset: ' . (time() + $retryAfter));
        
        // Use Response class if available
        if (class_exists('Response')) {
            Response::error(
                "Rate limit exceeded. Please try again in {$retryAfter} seconds.",
                429
            );
        } else {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Rate limit exceeded. Please try again in {$retryAfter} seconds.",
                'retry_after' => $retryAfter
            ]);
            exit;
        }
    }
}
