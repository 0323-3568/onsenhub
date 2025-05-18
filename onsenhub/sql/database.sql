-- Create database
CREATE DATABASE IF NOT EXISTS onsenhub;
USE onsenhub;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Onsen rooms table
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    image VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reservations table
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    guests INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

USE onsenhub; ALTER TABLE reservations
MODIFY status ENUM('pending', 'confirmed', 'cancelled', 'modification requested', 'cancellation requested', 'expired', 'modification approved', 'modification denied') DEFAULT 'pending'

-- Activity logs table
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert sample rooms
INSERT INTO rooms (name, description, price, capacity, image) VALUES
('Bamboo Forest Suite', 'Private onsen with bamboo forest view. Includes yukata rental and tea set.', 7500.00, 2, 'bamboo.jpg'),
('Cherry Blossom Room', 'Traditional tatami room with indoor onsen. Seasonal kaiseki dinner included.', 6500.00, 2, 'cherry.jpg'),
('Mountain View Villa', 'Spacious villa with outdoor onsen and mountain panorama. Private chef available.', 12000.00, 4, 'mountain.jpg'),
('Zen Garden Retreat', 'Minimalist room with zen garden and stone bath. Meditation sessions included.', 5500.00, 2, 'zen.jpg')
('Samurai Suite', 'Luxurious suite with private indoor onsen and traditional tatami lounge.', 9500.00, 2, 'samurai-suite.jpg')
('Moonlit Garden Onsen', 'Private outdoor onsen surrounded by fragrant night-blooming flowers.', 8500.00, 4, 'moonlit-garden.jpg');