<?php
/**
 * Support & Resources Page (Dynamic)
 */
require_once __DIR__ . '/../app/config/init.php';

$pageTitle = __('resources_title');
$db = getDB();

// Fetch categories with their resources
$categories = $db->query("SELECT * FROM resource_categories ORDER BY sort_order ASC, id ASC")->fetchAll();

$resourcesByCategory = [];
foreach ($categories as $cat) {
    $stmt = $db->prepare("SELECT * FROM resources WHERE category_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$cat['id']]);
    $resourcesByCategory[$cat['id']] = $stmt->fetchAll();
}

ob_start();
?>

<div class="container">
    <h1 class="section-title"><?= e(__('resources_title')) ?></h1>

    <div class="flash flash-success" style="border-color: var(--accent-light); background: var(--bg-warm); color: var(--secondary);">
        <?= e(__('resources_disclaimer')) ?>
    </div>

    <?php foreach ($categories as $cat): ?>
        <div class="resource-section">
            <h3><?= e($cat['name']) ?></h3>

            <?php if (empty($resourcesByCategory[$cat['id']])): ?>
                <p class="text-muted" style="padding: 1rem;"><?= e(__('resources_no_items')) ?></p>
            <?php else: ?>
                <div class="resources-grid">
                    <?php foreach ($resourcesByCategory[$cat['id']] as $res): ?>
                        <div class="resource-card" onclick="this.classList.toggle('expanded')">
                            <div class="resource-card-summary">
                                <?php if ($res['logo']): ?>
                                    <img src="<?= BASE_URL ?>/assets/uploads/<?= e($res['logo']) ?>" alt="<?= e($res['name']) ?>" class="resource-logo">
                                <?php else: ?>
                                    <div class="resource-logo-placeholder">&#127973;</div>
                                <?php endif; ?>
                                <div class="resource-card-info">
                                    <h4><?= e($res['name']) ?></h4>
                                    <?php if ($res['phone']): ?>
                                        <p class="phone"><?= e($res['phone']) ?></p>
                                    <?php endif; ?>
                                    <span class="resource-expand-hint"><?= e(__('resources_click_details')) ?></span>
                                </div>
                            </div>
                            <?php if ($res['description']): ?>
                                <div class="resource-card-details">
                                    <p><?= e($res['description']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
