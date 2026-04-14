-- ALM Features v2.4 Migration
-- Adds faculty_level, hire_date, resignation tracking, and reinstatement columns

USE alm_biometrics;

-- Add faculty_level column to employees table (if not exists)
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'alm_biometrics' 
    AND TABLE_NAME = 'employees' 
    AND COLUMN_NAME = 'faculty_level'
);

SET @sql = IF(
    @column_exists = 0,
    'ALTER TABLE employees ADD COLUMN faculty_level ENUM(''SHS'', ''College'', ''Both'') NULL AFTER department',
    'SELECT ''Column faculty_level already exists'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add hire_date column to employees table (if not exists)
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'alm_biometrics' 
    AND TABLE_NAME = 'employees' 
    AND COLUMN_NAME = 'hire_date'
);

SET @sql = IF(
    @column_exists = 0,
    'ALTER TABLE employees ADD COLUMN hire_date DATE NULL AFTER status, ADD INDEX idx_hire_date (hire_date)',
    'SELECT ''Column hire_date already exists'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add resignation tracking columns to resignations table (if not exists)
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'alm_biometrics' 
    AND TABLE_NAME = 'resignations' 
    AND COLUMN_NAME = 'declined_by'
);

SET @sql = IF(
    @column_exists = 0,
    'ALTER TABLE resignations ADD COLUMN declined_by INT NULL, ADD COLUMN decline_reason TEXT NULL, ADD COLUMN declined_at DATETIME NULL',
    'SELECT ''Resignation tracking columns already exist'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add reinstatement tracking columns to employees table (if not exists)
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'alm_biometrics' 
    AND TABLE_NAME = 'employees' 
    AND COLUMN_NAME = 'reinstated_at'
);

SET @sql = IF(
    @column_exists = 0,
    'ALTER TABLE employees ADD COLUMN reinstated_at DATETIME NULL, ADD COLUMN reinstated_by INT NULL',
    'SELECT ''Reinstatement columns already exist'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update resignations status enum to include 'Declined' (if not already present)
SET @enum_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'alm_biometrics' 
    AND TABLE_NAME = 'resignations' 
    AND COLUMN_NAME = 'status'
    AND COLUMN_TYPE NOT LIKE '%Declined%'
);

SET @sql = IF(
    @enum_exists > 0,
    'ALTER TABLE resignations MODIFY COLUMN status ENUM(''Pending'', ''Approved'', ''Processing'', ''Completed'', ''Declined'') DEFAULT ''Pending''',
    'SELECT ''Status enum already includes Declined'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
