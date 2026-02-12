-- Hope Space v2 Migration: Resources & Partners tables

CREATE TABLE IF NOT EXISTS `resource_categories` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resources` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `phone` VARCHAR(100) NULL,
    `logo` VARCHAR(255) NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `resource_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `partners` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `website` VARCHAR(255) NULL,
    `image` VARCHAR(255) NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default resource categories
INSERT INTO `resource_categories` (`name`, `sort_order`) VALUES
('Hospitals & Clinics', 1),
('Counselors & Helplines', 2),
('NGOs & Organizations', 3),
('Emergency Contacts', 4);

-- Seed existing resources
INSERT INTO `resources` (`category_id`, `name`, `description`, `phone`, `sort_order`) VALUES
(1, 'Muhimbili National Hospital', 'Mental Health Unit, Dar es Salaam', '+255 22 215 1599', 1),
(1, 'Mirembe National Mental Hospital', 'Dodoma, Tanzania', '+255 26 232 1831', 2),
(1, 'KCMC - Kilimanjaro Christian Medical Centre', 'Moshi, Kilimanjaro', '+255 27 275 4377', 3),
(2, 'CCBRT Mental Health Services', 'Dar es Salaam — Counseling & psychiatric care', '+255 22 260 1556', 1),
(2, 'Befrienders Tanzania', 'Emotional support & crisis helpline', '+255 22 266 4478', 2),
(3, 'BasicNeeds Tanzania', 'Community-based mental health support and advocacy', NULL, 1),
(3, 'MEHATA', 'Mental Health Association of Tanzania — Awareness, education, and support', NULL, 2),
(3, 'Tanzania Red Cross Society', 'Psychosocial support and emergency response', '+255 22 211 6610', 3),
(4, 'Emergency Services (Tanzania)', '114 — Police | 115 — Fire & Rescue | 112 — General Emergency', '112', 1);
