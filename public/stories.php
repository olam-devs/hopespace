<?php
/**
 * Browse Stories Page
 */
require_once __DIR__ . '/../app/config/init.php';

$pageTitle = __('stories_title');
$db = getDB();

$filterLang   = $_GET['filter_lang'] ?? '';
$filterAuthor = trim($_GET['filter_author'] ?? '');
$search       = trim($_GET['search'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 9;
$offset       = ($page - 1) * $perPage;

$where  = ["s.status = 'approved'"];
$params = [];

if ($filterLang && in_array($filterLang, ['en', 'sw'])) {
    $where[] = "s.language = ?";
    $params[] = $filterLang;
}

if ($filterAuthor !== '') {
    $where[] = "u.username = ?";
    $params[] = $filterAuthor;
}

if ($search !== '') {
    $where[] = "(s.title LIKE ? OR u.username LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSQL = implode(' AND ', $where);

$countSql = "SELECT COUNT(*) FROM stories s
             JOIN users u ON u.id = s.author_id
             WHERE $whereSQL";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$total      = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$sql = "SELECT s.id, s.title, s.slug, s.language, s.description, s.story_type, s.is_complete, s.created_at,
               u.username AS author_username,
               COUNT(sp.id) AS parts_count
        FROM stories s
        JOIN users u ON u.id = s.author_id
        LEFT JOIN story_parts sp ON sp.story_id = s.id AND sp.status = 'approved'
        WHERE $whereSQL
        GROUP BY s.id
        ORDER BY s.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$stories = $stmt->fetchAll();

ob_start();
?>

<div class="container">
    <div class="page-hero text-center">
        <h1 class="section-title"><?= e(__('stories_title')) ?></h1>
        <p class="section-subtitle"><?= e(__('stories_subtitle')) ?></p>
    </div>

    <!-- Search & Filters -->
    <form class="stories-search-form" method="GET" action="<?= BASE_URL ?>/stories.php" id="storiesFilterForm">
        <input type="hidden" name="lang" value="<?= currentLang() ?>">

        <div class="stories-search-wrap">
            <input type="text" name="search" id="storySearchInput" class="form-control"
                   placeholder="<?= e(__('story_search_placeholder')) ?>"
                   value="<?= e($search) ?>" autocomplete="off">
            <div class="search-autocomplete" id="searchAutocomplete"></div>
        </div>

        <select name="filter_lang" onchange="this.form.submit()">
            <option value=""><?= e(__('story_language_filter')) ?>: <?= e(__('filter_all')) ?></option>
            <option value="en" <?= $filterLang === 'en' ? 'selected' : '' ?>><?= e(__('lang_en')) ?></option>
            <option value="sw" <?= $filterLang === 'sw' ? 'selected' : '' ?>><?= e(__('lang_sw')) ?></option>
        </select>

        <button type="submit" class="btn btn-primary btn-sm"><?= e(__('search_btn')) ?></button>
        <?php if ($search || $filterLang || $filterAuthor): ?>
            <a href="<?= url('stories.php') ?>" class="btn btn-secondary btn-sm"><?= e(__('filter_all')) ?></a>
        <?php endif; ?>
    </form>

    <?php if ($filterAuthor): ?>
        <div class="author-filter-banner">
            <?= e(__('author_stories_by')) ?>: <strong><?= e($filterAuthor) ?></strong>
            <a href="<?= url('stories.php') ?>" class="btn btn-secondary btn-sm ml-1">&times; <?= e(__('filter_all')) ?></a>
        </div>
    <?php endif; ?>

    <?php if (empty($stories)): ?>
        <div class="card text-center empty-state">
            <p class="text-muted"><?= e(__('no_stories')) ?></p>
        </div>
    <?php else: ?>
        <div class="stories-grid">
            <?php foreach ($stories as $story): ?>
                <?php
                $firstPartUrl = BASE_URL . '/story.php?slug=' . urlencode($story['slug']) . '&lang=' . currentLang();
                $partsCount   = (int)$story['parts_count'];
                ?>
                <div class="story-card card">
                    <div class="story-card-header">
                        <div class="story-badges">
                            <span class="badge badge-language"><?= e(__('lang_' . $story['language'])) ?></span>
                            <span class="badge badge-format"><?= $story['story_type'] === 'full' ? e(__('story_type_full')) : e(__('story_type_parts')) ?></span>
                            <?php if ($story['is_complete']): ?>
                                <span class="badge badge-complete">&#10003; <?= e(__('story_the_end')) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <h3 class="story-title"><?= e($story['title']) ?></h3>
                    <p class="story-description"><?= e(mb_strimwidth($story['description'], 0, 160, '…')) ?></p>
                    <div class="story-meta">
                        <span class="story-author">
                            <?= e(__('story_by')) ?>
                            <a href="<?= BASE_URL ?>/stories.php?filter_author=<?= urlencode($story['author_username']) ?>&lang=<?= currentLang() ?>" class="author-link">
                                <?= e($story['author_username']) ?>
                            </a>
                        </span>
                        <?php if ($story['story_type'] === 'parts' && $partsCount > 0): ?>
                            <span class="story-parts-count"><?= $partsCount ?> <?= e(__('story_parts_count')) ?></span>
                        <?php endif; ?>
                        <span class="story-date"><?= date('M j, Y', strtotime($story['created_at'])) ?></span>
                    </div>
                    <a href="<?= $firstPartUrl ?>" class="btn btn-primary btn-sm story-read-btn"><?= e(__('story_read_btn')) ?></a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="<?= BASE_URL ?>/stories.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                       class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Story search autocomplete
(function() {
    const input   = document.getElementById('storySearchInput');
    const dropdown = document.getElementById('searchAutocomplete');
    if (!input || !dropdown) return;

    let debounce;
    input.addEventListener('input', function() {
        clearTimeout(debounce);
        const q = this.value.trim();
        if (q.length < 2) { dropdown.innerHTML = ''; dropdown.style.display = 'none'; return; }
        debounce = setTimeout(() => {
            fetch(BASE_URL + '/api/search_stories.php?q=' + encodeURIComponent(q) + '&lang=<?= currentLang() ?>')
                .then(r => r.json())
                .then(data => {
                    if (!data.results || data.results.length === 0) {
                        dropdown.innerHTML = '<div class="autocomplete-empty"><?= e(__('search_no_results')) ?></div>';
                    } else {
                        dropdown.innerHTML = data.results.map(r => {
                            const url = BASE_URL + '/story.php?slug=' + encodeURIComponent(r.slug) + '&lang=<?= currentLang() ?>';
                            return '<a class="autocomplete-item" href="' + url + '">' +
                                   '<span class="autocomplete-title">' + r.title + '</span>' +
                                   '<span class="autocomplete-author"><?= e(__('story_by')) ?> ' + r.author + '</span>' +
                                   '</a>';
                        }).join('');
                    }
                    dropdown.style.display = 'block';
                })
                .catch(() => { dropdown.innerHTML = ''; dropdown.style.display = 'none'; });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!input.contains(e.target)) { dropdown.style.display = 'none'; }
    });
})();
</script>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
