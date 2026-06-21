-- GreenStep Database Schema
-- SCSM2223 - Cross-Platform Application Development
-- Project 3: Personal Carbon Footprint & Eco-Lifestyle Tracker

-- Drop database if exists and create new one
DROP DATABASE IF EXISTS greenstep_db;
CREATE DATABASE greenstep_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE greenstep_db;

-- ============================================
-- 1. USER TABLE
-- ============================================
CREATE TABLE User (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('end-user', 'admin') DEFAULT 'end-user',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 2. ACTIVITY TYPE TABLE
-- ============================================
CREATE TABLE ActivityType (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category ENUM('Transport', 'Diet', 'Energy', 'Recycling') NOT NULL,
    name VARCHAR(100) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    kg_co2_per_unit DECIMAL(10, 4) NOT NULL,
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 3. ACTIVITY LOG TABLE
-- ============================================
CREATE TABLE ActivityLog (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(10) UNSIGNED NOT NULL,
    activity_type_id INT(10) UNSIGNED NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    carbon_footprint DECIMAL(10, 4) DEFAULT 0,
    logged_on DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE,
    FOREIGN KEY (activity_type_id) REFERENCES ActivityType(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, logged_on),
    INDEX idx_logged_on (logged_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 4. TIP TABLE
-- ============================================
CREATE TABLE Tip (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    body VARCHAR(2000) NOT NULL,
    category ENUM('Transport', 'Diet', 'Energy', 'Recycling', 'General') NOT NULL,
    source_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 5. TIP BADGE TABLE (Track user's received daily tips)
-- ============================================
CREATE TABLE TipBadge (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(10) UNSIGNED NOT NULL,
    tip_id INT(10) UNSIGNED NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE,
    FOREIGN KEY (tip_id) REFERENCES Tip(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_tip_date (user_id, date),
    INDEX idx_user_date (user_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 6. CHALLENGE TABLE
-- ============================================
CREATE TABLE Challenge (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(1000) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    target_co2_reduction DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 7. CHALLENGE MEMBER TABLE
-- ============================================
CREATE TABLE ChallengeMember (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT(10) UNSIGNED NOT NULL,
    user_id INT(10) UNSIGNED NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    current_reduction DECIMAL(10, 2) DEFAULT 0,
    FOREIGN KEY (challenge_id) REFERENCES Challenge(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE,
    UNIQUE KEY unique_challenge_user (challenge_id, user_id),
    INDEX idx_user_challenges (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 8. BADGE TABLE
-- ============================================
CREATE TABLE Badge (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    criteria_json JSON NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 9. USER BADGE TABLE (Earned badges)
-- ============================================
CREATE TABLE UserBadge (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(10) UNSIGNED NOT NULL,
    badge_id INT(10) UNSIGNED NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES Badge(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_badge (user_id, badge_id),
    INDEX idx_user_badges (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 10. FRIENDSHIP TABLE (Optional - for social features)
-- ============================================
CREATE TABLE Friendship (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id INT(10) UNSIGNED NOT NULL,
    receiver_id INT(10) UNSIGNED NOT NULL,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES User(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES User(id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship_pair (sender_id, receiver_id),
    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SEED DATA: Activity Types with Emission Factors
-- ============================================
INSERT INTO ActivityType (category, name, unit, kg_co2_per_unit) VALUES
-- Transport
('Transport', 'Car (Petrol)', 'km', 0.1920),
('Transport', 'Bus / Train travel', 'km', 0.0890),
('Transport', 'Bicycle commute', 'km', 0.0000),
('Transport', 'Motorcycle', 'km', 0.1030),
('Transport', 'Car (Electric)', 'km', 0.0530),

-- Diet
('Diet', 'Red Meat Meal', 'meal', 6.6100),
('Diet', 'Vegetarian Meal', 'meal', 1.7700),
('Diet', 'Vegan Meal', 'meal', 1.0500),
('Diet', 'Chicken Meal', 'meal', 1.3700),
('Diet', 'Fish Meal', 'meal', 1.4300),

-- Energy
('Energy', 'Electricity Usage', 'kWh', 0.4750),
('Energy', 'Air Conditioner usage', 'hour', 0.8500),
('Energy', 'Natural Gas', 'kWh', 0.1850),

-- Recycling (negative values = carbon reduction)
('Recycling', 'Paper Recycling', 'kg', -0.1700),
('Recycling', 'Plastic Recycling', 'kg', -0.4500),
('Recycling', 'Glass Recycling', 'kg', -0.3000),
('Recycling', 'Aluminum Recycling', 'kg', -9.1300);

-- ============================================
-- SEED DATA: Eco Tips
-- ============================================
INSERT INTO Tip (title, body, category, source_url) VALUES
('Use a reusable water bottle', 'Save approximately 156 plastic bottles per year by using a reusable water bottle instead of single-use disposable containers.', 'Recycling', 'https://www.earthday.org/'),
('Take shorter showers', 'Reducing shower duration by just 2 minutes saves up to 10 gallons of domestic water and reduces water-heating energy demands.', 'Energy', 'https://www.epa.gov/watersense'),
('Meatless Mondays', 'Going plant-based just one day per week saves over 1,200 lbs of carbon dioxide equivalent emissions annually.', 'Diet', 'https://www.meatlessmonday.com/'),
('Buy local produce', 'Support local agricultural supplies to heavily drop freight shipping engine fuel emissions. Food typically travels 1,500 miles before reaching your plate.', 'Diet', 'https://www.localharvest.org/'),
('Use public transport', 'Taking a bus or passenger train reduces individual transit carbon footprints by up to 75% compared to private car commuting.', 'Transport', 'https://www.transit.dot.gov/'),
('Recycle properly', 'Rinse out contaminated food containers thoroughly before submitting them to sorting bins to protect waste streams from contamination.', 'Recycling', 'https://www.recyclenow.com/'),
('Switch to LED bulbs', 'LED bulbs use 75% less energy and last 25 times longer than incandescent lighting.', 'Energy', 'https://www.energy.gov/'),
('Unplug unused electronics', 'Phantom power can account for 10% of your electricity bill. Unplug devices when not in use.', 'Energy', 'https://www.energy.gov/'),
('Walk or bike for short trips', 'Trips under 1 mile are perfect for walking or cycling, eliminating emissions entirely.', 'Transport', 'https://www.walkscore.com/'),
('Compost food scraps', 'Composting reduces landfill waste and creates nutrient-rich soil. Food waste in landfills produces methane.', 'Recycling', 'https://www.epa.gov/recycle/composting-home');

-- ============================================
-- SEED DATA: Challenges
-- ============================================
INSERT INTO Challenge (name, description, start_date, end_date, target_co2_reduction) VALUES
('Zero Waste Week', 'Go one week minimizing plastic wrappers, composting food scraps, and refusing single-use items.', '2026-06-01', '2026-06-07', 20.00),
('30-Day Eco Warrior Challenge', 'Reduce your household electricity footings by shifting to alternative transit modes and vegetarian meal tracks.', '2026-06-01', '2026-06-30', 50.00),
('Green Transport Month', 'Ditch private combustion vehicles. Rely completely on commuter electric transit systems, cycling, or shared buses.', '2026-06-01', '2026-06-30', 100.00),
('Plastic Free July', 'Eliminate single-use plastics from your daily routine for the entire month.', '2026-07-01', '2026-07-31', 15.00);

-- ============================================
-- SEED DATA: Badges
-- ============================================
INSERT INTO Badge (name, criteria_json, image_url) VALUES
('Green Commuter', '{"type": "transport_distance", "threshold": 50, "description": "Log over 50 kilometers in non-combustion alternative transit"}', '/badges/green-commuter.png'),
('Conscious Eater', '{"type": "diet_streak", "threshold": 7, "description": "Log a streak of 7 consecutive plant-based dinner choices"}', '/badges/conscious-eater.png'),
('Carbon Neutral Master', '{"type": "footprint_reduction", "threshold": 50, "description": "Successfully drop total cumulative footprint values below regional averages"}', '/badges/carbon-neutral.png'),
('Recycling Hero', '{"type": "recycling_amount", "threshold": 10, "description": "Recycle over 10kg of materials"}', '/badges/recycling-hero.png'),
('Energy Saver', '{"type": "energy_reduction", "threshold": 20, "description": "Reduce electricity usage by 20% compared to baseline"}', '/badges/energy-saver.png'),
('30-Day Streak', '{"type": "login_streak", "threshold": 30, "description": "Log activities for 30 consecutive days"}', '/badges/30-day-streak.png');

-- ============================================
-- Create Admin User (password: admin123 - CHANGE IN PRODUCTION!)
-- Password hashed with bcrypt
-- ============================================
INSERT INTO User (name, email, password_hash, role, joined_at) VALUES
('Administrator', 'admin@greenstep.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW());

-- ============================================
-- Verification: Check all tables were created
-- ============================================
SHOW TABLES;
