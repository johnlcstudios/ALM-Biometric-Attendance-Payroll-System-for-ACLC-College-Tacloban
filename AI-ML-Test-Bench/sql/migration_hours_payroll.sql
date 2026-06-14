-- Migration: Hours-based payroll support
-- Adds total_hours tracking and load_pay to subject_loads

-- 1. Add total_hours to attendance
ALTER TABLE attendance ADD COLUMN total_hours DECIMAL(6,2) DEFAULT NULL AFTER check_out;

-- 2. Add total_hours to payroll
ALTER TABLE payroll ADD COLUMN total_hours DECIMAL(8,2) DEFAULT NULL AFTER net_pay;

-- 3. Add load_pay column to subject_loads
ALTER TABLE subject_loads ADD COLUMN load_pay DECIMAL(10,2) DEFAULT 0.00 AFTER hours;

-- 4. Prevent duplicate schedules
ALTER TABLE subject_schedules ADD UNIQUE KEY uq_schedule (subject_load_id, day_of_week, time_start, time_end);

-- 5. Index for faster schedule lookups during kiosk scan
ALTER TABLE subject_schedules ADD INDEX idx_sche_lookup (day_of_week, time_start, time_end);
