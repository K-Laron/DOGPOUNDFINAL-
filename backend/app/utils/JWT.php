<?php
/**
 * JWT (JSON Web Token) Handler Class
 * 
 * @package AnimalShelter
 */

class JWT {
    
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
     * @param string $token JWT token to verify
     * @return array|false Decoded payload or false if invalid
     */
    public static function verify($token) {
        // Split token into parts
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        // Verify signature
        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "{$base64Header}.{$base64Payload}", JWT_SECRET, true)
        );
        
        // Use timing-safe comparison
        if (!hash_equals($expectedSignature, $base64Signature)) {
            return false;
        }
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        
        if (!$payload) {
            return false;
        }
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        // Check not before (if set)
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            return false;
        }
        
        return $payload;
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
     * @return string Refresh token
     */
    public static function generateRefreshToken($userId) {
        return self::generate(
            [
                'user_id' => $userId,
                'type' => 'refresh'
            ],
            JWT_REFRESH_EXPIRY
        );
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