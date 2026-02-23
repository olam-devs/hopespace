-- ============================================================
-- Space of Hope — Complete Self-Contained Database Migration
-- ============================================================
-- Run this SINGLE FILE on a fresh `spaceofhope` database.
-- Covers all previous migrations in one go:
--   schema.sql + migrate_v2.sql + phase2_module1_migration.sql
--   + anonymous_comments_migration.sql + stories_migration.sql
--
-- Safe to run on phpMyAdmin: Import > select this file > Go
-- Uses CREATE TABLE IF NOT EXISTS — won't overwrite existing data.
-- Uses INSERT IGNORE — skips duplicate seed rows silently.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. MESSAGES
-- ============================================================
CREATE TABLE IF NOT EXISTS `messages` (
    `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `language`     ENUM('sw', 'en') NOT NULL,
    `category`     VARCHAR(50) NOT NULL,
    `format`       ENUM('quote', 'paragraph', 'lesson', 'question') NOT NULL,
    `content`      TEXT NOT NULL,
    `status`       ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `published_at` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. REACTIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `reactions` (
    `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `message_id` BIGINT UNSIGNED NOT NULL,
    `type`       ENUM('helped', 'hope', 'not_alone') NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. ADMINS
-- ============================================================
CREATE TABLE IF NOT EXISTS `admins` (
    `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email`         VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role`          VARCHAR(50) NOT NULL DEFAULT 'moderator',
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin (password: admin123 — change this immediately!)
INSERT IGNORE INTO `admins` (`email`, `password_hash`, `role`) VALUES
('admin@spaceofhope.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ============================================================
-- 4. AUDIT LOG
-- (Updated schema: nullable message_id, comment_id col, extended action enum)
-- FK to anonymous_comments is safe here because FOREIGN_KEY_CHECKS=0
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id`   BIGINT UNSIGNED NOT NULL,
    `message_id` BIGINT UNSIGNED NULL,
    `comment_id` BIGINT UNSIGNED NULL,
    `action`     ENUM('approved','edited','rejected','comment_approved','comment_rejected','comment_edited','comment_deleted') NOT NULL,
    `details`    TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`)   REFERENCES `admins`(`id`)            ON DELETE CASCADE,
    FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`)          ON DELETE CASCADE,
    FOREIGN KEY (`comment_id`) REFERENCES `anonymous_comments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. RESOURCE CATEGORIES, RESOURCES, PARTNERS
-- ============================================================
CREATE TABLE IF NOT EXISTS `resource_categories` (
    `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(255) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resources` (
    `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` BIGINT UNSIGNED NOT NULL,
    `name`        VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `phone`       VARCHAR(100) NULL,
    `logo`        VARCHAR(255) NULL,
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `resource_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `partners` (
    `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `website`     VARCHAR(255) NULL,
    `image`       VARCHAR(255) NULL,
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `resource_categories` (`id`, `name`, `sort_order`) VALUES
(1, 'Hospitals & Clinics',       1),
(2, 'Counselors & Helplines',    2),
(3, 'NGOs & Organizations',      3),
(4, 'Emergency Contacts',        4);

INSERT IGNORE INTO `resources` (`category_id`, `name`, `description`, `phone`, `sort_order`) VALUES
(1, 'Muhimbili National Hospital',              'Mental Health Unit, Dar es Salaam',                         '+255 22 215 1599', 1),
(1, 'Mirembe National Mental Hospital',         'Dodoma, Tanzania',                                          '+255 26 232 1831', 2),
(1, 'KCMC - Kilimanjaro Christian Medical Centre', 'Moshi, Kilimanjaro',                                     '+255 27 275 4377', 3),
(2, 'CCBRT Mental Health Services',             'Dar es Salaam — Counseling & psychiatric care',             '+255 22 260 1556', 1),
(2, 'Befrienders Tanzania',                     'Emotional support & crisis helpline',                       '+255 22 266 4478', 2),
(3, 'BasicNeeds Tanzania',                      'Community-based mental health support and advocacy',        NULL,               1),
(3, 'MEHATA',                                   'Mental Health Association of Tanzania — Awareness, education, and support', NULL, 2),
(3, 'Tanzania Red Cross Society',               'Psychosocial support and emergency response',               '+255 22 211 6610', 3),
(4, 'Emergency Services (Tanzania)',            '114 — Police | 115 — Fire & Rescue | 112 — General Emergency', '112',          1);

-- ============================================================
-- 6. USERS, USER PROFILES, SUBSCRIPTIONS, SESSIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`             VARCHAR(50) NOT NULL UNIQUE,
    `email`                VARCHAR(255) NOT NULL UNIQUE,
    `password_hash`        VARCHAR(255) NOT NULL,
    `full_name`            VARCHAR(100) NOT NULL,
    `is_reader`            BOOLEAN DEFAULT TRUE,
    `is_author`            BOOLEAN DEFAULT FALSE,
    `is_community_owner`   BOOLEAN DEFAULT FALSE,
    `language_preference`  ENUM('en', 'sw') DEFAULT 'en',
    `is_verified`          BOOLEAN DEFAULT FALSE,
    `is_active`            BOOLEAN DEFAULT TRUE,
    `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (`username`),
    INDEX idx_email    (`email`),
    INDEX idx_verified (`is_verified`),
    INDEX idx_active   (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_profiles` (
    `user_id`     BIGINT UNSIGNED PRIMARY KEY,
    `bio`         TEXT NULL,
    `avatar_type` ENUM('upload', 'generated') DEFAULT 'generated',
    `avatar_path` VARCHAR(255) NULL,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_subscriptions` (
    `id`               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subscriber_id`    BIGINT UNSIGNED NOT NULL,
    `subscribed_to_id` BIGINT UNSIGNED NOT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`subscriber_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subscribed_to_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY unique_subscription (`subscriber_id`, `subscribed_to_id`),
    INDEX idx_subscriber    (`subscriber_id`),
    INDEX idx_subscribed_to (`subscribed_to_id`),
    CONSTRAINT chk_no_self_subscription CHECK (`subscriber_id` != `subscribed_to_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       BIGINT UNSIGNED NOT NULL,
    `session_token` VARCHAR(64) NOT NULL UNIQUE,
    `ip_address`    VARCHAR(45) NULL,
    `user_agent`    VARCHAR(255) NULL,
    `expires_at`    DATETIME NOT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX idx_token   (`session_token`),
    INDEX idx_user_id (`user_id`),
    INDEX idx_expires (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Test author account (password: password)
INSERT IGNORE INTO `users` (`username`, `email`, `password_hash`, `full_name`, `is_author`, `language_preference`) VALUES
('testauthor', 'author@spaceofhope.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test Author', TRUE, 'en');

INSERT IGNORE INTO `user_profiles` (`user_id`, `bio`, `avatar_type`)
SELECT `id`, 'A test author sharing hope and experiences.', 'generated'
FROM `users` WHERE `username` = 'testauthor';

-- ============================================================
-- 7. ANONYMOUS COMMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `anonymous_comments` (
    `id`               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `message_id`       BIGINT UNSIGNED NOT NULL,
    `anonymous_alias`  VARCHAR(30) NOT NULL,
    `content`          TEXT NOT NULL,
    `status`           ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at`      DATETIME NULL,
    `reviewed_by`      BIGINT UNSIGNED NULL,
    FOREIGN KEY (`message_id`)  REFERENCES `messages`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `admins`(`id`)   ON DELETE SET NULL,
    INDEX idx_message (`message_id`),
    INDEX idx_status  (`status`),
    INDEX idx_created (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. TESTIMONIES
-- ============================================================
CREATE TABLE IF NOT EXISTS `testimonies` (
    `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `alias`      VARCHAR(100) DEFAULT NULL,
    `content`    TEXT NOT NULL,
    `language`   ENUM('en', 'sw') NOT NULL DEFAULT 'en',
    `status`     ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. STORIES
-- ============================================================
CREATE TABLE IF NOT EXISTS `stories` (
    `id`                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `author_id`           BIGINT UNSIGNED NOT NULL,
    `title`               VARCHAR(200) NOT NULL,
    `slug`                VARCHAR(230) NOT NULL,
    `language`            ENUM('en', 'sw') NOT NULL DEFAULT 'en',
    `description`         TEXT NOT NULL,
    `story_type`          ENUM('full', 'parts') NOT NULL DEFAULT 'full',
    `status`              ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `is_complete`         TINYINT(1) NOT NULL DEFAULT 0,
    `next_release_title`  VARCHAR(200) DEFAULT NULL,
    `next_release_date`   DATE DEFAULT NULL,
    `next_release_note`   TEXT DEFAULT NULL,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `stories_slug_unique` (`slug`),
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. STORY PARTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `story_parts` (
    `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `story_id`    BIGINT UNSIGNED NOT NULL,
    `part_number` INT UNSIGNED NOT NULL DEFAULT 1,
    `part_title`  VARCHAR(200) DEFAULT NULL,
    `content`     LONGTEXT NOT NULL,
    `status`      ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `is_last_part` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `story_part_number_unique` (`story_id`, `part_number`),
    FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. STORY READS  (privacy-safe: hashed IP + date, no personal data)
-- ============================================================
CREATE TABLE IF NOT EXISTS `story_reads` (
    `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `story_id`    BIGINT UNSIGNED NOT NULL,
    `part_id`     BIGINT UNSIGNED NULL,        -- NULL = story listing click; set = specific part opened
    `part_number` INT UNSIGNED NULL,           -- convenience copy of part_number
    `ip_hash`     VARCHAR(64) NOT NULL,        -- SHA-256 of IP+date (daily dedupe per reader)
    `language`    ENUM('en','sw') NOT NULL DEFAULT 'en',
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_story_reads_story` (`story_id`),
    KEY `idx_story_reads_part`  (`part_id`),
    FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`)      ON DELETE CASCADE,
    FOREIGN KEY (`part_id`)  REFERENCES `story_parts`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. SEED: Sample messages
-- (from anonymous_comments_migration.sql — question format samples)
-- ============================================================

-- Remove old categories no longer in use
DELETE FROM `messages` WHERE `category` IN ('recovery', 'family');

INSERT IGNORE INTO `messages` (`language`, `category`, `format`, `content`, `status`, `created_at`, `published_at`) VALUES
-- Love
('en', 'love', 'quote',     'Love is patient, love is kind. It does not keep a record of wrongs.',                                                                          'approved', NOW(), NOW()),
('sw', 'love', 'quote',     'Upendo huvumilia, upendo ni wenye fadhili. Hauhesabu makosa.',                                                                                  'approved', NOW(), NOW()),
('en', 'love', 'paragraph', 'I learned that true love is not about perfection but about choosing someone every single day despite their flaws.',                              'approved', NOW(), NOW()),
('sw', 'love', 'paragraph', 'Nimejifunza kuwa upendo wa kweli si ukamilifu bali ni kumchagua mtu kila siku licha ya mapungufu yake.',                                         'approved', NOW(), NOW()),
('en', 'love', 'lesson',    'Love taught me that vulnerability is not weakness, but the greatest form of courage.',                                                           'approved', NOW(), NOW()),
('sw', 'love', 'lesson',    'Upendo umenifundisha kuwa kuonyesha hisia si udhaifu, bali ni ujasiri mkubwa.',                                                                  'approved', NOW(), NOW()),
('en', 'love', 'question',  'How do you keep love alive when life gets overwhelming and busy?',                                                                               'approved', NOW(), NOW()),
('sw', 'love', 'question',  'Unafanyaje kudumisha upendo wakati maisha yanakuwa magumu na yenye shughuli nyingi?',                                                            'approved', NOW(), NOW()),
-- Investment Tips
('en', 'investment_tips', 'quote',     'The best investment you can make is in yourself and your skills.',                                                                    'approved', NOW(), NOW()),
('sw', 'investment_tips', 'quote',     'Uwekezaji bora unaweza kufanya ni ndani yako mwenyewe na ujuzi wako.',                                                                'approved', NOW(), NOW()),
('en', 'investment_tips', 'paragraph', 'I started saving just 10% of my income monthly. After two years, I had enough to start a small business that now supports my family.','approved', NOW(), NOW()),
('sw', 'investment_tips', 'paragraph', 'Nilianza kuweka akiba ya 10% ya mapato yangu kila mwezi. Baada ya miaka miwili, nilikuwa na kutosha kuanzisha biashara ndogo inayosaidia familia yangu.', 'approved', NOW(), NOW()),
('en', 'investment_tips', 'lesson',    'I learned that consistency in saving matters more than the amount. Small daily habits build wealth over time.',                        'approved', NOW(), NOW()),
('sw', 'investment_tips', 'lesson',    'Nimejifunza kuwa uthabiti wa kuweka akiba ni muhimu kuliko kiasi. Tabia ndogo za kila siku hujenga utajiri baada ya muda.',            'approved', NOW(), NOW()),
('en', 'investment_tips', 'question',  'What is the best way to start investing with a small income in Africa?',                                                              'approved', NOW(), NOW()),
('sw', 'investment_tips', 'question',  'Njia bora ya kuanza kuwekeza na mapato madogo Afrika ni ipi?',                                                                        'approved', NOW(), NOW()),
-- Questions for existing categories
('en', 'life',         'question', 'What is one lesson life has taught you that you wish you knew earlier?',                                                                  'approved', NOW(), NOW()),
('sw', 'life',         'question', 'Somo moja ambalo maisha yamekufundisha ambalo ungetamani kulijua mapema ni lipi?',                                                        'approved', NOW(), NOW()),
('en', 'faith',        'question', 'How do you maintain your faith during the hardest seasons of life?',                                                                      'approved', NOW(), NOW()),
('sw', 'faith',        'question', 'Unafanyaje kudumisha imani yako wakati wa majira magumu zaidi ya maisha?',                                                                'approved', NOW(), NOW()),
('en', 'education',    'question', 'What advice would you give to someone who cannot afford formal education?',                                                               'approved', NOW(), NOW()),
('sw', 'education',    'question', 'Ni ushauri gani ungempa mtu ambaye hawezi kumudu elimu rasmi?',                                                                           'approved', NOW(), NOW()),
('en', 'finance',      'question', 'How do you manage finances when your income is irregular or seasonal?',                                                                   'approved', NOW(), NOW()),
('sw', 'finance',      'question', 'Unasimamia fedha vipi wakati mapato yako si ya kawaida au ni ya msimu?',                                                                  'approved', NOW(), NOW()),
('en', 'encouragement','question', 'What keeps you going when you feel like giving up on everything?',                                                                        'approved', NOW(), NOW()),
('sw', 'encouragement','question', 'Ni nini kinachokusukuma mbele unapohisi kutaka kuacha kila kitu?',                                                                        'approved', NOW(), NOW()),
('en', 'mental_health', 'question','What is one small thing you do daily that helps your mental health?',                                                                     'approved', NOW(), NOW()),
('sw', 'mental_health', 'question','Ni kitu gani kidogo unachokifanya kila siku kinachosaidia afya yako ya akili?',                                                           'approved', NOW(), NOW()),
('en', 'marriage',     'question', 'What is the most important thing you have learned about making a marriage work?',                                                         'approved', NOW(), NOW()),
('sw', 'marriage',     'question', 'Ni jambo gani muhimu zaidi umejifunza kuhusu kufanya ndoa ifanye kazi?',                                                                  'approved', NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Migration complete.
-- Tables created: messages, reactions, admins, audit_log,
--   resource_categories, resources, partners,
--   users, user_profiles, user_subscriptions, user_sessions,
--   anonymous_comments, testimonies,
--   stories, story_parts, story_reads
-- ============================================================
