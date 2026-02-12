<?php
/**
 * Homepage - Space of Hope
 */
require_once __DIR__ . '/../app/config/init.php';

$pageTitle = __('home');

// Fetch latest approved messages (limit 6)
$db = getDB();
$stmt = $db->query("SELECT * FROM messages WHERE status = 'approved' ORDER BY published_at DESC LIMIT 6");
$messages = $stmt->fetchAll();

// Start output buffering for layout
ob_start();
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1><?= e(__('hero_title')) ?></h1>
        <p><?= e(__('hero_subtitle')) ?></p>
        <div class="hero-buttons">
            <a href="<?= url('submit.php') ?>" class="btn btn-primary"><?= e(__('hero_cta')) ?></a>
            <a href="<?= url('messages.php') ?>" class="btn btn-outline"><?= e(__('hero_browse')) ?></a>
        </div>
    </div>
</section>

<!-- Recent Messages -->
<div class="container">
    <h2 class="section-title"><?= e(__('recent_messages')) ?></h2>

    <?php if (empty($messages)): ?>
        <div class="card text-center" style="padding: 3rem;">
            <p class="text-muted"><?= e(__('no_messages')) ?></p>
            <a href="<?= url('submit.php') ?>" class="btn btn-primary mt-2"><?= e(__('hero_cta')) ?></a>
        </div>
    <?php else: ?>
        <div class="messages-grid">
            <?php foreach ($messages as $msg): ?>
                <div class="card message-card">
                    <div class="message-meta">
                        <span class="badge badge-category"><?= e(__('cat_' . $msg['category'])) ?></span>
                        <span class="badge badge-language"><?= e(__('lang_' . $msg['language'])) ?></span>
                        <span class="badge badge-format"><?= e(__('format_' . $msg['format'])) ?></span>
                    </div>
                    <div class="message-content <?= $msg['format'] === 'quote' ? 'quote' : '' ?>">
                        <?= e($msg['content']) ?>
                    </div>
                    <div class="message-actions">
                        <div class="reactions">
                            <button class="reaction-btn" data-id="<?= $msg['id'] ?>" data-type="helped">&#128156; <?= e(__('reaction_helped')) ?></button>
                            <button class="reaction-btn" data-id="<?= $msg['id'] ?>" data-type="hope">&#127793; <?= e(__('reaction_hope')) ?></button>
                            <button class="reaction-btn" data-id="<?= $msg['id'] ?>" data-type="not_alone">&#129309; <?= e(__('reaction_not_alone')) ?></button>
                        </div>
                        <div class="share-actions">
                            <button class="share-btn" onclick="shareWhatsApp(<?= $msg['id'] ?>, this)" data-content="<?= e($msg['content']) ?>">&#128172; <?= e(__('share_whatsapp')) ?></button>
                            <button class="share-btn" onclick="copyMessage(<?= $msg['id'] ?>, this)" data-content="<?= e($msg['content']) ?>">&#128203; <?= e(__('share_copy')) ?></button>
                        </div>
                    </div>
                    <div class="message-date"><?= date('M j, Y', strtotime($msg['published_at'] ?? $msg['created_at'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-3">
            <a href="<?= url('messages.php') ?>" class="btn btn-primary"><?= e(__('view_all')) ?></a>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
