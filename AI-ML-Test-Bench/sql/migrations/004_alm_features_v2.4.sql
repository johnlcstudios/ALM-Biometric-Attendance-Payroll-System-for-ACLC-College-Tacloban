-- ALM Features v2.4 Migration
-- Adds faculty_level, hire_date, resignation tracking, and reinstatement columns

USE alm_biometrics;

-- Add faculty_level column to employees table
ALTER TABLE employees 
ADD COLUMN IF NOT EXISTS faculty_level ENUM('SHS', 'College', 'Both', NULL) NULL AFTER department;

-- Add hire_date column to employees table
ALTER TABLE employees 
ADD COLUMN IF NOT EXISTS hire_date DATE NULL AFTER status,
ADD INDEX IF NOT EXISTS idx_hire_date (hire_date);

-- Add resignation tracking columns to resignations table
ALTER TABLE resignations 
ADD COLUMN IF NOT EXISTS declined_by INT NULL,
ADD COLUMN IF NOT EXISTS decline_reason TEXT NULL,
ADD COLUMN IF NOT EXISTS declined_at DATETIME NULL;

-- Add reinstatement tracking columns to employees table
ALTER TABLE employees 
ADD COLUMN IF NOT EXISTS reinstated_at DATETIME NULL,
ADD COLUMN IF NOT EXISTS reinstated_by INT NULL;

-- Update resignations status enum to include 'Declined'
ALTER TABLE resignations 
MODIFY COLUMN status ENUM('Pending', 'Approved', 'Processing', 'Completed', 'Declined') DEFAULT 'Pending';
