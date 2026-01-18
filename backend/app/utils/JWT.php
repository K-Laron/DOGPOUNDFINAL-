<?php
/**
 * JWT (JSON Web Token) Handler Class
 * 
 * @package AnimalShelter
 */

class JWT {
    
    /**
     * @var PDO|null Database connection for token version verification
     */
    private static $db = null;
    
    /**
     * Set database connection for token version verification
     * 
     * @param PDO $db Database connection
     */
    public static function setDatabase(PDO $db): void {
        self::$db = $db;
    }
    
    /**
     * Generate JWT token
     * 
     * @param array $payload Token payload data
     * @param int|null $expiry Custom expiry time in seconds
     * @return string Generated JWT token
     */
    public static function generate($payload, $expiry = null) {
        // Create header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        // Add standard claims to payload
        $payload['iat'] = time();                              // Issued at
        $payload['exp'] = time() + ($expiry ?? JWT_EXPIRY);    // Expiration
        $payload['jti'] = bin2hex(random_bytes(16));           // Unique token ID
        
        // Encode header and payload
        $base64Header = self::base64UrlEncode(json_encode($header));
        $base64Payload = self::base64UrlEncode(json_encode($payload));
        
        // Create signature
        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", JWT_SECRET, true);
        $base64Signature = self::base64UrlEncode($signature);
        
        // Return complete token
        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }

    /**
     * Verify and decode JWT token
     * 
     * This method validates the token's signature and expiration.
     * It ensures the token hasn't been tampered with and is still valid.
     * 
     * @param string $token JWT token to verify
     * @return array|false Decoded payload or false if invalid
     */
    public static function verify($token) {
        // JWTs have 3 parts separated by dots: header.payload.signature
        $parts = explode('.', $token);
        
        // Invalid format if not exactly 3 parts
        if (count($parts) !== 3) {
            return false;
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        // ====================================================
        // SIGNATURE VERIFICATION
        // Recreate the signature and compare with the provided one
        // This ensures the token hasn't been tampered with
        // ====================================================
        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "{$base64Header}.{$base64Payload}", JWT_SECRET, true)
        );
        
        // Use timing-safe comparison to prevent timing attacks
        // (attackers can't guess the signature by measuring response time)
        if (!hash_equals($expectedSignature, $base64Signature)) {
            return false;
        }
        
        // ====================================================
        // PAYLOAD EXTRACTION
        // Decode the base64 payload to get the claims
        // ====================================================
        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        
        if (!$payload) {
            return false;
        }
        
        // ====================================================
        // EXPIRATION CHECK
        // Ensure the token hasn't expired
        // ====================================================
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        // ====================================================
        // NOT BEFORE CHECK (optional claim)
        // Token isn't valid until this timestamp
        // ====================================================
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            return false;
        }
        
        // ====================================================
        // TOKEN VERSION CHECK
        // Verify token version matches user's current version
        // This allows server-side token invalidation on logout
        // ====================================================
        if (self::$db && isset($payload['user_id']) && isset($payload['token_version'])) {
            if (!self::verifyTokenVersion($payload['user_id'], $payload['token_version'])) {
                return false;
            }
        }
        
        // Token is valid - return the payload data
        return $payload;
    }
    
    /**
     * Verify token version against database
     * 
     * @param int $userId User ID
     * @param int $tokenVersion Token version from payload
     * @return bool True if version matches
     */
    private static function verifyTokenVersion(int $userId, int $tokenVersion): bool {
        if (!self::$db) {
            return true; // Skip check if no database connection
        }
        
        try {
            $stmt = self::$db->prepare("SELECT Token_Version FROM Users WHERE UserID = :user_id AND Is_Deleted = FALSE");
            $stmt->execute(['user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false;
            }
            
            // Token version must match current user version
            return (int)$user['Token_Version'] === (int)$tokenVersion;
        } catch (PDOException $e) {
            error_log("JWT token version check failed: " . $e->getMessage());
            return true; // Fail open on database errors to prevent lockout
        }
    }
    
    /**
     * Increment user's token version (invalidates all existing tokens)
     * 
     * @param PDO $db Database connection
     * @param int $userId User ID
     * @return bool Success
     */
    public static function incrementTokenVersion(PDO $db, int $userId): bool {
        try {
            $stmt = $db->prepare("UPDATE Users SET Token_Version = Token_Version + 1 WHERE UserID = :user_id");
            return $stmt->execute(['user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("Failed to increment token version: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's current token version
     * 
     * @param PDO $db Database connection
     * @param int $userId User ID
     * @return int|null Token version or null if not found
     */
    public static function getTokenVersion(PDO $db, int $userId): ?int {
        try {
            $stmt = $db->prepare("SELECT Token_Version FROM Users WHERE UserID = :user_id AND Is_Deleted = FALSE");
            $stmt->execute(['user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user ? (int)$user['Token_Version'] : null;
        } catch (PDOException $e) {
            error_log("Failed to get token version: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Decode token without verification (for debugging)
     * WARNING: Do not use for authentication!
     * 
     * @param string $token JWT token to decode
     * @return array|null Decoded payload or null if invalid format
     */
    public static function decode($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        return json_decode(self::base64UrlDecode($parts[1]), true);
    }

    /**
     * Generate refresh token
     * 
     * @param int $userId User ID
     * @param int|null $tokenVersion Token version (fetched from DB if not provided)
     * @return string Refresh token
     */
    public static function generateRefreshToken($userId, $tokenVersion = null) {
        $payload = [
            'user_id' => $userId,
            'type' => 'refresh'
        ];
        
        // Include token version if available
        if ($tokenVersion !== null) {
            $payload['token_version'] = $tokenVersion;
        }
        
        return self::generate($payload, JWT_REFRESH_EXPIRY);
    }
    
    /**
     * Generate access token with token version for invalidation support
     * 
     * @param array $userData User data to include in payload
     * @param int $tokenVersion User's current token version
     * @param int|null $expiry Custom expiry time in seconds
     * @return string JWT token
     */
    public static function generateWithVersion(array $userData, int $tokenVersion, $expiry = null): string {
        $userData['token_version'] = $tokenVersion;
        return self::generate($userData, $expiry);
    }

    /**
     * Check if token is expired
     * 
     * @param string $token JWT token
     * @return bool True if expired
     */
    public static function isExpired($token) {
        $payload = self::decode($token);
        
        if (!$payload || !isset($payload['exp'])) {
            return true;
        }
        
        return $payload['exp'] < time();
    }

    /**
     * Get time until token expires
     * 
     * @param string $token JWT token
     * @return int|null Seconds until expiration or null if invalid
     */
    public static function getExpiresIn($token) {
        $payload = self::decode($token);
        
        if (!$payload || !isset($payload['exp'])) {
            return null;
        }
        
        $expiresIn = $payload['exp'] - time();
        return $expiresIn > 0 ? $expiresIn : 0;
    }

    /**
     * Base64 URL encode
     * 
     * @param string $data Data to encode
     * @return string Encoded string
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     * 
     * @param string $data Data to decode
     * @return string Decoded string
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}