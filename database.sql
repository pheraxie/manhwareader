-- Base de données pour ManhwaReader Pro
-- Exécutez ce script dans phpMyAdmin ou via MySQL

CREATE DATABASE IF NOT EXISTS manhwareader CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE manhwareader;

-- Table pour les manhwas
CREATE TABLE IF NOT EXISTS manhwas (
    id VARCHAR(255) PRIMARY KEY,
    manhwa_id VARCHAR(255) NOT NULL UNIQUE,
    manhwa_title VARCHAR(500) NOT NULL,
    manhwa_cover TEXT,
    manhwa_description TEXT,
    manhwa_season VARCHAR(100),
    date_added DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_manhwa_id (manhwa_id),
    INDEX idx_date_added (date_added)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les chapitres
CREATE TABLE IF NOT EXISTS chapters (
    id VARCHAR(255) PRIMARY KEY,
    manhwa_id VARCHAR(255) NOT NULL,
    chapter_number INT NOT NULL,
    chapter_title VARCHAR(500),
    chapter_description TEXT,
    chapter_season VARCHAR(100),
    chapter_pages TEXT,
    chapter_cover TEXT,
    is_favorite BOOLEAN DEFAULT FALSE,
    date_added DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_manhwa_id (manhwa_id),
    INDEX idx_chapter_number (chapter_number),
    INDEX idx_is_favorite (is_favorite),
    INDEX idx_date_added (date_added),
    UNIQUE KEY unique_manhwa_chapter (manhwa_id, chapter_number),
    FOREIGN KEY (manhwa_id) REFERENCES manhwas(manhwa_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les suivis (tracking)
CREATE TABLE IF NOT EXISTS tracking (
    id VARCHAR(255) PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    chapter INT NOT NULL DEFAULT 0,
    status ENUM('en-cours', 'fini', 'en-pause') DEFAULT 'en-cours',
    notes TEXT,
    season VARCHAR(100),
    date_added DATETIME NOT NULL,
    date_updated DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_date_added (date_added)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour la corbeille (trash)
CREATE TABLE IF NOT EXISTS trash (
    id VARCHAR(255) PRIMARY KEY,
    trash_type ENUM('manhwa', 'chapter', 'tracking') NOT NULL,
    original_data TEXT NOT NULL,
    deleted_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_trash_type (trash_type),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

