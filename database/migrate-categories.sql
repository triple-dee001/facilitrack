-- ============================================================
-- Run this in phpMyAdmin if your database was already created
-- This adds the 4 new categories to the existing table
-- ============================================================

USE facility_maintenance;

ALTER TABLE maintenance_requests 
MODIFY COLUMN category ENUM(
    'plumbing', 'electrical', 'structural', 'cleaning', 
    'pest_control', 'networking', 'furniture', 'security', 
    'equipment', 'other'
) NOT NULL DEFAULT 'other';

-- Update seed accounts with proper location labels
UPDATE users SET flat_number = 'ADMIN OFFICE' WHERE email = 'admin@facility.com';
UPDATE users SET flat_number = 'MANAGEMENT OFFICE' WHERE email = 'manager@facility.com';
