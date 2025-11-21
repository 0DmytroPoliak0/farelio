-- db/schema.sql
-- Run after creating database `travel_app` in phpMyAdmin

USE travel_app;

-- 1) Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Destinations (airports / cities)
CREATE TABLE destinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code CHAR(3) NOT NULL UNIQUE,         -- e.g. YVR, FCO
    city VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) Flights (simple round trips)
CREATE TABLE flights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origin_code CHAR(3) NOT NULL,
    destination_code CHAR(3) NOT NULL,
    depart_date DATE NOT NULL,
    return_date DATE NOT NULL,
    airline VARCHAR(100) NOT NULL,
    stops TINYINT NOT NULL DEFAULT 0,
    total_duration_min INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    bag_included TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_flights_origin
        FOREIGN KEY (origin_code) REFERENCES destinations(code),
    CONSTRAINT fk_flights_destination
        FOREIGN KEY (destination_code) REFERENCES destinations(code),
    INDEX idx_route_dates (origin_code, destination_code, depart_date, return_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Hotels
CREATE TABLE hotels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destination_code CHAR(3) NOT NULL,
    name VARCHAR(150) NOT NULL,
    rating DECIMAL(2,1),
    distance_to_center_km DECIMAL(4,1),
    nightly_price DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_hotels_destination
        FOREIGN KEY (destination_code) REFERENCES destinations(code),
    INDEX idx_hotels_destination (destination_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) Saved trips (chosen bundle)
CREATE TABLE trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    flight_id INT NOT NULL,
    hotel_id INT NOT NULL,
    origin_code CHAR(3) NOT NULL,
    destination_code CHAR(3) NOT NULL,
    depart_date DATE NOT NULL,
    return_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trips_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_trips_flight
        FOREIGN KEY (flight_id) REFERENCES flights(id),
    CONSTRAINT fk_trips_hotel
        FOREIGN KEY (hotel_id) REFERENCES hotels(id),
    INDEX idx_trips_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;