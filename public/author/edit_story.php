<?php
/**
 * Author — Edit / Delete Story
 * Only the story owner can edit or delete their own story.
 */
require_once __DIR__ . '/../../app/config/init.php';
require_once APP_PATH . '/middleware/Auth.php';

if (!Auth::isAuthenticated()) {
    redirect(url('author/login.php'));
}

$db     = getDB();
$userId = Auth::getCurrentUserId();

// Verify author
$chk = $db->prepare("SELECT is_author FROM users WHERE id = ? AND is_active = 1");
$chk->execute([$userId]);
$userRow = $chk->fetch();
if (!$userRow || !$userRow['is_author']) {
    setFlash('error', 'Author access required.');
    redirect(url('author/login.php'));
}

$storyId = (int)($_GET['story_id'] ?? 0);
if (!$storyId) {
    redirect(url('author/dashboard.php'));
}

// Fetch the story — must belong to this author
$stmt = $db->prepare("SELECT * FROM stories WHERE id = ? AND author_id = ?");
$stmt->execute([$storyId, $userId]);
$story = $stmt->fetch();

if (!$story) {
    setFlash('error', 'Story not found or access denied.');
    redirect(url('author/dashboard.php'));
}

$errors = [];

// ── Handle DELETE ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
        redirect(url('author/edit_story.php?story_id=' . $storyId));
    }
    // Delete story parts first, then story
    $db->prepare("DELETE FROM story_parts WHERE story_id = ?")->execute([$storyId]);
    $db->prepare("DELETE FROM stories WHERE id = ? AND author_id = ?")->execute([$storyId, $userId]);
    setFlash('success', 'Story "' . $story['title'] . '" deleted successfully.');
    redirect(url('author/dashboard.php'));
}

// ── Handle EDIT / SAVE ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
        redirect(url('author/edit_story.php?story_id=' . $storyId));
    }

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $storyType   = in_array($_POST['story_type'] ?? '', ['full', 'parts']) ? $_POST['story_type'] : $story['story_type'];
    $nextTitle   = trim($_POST['next_release_title'] ?? '');
    $nextDate    = trim($_POST['next_release_date'] ?? '');
    $nextNote    = trim($_POST['next_release_note'] ?? '');

    if (strlen($title) < 3)       $errors[] = 'Title must be at least 3 characters.';
    if (strlen($description) < 10) $errors[] = 'Description must be at least 10 characters.';

    if (empty($errors)) {
        // Generate new slug if title changed
        $newSlug = $story['slug'];
        if ($title !== $story['title']) {
            $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
            $baseSlug = trim($baseSlug, '-');
            // Ensure uniqueness (excluding this story)
            $count = 0;
            $checkSlug = $baseSlug;
            do {
                $slugCheck = $db->prepare("SELECT id FROM stories WHERE slug = ? AND id != ?");
                $slugCheck->execute([$checkSlug, $storyId]);
                if ($slugCheck->fetch()) {
                    $count++;
                    $checkSlug = $baseSlug . '-' . $count;
                } else {
                    $newSlug = $checkSlug;
                    break;
                }
            } while (true);
        }

        $update = $db->prepare("
            UPDATE stories
            SET title = ?, slug = ?, description = ?, story_type = ?,
                next_release_title = ?, next_release_date = ?, next_release_note = ?,
                updated_at = NOW()
            WHERE id = ? AND author_id = ?
        ");
        $update->execute([
            $title, $newSlug, $description, $storyType,
            $nextTitle ?: null,
            $nextDate ?: null,
            $nextNote ?: null,
            $storyId, $userId
        ]);

        // Refresh story data
        $stmt->execute([$storyId, $userId]);
        $story = $stmt->fetch();
        setFlash('success', 'Story updated successfully.');
        redirect(url('author/edit_story.php?story_id=' . $storyId));
    }
}

$pageTitle = 'Edit Story — ' . $story['title'];
ob_start();
?>

<div class="container">
    <div class="author-dashboard">

        <div class="dashboard-header">
            <div>
                <h1>Edit Story</h1>
                <p class="dashboard-welcome">Editing: <strong><?= e($story['title']) ?></strong></p>
            </div>
            <div class="dashboard-actions">
                <a href="<?= url('author/dashboard.php') ?>" class="btn btn-secondary">← My Stories</a>
            </div>
        </div>

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="flash flash-<?= $flash['type'] ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="flash flash-error">
                <?php foreach ($errors as $err): ?>
                    <div>• <?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <div class="card" style="padding:2rem;margin-bottom:1.5rem;">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save">

                <div class="form-group">
                    <label class="form-label" for="title">Story Title</label>
                    <input type="text" id="title" name="title" class="form-control"
                           value="<?= e($story['title']) ?>" required maxlength="200">
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Description / Blurb</label>
                    <textarea id="description" name="description" class="form-control"
                              rows="4" required><?= e($story['description']) ?></textarea>
                    <small class="form-hint">This is the preview readers see before opening your story.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Story Type</label>
                    <div style="display:flex;gap:1.5rem;margin-top:.5rem;">
                        <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;">
                            <input type="radio" name="story_type" value="full"
                                <?= $story['story_type'] === 'full' ? 'checked' : '' ?>>
                            Full (single read)
                        </label>
                        <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;">
                            <input type="radio" name="story_type" value="parts"
                                <?= $story['story_type'] === 'parts' ? 'checked' : '' ?>>
                            Multi-part series
                        </label>
                    </div>
                </div>

                <?php if ($story['story_type'] === 'parts'): ?>
                <div id="parts-schedule" style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:1.25rem;margin-top:.5rem;">
                    <p style="font-weight:600;margin-bottom:1rem;">&#128197; Next Part Schedule (optional)</p>
                    <div class="form-group">
                        <label class="form-label" for="next_release_title">Next Part Title</label>
                        <input type="text" id="next_release_title" name="next_release_title" class="form-control"
                               value="<?= e($story['next_release_title'] ?? '') ?>" maxlength="200">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="next_release_date">Release Date</label>
                        <input type="date" id="next_release_date" name="next_release_date" class="form-control"
                               value="<?= e($story['next_release_date'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="next_release_note">Teaser Note</label>
                        <textarea id="next_release_note" name="next_release_note" class="form-control"
                                  rows="2"><?= e($story['next_release_note'] ?? '') ?></textarea>
                    </div>
                </div>
                <?php endif; ?>

                <div style="display:flex;gap:1rem;margin-top:1.5rem;">
                    <button type="submit" class="btn btn-primary">&#10003; Save Changes</button>
                    <a href="<?= url('author/dashboard.php') ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Danger Zone: Delete -->
        <div class="card" style="border:1.5px solid var(--danger,#dc3545);padding:1.5rem;">
            <h3 style="color:var(--danger,#dc3545);margin-bottom:.5rem;">&#9888; Danger Zone</h3>
            <p style="color:var(--text-muted);margin-bottom:1rem;font-size:.95rem;">
                Deleting this story will permanently remove <strong>all <?= (int)($story['total_parts'] ?? 0) ?> parts</strong> and all reader progress. This cannot be undone.
            </p>
            <form method="POST" onsubmit="return confirm('Delete story \"<?= addslashes(e($story['title'])) ?>\"?\n\nAll parts will be permanently deleted. This cannot be undone.');">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-sm" style="background:var(--danger,#dc3545);color:#fff;border-color:var(--danger,#dc3545);">
                    &#128465; Delete This Story
                </button>
            </form>
        </div>

    </div>
</div>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
