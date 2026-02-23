<?php
/**
 * Browse Testimonies Page
 */
require_once __DIR__ . '/../app/config/init.php';

$pageTitle = __('testimonies_title');
$db = getDB();

$filterLang = $_GET['filter_lang'] ?? '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 10;
$offset     = ($page - 1) * $perPage;

$where  = ["status = 'approved'"];
$params = [];

if ($filterLang && in_array($filterLang, ['en', 'sw'])) {
    $where[] = "language = ?";
    $params[] = $filterLang;
}

$whereSQL = implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) FROM testimonies WHERE $whereSQL");
$countStmt->execute($params);
$total      = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$stmt = $db->prepare("SELECT * FROM testimonies WHERE $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$testimonies = $stmt->fetchAll();

ob_start();
?>

<div class="container">
    <div class="page-hero text-center">
        <h1 class="section-title"><?= e(__('testimonies_title')) ?></h1>
        <p class="section-subtitle"><?= e(__('testimonies_subtitle')) ?></p>
    </div>

    <!-- Filters & CTA -->
    <div class="testimonies-toolbar">
        <form class="filters filters-compact" method="GET" action="<?= BASE_URL ?>/testimonies.php">
            <input type="hidden" name="lang" value="<?= currentLang() ?>">
            <select name="filter_lang" onchange="this.form.submit()">
                <option value=""><?= e(__('filter_language')) ?>: <?= e(__('filter_all')) ?></option>
                <option value="en" <?= $filterLang === 'en' ? 'selected' : '' ?>><?= e(__('lang_en')) ?></option>
                <option value="sw" <?= $filterLang === 'sw' ? 'selected' : '' ?>><?= e(__('lang_sw')) ?></option>
            </select>
        </form>
        <a href="<?= url('submit_testimony.php') ?>" class="btn btn-primary"><?= e(__('testimony_submit_title')) ?></a>
    </div>

    <?php if (empty($testimonies)): ?>
        <div class="card text-center empty-state">
            <p class="text-muted"><?= e(__('no_testimonies')) ?></p>
            <a href="<?= url('submit_testimony.php') ?>" class="btn btn-primary mt-2"><?= e(__('testimony_submit_btn')) ?></a>
        </div>
    <?php else: ?>
        <div class="testimonies-grid">
            <?php foreach ($testimonies as $t): ?>
                <div class="testimony-card card">
                    <div class="testimony-quote-mark">&ldquo;</div>
                    <p class="testimony-content"><?= nl2br(e($t['content'])) ?></p>
                    <div class="testimony-footer">
                        <span class="testimony-author">
                            — <?= $t['alias'] ? e($t['alias']) : e(__('anonymous_testimony')) ?>
                        </span>
                        <span class="badge badge-language"><?= e(__('lang_' . $t['language'])) ?></span>
                        <span class="testimony-date"><?= date('M j, Y', strtotime($t['created_at'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="<?= BASE_URL ?>/testimonies.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                       class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
