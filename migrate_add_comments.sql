-- Migration: create comments table and add missing columns if absent
-- Executez ce fichier via phpMyAdmin ou en CLI MySQL

USE manhwareader;

-- Add columns to manhwas
ALTER TABLE manhwas
  ADD COLUMN IF NOT EXISTS read_count INT DEFAULT 0,
  ADD COLUMN IF NOT EXISTS order_index INT DEFAULT 0,
  ADD COLUMN IF NOT EXISTS last_read_at DATETIME DEFAULT NULL;

-- Add last_read_at to chapters
ALTER TABLE chapters
  ADD COLUMN IF NOT EXISTS last_read_at DATETIME DEFAULT NULL;

-- Add order_index and user_id to tracking
ALTER TABLE tracking
  ADD COLUMN IF NOT EXISTS order_index INT DEFAULT 0,
  ADD COLUMN IF NOT EXISTS user_id VARCHAR(255) DEFAULT NULL;

-- Create comments table
CREATE TABLE IF NOT EXISTS comments (
    id VARCHAR(255) PRIMARY KEY,
    manhwa_id VARCHAR(255) NOT NULL,
    chapter_number INT DEFAULT NULL,
    user_id VARCHAR(255) DEFAULT NULL,
    author VARCHAR(255) DEFAULT 'Anonyme',
    text TEXT NOT NULL,
    images TEXT DEFAULT NULL,
    date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_manhwa_chapter (manhwa_id, chapter_number),
    INDEX idx_comments_user (user_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
