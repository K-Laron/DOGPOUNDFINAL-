-- =====================================================
-- MIGRATION 001: Password Reset Tokens
-- Description: Add table for password reset functionality
-- Date: 2026-01-16
-- =====================================================

-- Password Reset Tokens Table
CREATE TABLE IF NOT EXISTS Password_Reset_Tokens (
    TokenID INT PRIMARY KEY AUTO_INCREMENT,
    UserID INT NOT NULL,
    Token VARCHAR(64) NOT NULL UNIQUE,
    Expires_At DATETIME NOT NULL,
    Used BOOLEAN DEFAULT FALSE,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Indexes for performance
CREATE INDEX idx_password_reset_token ON Password_Reset_Tokens(Token);
CREATE INDEX idx_password_reset_expires ON Password_Reset_Tokens(Expires_At);
CREATE INDEX idx_password_reset_user ON Password_Reset_Tokens(UserID);

-- Cleanup old/expired tokens (run periodically via cron or manually)
-- DELETE FROM Password_Reset_Tokens WHERE Expires_At < NOW() OR Used = TRUE;
