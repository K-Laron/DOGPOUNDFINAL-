<?php
/**
 * Simple JWT Handler
 */

class JWT {
    
    /**
     * Generate JWT token
     */
    public static function generate($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_EXPIRY;
        $payload = json_encode($payload);
        
        $base64Header = self::base64UrlEncode($header);
        $base64Payload = self::base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", JWT_SECRET, true);
        $base64Signature = self::base64UrlEncode($signature);
        
        return "$base64Header.$base64Payload.$base64Signature";
    }

    /**
     * Verify and decode JWT token
     */
    public static function verify($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "$base64Header.$base64Payload", JWT_SECRET, true)
        );
        
        if ($signature !== $base64Signature) {
            return false;
        }
        
        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        
        if ($payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}