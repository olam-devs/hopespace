<?php
/**
 * Admin — Story & Testimony Moderation
 */
require_once __DIR__ . '/../../app/config/init.php';

if (!isAdmin()) {
    redirect(url('admin/login.php'));
}

$db  = getDB();
$tab = $_GET['tab'] ?? 'pending';

// --- Handle Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
        redirect(BASE_URL . '/admin/stories.php?tab=' . $tab);
    }

    $action = $_POST['action'] ?? '';

    // Story Part moderation
    if (in_array($action, ['approve_part', 'reject_part']) && !empty($_POST['part_id'])) {
        $partId    = (int)$_POST['part_id'];
        $newStatus = $action === 'approve_part' ? 'approved' : 'rejected';

        $db->prepare("UPDATE story_parts SET status = ? WHERE id = ?")->execute([$newStatus, $partId]);

        // If approving and story itself is still pending, approve the story too
        if ($newStatus === 'approved') {
            $partRow = $db->prepare("SELECT story_id FROM story_parts WHERE id = ?");
            $partRow->execute([$partId]);
            $partRow = $partRow->fetch();
            if ($partRow) {
                $db->prepare("UPDATE stories SET status = 'approved' WHERE id = ? AND status = 'pending'")
                   ->execute([$partRow['story_id']]);
            }
        }

        $db->prepare("INSERT INTO audit_log (admin_id, message_id, action, details, created_at) VALUES (?, 0, ?, ?, NOW())")
           ->execute([$_SESSION['admin_id'], 'edited', 'Story part ' . $partId . ' ' . $newStatus]);

        setFlash('success', 'Part ' . $newStatus . ' successfully.');
        redirect(BASE_URL . '/admin/stories.php?tab=parts');
    }

    // Story moderation
    if (in_array($action, ['approve_story', 'reject_story']) && !empty($_POST['story_id'])) {
        $storyId   = (int)$_POST['story_id'];
        $newStatus = $action === 'approve_story' ? 'approved' : 'rejected';
        $db->prepare("UPDATE stories SET status = ? WHERE id = ?")->execute([$newStatus, $storyId]);
        setFlash('success', 'Story ' . $newStatus . '.');
        redirect(BASE_URL . '/admin/stories.php?tab=stories');
    }

    // Admin: Delete story entirely
    if ($action === 'delete_story' && !empty($_POST['story_id'])) {
        $storyId = (int)$_POST['story_id'];
        $db->prepare("DELETE FROM story_parts WHERE story_id = ?")->execute([$storyId]);
        $db->prepare("DELETE FROM stories WHERE id = ?")->execute([$storyId]);
        $db->prepare("INSERT INTO audit_log (admin_id, message_id, action, details, created_at) VALUES (?, 0, ?, ?, NOW())")
           ->execute([$_SESSION['admin_id'], 'deleted', 'Story ' . $storyId . ' and all parts deleted by admin']);
        setFlash('success', 'Story deleted permanently.');
        redirect(BASE_URL . '/admin/stories.php?tab=stories');
    }

    // Admin: Delete single story part
    if ($action === 'delete_part' && !empty($_POST['part_id'])) {
        $partId = (int)$_POST['part_id'];
        $db->prepare("DELETE FROM story_parts WHERE id = ?")->execute([$partId]);
        $db->prepare("INSERT INTO audit_log (admin_id, message_id, action, details, created_at) VALUES (?, 0, ?, ?, NOW())")
           ->execute([$_SESSION['admin_id'], 'deleted', 'Story part ' . $partId . ' deleted by admin']);
        setFlash('success', 'Part deleted.');
        redirect(BASE_URL . '/admin/stories.php?tab=parts');
    }

    // Testimony moderation
    if (in_array($action, ['approve_testimony', 'reject_testimony']) && !empty($_POST['testimony_id'])) {
        $tId       = (int)$_POST['testimony_id'];
        $newStatus = $action === 'approve_testimony' ? 'approved' : 'rejected';
        $db->prepare("UPDATE testimonies SET status = ? WHERE id = ?")->execute([$newStatus, $tId]);
        setFlash('success', 'Testimony ' . $newStatus . '.');
        redirect(BASE_URL . '/admin/stories.php?tab=testimonies');
    }
}

// --- Fetch data by tab ---
$pendingPartsCount       = $db->query("SELECT COUNT(*) FROM story_parts WHERE status = 'pending'")->fetchColumn();
$pendingStoriesCount     = $db->query("SELECT COUNT(*) FROM stories WHERE status = 'pending'")->fetchColumn();
$pendingTestimoniesCount = $db->query("SELECT COUNT(*) FROM testimonies WHERE status = 'pending'")->fetchColumn();

// Total reads across all stories (graceful if table missing)
$totalStoryReads = 0;
try {
    $totalStoryReads = $db->query("SELECT COUNT(*) FROM story_reads")->fetchColumn();
} catch (Exception $e) {}

// Per-story read counts map (story_id => count)
$storyReadCounts = [];
$partReadCounts  = [];
try {
    foreach ($db->query("SELECT story_id, COUNT(*) AS cnt FROM story_reads GROUP BY story_id")->fetchAll() as $r) {
        $storyReadCounts[(int)$r['story_id']] = (int)$r['cnt'];
    }
    foreach ($db->query("SELECT part_id, COUNT(*) AS cnt FROM story_reads WHERE part_id IS NOT NULL GROUP BY part_id")->fetchAll() as $r) {
        $partReadCounts[(int)$r['part_id']] = (int)$r['cnt'];
    }
} catch (Exception $e) {}

$items = [];
if ($tab === 'parts') {
    $stmt = $db->query("
        SELECT sp.*, s.title AS story_title, s.id AS s_id, u.username AS author_username
        FROM story_parts sp
        JOIN stories s ON s.id = sp.story_id
        JOIN users u ON u.id = s.author_id
        ORDER BY FIELD(sp.status,'pending','approved','rejected'), sp.created_at DESC
        LIMIT 100
    ");
    $items = $stmt->fetchAll();
} elseif ($tab === 'stories') {
    $stmt = $db->query("
        SELECT s.*, u.username AS author_username,
               COUNT(sp.id) AS total_parts,
               SUM(CASE WHEN sp.status='approved' THEN 1 ELSE 0 END) AS approved_parts,
               SUM(CASE WHEN sp.status='pending'  THEN 1 ELSE 0 END) AS pending_parts
        FROM stories s
        JOIN users u ON u.id = s.author_id
        LEFT JOIN story_parts sp ON sp.story_id = s.id
        GROUP BY s.id
        ORDER BY FIELD(s.status,'pending','approved','rejected'), s.created_at DESC
        LIMIT 100
    ");
    $items = $stmt->fetchAll();
} elseif ($tab === 'testimonies') {
    $items = $db->query("SELECT * FROM testimonies ORDER BY FIELD(status,'pending','approved','rejected'), created_at DESC LIMIT 100")->fetchAll();
} else {
    // Default: pending parts
    $tab = 'parts';
    $stmt = $db->query("
        SELECT sp.*, s.title AS story_title, s.id AS s_id, u.username AS author_username
        FROM story_parts sp
        JOIN stories s ON s.id = sp.story_id
        JOIN users u ON u.id = s.author_id
        WHERE sp.status = 'pending'
        ORDER BY sp.created_at ASC
        LIMIT 100
    ");
    $items = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stories Admin — Hope Space</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { background: var(--bg); }
        .admin-wrap { max-width: 1100px; margin: 0 auto; padding: 2rem 1rem; }
        .admin-nav { display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
        .admin-nav a { padding:.5rem 1rem; border-radius:8px; background:var(--bg-card); border:1px solid var(--border); font-weight:600; text-decoration:none; color:var(--text); }
        .admin-nav a.active { background:var(--primary); color:#fff; border-color:var(--primary); }
        .badge-count { background:var(--danger); color:#fff; border-radius:20px; padding:1px 7px; font-size:12px; margin-left:4px; }
        .item-card { background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:1.5rem; margin-bottom:1rem; }
        .item-meta { font-size:13px; color:var(--text-muted); margin-bottom:.5rem; display:flex; gap:1rem; flex-wrap:wrap; align-items:center; }
        .item-title { font-weight:700; font-size:1.1rem; margin-bottom:.5rem; }
        .item-content { max-height:200px; overflow:hidden; position:relative; font-size:.95rem; line-height:1.7; }
        .item-content.expanded { max-height:none; }
        .item-actions { display:flex; gap:.75rem; margin-top:1rem; flex-wrap:wrap; }
        .status-pending  { color:#b45309; font-weight:600; background:#fff3cd; padding:2px 8px; border-radius:20px; font-size:12px; }
        .status-approved { color:#155724; font-weight:600; background:#d4edda; padding:2px 8px; border-radius:20px; font-size:12px; }
        .status-rejected { color:#721c24; font-weight:600; background:#f8d7da; padding:2px 8px; border-radius:20px; font-size:12px; }
        .read-pill { background:#e0f2fe; color:#075985; font-weight:700; padding:3px 10px; border-radius:20px; font-size:12px; white-space:nowrap; }
        .stats-bar { display:flex; gap:1.5rem; background:var(--bg-card); border:1px solid var(--border-light); border-radius:12px; padding:1rem 1.5rem; margin-bottom:1.5rem; flex-wrap:wrap; }
        .stat-box { text-align:center; }
        .stat-box .num { font-size:1.6rem; font-weight:800; color:var(--primary); line-height:1; }
        .stat-box .lbl { font-size:0.75rem; color:var(--text-muted); margin-top:2px; }
    </style>
</head>
<body>
<div class="admin-wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
        <h1>Stories &amp; Content Admin</h1>
        <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-secondary btn-sm">&larr; Main Admin</a>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
        <div class="flash flash-<?= $flash['type'] ?>"> <?= e($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Summary Stats -->
    <div class="stats-bar">
        <div class="stat-box">
            <div class="num"><?= number_format((int)$totalStoryReads) ?></div>
            <div class="lbl">Total Story Reads</div>
        </div>
        <div class="stat-box">
            <div class="num"><?= (int)$db->query("SELECT COUNT(*) FROM stories WHERE status='approved'")->fetchColumn() ?></div>
            <div class="lbl">Published Stories</div>
        </div>
        <div class="stat-box">
            <div class="num"><?= (int)$db->query("SELECT COUNT(*) FROM story_parts WHERE status='approved'")->fetchColumn() ?></div>
            <div class="lbl">Published Parts</div>
        </div>
        <div class="stat-box">
            <div class="num"><?= (int)$db->query("SELECT COUNT(DISTINCT author_id) FROM stories")->fetchColumn() ?></div>
            <div class="lbl">Authors</div>
        </div>
        <div class="stat-box">
            <div class="num"><?= (int)$db->query("SELECT COUNT(*) FROM testimonies WHERE status='approved'")->fetchColumn() ?></div>
            <div class="lbl">Testimonies</div>
        </div>
    </div>

    <div class="admin-nav">
        <a href="?tab=parts&lang=<?= currentLang() ?>" class="<?= $tab === 'parts' ? 'active' : '' ?>">
            Story Parts <?php if ($pendingPartsCount): ?><span class="badge-count"><?= $pendingPartsCount ?></span><?php endif; ?>
        </a>
        <a href="?tab=stories&lang=<?= currentLang() ?>" class="<?= $tab === 'stories' ? 'active' : '' ?>">
            Stories <?php if ($pendingStoriesCount): ?><span class="badge-count"><?= $pendingStoriesCount ?></span><?php endif; ?>
        </a>
        <a href="?tab=testimonies&lang=<?= currentLang() ?>" class="<?= $tab === 'testimonies' ? 'active' : '' ?>">
            Testimonies <?php if ($pendingTestimoniesCount): ?><span class="badge-count"><?= $pendingTestimoniesCount ?></span><?php endif; ?>
        </a>
    </div>

    <?php if (empty($items)): ?>
        <div class="card text-center" style="padding:3rem;">
            <p class="text-muted">No items to review.</p>
        </div>
    <?php else: ?>

        <?php if ($tab === 'parts'): ?>
            <?php foreach ($items as $part): ?>
                <?php $partReads = $partReadCounts[(int)$part['id']] ?? 0; ?>
                <div class="item-card">
                    <div class="item-meta">
                        <span>Story: <strong><?= e($part['story_title']) ?></strong></span>
                        <span>Author: <strong><?= e($part['author_username']) ?></strong></span>
                        <span>Part: <strong><?= $part['part_number'] ?></strong></span>
                        <?php if ($part['is_last_part']): ?><span style="color:var(--success);font-size:12px;">&#9989; Final Part</span><?php endif; ?>
                        <span class="status-<?= $part['status'] ?>"><?= ucfirst($part['status']) ?></span>
                        <span class="read-pill">&#128065; <?= number_format($partReads) ?> reads</span>
                        <span style="margin-left:auto;font-size:12px;"><?= date('M j, Y g:i a', strtotime($part['created_at'])) ?></span>
                    </div>
                    <?php if ($part['part_title']): ?><div class="item-title"><?= e($part['part_title']) ?></div><?php endif; ?>
                    <div class="item-content" id="part-content-<?= $part['id'] ?>">
                        <?= strip_tags($part['content'], '<p><br><strong><em><b><i><h2><h3><h4><ul><ol><li><blockquote><hr>') ?>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm mt-1"
                            onclick="toggleContent('part-content-<?= $part['id'] ?>', this)">Read more</button>
                    <div class="item-actions">
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="part_id" value="<?= $part['id'] ?>">
                            <button type="submit" name="action" value="approve_part" class="btn btn-primary btn-sm">&#10003; Approve</button>
                        </form>
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="part_id" value="<?= $part['id'] ?>">
                            <button type="submit" name="action" value="reject_part" class="btn btn-sm" style="background:var(--danger);color:#fff;">&#10007; Reject</button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Permanently delete this story part?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="part_id" value="<?= $part['id'] ?>">
                            <button type="submit" name="action" value="delete_part" class="btn btn-sm" style="background:#6b0f1a;color:#fff;">&#128465; Delete Part</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php elseif ($tab === 'stories'): ?>
            <?php foreach ($items as $story): ?>
                <?php $storyReads = $storyReadCounts[(int)$story['id']] ?? 0; ?>
                <div class="item-card">
                    <div class="item-meta">
                        <span>Author: <strong><?= e($story['author_username']) ?></strong></span>
                        <span><?= e(__('lang_' . $story['language'])) ?></span>
                        <span><?= $story['story_type'] === 'full' ? 'Full Story' : 'Multi-Part' ?></span>
                        <?php if ($story['story_type'] === 'parts'): ?>
                            <span><?= (int)$story['approved_parts'] ?> published / <?= (int)$story['total_parts'] ?> total parts</span>
                        <?php endif; ?>
                        <span class="status-<?= $story['status'] ?>"><?= ucfirst($story['status']) ?></span>
                        <span class="read-pill">&#128065; <?= number_format($storyReads) ?> reads</span>
                        <span style="margin-left:auto;font-size:12px;"><?= date('M j, Y', strtotime($story['created_at'])) ?></span>
                    </div>
                    <div class="item-title"><?= e($story['title']) ?></div>
                    <p style="color:var(--text-light);font-size:.9rem;margin:.25rem 0 .75rem;"><?= e($story['description']) ?></p>
                    <div class="item-actions">
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="story_id" value="<?= $story['id'] ?>">
                            <button type="submit" name="action" value="approve_story" class="btn btn-primary btn-sm">&#10003; Approve Story</button>
                        </form>
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="story_id" value="<?= $story['id'] ?>">
                            <button type="submit" name="action" value="reject_story" class="btn btn-sm" style="background:var(--danger);color:#fff;">&#10007; Reject</button>
                        </form>
                        <?php if ($story['status'] === 'approved'): ?>
                            <a href="<?= BASE_URL ?>/story.php?slug=<?= urlencode($story['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm">&#128279; View Live</a>
                        <?php endif; ?>
                        <form method="POST" onsubmit="return confirm('Permanently delete this story and ALL its parts?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="story_id" value="<?= $story['id'] ?>">
                            <button type="submit" name="action" value="delete_story" class="btn btn-sm" style="background:#6b0f1a;color:#fff;">&#128465; Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php elseif ($tab === 'testimonies'): ?>
            <?php foreach ($items as $t): ?>
                <div class="item-card">
                    <div class="item-meta">
                        <span>By: <strong><?= $t['alias'] ? e($t['alias']) : 'Anonymous' ?></strong></span>
                        <span>Language: <?= e(__('lang_' . $t['language'])) ?></span>
                        <span class="status-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
                        <span><?= date('M j, Y', strtotime($t['created_at'])) ?></span>
                    </div>
                    <p style="line-height:1.7;"><?= nl2br(e($t['content'])) ?></p>
                    <div class="item-actions">
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="testimony_id" value="<?= $t['id'] ?>">
                            <button type="submit" name="action" value="approve_testimony" class="btn btn-primary btn-sm">&#10003; Approve</button>
                        </form>
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="testimony_id" value="<?= $t['id'] ?>">
                            <button type="submit" name="action" value="reject_testimony" class="btn btn-sm" style="background:var(--danger);color:#fff;">&#10007; Reject</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script>
function toggleContent(id, btn) {
    const el = document.getElementById(id);
    el.classList.toggle('expanded');
    btn.textContent = el.classList.contains('expanded') ? 'Show less' : 'Read more';
}
</script>
</body>
</html>
