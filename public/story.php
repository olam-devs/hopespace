<?php
/**
 * Story Reader Page
 * Reads a story by slug. Supports multi-part navigation.
 */
require_once __DIR__ . '/../app/config/init.php';

$db   = getDB();
$slug = trim($_GET['slug'] ?? '');
$requestedPart = max(1, (int)($_GET['part'] ?? 1));

if (!$slug) {
    redirect(url('stories.php'));
}

// Fetch story
$storyStmt = $db->prepare("
    SELECT s.*, u.username AS author_username
    FROM stories s
    JOIN users u ON u.id = s.author_id
    WHERE s.slug = ? AND s.status = 'approved'
");
$storyStmt->execute([$slug]);
$story = $storyStmt->fetch();

if (!$story) {
    redirect(url('stories.php'));
}

// Fetch approved parts
$partsStmt = $db->prepare("
    SELECT * FROM story_parts
    WHERE story_id = ? AND status = 'approved'
    ORDER BY part_number ASC
");
$partsStmt->execute([$story['id']]);
$allParts = $partsStmt->fetchAll();

if (empty($allParts)) {
    redirect(url('stories.php'));
}

// Determine which part to show
$currentPart = null;
foreach ($allParts as $p) {
    if ((int)$p['part_number'] === $requestedPart) {
        $currentPart = $p;
        break;
    }
}
// If requested part not found, show first
if (!$currentPart) {
    $currentPart = $allParts[0];
    $requestedPart = (int)$currentPart['part_number'];
}

// Find next/prev parts
$nextPart = null;
$prevPart = null;
foreach ($allParts as $p) {
    if ((int)$p['part_number'] === $requestedPart + 1) $nextPart = $p;
    if ((int)$p['part_number'] === $requestedPart - 1) $prevPart = $p;
}

$isLastApprovedPart = ((int)$currentPart['is_last_part'] === 1);
$isFullStory        = $story['story_type'] === 'full';
$isSinglePart       = count($allParts) === 1;

$pageTitle = e($story['title']) . ($isFullStory ? '' : ' — ' . __('story_part_label') . ' ' . $requestedPart);

// --- Track this read (privacy-safe: hashed IP + date, one entry per IP per part per day) ---
try {
    $ipHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . date('Y-m-d') . $currentPart['id']);
    // Only insert if this IP hasn't already read this part today
    $dupCheck = $db->prepare("SELECT id FROM story_reads WHERE story_id = ? AND part_id = ? AND ip_hash = ? AND DATE(created_at) = CURDATE()");
    $dupCheck->execute([$story['id'], $currentPart['id'], $ipHash]);
    if (!$dupCheck->fetch()) {
        $db->prepare("INSERT INTO story_reads (story_id, part_id, part_number, ip_hash, language, created_at) VALUES (?, ?, ?, ?, ?, NOW())")
           ->execute([$story['id'], $currentPart['id'], $requestedPart, $ipHash, currentLang()]);
    }
} catch (Exception $e) {
    // Silently fail if table not yet created
}

/**
 * Sanitize story HTML for display — allow only safe formatting tags
 */
function sanitizeStoryHtml(string $html): string {
    return strip_tags($html, '<p><br><strong><em><b><i><h2><h3><h4><ul><ol><li><blockquote><hr>');
}

ob_start();
?>

<div class="container">
    <div class="story-reader">

        <!-- Breadcrumb -->
        <div class="story-breadcrumb">
            <a href="<?= url('stories.php') ?>">&larr; <?= e(__('story_back_to_stories')) ?></a>
            <?php if ($story['story_type'] === 'parts' && count($allParts) > 1): ?>
                <span class="breadcrumb-sep">/</span>
                <a href="<?= url('story.php?slug=' . urlencode($slug)) ?>"><?= e($story['title']) ?></a>
                <span class="breadcrumb-sep">/</span>
                <span><?= e(__('story_part_label')) ?> <?= $requestedPart ?></span>
            <?php endif; ?>
        </div>

        <!-- Story Header -->
        <div class="story-header">
            <div class="story-header-badges">
                <span class="badge badge-language"><?= e(__('lang_' . $story['language'])) ?></span>
                <?php if ($story['story_type'] === 'parts'): ?>
                    <span class="badge badge-format"><?= e(__('story_type_parts')) ?></span>
                <?php else: ?>
                    <span class="badge badge-format"><?= e(__('story_type_full')) ?></span>
                <?php endif; ?>
            </div>
            <h1 class="story-reader-title"><?= e($story['title']) ?></h1>
            <?php if (!$isFullStory && !$isSinglePart): ?>
                <p class="story-part-label"><?= e(__('story_part_label')) ?> <?= $requestedPart ?><?php if ($currentPart['part_title']): ?> — <?= e($currentPart['part_title']) ?><?php endif; ?></p>
            <?php endif; ?>
            <p class="story-reader-author">
                <?= e(__('story_published_by')) ?>
                <a href="<?= BASE_URL ?>/stories.php?filter_author=<?= urlencode($story['author_username']) ?>&lang=<?= currentLang() ?>" class="author-link">
                    <?= e($story['author_username']) ?>
                </a>
            </p>

            <?php if ($story['description'] && $requestedPart === 1): ?>
                <p class="story-description-text"><?= e($story['description']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Table of Contents for multi-part -->
        <?php if ($story['story_type'] === 'parts' && count($allParts) > 1): ?>
            <details class="story-toc">
                <summary><?= e(__('story_toc')) ?></summary>
                <ul>
                    <?php foreach ($allParts as $p): ?>
                        <li class="<?= (int)$p['part_number'] === $requestedPart ? 'toc-active' : '' ?>">
                            <a href="<?= BASE_URL ?>/story.php?slug=<?= urlencode($slug) ?>&part=<?= $p['part_number'] ?>&lang=<?= currentLang() ?>">
                                <?= e(__('story_part_label')) ?> <?= $p['part_number'] ?>
                                <?php if ($p['part_title']): ?> — <?= e($p['part_title']) ?><?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endif; ?>

        <!-- Story Content -->
        <div class="story-content">
            <?= sanitizeStoryHtml($currentPart['content']) ?>
        </div>

        <!-- THE END / Next Part Section -->
        <div class="story-end-section">
            <?php if ($isLastApprovedPart || ($isFullStory && $isSinglePart)): ?>
                <!-- Story is complete — show THE END -->
                <div class="the-end-banner">
                    <span class="the-end-text"><?= e(__('story_the_end')) ?></span>
                </div>
            <?php elseif ($nextPart): ?>
                <!-- There is a next approved part -->
                <div class="next-part-cta">
                    <a href="<?= BASE_URL ?>/story.php?slug=<?= urlencode($slug) ?>&part=<?= $nextPart['part_number'] ?>&lang=<?= currentLang() ?>" class="btn btn-primary btn-lg">
                        <?= e(__('story_next_part_btn')) ?> <?= $nextPart['part_number'] ?>
                        <?php if ($nextPart['part_title']): ?> — <?= e($nextPart['part_title']) ?><?php endif; ?>
                        &rarr;
                    </a>
                </div>
            <?php else: ?>
                <!-- More parts coming -->
                <div class="more-coming-banner">
                    <p><?= e(__('story_no_more_parts')) ?></p>
                </div>
            <?php endif; ?>

            <!-- Next Release Announcement -->
            <?php if ($story['next_release_title'] || $story['next_release_note']): ?>
                <div class="next-release-card card">
                    <div class="next-release-header"><?= e(__('story_coming_next')) ?></div>
                    <?php if ($story['next_release_title']): ?>
                        <h3 class="next-release-title"><?= e($story['next_release_title']) ?></h3>
                    <?php endif; ?>
                    <?php if ($story['next_release_date']): ?>
                        <p class="next-release-date">
                            <?= e(__('story_coming_date')) ?>: <strong><?= date('F j, Y', strtotime($story['next_release_date'])) ?></strong>
                        </p>
                    <?php endif; ?>
                    <?php if ($story['next_release_note']): ?>
                        <p class="next-release-note"><?= e($story['next_release_note']) ?></p>
                    <?php endif; ?>
                    <p class="next-release-author">&mdash; <?= e($story['author_username']) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Part Navigation -->
        <?php if ($story['story_type'] === 'parts' && count($allParts) > 1): ?>
            <div class="part-navigation">
                <?php if ($prevPart): ?>
                    <a href="<?= BASE_URL ?>/story.php?slug=<?= urlencode($slug) ?>&part=<?= $prevPart['part_number'] ?>&lang=<?= currentLang() ?>" class="btn btn-secondary">
                        &larr; <?= e(__('story_part_label')) ?> <?= $prevPart['part_number'] ?>
                    </a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>
                <?php if ($nextPart): ?>
                    <a href="<?= BASE_URL ?>/story.php?slug=<?= urlencode($slug) ?>&part=<?= $nextPart['part_number'] ?>&lang=<?= currentLang() ?>" class="btn btn-primary">
                        <?= e(__('story_part_label')) ?> <?= $nextPart['part_number'] ?> &rarr;
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="story-back-link">
            <a href="<?= url('stories.php') ?>" class="btn btn-secondary">&larr; <?= e(__('story_back_to_stories')) ?></a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
