-- Migration: Add 'Euthanasia' to Medical_Records.Diagnosis_Type ENUM
-- Date: 2026-01-18
-- Description: Adds 'Euthanasia' as a valid diagnosis type

ALTER TABLE Medical_Records 
MODIFY COLUMN Diagnosis_Type ENUM(
    'Checkup', 
    'Vaccination', 
    'Surgery', 
    'Treatment', 
    'Emergency', 
    'Deworming', 
    'Spay/Neuter', 
    'Euthanasia'
) NOT NULL;
