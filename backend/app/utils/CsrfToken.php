<?php
/**
 * CSRF Token Utility
 * Generates and validates CSRF tokens to prevent cross-site request forgery attacks
 * 
 * @package AnimalShelter
 */

class CsrfToken {
    /**
     * Token length in bytes (32 bytes = 64 hex characters)
     */
    private const TOKEN_LENGTH = 32;
    
    /**
     * Token expiry in seconds (2 hours)
     */
    private const TOKEN_EXPIRY = 7200;
    
    /**
     * Header name for CSRF token
     */
    public const HEADER_NAME = 'X-CSRF-Token';
    
    /**
     * Generate a new CSRF token
     * 
     * @return string The generated token
     */
    public static function generate(): string {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }
    
    /**
     * Create a token with embedded expiry time
     * Format: expiry_timestamp.token
     * 
     * @param int $expirySeconds Custom expiry in seconds (optional)
     * @return string Token with embedded expiry
     */
    public static function createWithExpiry(int $expirySeconds = self::TOKEN_EXPIRY): string {
        $expiry = time() + $expirySeconds;
        $token = self::generate();
        $payload = $expiry . '.' . $token;
        
        // Sign the payload to prevent tampering
        $signature = self::sign($payload);
        
        return base64_encode($payload . '.' . $signature);
    }
    
    /**
     * Validate a CSRF token
     * 
     * @param string $token The token to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate(string $token): bool {
        if (empty($token)) {
            return false;
        }
        
        try {
            $decoded = base64_decode($token, true);
            if ($decoded === false) {
                return false;
            }
            
            $parts = explode('.', $decoded);
            if (count($parts) !== 3) {
                return false;
            }
            
            [$expiry, $tokenValue, $signature] = $parts;
            
            // Verify signature
            $payload = $expiry . '.' . $tokenValue;
            if (!hash_equals($signature, self::sign($payload))) {
                return false;
            }
            
            // Check expiry
            if (!is_numeric($expiry) || (int)$expiry < time()) {
                return false;
            }
            
            // Validate token format (should be 64 hex characters)
            if (!preg_match('/^[a-f0-9]{64}$/i', $tokenValue)) {
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Sign a payload using HMAC
     * 
     * @param string $payload The payload to sign
     * @return string The signature
     */
    private static function sign(string $payload): string {
        $secret = self::getSecret();
        return hash_hmac('sha256', $payload, $secret);
    }
    
    /**
     * Get the signing secret
     * Uses JWT_SECRET from environment or generates a fallback
     * 
     * @return string The secret key
     */
    private static function getSecret(): string {
        // Use the JWT secret if available, or a dedicated CSRF secret
        $secret = getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? null);
        
        if (!$secret) {
            // Fallback to a file-based secret for development
            $secretFile = dirname(__DIR__, 2) . '/.csrf_secret';
            if (file_exists($secretFile)) {
                $secret = trim(file_get_contents($secretFile));
            } else {
                // Generate and store a new secret
                $secret = bin2hex(random_bytes(32));
                file_put_contents($secretFile, $secret);
            }
        }
        
        return $secret;
    }
    
    /**
     * Get token from request headers
     * 
     * @return string|null The token or null if not found
     */
    public static function getFromRequest(): ?string {
        // Check header (preferred)
        $headers = getallheaders();
        $headerName = self::HEADER_NAME;
        
        // Headers might be case-insensitive
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, $headerName) === 0) {
                return $value;
            }
        }
        
        // Also check for HTTP_X_CSRF_TOKEN (CGI/FastCGI)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        
        return null;
    }
    
    /**
     * Check if the request method requires CSRF protection
     * 
     * @param string $method HTTP method
     * @return bool True if CSRF protection is required
     */
    public static function requiresProtection(string $method): bool {
        // Safe methods (idempotent) don't need CSRF protection
        $safeMethods = ['GET', 'HEAD', 'OPTIONS'];
        return !in_array(strtoupper($method), $safeMethods, true);
    }
}
