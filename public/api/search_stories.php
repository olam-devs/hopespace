<?php
/**
 * Story Search API — autocomplete endpoint
 * Returns JSON: { results: [{title, slug, author, language}] }
 */
require_once __DIR__ . '/../../app/config/init.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

$q    = trim($_GET['q'] ?? '');
$lang = $_GET['lang'] ?? '';

if (mb_strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$db = getDB();

$where  = ["s.status = 'approved'"];
$params = [];

if ($lang && in_array($lang, ['en', 'sw'])) {
    $where[] = "s.language = ?";
    $params[] = $lang;
}

// Match story title OR author username
$where[] = "(s.title LIKE ? OR u.username LIKE ?)";
$params[] = '%' . $q . '%';
$params[] = '%' . $q . '%';

$sql = "SELECT s.title, s.slug, s.language, u.username AS author
        FROM stories s
        JOIN users u ON u.id = s.author_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY
            CASE WHEN s.title LIKE ? THEN 0 ELSE 1 END,
            s.created_at DESC
        LIMIT 8";

$params[] = $q . '%'; // prefix match for sorting priority

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$results = [];
foreach ($rows as $r) {
    $results[] = [
        'title'    => $r['title'],
        'slug'     => $r['slug'],
        'author'   => $r['author'],
        'language' => $r['language'],
    ];
}

echo json_encode(['results' => $results]);
