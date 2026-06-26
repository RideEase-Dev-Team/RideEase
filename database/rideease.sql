-- ============================================================
-- RideEase – Smart Ride Sharing & Cab Booking Management System
-- Database: rideease_db
-- Version: 1.0
-- ============================================================

CREATE DATABASE IF NOT EXISTS rideease_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rideease_db;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('passenger','driver','admin') NOT NULL DEFAULT 'passenger',
    profile_pic VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: drivers
-- ============================================================
CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    license_no VARCHAR(50) NOT NULL,
    nid_no VARCHAR(50) NOT NULL,
    experience_years INT NOT NULL DEFAULT 0,
    avg_rating DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    total_trips INT NOT NULL DEFAULT 0,
    is_available TINYINT(1) NOT NULL DEFAULT 0,
    is_approved TINYINT(1) NOT NULL DEFAULT 0,
    is_suspended TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: vehicles
-- ============================================================
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year YEAR NOT NULL,
    color VARCHAR(30) NOT NULL,
    plate_no VARCHAR(20) NOT NULL UNIQUE,
    vehicle_type ENUM('car','CNG','motorcycle','microbus') NOT NULL DEFAULT 'car',
    is_approved TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: peak_hours
-- ============================================================
CREATE TABLE IF NOT EXISTS peak_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week TINYINT NOT NULL COMMENT '0=Sunday,1=Monday,...,6=Saturday',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    multiplier DECIMAL(3,2) NOT NULL DEFAULT 1.50,
    label VARCHAR(50) DEFAULT 'Peak Hour',
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: coupons
-- ============================================================
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(8,2) NOT NULL,
    min_fare DECIMAL(8,2) NOT NULL DEFAULT 0,
    max_uses INT NOT NULL DEFAULT 100,
    used_count INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    expires_at DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: rides
-- ============================================================
CREATE TABLE IF NOT EXISTS rides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    passenger_id INT NOT NULL,
    driver_id INT DEFAULT NULL,
    pickup_location VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    distance_km DECIMAL(8,2) NOT NULL DEFAULT 0,
    base_fare DECIMAL(8,2) NOT NULL DEFAULT 50.00,
    peak_multiplier DECIMAL(3,2) NOT NULL DEFAULT 1.00,
    discount_amount DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    final_fare DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','assigned','on_ride','completed','cancelled') NOT NULL DEFAULT 'pending',
    payment_method ENUM('cash','bkash','card') DEFAULT NULL,
    coupon_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    assigned_at DATETIME DEFAULT NULL,
    started_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: cancellations
-- ============================================================
CREATE TABLE IF NOT EXISTS cancellations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT NOT NULL UNIQUE,
    cancelled_by INT NOT NULL,
    cancelled_by_role ENUM('passenger','driver','admin') NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
    FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: payments
-- ============================================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT NOT NULL UNIQUE,
    amount DECIMAL(8,2) NOT NULL,
    method ENUM('cash','bkash','card') NOT NULL,
    status ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
    transaction_id VARCHAR(100) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: driver_earnings
-- ============================================================
CREATE TABLE IF NOT EXISTS driver_earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    ride_id INT NOT NULL UNIQUE,
    gross_amount DECIMAL(8,2) NOT NULL,
    commission_pct DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    commission_amount DECIMAL(8,2) NOT NULL,
    net_amount DECIMAL(8,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: ratings
-- ============================================================
CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT NOT NULL UNIQUE,
    driver_id INT NOT NULL,
    passenger_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    feedback TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: sos_alerts
-- ============================================================
CREATE TABLE IF NOT EXISTS sos_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    message TEXT DEFAULT NULL,
    is_resolved TINYINT(1) NOT NULL DEFAULT 0,
    resolved_by INT DEFAULT NULL,
    resolved_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: complaints
-- ============================================================
CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ride_id INT DEFAULT NULL,
    subject VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('open','in_review','resolved') NOT NULL DEFAULT 'open',
    admin_response TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: favorite_locations
-- ============================================================
CREATE TABLE IF NOT EXISTS favorite_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label VARCHAR(50) NOT NULL,
    address VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Peak Hours Configuration
INSERT INTO peak_hours (day_of_week, start_time, end_time, multiplier, label) VALUES
(1, '08:00:00', '10:00:00', 1.50, 'Morning Rush'),
(1, '17:00:00', '20:00:00', 1.75, 'Evening Rush'),
(2, '08:00:00', '10:00:00', 1.50, 'Morning Rush'),
(2, '17:00:00', '20:00:00', 1.75, 'Evening Rush'),
(3, '08:00:00', '10:00:00', 1.50, 'Morning Rush'),
(3, '17:00:00', '20:00:00', 1.75, 'Evening Rush'),
(4, '08:00:00', '10:00:00', 1.50, 'Morning Rush'),
(4, '17:00:00', '20:00:00', 1.75, 'Evening Rush'),
(5, '08:00:00', '10:00:00', 1.50, 'Morning Rush'),
(5, '17:00:00', '21:00:00', 2.00, 'Friday Evening Rush'),
(6, '10:00:00', '22:00:00', 1.25, 'Weekend'),
(0, '10:00:00', '22:00:00', 1.25, 'Weekend');

-- Coupons
INSERT INTO coupons (code, discount_type, discount_value, min_fare, max_uses, is_active, expires_at) VALUES
('RIDE10', 'percent', 10.00, 100.00, 500, 1, '2027-12-31'),
('FIRST50', 'fixed', 50.00, 80.00, 100, 1, '2027-12-31'),
('SAVE20', 'percent', 20.00, 200.00, 200, 1, '2027-06-30'),
('WELCOME', 'fixed', 30.00, 60.00, 1000, 1, '2027-12-31'),
('PROMO25', 'percent', 25.00, 150.00, 300, 1, '2026-12-31');

-- Users (passwords are bcrypt hashed)
-- Admin: Admin@123
INSERT INTO users (name, email, phone, password_hash, role, is_active) VALUES
('System Admin', 'admin@rideease.com', '01700000000',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'admin', 1);

-- Passenger: User@123
INSERT INTO users (name, email, phone, password_hash, role, is_active) VALUES
('Rahim Uddin', 'user@rideease.com', '01711111111',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'passenger', 1),
('Fatema Begum', 'fatema@rideease.com', '01722222222',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'passenger', 1);

-- Driver: Driver@123
INSERT INTO users (name, email, phone, password_hash, role, is_active) VALUES
('Karim Driver', 'driver@rideease.com', '01733333333',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'driver', 1),
('Jamal Hossain', 'jamal@rideease.com', '01744444444',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'driver', 1);

-- Driver profiles
INSERT INTO drivers (user_id, license_no, nid_no, experience_years, avg_rating, total_trips, is_available, is_approved) VALUES
(4, 'DL-2020-001234', 'NID-19901234567', 5, 4.70, 234, 1, 1),
(5, 'DL-2019-005678', 'NID-19885678901', 7, 4.50, 178, 0, 1);

-- Vehicles
INSERT INTO vehicles (driver_id, make, model, year, color, plate_no, vehicle_type, is_approved) VALUES
(1, 'Toyota', 'Allion', 2018, 'White', 'DHAKA-GA-1234', 'car', 1),
(2, 'Honda', 'City', 2019, 'Silver', 'DHAKA-GA-5678', 'car', 1);

-- Sample Rides
INSERT INTO rides (passenger_id, driver_id, pickup_location, destination, distance_km, base_fare, peak_multiplier, discount_amount, final_fare, status, payment_method, created_at, completed_at) VALUES
(2, 1, 'Mirpur 10, Dhaka', 'Motijheel, Dhaka', 8.50, 50.00, 1.00, 0.00, 152.00, 'completed', 'cash', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 1, 'Dhanmondi 27, Dhaka', 'Gulshan 1, Dhaka', 6.20, 50.00, 1.50, 0.00, 161.60, 'completed', 'bkash', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 2, 'Uttara, Dhaka', 'Farmgate, Dhaka', 12.00, 50.00, 1.75, 50.00, 204.00, 'completed', 'card', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, NULL, 'Banani, Dhaka', 'Bashundhara, Dhaka', 5.00, 50.00, 1.00, 0.00, 110.00, 'cancelled', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),
(3, 1, 'Rampura, Dhaka', 'Paltan, Dhaka', 7.30, 50.00, 1.00, 30.00, 107.60, 'completed', 'cash', DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY));

-- Payments
INSERT INTO payments (ride_id, amount, method, status, transaction_id, created_at) VALUES
(1, 152.00, 'cash', 'completed', 'TXN-CASH-00001', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 161.60, 'bkash', 'completed', 'TXN-BKS-78234', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 204.00, 'card', 'completed', 'TXN-CRD-55123', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 107.60, 'cash', 'completed', 'TXN-CASH-00002', DATE_SUB(NOW(), INTERVAL 7 DAY));

-- Driver Earnings
INSERT INTO driver_earnings (driver_id, ride_id, gross_amount, commission_pct, commission_amount, net_amount, created_at) VALUES
(1, 1, 152.00, 20.00, 30.40, 121.60, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 2, 161.60, 20.00, 32.32, 129.28, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 3, 204.00, 20.00, 40.80, 163.20, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 5, 107.60, 20.00, 21.52, 86.08, DATE_SUB(NOW(), INTERVAL 7 DAY));

-- Cancellations
INSERT INTO cancellations (ride_id, cancelled_by, cancelled_by_role, reason) VALUES
(4, 2, 'passenger', 'Changed my travel plans');

-- Ratings
INSERT INTO ratings (ride_id, driver_id, passenger_id, rating, feedback) VALUES
(1, 1, 2, 5, 'Excellent driver! Very professional and on time.'),
(2, 1, 2, 4, 'Good ride, AC was nice.'),
(3, 2, 3, 5, 'Very smooth and comfortable ride.'),
(5, 1, 3, 4, 'Good driver, would ride again.');

-- Favorite Locations
INSERT INTO favorite_locations (user_id, label, address) VALUES
(2, 'Home', 'Mirpur 12, Dhaka'),
(2, 'Office', 'Motijheel, Dhaka'),
(3, 'Home', 'Uttara Sector 7, Dhaka'),
(3, 'University', 'NSU, Bashundhara, Dhaka');

-- SOS Alerts (sample)
INSERT INTO sos_alerts (ride_id, user_id, message, is_resolved) VALUES
(2, 2, 'Driver is behaving suspiciously. Need assistance.', 1);

-- Complaints
INSERT INTO complaints (user_id, ride_id, subject, description, status, admin_response) VALUES
(2, 2, 'Driver arrived late', 'The driver was 15 minutes late and did not apologize.', 'resolved', 'We have noted the feedback and spoken to the driver. Thank you.'),
(3, 3, 'Overcharged for ride', 'I was charged more than the estimated fare shown.', 'open', NULL);
