-- Migration: Add profile_picture column to employees table
USE alm_biometrics;

ALTER TABLE employees 
ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL 
AFTER face_descriptor;

-- Add index for better performance
CREATE INDEX idx_employee_profile_picture ON employees(profile_picture);
