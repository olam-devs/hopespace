<?php
/**
 * Application Bootstrap
 * Space of Hope Platform
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone
date_default_timezone_set('Africa/Dar_es_Salaam');

// Base paths
define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Base URL (adjust for your environment)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $protocol . '://' . $host . '/SpaceofHope/public');

// Load database
require_once APP_PATH . '/config/database.php';

// Load helpers
require_once APP_PATH . '/config/helpers.php';

// Load language
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
if (!in_array($lang, ['en', 'sw'])) {
    $lang = 'en';
}
$_SESSION['lang'] = $lang;

$translations = require APP_PATH . '/i18n/' . $lang . '.php';

/**
 * Translate a key
 */
function __($key) {
    global $translations;
    return $translations[$key] ?? $key;
}

/**
 * Get current language
 */
function currentLang() {
    return $_SESSION['lang'] ?? 'en';
}

/**
 * Generate URL with current language
 */
function url($path) {
    $lang = currentLang();
    return BASE_URL . '/' . ltrim($path, '/') . (strpos($path, '?') !== false ? '&' : '?') . 'lang=' . $lang;
}
