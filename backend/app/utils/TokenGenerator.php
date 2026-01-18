<?php
/**
 * Token Generator Utility
 * Generates secure random tokens for password reset and other purposes
 * 
 * @package AnimalShelter
 */

class TokenGenerator {
    /**
     * Default token length in bytes (32 bytes = 64 hex characters)
     */
    private const DEFAULT_LENGTH = 32;
    
    /**
     * Default token expiry in seconds (1 hour)
     */
    private const DEFAULT_EXPIRY = 3600;
    
    /**
     * Generate a cryptographically secure random token
     * 
     * @param int $length Length in bytes (will be doubled in hex output)
     * @return string Hex-encoded random token
     */
    public static function generate(int $length = self::DEFAULT_LENGTH): string {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Generate a URL-safe token using base64 encoding
     * 
     * @param int $length Length in bytes
     * @return string URL-safe base64 encoded token
     */
    public static function generateUrlSafe(int $length = self::DEFAULT_LENGTH): string {
        $bytes = random_bytes($length);
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
    
    /**
     * Generate a numeric token (PIN code style)
     * 
     * @param int $digits Number of digits
     * @return string Numeric token
     */
    public static function generateNumeric(int $digits = 6): string {
        $min = pow(10, $digits - 1);
        $max = pow(10, $digits) - 1;
        return (string)random_int($min, $max);
    }
    
    /**
     * Hash a token for secure storage
     * Uses SHA-256 for consistent length and fast comparison
     * 
     * @param string $token Plain token
     * @return string Hashed token
     */
    public static function hash(string $token): string {
        return hash('sha256', $token);
    }
    
    /**
     * Verify a token against its hash using timing-safe comparison
     * 
     * @param string $token Plain token to verify
     * @param string $hashedToken Stored hashed token
     * @return bool True if token matches
     */
    public static function verify(string $token, string $hashedToken): bool {
        return hash_equals($hashedToken, self::hash($token));
    }
    
    /**
     * Calculate expiry datetime
     * 
     * @param int $seconds Seconds from now
     * @return string MySQL datetime format
     */
    public static function expiresAt(int $seconds = self::DEFAULT_EXPIRY): string {
        return date('Y-m-d H:i:s', time() + $seconds);
    }
    
    /**
     * Check if a datetime has expired
     * 
     * @param string $expiresAt MySQL datetime string
     * @return bool True if expired
     */
    public static function isExpired(string $expiresAt): bool {
        return strtotime($expiresAt) < time();
    }
    
    /**
     * Generate a password reset token and store it in database
     * 
     * @param PDO $db Database connection
     * @param int $userId User ID
     * @param int $expirySeconds Token expiry in seconds (default 1 hour)
     * @return string The plain token (to be sent to user)
     */
    public static function createPasswordResetToken(PDO $db, int $userId, int $expirySeconds = self::DEFAULT_EXPIRY): string {
        // Generate a new token
        $plainToken = self::generate();
        $hashedToken = self::hash($plainToken);
        $expiresAt = self::expiresAt($expirySeconds);
        
        // Invalidate any existing tokens for this user
        $stmt = $db->prepare("UPDATE Password_Reset_Tokens SET Used = TRUE WHERE UserID = :user_id AND Used = FALSE");
        $stmt->execute(['user_id' => $userId]);
        
        // Insert new token
        $stmt = $db->prepare("
            INSERT INTO Password_Reset_Tokens (UserID, Token, Expires_At, Used)
            VALUES (:user_id, :token, :expires_at, FALSE)
        ");
        $stmt->execute([
            'user_id' => $userId,
            'token' => $hashedToken,
            'expires_at' => $expiresAt
        ]);
        
        return $plainToken;
    }
    
    /**
     * Validate a password reset token
     * 
     * @param PDO $db Database connection
     * @param string $plainToken The token to validate
     * @return array|false Token record if valid, false otherwise
     */
    public static function validatePasswordResetToken(PDO $db, string $plainToken) {
        $hashedToken = self::hash($plainToken);
        
        $stmt = $db->prepare("
            SELECT prt.*, u.Email, u.UserID, u.FirstName
            FROM Password_Reset_Tokens prt
            JOIN Users u ON prt.UserID = u.UserID
            WHERE prt.Token = :token
            AND prt.Used = FALSE
            AND prt.Expires_At > NOW()
            AND u.Is_Deleted = FALSE
        ");
        $stmt->execute(['token' => $hashedToken]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mark a password reset token as used
     * 
     * @param PDO $db Database connection
     * @param int $tokenId Token ID
     * @return bool Success
     */
    public static function markTokenUsed(PDO $db, int $tokenId): bool {
        $stmt = $db->prepare("UPDATE Password_Reset_Tokens SET Used = TRUE WHERE TokenID = :id");
        return $stmt->execute(['id' => $tokenId]);
    }
    
    /**
     * Clean up expired tokens (for maintenance/cron)
     * 
     * @param PDO $db Database connection
     * @return int Number of tokens deleted
     */
    public static function cleanupExpiredTokens(PDO $db): int {
        $stmt = $db->prepare("DELETE FROM Password_Reset_Tokens WHERE Expires_At < NOW() OR Used = TRUE");
        $stmt->execute();
        return $stmt->rowCount();
    }
}
