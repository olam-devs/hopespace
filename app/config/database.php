<?php
/**
 * Database Configuration
 * Space of Hope - Bilingual Hope Message Platform
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'spaceofhope');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP has no password for root

/**
 * Get PDO database connection
 * Uses prepared statements for security
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('Database connection failed. Please check your configuration.');
        }
    }

    return $pdo;
}
