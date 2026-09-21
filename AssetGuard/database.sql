-- =====================================================
-- AssetGuard - Equipment & Fleet Maintenance Tracking System
-- Database Schema & Seed Data
-- =====================================================

CREATE DATABASE IF NOT EXISTS assetguard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE assetguard;

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'technician') DEFAULT 'technician',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- EQUIPMENT TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    type VARCHAR(100),
    location VARCHAR(150),
    status ENUM('Operational', 'Under Maintenance', 'Broken', 'Retired') DEFAULT 'Operational',
    risk_level ENUM('Low', 'Medium', 'High') DEFAULT 'Low',
    responsible_id INT,
    purchase_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (responsible_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- MAINTENANCE REQUESTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS maintenance_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    assigned_to INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    status ENUM('Open', 'In Progress', 'Completed') DEFAULT 'Open',
    scheduled_date DATE,
    completed_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- MAINTENANCE SCHEDULE TABLE (Preventive/Recurring)
-- =====================================================
CREATE TABLE IF NOT EXISTS maintenance_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    frequency ENUM('Weekly', 'Monthly', 'Quarterly', 'Yearly') DEFAULT 'Monthly',
    last_done_date DATE,
    next_due_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
);

-- =====================================================
-- SPARE PARTS ALERTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS spare_parts_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    part_name VARCHAR(150) NOT NULL,
    quantity_available INT DEFAULT 0,
    alert_threshold INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
);

-- =====================================================
-- MAINTENANCE HISTORY TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS maintenance_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    request_id INT,
    performed_by INT,
    notes TEXT,
    performed_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES maintenance_requests(id) ON DELETE SET NULL,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- SEED DATA
-- =====================================================

-- Users (password for all demo accounts: 123456)
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@assetguard.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Technician User', 'tech@assetguard.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'technician'),
('Khalid Al-Otaibi', 'khalid@assetguard.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'technician'),
('Faisal Al-Zahrani', 'faisal@assetguard.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'technician');

-- Equipment
INSERT INTO equipment (name, type, location, status, risk_level, responsible_id, purchase_date) VALUES
('Excavator CAT-320', 'Heavy Machinery', 'Riyadh Site A', 'Operational', 'Medium', 3, '2022-03-15'),
('Delivery Truck #12', 'Fleet Vehicle', 'Jeddah Depot', 'Under Maintenance', 'High', 4, '2021-07-10'),
('Generator GEN-500', 'Power Equipment', 'Dammam Warehouse', 'Operational', 'Low', 3, '2023-01-20'),
('Forklift FL-08', 'Warehouse Equipment', 'Riyadh Site B', 'Broken', 'High', 4, '2020-11-05'),
('Crane TC-750', 'Heavy Machinery', 'Makkah Site', 'Operational', 'Medium', 3, '2022-09-01'),
('Delivery Van #05', 'Fleet Vehicle', 'Jeddah Depot', 'Operational', 'Low', 4, '2023-05-12');

-- Maintenance Requests
INSERT INTO maintenance_requests (equipment_id, assigned_to, title, description, priority, status, scheduled_date, completed_date) VALUES
(2, 4, 'Brake system inspection', 'Driver reported soft brake pedal', 'High', 'In Progress', '2026-09-18', NULL),
(4, 4, 'Hydraulic fluid leak', 'Visible leak under forklift mast', 'High', 'Open', '2026-09-22', NULL),
(1, 3, 'Routine engine check', 'Scheduled 250-hour service', 'Medium', 'Completed', '2026-08-10', '2026-08-11'),
(3, 3, 'Oil filter replacement', 'Quarterly preventive maintenance', 'Low', 'Completed', '2026-07-01', '2026-07-01'),
(5, 3, 'Cable inspection', 'Annual safety inspection required', 'Medium', 'Open', '2026-10-05', NULL);

-- Maintenance Schedule (Preventive)
INSERT INTO maintenance_schedule (equipment_id, frequency, last_done_date, next_due_date, notes) VALUES
(1, 'Quarterly', '2026-08-11', '2026-11-11', '250-hour engine service cycle'),
(3, 'Monthly', '2026-08-01', '2026-09-01', 'Oil and filter check'),
(5, 'Yearly', '2025-10-05', '2026-10-05', 'Full safety and cable inspection'),
(6, 'Quarterly', '2026-06-15', '2026-09-15', 'Tire rotation and fluid check');

-- Spare Parts Alerts
INSERT INTO spare_parts_alerts (equipment_id, part_name, quantity_available, alert_threshold) VALUES
(2, 'Brake Pads (Front)', 1, 2),
(4, 'Hydraulic Seal Kit', 0, 1),
(1, 'Engine Oil Filter', 5, 3),
(5, 'Wire Rope Cable', 1, 1);

-- Maintenance History
INSERT INTO maintenance_history (equipment_id, request_id, performed_by, notes, performed_date) VALUES
(1, 3, 3, 'Engine check completed, all parameters normal.', '2026-08-11'),
(3, 4, 3, 'Oil filter replaced successfully.', '2026-07-01'),
(2, NULL, 4, 'Previous brake pad replacement.', '2026-03-15'),
(4, NULL, 4, 'Forklift hydraulic system serviced.', '2026-05-20');
