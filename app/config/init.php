<?php
/**
 * Application Bootstrap
 * Hope Space Platform
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

/**
 * Get site setting
 */
function getSetting($key) {
    static $cache = [];
    if (!isset($cache[$key])) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $cache[$key] = $stmt->fetchColumn() ?: null;
        } catch (Exception $e) {
            $cache[$key] = null;
        }
    }
    return $cache[$key];
}

/**
 * Track page visit (non-admin pages only)
 */
function trackVisit() {
    $page = basename($_SERVER['SCRIPT_NAME'], '.php');
    $adminPages = ['admin', 'admin_resources', 'admin_partners', 'admin_analytics', 'react'];
    if (in_array($page, $adminPages)) return;

    try {
        $db = getDB();
        $ipHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . date('Y-m-d'));
        $stmt = $db->prepare("INSERT INTO page_visits (page, ip_hash, visited_at) VALUES (?, ?, NOW())");
        $stmt->execute([$page, $ipHash]);
    } catch (Exception $e) {
        // Silently fail if table doesn't exist yet
    }
}

trackVisit();
