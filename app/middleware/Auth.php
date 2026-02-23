<?php
/**
 * Authentication Middleware
 * Space of Hope - Phase 2
 * Handles session-based authentication
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class Auth {
    private static $pdo = null;
    private static $session_lifetime = 86400; // 24 hours in seconds
    private static $session_lifetime_extended = 2592000; // 30 days (remember me)

    /**
     * Initialize session
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Lax');
            
            // Use HTTPS in production
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            
            session_start();
        }
    }

    /**
     * Get database connection
     */
    private static function getDB() {
        if (self::$pdo === null) {
            self::$pdo = getDB();
        }
        return self::$pdo;
    }

    /**
     * Login user and create session
     * @param int $user_id
     * @param bool $remember_me
     * @return bool Success status
     */
    public static function login($user_id, $remember_me = false) {
        self::startSession();
        
        try {
            // Generate secure session token
            $session_token = bin2hex(random_bytes(32));
            
            // Set expiry
            $lifetime = $remember_me ? self::$session_lifetime_extended : self::$session_lifetime;
            $expires_at = date('Y-m-d H:i:s', time() + $lifetime);
            
            // Get IP and user agent
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Save session to database
            $pdo = self::getDB();
            $stmt = $pdo->prepare("
                INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $session_token, $ip_address, $user_agent, $expires_at]);
            
            // Store in PHP session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['session_token'] = $session_token;
            $_SESSION['session_expires'] = $expires_at;
            
            return true;
            
        } catch (PDOException $e) {
            error_log('Login session error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Logout user and destroy session
     * @return bool Success status
     */
    public static function logout() {
        self::startSession();
        
        try {
            // Delete session from database
            if (isset($_SESSION['session_token'])) {
                $pdo = self::getDB();
                $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
                $stmt->execute([$_SESSION['session_token']]);
            }
            
            // Clear PHP session
            $_SESSION = [];
            
            // Destroy session cookie
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }
            
            // Destroy session
            session_destroy();
            
            return true;
            
        } catch (PDOException $e) {
            error_log('Logout error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is authenticated
     * @return bool
     */
    public static function isAuthenticated() {
        self::startSession();
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
            return false;
        }
        
        try {
            $pdo = self::getDB();
            $stmt = $pdo->prepare("
                SELECT user_id, expires_at 
                FROM user_sessions
                WHERE session_token = ? AND user_id = ?
            ");
            $stmt->execute([$_SESSION['session_token'], $_SESSION['user_id']]);
            $session = $stmt->fetch();
            
            if (!$session) {
                return false;
            }
            
            // Check if session expired
            if (strtotime($session['expires_at']) < time()) {
                self::logout();
                return false;
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log('Auth check error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Require authentication - redirect to login if not authenticated
     * @param string $redirect_url URL to redirect after login
     */
    public static function requireAuth($redirect_url = null) {
        if (!self::isAuthenticated()) {
            $current_url = $redirect_url ?? $_SERVER['REQUEST_URI'] ?? '/';
            $lang = $_GET['lang'] ?? 'en';
            header("Location: /SpaceofHope/public/login.php?lang={$lang}&redirect=" . urlencode($current_url));
            exit;
        }
    }

    /**
     * Get current logged-in user
     * @return array|null User data or null
     */
    public static function getCurrentUser() {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        $userModel = new User();
        return $userModel->getById($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     * @return int|null
     */
    public static function getCurrentUserId() {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Clean up expired sessions (call periodically via cron or on login/logout)
     */
    public static function cleanupExpiredSessions() {
        try {
            $pdo = self::getDB();
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
            $stmt->execute();
        } catch (PDOException $e) {
            error_log('Session cleanup error: ' . $e->getMessage());
        }
    }
}
