<?php
/**
 * Helper Functions
 */

/**
 * Generate CSRF token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF hidden input field
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

/**
 * Sanitize output
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to URL
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Set flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Check if admin is logged in
 */
function isAdmin(): bool {
    return !empty($_SESSION['admin_id']);
}

/**
 * Require admin login
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        redirect(url('admin.php?action=login'));
    }
}

/**
 * Get list of categories
 */
function getCategories(): array {
    return ['life', 'faith', 'education', 'finance', 'encouragement', 'marriage', 'mental_health', 'love', 'investment_tips'];
}

/**
 * Get list of message formats
 */
function getFormats(): array {
    return ['quote', 'paragraph', 'lesson', 'question'];
}
