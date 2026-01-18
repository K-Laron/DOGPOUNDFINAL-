-- =====================================================
-- Migration: 003_performance_indexes.sql
-- Description: Add performance indexes based on query patterns
-- Date: 2026-01-16
-- =====================================================

-- Add username index for login queries
-- Query pattern: WHERE (Email = :identifier OR Username = :identifier)
CREATE INDEX IF NOT EXISTS idx_users_username ON Users(Username);

-- Composite index for common animal filters
-- Query pattern: WHERE Is_Deleted = FALSE AND Current_Status = 'Available'
CREATE INDEX IF NOT EXISTS idx_animals_deleted_status ON Animals(Is_Deleted, Current_Status);

-- Composite index for adoption status checks on specific animals
-- Query pattern: WHERE AnimalID = :id AND Status IN ('Pending', 'Approved')
CREATE INDEX IF NOT EXISTS idx_adoption_animal_status ON Adoption_Requests(AnimalID, Status);

-- Composite index for user's invoices by status
-- Query pattern: WHERE Payer_UserID = :user_id AND Status = 'Unpaid'
CREATE INDEX IF NOT EXISTS idx_invoices_payer_status ON Invoices(Payer_UserID, Status, Is_Deleted);

-- Composite index for user activity lookups by type
-- Query pattern: WHERE UserID = :user_id AND Action_Type = 'LOGIN'
CREATE INDEX IF NOT EXISTS idx_activity_user_type ON Activity_Logs(UserID, Action_Type);

-- Index for medical records by animal and date
-- Query pattern: WHERE AnimalID = :id ORDER BY Date_Performed DESC
CREATE INDEX IF NOT EXISTS idx_medical_animal_date ON Medical_Records(AnimalID, Date_Performed DESC);

-- Index for feeding records by animal and time
-- Query pattern: WHERE AnimalID = :id ORDER BY Feeding_Time DESC
CREATE INDEX IF NOT EXISTS idx_feeding_animal_time ON Feeding_Records(AnimalID, Feeding_Time DESC);

-- Composite index for impound records
CREATE INDEX IF NOT EXISTS idx_impound_animal ON Impound_Records(AnimalID);

-- =====================================================
-- VERIFY INDEXES (run this to check what indexes exist)
-- =====================================================
-- SHOW INDEX FROM Users;
-- SHOW INDEX FROM Animals;
-- SHOW INDEX FROM Adoption_Requests;
-- SHOW INDEX FROM Invoices;
-- SHOW INDEX FROM Activity_Logs;
-- SHOW INDEX FROM Medical_Records;
-- SHOW INDEX FROM Feeding_Records;
