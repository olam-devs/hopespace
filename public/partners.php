<?php
/**
 * Partners Page
 */
require_once __DIR__ . '/../app/config/init.php';

$pageTitle = __('partners_title');
$db = getDB();

$partners = $db->query("SELECT * FROM partners ORDER BY sort_order ASC, id ASC")->fetchAll();

ob_start();
?>

<div class="container">
    <h1 class="section-title"><?= e(__('partners_title')) ?></h1>
    <p class="section-subtitle"><?= e(__('partners_subtitle')) ?></p>

    <?php if (empty($partners)): ?>
        <div class="card text-center" style="padding: 3rem;">
            <p class="text-muted" style="font-size: 1.1rem;"><?= e(__('partners_no_items')) ?></p>
        </div>
    <?php else: ?>
        <div class="partners-grid">
            <?php foreach ($partners as $p): ?>
                <div class="partner-card card">
                    <?php if ($p['image']): ?>
                        <div class="partner-image">
                            <img src="<?= BASE_URL ?>/assets/uploads/<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>">
                        </div>
                    <?php else: ?>
                        <div class="partner-image partner-image-placeholder">
                            <span>&#129309;</span>
                        </div>
                    <?php endif; ?>
                    <div class="partner-info">
                        <h3><?= e($p['name']) ?></h3>
                        <?php if ($p['description']): ?>
                            <p><?= e($p['description']) ?></p>
                        <?php endif; ?>
                        <?php if ($p['website']): ?>
                            <a href="<?= e($p['website']) ?>" target="_blank" rel="noopener" class="partner-link">&#127760; <?= e($p['website']) ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- CTA: Become a Partner -->
    <div class="partner-cta">
        <div class="partner-cta-inner">
            <h2><?= e(__('partners_cta_title')) ?></h2>
            <p><?= e(__('partners_cta_text')) ?></p>
            <div class="partner-cta-contacts">
                <a href="tel:+255627404843" class="btn btn-outline">&#128222; <?= e(__('partners_cta_phone')) ?></a>
                <a href="mailto:hopespace@gmail.com" class="btn btn-outline">&#9993; <?= e(__('partners_cta_email')) ?></a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
