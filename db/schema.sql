-- KOD Müzik — Database Schema
-- Database: kodmuzik_events
-- Charset: utf8mb4 (Turkish characters + emoji support)
-- Run this file in phpMyAdmin or via CLI before running the migration script.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Events table (past + future)
CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type ENUM('past', 'future') NOT NULL DEFAULT 'past',
    title_tr VARCHAR(255) DEFAULT NULL,
    title_en VARCHAR(255) DEFAULT NULL,
    artists_tr JSON DEFAULT NULL,
    artists_en JSON DEFAULT NULL,
    genre_tr VARCHAR(100) DEFAULT NULL,
    genre_en VARCHAR(100) DEFAULT NULL,
    event_date DATE NOT NULL,
    venue_tr VARCHAR(255) NOT NULL,
    venue_en VARCHAR(255) NOT NULL,
    city_tr VARCHAR(100) NOT NULL,
    city_en VARCHAR(100) NOT NULL,
    series_tr VARCHAR(255) DEFAULT '',
    series_en VARCHAR(255) DEFAULT '',
    description_tr TEXT DEFAULT NULL,
    description_en TEXT DEFAULT NULL,
    ticket_url VARCHAR(500) DEFAULT NULL,
    info_url VARCHAR(500) DEFAULT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_event_date (event_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gallery table
CREATE TABLE IF NOT EXISTS gallery (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED DEFAULT NULL,
    image_path VARCHAR(500) NOT NULL,
    thumbnail_path VARCHAR(500) DEFAULT NULL,
    caption_tr VARCHAR(500) DEFAULT NULL,
    caption_en VARCHAR(500) DEFAULT NULL,
    category ENUM('poster', 'photo', 'flyer', 'other') DEFAULT 'photo',
    year SMALLINT UNSIGNED DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_year (year),
    INDEX idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
