-- =====================================================
-- MIGRATION 002: Token Version for JWT Invalidation
-- Description: Add Token_Version column to Users for logout functionality
-- Date: 2026-01-16
-- =====================================================

-- Add Token_Version column to Users table
-- This allows server-side invalidation of JWT tokens
ALTER TABLE Users ADD COLUMN Token_Version INT DEFAULT 1 AFTER Account_Status;

-- When a user logs out or changes password, increment this value
-- All tokens with older version numbers become invalid
