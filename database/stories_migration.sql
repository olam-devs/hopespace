-- ============================================================
-- Hope Space — Stories, Story Parts, Testimonies & Read Tracking
-- Run AFTER: schema.sql, migrate_v2.sql, phase2_module1_migration.sql,
--            anonymous_comments_migration.sql
-- ============================================================

-- Testimonies table (anonymous or named)
CREATE TABLE IF NOT EXISTS `testimonies` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `alias` VARCHAR(100) DEFAULT NULL,
    `content` TEXT NOT NULL,
    `language` ENUM('en', 'sw') NOT NULL DEFAULT 'en',
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stories table (written by registered authors)
CREATE TABLE IF NOT EXISTS `stories` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `author_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(230) NOT NULL,
    `language` ENUM('en', 'sw') NOT NULL DEFAULT 'en',
    `description` TEXT NOT NULL,
    `story_type` ENUM('full', 'parts') NOT NULL DEFAULT 'full',
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `is_complete` TINYINT(1) NOT NULL DEFAULT 0,
    `next_release_title` VARCHAR(200) DEFAULT NULL,
    `next_release_date` DATE DEFAULT NULL,
    `next_release_note` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `stories_slug_unique` (`slug`),
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Story parts table (each chapter/part of a story)
CREATE TABLE IF NOT EXISTS `story_parts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `story_id` BIGINT UNSIGNED NOT NULL,
    `part_number` INT UNSIGNED NOT NULL DEFAULT 1,
    `part_title` VARCHAR(200) DEFAULT NULL,
    `content` LONGTEXT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `is_last_part` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `story_part_number_unique` (`story_id`, `part_number`),
    FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Story reads tracking (privacy-safe: hashed IP + date, no personal data)
-- Tracks every time a reader opens a story or a specific part
CREATE TABLE IF NOT EXISTS `story_reads` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `story_id` BIGINT UNSIGNED NOT NULL,
    `part_id` BIGINT UNSIGNED NULL,          -- NULL = story listing click; set = specific part opened
    `part_number` INT UNSIGNED NULL,          -- convenience copy of part_number
    `ip_hash` VARCHAR(64) NOT NULL,           -- SHA-256 of IP+date (daily dedupe per reader)
    `language` ENUM('en','sw') NOT NULL DEFAULT 'en',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_story_reads_story` (`story_id`),
    KEY `idx_story_reads_part`  (`part_id`),
    FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`part_id`)  REFERENCES `story_parts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
