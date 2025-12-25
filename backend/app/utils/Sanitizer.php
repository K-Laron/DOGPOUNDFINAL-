<?php
/**
 * Input Sanitizer Utility
 * Provides comprehensive input sanitization to prevent XSS and other injection attacks
 * 
 * @package AnimalShelter
 */

class Sanitizer {
    /**
     * Sanitize a string value
     * Removes control characters, trims whitespace, and escapes HTML entities
     * 
     * @param mixed $value Value to sanitize
     * @param bool $allowHtml If true, only strips dangerous tags instead of all HTML
     * @return string Sanitized string
     */
    public static function string($value, $allowHtml = false) {
        if ($value === null) {
            return '';
        }
        
        if (!is_string($value)) {
            $value = (string)$value;
        }
        
        // Remove null bytes and other control characters (except newlines and tabs)
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        // Trim whitespace
        $value = trim($value);
        
        if ($allowHtml) {
            // Only strip dangerous tags and attributes
            return self::stripDangerousTags($value);
        }
        
        // Escape all HTML entities
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitize an email address
     * 
     * @param mixed $value Email to sanitize
     * @return string|null Sanitized email or null if invalid
     */
    public static function email($value) {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Basic string sanitization
        $value = self::string($value);
        
        // Remove any whitespace
        $value = preg_replace('/\s+/', '', $value);
        
        // Lowercase
        $value = strtolower($value);
        
        // Validate and sanitize using filter
        $sanitized = filter_var($value, FILTER_SANITIZE_EMAIL);
        
        // Return only if valid
        if (filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
            return $sanitized;
        }
        
        return null;
    }
    
    /**
     * Sanitize an integer value
     * 
     * @param mixed $value Value to sanitize
     * @param int|null $default Default value if sanitization fails
     * @return int|null Sanitized integer
     */
    public static function integer($value, $default = null) {
        if ($value === null || $value === '') {
            return $default;
        }
        
        $sanitized = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        
        if ($sanitized !== false && $sanitized !== '') {
            return (int)$sanitized;
        }
        
        return $default;
    }
    
    /**
     * Sanitize a float/decimal value
     * 
     * @param mixed $value Value to sanitize
     * @param float|null $default Default value if sanitization fails
     * @return float|null Sanitized float
     */
    public static function float($value, $default = null) {
        if ($value === null || $value === '') {
            return $default;
        }
        
        $sanitized = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        
        if ($sanitized !== false && $sanitized !== '') {
            return (float)$sanitized;
        }
        
        return $default;
    }
    
    /**
     * Sanitize a boolean value
     * 
     * @param mixed $value Value to sanitize
     * @param bool $default Default value
     * @return bool Sanitized boolean
     */
    public static function boolean($value, $default = false) {
        if ($value === null) {
            return $default;
        }
        
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
    
    /**
     * Sanitize a URL
     * 
     * @param mixed $value URL to sanitize
     * @return string|null Sanitized URL or null if invalid
     */
    public static function url($value) {
        if ($value === null || $value === '') {
            return null;
        }
        
        $sanitized = filter_var(trim($value), FILTER_SANITIZE_URL);
        
        if (filter_var($sanitized, FILTER_VALIDATE_URL)) {
            return $sanitized;
        }
        
        return null;
    }
    
    /**
     * Strip all HTML tags from a string
     * 
     * @param mixed $value Value to strip
     * @param string $allowedTags Optional allowed tags (e.g., '<p><br>')
     * @return string Stripped string
     */
    public static function stripTags($value, $allowedTags = '') {
        if ($value === null) {
            return '';
        }
        
        return strip_tags((string)$value, $allowedTags);
    }
    
    /**
     * Strip only dangerous HTML tags and attributes
     * 
     * @param string $value HTML string
     * @return string Sanitized HTML
     */
    public static function stripDangerousTags($value) {
        // Remove script tags and their contents
        $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);
        
        // Remove style tags and their contents
        $value = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $value);
        
        // Remove on* event handlers
        $value = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $value);
        $value = preg_replace('/\bon\w+\s*=\s*[^\s>]*/i', '', $value);
        
        // Remove javascript: URLs
        $value = preg_replace('/javascript\s*:/i', '', $value);
        
        // Remove data: URLs (can contain scripts)
        $value = preg_replace('/data\s*:[^"\'>\s]*/i', '', $value);
        
        // Remove vbscript: URLs
        $value = preg_replace('/vbscript\s*:/i', '', $value);
        
        return $value;
    }
    
    /**
     * Sanitize a filename (prevent path traversal)
     * 
     * @param string $filename Filename to sanitize
     * @return string Safe filename
     */
    public static function filename($filename) {
        if ($filename === null) {
            return '';
        }
        
        // Remove path components
        $filename = basename((string)$filename);
        
        // Remove null bytes
        $filename = str_replace("\0", '', $filename);
        
        // Remove directory traversal attempts
        $filename = str_replace(['..', '/', '\\'], '', $filename);
        
        // Replace potentially dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 250 - strlen($ext)) . '.' . $ext;
        }
        
        return $filename;
    }
    
    /**
     * Sanitize a value for safe database storage
     * Note: This is supplementary to PDO prepared statements, not a replacement
     * 
     * @param mixed $value Value to sanitize
     * @return mixed Sanitized value
     */
    public static function forDatabase($value) {
        if ($value === null) {
            return null;
        }
        
        if (is_string($value)) {
            // Remove null bytes
            $value = str_replace("\0", '', $value);
            
            // Trim
            $value = trim($value);
            
            return $value;
        }
        
        return $value;
    }
    
    /**
     * Sanitize an array of values recursively
     * 
     * @param array $data Array to sanitize
     * @param array $options Sanitization options per field
     * @return array Sanitized array
     */
    public static function array($data, $options = []) {
        if (!is_array($data)) {
            return [];
        }
        
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            // Sanitize key
            $safeKey = self::string($key);
            
            if (is_array($value)) {
                // Recursively sanitize nested arrays
                $sanitized[$safeKey] = self::array($value, $options[$key] ?? []);
            } elseif (is_string($value)) {
                // Apply field-specific sanitization if defined
                if (isset($options[$key])) {
                    $sanitized[$safeKey] = self::applyOption($value, $options[$key]);
                } else {
                    // Default string sanitization
                    $sanitized[$safeKey] = self::string($value);
                }
            } elseif (is_numeric($value)) {
                // Keep numeric values as-is
                $sanitized[$safeKey] = $value;
            } elseif (is_bool($value)) {
                $sanitized[$safeKey] = $value;
            } elseif ($value === null) {
                $sanitized[$safeKey] = null;
            } else {
                // Convert other types to string and sanitize
                $sanitized[$safeKey] = self::string((string)$value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize entire request data
     * This is the main method to call for automatic sanitization
     * 
     * @param array $data Request data
     * @return array Sanitized request data
     */
    public static function request($data) {
        if (!is_array($data)) {
            return [];
        }
        
        // Fields that should preserve raw values (for password hashing)
        $preserveFields = ['password', 'current_password', 'new_password', 'password_confirmation'];
        
        // Fields that are emails
        $emailFields = ['email'];
        
        // Fields that are integers
        $integerFields = ['id', 'page', 'per_page', 'limit', 'offset', 'user_id', 'animal_id'];
        
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            
            // Preserve password fields (they will be hashed, not displayed)
            if (in_array($lowerKey, $preserveFields)) {
                $sanitized[$key] = $value;
                continue;
            }
            
            if (is_array($value)) {
                $sanitized[$key] = self::array($value);
            } elseif (in_array($lowerKey, $emailFields) && is_string($value)) {
                $sanitized[$key] = self::email($value) ?? self::string($value);
            } elseif (in_array($lowerKey, $integerFields) || (is_string($key) && str_ends_with(strtolower($key), '_id'))) {
                $sanitized[$key] = self::integer($value, $value);
            } elseif (is_string($value)) {
                $sanitized[$key] = self::string($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Apply a specific sanitization option
     * 
     * @param mixed $value Value to sanitize
     * @param string $option Sanitization type
     * @return mixed Sanitized value
     */
    private static function applyOption($value, $option) {
        switch ($option) {
            case 'email':
                return self::email($value);
            case 'integer':
            case 'int':
                return self::integer($value);
            case 'float':
            case 'decimal':
                return self::float($value);
            case 'boolean':
            case 'bool':
                return self::boolean($value);
            case 'url':
                return self::url($value);
            case 'filename':
                return self::filename($value);
            case 'strip_tags':
                return self::stripTags($value);
            case 'html':
                return self::string($value, true);
            case 'raw':
                return $value;
            default:
                return self::string($value);
        }
    }
}
