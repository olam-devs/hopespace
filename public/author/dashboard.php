<?php
/**
 * Author Dashboard — My Stories
 */
require_once __DIR__ . '/../../app/config/init.php';
require_once APP_PATH . '/middleware/Auth.php';

$pageTitle = __('author_dashboard_title');

// Require auth
if (!Auth::isAuthenticated()) {
    redirect(url('author/login.php'));
}

$db     = getDB();
$userId = Auth::getCurrentUserId();

// Verify user is an author
$chk = $db->prepare("SELECT username, is_author FROM users WHERE id = ? AND is_active = 1");
$chk->execute([$userId]);
$authorRow = $chk->fetch();

if (!$authorRow || !$authorRow['is_author']) {
    setFlash('error', 'Your account does not have author access.');
    redirect(url('author/register.php'));
}

$authorUsername = $authorRow['username'];

// Fetch author's stories with part counts and total reads
$myStories = [];
try {
    $stories = $db->prepare("
        SELECT s.*,
               COUNT(DISTINCT sp.id)                                              AS total_parts,
               SUM(CASE WHEN sp.status = 'approved' THEN 1 ELSE 0 END)           AS approved_parts,
               SUM(CASE WHEN sp.status = 'pending'  THEN 1 ELSE 0 END)           AS pending_parts,
               COALESCE(SUM(CASE WHEN sp.status = 'approved' THEN sp.read_count ELSE 0 END), 0) AS total_reads
        FROM stories s
        LEFT JOIN (
            SELECT sp2.id, sp2.story_id, sp2.status,
                   COUNT(sr.id) AS read_count
            FROM story_parts sp2
            LEFT JOIN story_reads sr ON sr.part_id = sp2.id
            GROUP BY sp2.id
        ) sp ON sp.story_id = s.id
        WHERE s.author_id = ?
        GROUP BY s.id
        ORDER BY s.updated_at DESC
    ");
    $stories->execute([$userId]);
    $myStories = $stories->fetchAll();
} catch (Exception $e) {
    // Fallback without read counts (story_reads table may not exist yet)
    $stories = $db->prepare("
        SELECT s.*,
               COUNT(sp.id) AS total_parts,
               SUM(CASE WHEN sp.status = 'approved' THEN 1 ELSE 0 END) AS approved_parts,
               SUM(CASE WHEN sp.status = 'pending'  THEN 1 ELSE 0 END) AS pending_parts,
               0 AS total_reads
        FROM stories s
        LEFT JOIN story_parts sp ON sp.story_id = s.id
        WHERE s.author_id = ?
        GROUP BY s.id
        ORDER BY s.updated_at DESC
    ");
    $stories->execute([$userId]);
    $myStories = $stories->fetchAll();
}

ob_start();
?>

<div class="container">
    <div class="author-dashboard">

        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div>
                <h1><?= e(__('author_dashboard_title')) ?></h1>
                <p class="dashboard-welcome"><?= e(__('author_dashboard_welcome')) ?>, <strong><?= e($authorUsername) ?></strong></p>
            </div>
            <div class="dashboard-actions">
                <a href="<?= url('author/create_story.php') ?>" class="btn btn-primary">&#43; <?= e(__('author_new_story_btn')) ?></a>
                <a href="<?= url('author/logout.php') ?>" class="btn btn-secondary"><?= e(__('author_logout_btn')) ?></a>
            </div>
        </div>

        <?php if (empty($myStories)): ?>
            <div class="card text-center empty-state">
                <div class="empty-icon">&#128221;</div>
                <p class="text-muted"><?= e(__('author_no_stories')) ?></p>
                <a href="<?= url('author/create_story.php') ?>" class="btn btn-primary mt-2"><?= e(__('author_new_story_btn')) ?></a>
            </div>
        <?php else: ?>
            <div class="author-stories-list">
                <?php foreach ($myStories as $story): ?>
                    <?php
                    $statusClass = 'status-' . $story['status'];
                    if ($story['status'] === 'approved') {
                        $statusLabel = __('author_story_status_approved');
                    } elseif ($story['status'] === 'rejected') {
                        $statusLabel = __('author_story_status_rejected');
                    } else {
                        $statusLabel = __('author_story_status_pending');
                    }
                    $nextPartNum = (int)$story['total_parts'] + 1;
                    ?>
                    <div class="story-manage-card card">
                        <div class="story-manage-header">
                            <div>
                                <h3 class="story-manage-title"><?= e($story['title']) ?></h3>
                                <div class="story-manage-meta">
                                    <span class="badge badge-language"><?= e(__('lang_' . $story['language'])) ?></span>
                                    <span class="badge badge-format"><?= $story['story_type'] === 'full' ? e(__('story_type_full')) : e(__('story_type_parts')) ?></span>
                                    <span class="story-status <?= $statusClass ?>"><?= e($statusLabel) ?></span>
                                    <?php if ($story['story_type'] === 'parts'): ?>
                                        <span class="parts-count"><?= (int)$story['total_parts'] ?> <?= e(__('author_parts_written')) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="story-manage-actions">
                                <?php if ($story['status'] === 'approved'): ?>
                                    <a href="<?= BASE_URL ?>/story.php?slug=<?= urlencode($story['slug']) ?>&lang=<?= currentLang() ?>"
                                       class="btn btn-secondary btn-sm" target="_blank"><?= e(__('author_view_story')) ?></a>
                                <?php endif; ?>
                                <?php if ($story['story_type'] === 'parts'): ?>
                                    <a href="<?= url('author/write_part.php?story_id=' . $story['id']) ?>"
                                       class="btn btn-primary btn-sm">&#43; <?= e(__('author_add_next_part')) ?> <?= $nextPartNum ?></a>
                                <?php elseif ($story['total_parts'] == 0): ?>
                                    <a href="<?= url('author/write_part.php?story_id=' . $story['id']) ?>"
                                       class="btn btn-primary btn-sm">&#9997; Write Story</a>
                                <?php endif; ?>
                                <!-- Edit & Delete -->
                                <a href="<?= url('author/edit_story.php?story_id=' . $story['id']) ?>"
                                   class="btn btn-secondary btn-sm">&#9998; Edit</a>
                                <form method="POST" action="<?= url('author/edit_story.php?story_id=' . $story['id']) ?>"
                                      style="display:inline;"
                                      onsubmit="return confirm('Delete story &quot;<?= addslashes(e($story['title'])) ?>&quot;?\n\nAll parts will be permanently deleted. This cannot be undone.')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-sm"
                                            style="background:var(--danger,#dc3545);color:#fff;border-color:var(--danger,#dc3545);">&#128465; Delete</button>
                                </form>
                            </div>
                        </div>

                        <p class="story-manage-desc"><?= e(mb_strimwidth($story['description'], 0, 200, '…')) ?></p>

                        <!-- Read & Part Stats -->
                        <div class="story-stats-row">
                            <span class="story-stat-pill reads-pill">
                                &#128065; <strong><?= number_format((int)$story['total_reads']) ?></strong> reads
                            </span>
                            <?php if ($story['story_type'] === 'parts'): ?>
                                <span class="story-stat-pill parts-pill">
                                    &#128196; <strong><?= (int)$story['approved_parts'] ?></strong> published
                                    <?php if ($story['pending_parts'] > 0): ?>
                                        &middot; <span class="pending-dot">&#9203; <?= (int)$story['pending_parts'] ?> pending review</span>
                                    <?php endif; ?>
                                </span>
                            <?php elseif ($story['pending_parts'] > 0): ?>
                                <span class="story-stat-pill pending-pill">&#9203; Pending admin review</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
