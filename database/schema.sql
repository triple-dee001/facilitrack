-- ============================================================
-- FaciliTrack — Digital Facility Maintenance Reporting System
-- Database Schema (Multi-Organization)
-- ============================================================

CREATE DATABASE IF NOT EXISTS facility_maintenance;
USE facility_maintenance;

-- ============================================================
-- Organizations Table (Multi-Tenant Support)
-- ============================================================
CREATE TABLE IF NOT EXISTS organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    type ENUM('office', 'campus', 'residential', 'school', 'hospital', 'other') NOT NULL DEFAULT 'other',
    address TEXT,
    org_code VARCHAR(20) NOT NULL UNIQUE,
    created_by INT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Users Table
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(100),
    location VARCHAR(200),
    role ENUM('user', 'technician', 'manager', 'admin') NOT NULL DEFAULT 'user',
    is_approved TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update organizations.created_by FK after users table exists
ALTER TABLE organizations ADD FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- ============================================================
-- Maintenance Requests Table
-- ============================================================
CREATE TABLE IF NOT EXISTS maintenance_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('plumbing', 'electrical', 'structural', 'cleaning', 'pest_control', 'networking', 'furniture', 'security', 'equipment', 'other') NOT NULL DEFAULT 'other',
    priority ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'pending',
    issue_location VARCHAR(300) NOT NULL,
    image_path VARCHAR(500) DEFAULT NULL,
    assigned_to INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Request Updates / Activity Log Table
-- ============================================================
CREATE TABLE IF NOT EXISTS request_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    updated_by INT NOT NULL,
    old_status ENUM('pending', 'in_progress', 'resolved', 'closed'),
    new_status ENUM('pending', 'in_progress', 'resolved', 'closed'),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Seed: Default Organization and Admin Account
-- ============================================================
INSERT INTO organizations (name, type, address, org_code)
VALUES ('Demo Facility', 'office', '123 Main Street, Lagos, Nigeria', 'DEMO-2026');

-- Password: password (bcrypt hash)
INSERT INTO users (organization_id, full_name, email, password_hash, phone, department, location, role, is_approved)
VALUES (
    1,
    'System Administrator',
    'admin@facility.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '08012345678',
    'Administration',
    'Admin Office',
    'admin',
    1
);

-- Update organization created_by
UPDATE organizations SET created_by = 1 WHERE id = 1;

-- Seed a sample manager account (Password: password)
INSERT INTO users (organization_id, full_name, email, password_hash, phone, department, location, role, is_approved)
VALUES (
    1,
    'Facility Manager',
    'manager@facility.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '08098765432',
    'Maintenance',
    'Management Office',
    'manager',
    1
);

-- Seed a sample technician account (Password: password)
INSERT INTO users (organization_id, full_name, email, password_hash, phone, department, location, role, is_approved)
VALUES (
    1,
    'John Technician',
    'tech@facility.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '08011112222',
    'Maintenance',
    'Workshop',
    'technician',
    1
);
