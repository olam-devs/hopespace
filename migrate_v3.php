<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

$statements = [
    "CREATE TABLE IF NOT EXISTS `site_settings` (
        `setting_key` VARCHAR(100) PRIMARY KEY,
        `setting_value` TEXT NULL,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('site_logo', NULL)",

    "CREATE TABLE IF NOT EXISTS `page_visits` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `page` VARCHAR(100) NOT NULL,
        `ip_hash` VARCHAR(64) NOT NULL,
        `visited_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_visited_at (`visited_at`),
        INDEX idx_page (`page`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

$count = 0;
foreach ($statements as $sql) {
    try {
        $db->exec($sql);
        $count++;
        echo "<p style='color:green'>OK</p>";
    } catch (PDOException $e) {
        echo "<p style='color:orange'>Skipped: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Migration v3 Complete! ($count statements)</h2>";
echo "<p><strong>DELETE this file now!</strong></p>";
