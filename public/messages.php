<?php
/**
 * Browse Approved Messages
 */
require_once __DIR__ . '/../app/config/init.php';

$pageTitle = __('messages_title');

$db = getDB();

// Filter parameters
$filterLang = $_GET['filter_lang'] ?? '';
$filterCat = $_GET['filter_cat'] ?? '';
$sortBy = $_GET['sort'] ?? 'recent';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Validate sort
$validSorts = ['recent', 'most_reacted', 'trending', 'mixed'];
if (!in_array($sortBy, $validSorts)) {
    $sortBy = 'recent';
}

// Build WHERE clause
$where = ["m.status = 'approved'"];
$params = [];

if ($filterLang && in_array($filterLang, ['en', 'sw'])) {
    $where[] = "m.language = ?";
    $params[] = $filterLang;
}

if ($filterCat && in_array($filterCat, getCategories())) {
    $where[] = "m.category = ?";
    $params[] = $filterCat;
}

if ($search !== '') {
    $where[] = "m.content LIKE ?";
    $params[] = '%' . $search . '%';
}

$whereSQL = implode(' AND ', $where);

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM messages m WHERE $whereSQL");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

// Build ORDER BY based on sort
switch ($sortBy) {
    case 'most_reacted':
        $orderBy = "reaction_count DESC, m.published_at DESC";
        break;
    case 'trending':
        $orderBy = "trending_count DESC, m.published_at DESC";
        break;
    case 'mixed':
        $orderBy = "(COALESCE(reaction_count, 0) * 2 - DATEDIFF(NOW(), m.published_at)) DESC";
        break;
    default: // recent
        $orderBy = "m.published_at DESC";
        break;
}

// Fetch messages with reaction counts (used for sorting, not displayed to user)
$sql = "SELECT m.*,
    COALESCE(rc.reaction_count, 0) AS reaction_count,
    COALESCE(tr.trending_count, 0) AS trending_count
FROM messages m
LEFT JOIN (
    SELECT message_id, COUNT(*) AS reaction_count
    FROM reactions
    GROUP BY message_id
) rc ON rc.message_id = m.id
LEFT JOIN (
    SELECT message_id, COUNT(*) AS trending_count
    FROM reactions
    WHERE created_at >= NOW() - INTERVAL 7 DAY
    GROUP BY message_id
) tr ON tr.message_id = m.id
WHERE $whereSQL
ORDER BY $orderBy
LIMIT $perPage OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

ob_start();
?>

<div class="container">
    <h1 class="section-title"><?= e(__('messages_title')) ?></h1>

    <!-- Search, Filters & Sort -->
    <form class="filters" method="GET" action="<?= BASE_URL ?>/messages.php">
        <input type="hidden" name="lang" value="<?= currentLang() ?>">

        <input type="text" name="search" class="form-control" placeholder="<?= e(__('search_placeholder')) ?>" value="<?= e($search) ?>" style="flex: 1; min-width: 150px;">

        <select name="filter_lang" onchange="this.form.submit()">
            <option value=""><?= e(__('filter_language')) ?>: <?= e(__('filter_all')) ?></option>
            <option value="en" <?= $filterLang === 'en' ? 'selected' : '' ?>><?= e(__('lang_en')) ?></option>
            <option value="sw" <?= $filterLang === 'sw' ? 'selected' : '' ?>><?= e(__('lang_sw')) ?></option>
        </select>

        <select name="filter_cat" onchange="this.form.submit()">
            <option value=""><?= e(__('filter_category')) ?>: <?= e(__('filter_all')) ?></option>
            <?php foreach (getCategories() as $cat): ?>
                <option value="<?= $cat ?>" <?= $filterCat === $cat ? 'selected' : '' ?>><?= e(__('cat_' . $cat)) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="sort" onchange="this.form.submit()">
            <option value="recent" <?= $sortBy === 'recent' ? 'selected' : '' ?>><?= e(__('sort_by')) ?>: <?= e(__('sort_recent')) ?></option>
            <option value="most_reacted" <?= $sortBy === 'most_reacted' ? 'selected' : '' ?>><?= e(__('sort_by')) ?>: <?= e(__('sort_most_reacted')) ?></option>
            <option value="trending" <?= $sortBy === 'trending' ? 'selected' : '' ?>><?= e(__('sort_by')) ?>: <?= e(__('sort_trending')) ?></option>
            <option value="mixed" <?= $sortBy === 'mixed' ? 'selected' : '' ?>><?= e(__('sort_by')) ?>: <?= e(__('sort_mixed')) ?></option>
        </select>

        <button type="submit" class="btn btn-primary btn-sm"><?= e(__('search_btn')) ?></button>
    </form>

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

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="text-center mt-3" style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php
                    $pParams = $_GET;
                    $pParams['page'] = $i;
                    $pUrl = BASE_URL . '/messages.php?' . http_build_query($pParams);
                    ?>
                    <a href="<?= e($pUrl) ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
