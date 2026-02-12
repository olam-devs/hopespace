<?php
/**
 * API endpoint for reactions (toggle: react / unreact)
 */
require_once __DIR__ . '/../app/config/init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$messageId = (int)($input['message_id'] ?? 0);
$type = $input['type'] ?? '';

if (!$messageId || !in_array($type, ['helped', 'hope', 'not_alone'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$db = getDB();

// Check message exists and is approved
$stmt = $db->prepare("SELECT id FROM messages WHERE id = ? AND status = 'approved'");
$stmt->execute([$messageId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Message not found']);
    exit;
}

// Toggle: if already reacted in session, unreact (delete); otherwise react (insert)
$sessionKey = "reacted_{$messageId}_{$type}";

if (!empty($_SESSION[$sessionKey])) {
    // Unreact — delete one matching reaction
    $stmt = $db->prepare("DELETE FROM reactions WHERE message_id = ? AND type = ? LIMIT 1");
    $stmt->execute([$messageId, $type]);
    unset($_SESSION[$sessionKey]);

    echo json_encode(['success' => true, 'action' => 'removed']);
} else {
    // React — insert
    $stmt = $db->prepare("INSERT INTO reactions (message_id, type, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$messageId, $type]);
    $_SESSION[$sessionKey] = true;

    echo json_encode(['success' => true, 'action' => 'added']);
}
