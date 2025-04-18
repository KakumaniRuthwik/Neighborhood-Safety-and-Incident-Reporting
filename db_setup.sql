-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS safecommunities;

-- Use the database
USE safecommunities;

-- Create incidents table
CREATE TABLE IF NOT EXISTS incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    reporter_name VARCHAR(100),
    reporter_email VARCHAR(100),
    photo_path VARCHAR(255),
    lat DECIMAL(10, 8),
    lng DECIMAL(11, 8),
    status VARCHAR(20) DEFAULT 'pending', -- pending, verified, resolved
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create index for faster querying
CREATE INDEX idx_incident_type ON incidents(incident_type);
CREATE INDEX idx_created_at ON incidents(created_at);
CREATE INDEX idx_status ON incidents(status);

-- Create users table for future authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    area VARCHAR(100),
    role VARCHAR(20) DEFAULT 'user', -- user, admin, moderator
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create comments table for incident discussions
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    user_id INT,
    name VARCHAR(100), -- For guest comments
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Sample data for incidents
INSERT INTO incidents (incident_type, title, description, location, date, time, lat, lng, status) VALUES
('theft', 'Bicycle stolen from front yard', 'Red mountain bike taken from unlocked front yard.', '123 Main St', '2023-07-15', '14:30:00', 40.712, -74.006, 'verified'),
('suspicious', 'Suspicious person looking in windows', 'Individual in dark clothing looking into home windows at night.', '456 Oak Ave', '2023-07-16', '23:15:00', 40.718, -74.010, 'verified'),
('vandalism', 'Graffiti on public building', 'New graffiti appeared overnight on the side of the community center.', '789 Elm St', '2023-07-17', '08:00:00', 40.715, -73.998, 'pending'),
('hazard', 'Large pothole damaging vehicles', 'Deep pothole causing damage to cars. Several flat tires reported.', 'Corner of 5th and Pine', '2023-07-18', '12:45:00', 40.720, -74.015, 'pending'),
('theft', 'Package theft', 'Amazon package stolen from doorstep. Captured on Ring camera.', '101 Park Place', '2023-07-19', '15:20:00', 40.708, -74.002, 'verified'),
('suspicious', 'Unknown vehicle parked for days', 'Dark sedan with out-of-state plates parked for 3 days without moving.', '202 Cherry Lane', '2023-07-20', '10:00:00', 40.714, -74.009, 'pending');
