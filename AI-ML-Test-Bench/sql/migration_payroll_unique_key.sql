-- Migration: Add UNIQUE KEY to payroll table for safe re-runs
-- Run this on existing databases to enable INSERT ON DUPLICATE KEY UPDATE
-- Note: If the old unique key uq_payroll_company_emp_period exists, it will be replaced.

-- Drop the old unique key if it exists
SET @old_key_exists = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'payroll'
    AND index_name = 'uq_payroll_company_emp_period'
);
SET @sql = IF(@old_key_exists > 0,
    'ALTER TABLE payroll DROP INDEX uq_payroll_company_emp_period',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add the new unique key including payroll_type
SET @new_key_exists = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'payroll'
    AND index_name = 'uq_payroll_company_emp_period_type'
);
SET @sql = IF(@new_key_exists = 0,
    'ALTER TABLE payroll ADD UNIQUE KEY uq_payroll_company_emp_period_type (company_id, employee_id, period, payroll_type)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add composite index for faster payroll queries
SET @idx_exists = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'employees'
    AND index_name = 'idx_employee_company_position_status'
);
SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE employees ADD INDEX idx_employee_company_position_status (company_id, position, status)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
