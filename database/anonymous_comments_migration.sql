-- ============================================================
-- Anonymous Comments & Category Updates Migration
-- Space of Hope
-- ============================================================
-- Run this after schema.sql and phase2_module1_migration.sql
-- Import via phpMyAdmin or run:
-- mysql -u root spaceofhope < database/anonymous_comments_migration.sql

-- ------------------------------------------------------------
-- 1. Update messages format enum to include 'question'
-- ------------------------------------------------------------
ALTER TABLE `messages`
MODIFY COLUMN `format` ENUM('quote', 'paragraph', 'lesson', 'question') NOT NULL;

-- ------------------------------------------------------------
-- 2. Remove old categories (recovery, family) from messages
-- ------------------------------------------------------------
DELETE FROM `messages` WHERE `category` IN ('recovery', 'family');

-- ------------------------------------------------------------
-- 3. Anonymous Comments Table
-- Comments on anonymous messages (especially questions)
-- Admin must approve before they show publicly
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `anonymous_comments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `message_id` BIGINT UNSIGNED NOT NULL,
    `anonymous_alias` VARCHAR(30) NOT NULL,
    `content` TEXT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` DATETIME NULL,
    `reviewed_by` BIGINT UNSIGNED NULL,
    FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL,
    INDEX idx_message (`message_id`),
    INDEX idx_status (`status`),
    INDEX idx_created (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Update audit_log to support comment actions
-- ------------------------------------------------------------
ALTER TABLE `audit_log`
MODIFY COLUMN `action` ENUM('approved', 'edited', 'rejected', 'comment_approved', 'comment_rejected', 'comment_edited', 'comment_deleted') NOT NULL;

-- Make message_id nullable for comment-only audit entries
ALTER TABLE `audit_log`
MODIFY COLUMN `message_id` BIGINT UNSIGNED NULL;

-- Add comment_id column to audit_log
ALTER TABLE `audit_log`
ADD COLUMN `comment_id` BIGINT UNSIGNED NULL AFTER `message_id`,
ADD FOREIGN KEY (`comment_id`) REFERENCES `anonymous_comments`(`id`) ON DELETE SET NULL;

-- ------------------------------------------------------------
-- 5. Seed sample messages for new categories
-- ------------------------------------------------------------
INSERT INTO `messages` (`language`, `category`, `format`, `content`, `status`, `created_at`, `published_at`) VALUES

-- Love
('en', 'love', 'quote', 'Love is patient, love is kind. It does not keep a record of wrongs.', 'approved', NOW(), NOW()),
('sw', 'love', 'quote', 'Upendo huvumilia, upendo ni wenye fadhili. Hauhesabu makosa.', 'approved', NOW(), NOW()),
('en', 'love', 'paragraph', 'I learned that true love is not about perfection but about choosing someone every single day despite their flaws.', 'approved', NOW(), NOW()),
('sw', 'love', 'paragraph', 'Nimejifunza kuwa upendo wa kweli si ukamilifu bali ni kumchagua mtu kila siku licha ya mapungufu yake.', 'approved', NOW(), NOW()),
('en', 'love', 'lesson', 'Love taught me that vulnerability is not weakness, but the greatest form of courage.', 'approved', NOW(), NOW()),
('sw', 'love', 'lesson', 'Upendo umenifundisha kuwa kuonyesha hisia si udhaifu, bali ni ujasiri mkubwa.', 'approved', NOW(), NOW()),
('en', 'love', 'question', 'How do you keep love alive when life gets overwhelming and busy?', 'approved', NOW(), NOW()),
('sw', 'love', 'question', 'Unafanyaje kudumisha upendo wakati maisha yanakuwa magumu na yenye shughuli nyingi?', 'approved', NOW(), NOW()),

-- Investment Tips
('en', 'investment_tips', 'quote', 'The best investment you can make is in yourself and your skills.', 'approved', NOW(), NOW()),
('sw', 'investment_tips', 'quote', 'Uwekezaji bora unaweza kufanya ni ndani yako mwenyewe na ujuzi wako.', 'approved', NOW(), NOW()),
('en', 'investment_tips', 'paragraph', 'I started saving just 10% of my income monthly. After two years, I had enough to start a small business that now supports my family.', 'approved', NOW(), NOW()),
('sw', 'investment_tips', 'paragraph', 'Nilianza kuweka akiba ya 10% ya mapato yangu kila mwezi. Baada ya miaka miwili, nilikuwa na kutosha kuanzisha biashara ndogo inayosaidia familia yangu.', 'approved', NOW(), NOW()),
('en', 'investment_tips', 'lesson', 'I learned that consistency in saving matters more than the amount. Small daily habits build wealth over time.', 'approved', NOW(), NOW()),
('sw', 'investment_tips', 'lesson', 'Nimejifunza kuwa uthabiti wa kuweka akiba ni muhimu kuliko kiasi. Tabia ndogo za kila siku hujenga utajiri baada ya muda.', 'approved', NOW(), NOW()),
('en', 'investment_tips', 'question', 'What is the best way to start investing with a small income in Africa?', 'approved', NOW(), NOW()),
('sw', 'investment_tips', 'question', 'Njia bora ya kuanza kuwekeza na mapato madogo Afrika ni ipi?', 'approved', NOW(), NOW()),

-- Add question format samples for existing categories
('en', 'life', 'question', 'What is one lesson life has taught you that you wish you knew earlier?', 'approved', NOW(), NOW()),
('sw', 'life', 'question', 'Somo moja ambalo maisha yamekufundisha ambalo ungetamani kulijua mapema ni lipi?', 'approved', NOW(), NOW()),
('en', 'faith', 'question', 'How do you maintain your faith during the hardest seasons of life?', 'approved', NOW(), NOW()),
('sw', 'faith', 'question', 'Unafanyaje kudumisha imani yako wakati wa majira magumu zaidi ya maisha?', 'approved', NOW(), NOW()),
('en', 'education', 'question', 'What advice would you give to someone who cannot afford formal education?', 'approved', NOW(), NOW()),
('sw', 'education', 'question', 'Ni ushauri gani ungempa mtu ambaye hawezi kumudu elimu rasmi?', 'approved', NOW(), NOW()),
('en', 'finance', 'question', 'How do you manage finances when your income is irregular or seasonal?', 'approved', NOW(), NOW()),
('sw', 'finance', 'question', 'Unasimamia fedha vipi wakati mapato yako si ya kawaida au ni ya msimu?', 'approved', NOW(), NOW()),
('en', 'encouragement', 'question', 'What keeps you going when you feel like giving up on everything?', 'approved', NOW(), NOW()),
('sw', 'encouragement', 'question', 'Ni nini kinachokusukuma mbele unapohisi kutaka kuacha kila kitu?', 'approved', NOW(), NOW()),
('en', 'mental_health', 'question', 'What is one small thing you do daily that helps your mental health?', 'approved', NOW(), NOW()),
('sw', 'mental_health', 'question', 'Ni kitu gani kidogo unachokifanya kila siku kinachosaidia afya yako ya akili?', 'approved', NOW(), NOW()),
('en', 'marriage', 'question', 'What is the most important thing you have learned about making a marriage work?', 'approved', NOW(), NOW()),
('sw', 'marriage', 'question', 'Ni jambo gani muhimu zaidi umejifunza kuhusu kufanya ndoa ifanye kazi?', 'approved', NOW(), NOW());

-- ------------------------------------------------------------
-- Migration Complete
-- ------------------------------------------------------------
