<?php
/**
 * Browse Approved Messages
 */
require_once __DIR__ . '/../app/config/init.php';

$pageTitle = __('messages_title');

$db = getDB();

// --- Generate anonymous alias for comments ---
function generateAlias(): string {
    $adjectives = ['Brave', 'Kind', 'Gentle', 'Strong', 'Wise', 'Calm', 'Bold', 'Bright', 'Free', 'True', 'Warm', 'Pure', 'Noble', 'Swift', 'Humble'];
    $nouns = ['Star', 'Heart', 'Soul', 'Light', 'Hope', 'Dream', 'River', 'Sky', 'Moon', 'Sun', 'Tree', 'Eagle', 'Lion', 'Rose', 'Wind'];
    return $adjectives[array_rand($adjectives)] . $nouns[array_rand($nouns)] . rand(10, 99);
}

// --- Handle comment submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_submit'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', __('submit_error'));
        redirect(url('messages.php'));
    }

    $messageId = (int)($_POST['message_id'] ?? 0);
    $commentContent = trim($_POST['comment_content'] ?? '');

    if ($messageId && !empty($commentContent) && mb_strlen($commentContent) <= 500) {
        // Verify the message exists and is approved
        $check = $db->prepare("SELECT id FROM messages WHERE id = ? AND status = 'approved'");
        $check->execute([$messageId]);
        if ($check->fetch()) {
            // Generate or reuse session alias for this message
            $aliasKey = 'alias_msg_' . $messageId;
            if (!empty($_SESSION[$aliasKey])) {
                $alias = $_SESSION[$aliasKey];
            } else {
                $alias = generateAlias();
                $_SESSION[$aliasKey] = $alias;
            }

            $stmt = $db->prepare("INSERT INTO anonymous_comments (message_id, anonymous_alias, content, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
            $stmt->execute([$messageId, $alias, $commentContent]);

            unset($_SESSION['csrf_token']);
            setFlash('success', __('comment_submitted'));
        }
    }

    // Redirect back preserving filters
    $rParams = $_GET;
    $redirectUrl = BASE_URL . '/messages.php' . ($rParams ? '?' . http_build_query($rParams) : '');
    redirect($redirectUrl);
}

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

// Fetch messages with reaction counts and comment counts
$sql = "SELECT m.*,
    COALESCE(rc.reaction_count, 0) AS reaction_count,
    COALESCE(tr.trending_count, 0) AS trending_count,
    COALESCE(cc.comment_count, 0) AS comment_count
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
LEFT JOIN (
    SELECT message_id, COUNT(*) AS comment_count
    FROM anonymous_comments
    WHERE status = 'approved'
    GROUP BY message_id
) cc ON cc.message_id = m.id
WHERE $whereSQL
ORDER BY $orderBy
LIMIT $perPage OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

// Fetch approved comments for all displayed messages
$messageIds = array_column($messages, 'id');
$commentsMap = [];
if (!empty($messageIds)) {
    $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
    $cStmt = $db->prepare("SELECT * FROM anonymous_comments WHERE message_id IN ($placeholders) AND status = 'approved' ORDER BY created_at DESC");
    $cStmt->execute($messageIds);
    $allComments = $cStmt->fetchAll();
    foreach ($allComments as $c) {
        $commentsMap[$c['message_id']][] = $c;
    }
}

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
                <?php $msgComments = $commentsMap[$msg['id']] ?? []; ?>
                <div class="card message-card">
                    <div class="message-meta">
                        <span class="badge badge-category"><?= e(__('cat_' . $msg['category'])) ?></span>
                        <span class="badge badge-language"><?= e(__('lang_' . $msg['language'])) ?></span>
                        <span class="badge badge-format"><?= e(__('format_' . $msg['format'])) ?></span>
                    </div>
                    <div class="message-content <?= $msg['format'] === 'quote' ? 'quote' : ($msg['format'] === 'question' ? 'question' : '') ?>">
                        <?= e($msg['content']) ?>
                    </div>

                    <?php if ($msg['format'] === 'question'): ?>
                        <p class="question-invite-hint"><?= e(__('question_invite')) ?></p>
                    <?php endif; ?>

                    <div class="message-actions">
                        <div class="reactions">
                            <button class="reaction-btn" data-id="<?= $msg['id'] ?>" data-type="helped">&#128156; <?= e(__('reaction_helped')) ?></button>
                            <button class="reaction-btn" data-id="<?= $msg['id'] ?>" data-type="hope">&#127793; <?= e(__('reaction_hope')) ?></button>
                            <button class="reaction-btn" data-id="<?= $msg['id'] ?>" data-type="not_alone">&#129309; <?= e(__('reaction_not_alone')) ?></button>
                        </div>
                        <div class="share-actions">
                            <button class="share-btn" onclick="shareWhatsApp(<?= $msg['id'] ?>, this)" data-content="<?= e($msg['content']) ?>">&#128172; <?= e(__('share_whatsapp')) ?></button>
                            <button class="share-btn" onclick="copyMessage(<?= $msg['id'] ?>, this)" data-content="<?= e($msg['content']) ?>">&#128203; <?= e(__('share_copy')) ?></button>
                            <button class="share-btn comment-toggle-btn" onclick="toggleComments(<?= $msg['id'] ?>)">&#128172; <?= e(__('comments_label')) ?> (<?= count($msgComments) ?>)</button>
                        </div>
                    </div>

                    <div class="message-date"><?= date('M j, Y', strtotime($msg['published_at'] ?? $msg['created_at'])) ?></div>

                    <!-- Comments Section (hidden by default) -->
                    <div class="comments-section" id="comments-<?= $msg['id'] ?>" style="display: none;">
                        <div class="comments-divider"></div>

                        <!-- Comment Form -->
                        <form method="POST" action="<?= BASE_URL ?>/messages.php?<?= http_build_query($_GET) ?>" class="comment-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                            <input type="hidden" name="comment_submit" value="1">
                            <textarea name="comment_content" class="form-control comment-input" placeholder="<?= e(__('write_comment')) ?>" maxlength="500" required></textarea>
                            <button type="submit" class="btn btn-primary btn-sm mt-1"><?= e(__('post_comment')) ?></button>
                        </form>

                        <!-- Comments List -->
                        <?php if (empty($msgComments)): ?>
                            <p class="text-muted comment-empty"><?= e(__('no_comments_yet')) ?></p>
                        <?php else: ?>
                            <div class="comments-list" id="comments-list-<?= $msg['id'] ?>">
                                <?php foreach ($msgComments as $idx => $comment): ?>
                                    <div class="comment-card<?= $idx >= 3 ? ' comment-hidden' : '' ?>" <?= $idx >= 3 ? 'style="display:none;"' : '' ?>>
                                        <div class="comment-header">
                                            <span class="comment-alias"><?= e($comment['anonymous_alias']) ?></span>
                                            <span class="comment-date"><?= date('M j, Y', strtotime($comment['created_at'])) ?></span>
                                        </div>
                                        <p class="comment-text"><?= e($comment['content']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($msgComments) > 3): ?>
                                <button class="btn btn-secondary btn-sm see-more-btn" onclick="toggleMoreComments(<?= $msg['id'] ?>, this)" data-show-text="<?= e(__('see_more_comments')) ?> (<?= count($msgComments) - 3 ?>)" data-hide-text="<?= e(__('hide_comments')) ?>">
                                    <?= e(__('see_more_comments')) ?> (<?= count($msgComments) - 3 ?>)
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
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
