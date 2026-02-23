<?php
/**
 * Author — Write Story Part
 * Handles both "full story" (single part) and multi-part stories.
 */
require_once __DIR__ . '/../../app/config/init.php';
require_once APP_PATH . '/middleware/Auth.php';

if (!Auth::isAuthenticated()) {
    redirect(url('author/login.php'));
}

$db     = getDB();
$userId = Auth::getCurrentUserId();

// Verify author role
$chk = $db->prepare("SELECT is_author FROM users WHERE id = ? AND is_active = 1");
$chk->execute([$userId]);
$row = $chk->fetch();
if (!$row || !$row['is_author']) {
    redirect(url('author/login.php'));
}

$storyId = (int)($_GET['story_id'] ?? 0);
if (!$storyId) redirect(url('author/dashboard.php'));

// Verify ownership
$story = $db->prepare("SELECT * FROM stories WHERE id = ? AND author_id = ?");
$story->execute([$storyId, $userId]);
$story = $story->fetch();
if (!$story) redirect(url('author/dashboard.php'));

// Count approved+pending parts to determine next part number
$partCountStmt = $db->prepare("SELECT COUNT(*) FROM story_parts WHERE story_id = ?");
$partCountStmt->execute([$storyId]);
$existingCount = (int)$partCountStmt->fetchColumn();
$nextPartNum   = $existingCount + 1;

$isFullStory = $story['story_type'] === 'full';
$pageTitle   = e($story['title']) . ($isFullStory ? '' : ' — Part ' . $nextPartNum);

$errors = [];
$mode   = 'write'; // 'write' or 'preview'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = __('submit_error');
    } else {
        $action      = $_POST['action'] ?? 'submit'; // 'preview' or 'submit'
        $partTitle   = trim($_POST['part_title'] ?? '');
        $content     = $_POST['content'] ?? '';
        $isLastPart  = isset($_POST['is_last_part']) ? 1 : 0;

        // Next release fields
        $nextRelTitle = trim($_POST['next_release_title'] ?? '');
        $nextRelDate  = trim($_POST['next_release_date'] ?? '');
        $nextRelNote  = trim($_POST['next_release_note'] ?? '');

        // Sanitize content — strip script/iframe but keep formatting tags
        $allowedTags = '<p><br><strong><em><b><i><h2><h3><h4><ul><ol><li><blockquote><hr>';
        $content     = strip_tags($content, $allowedTags);
        $content     = trim($content);

        if (mb_strlen(strip_tags($content)) < 50) {
            $errors[] = 'Story content must be at least 50 characters.';
        }

        if ($action === 'preview' && empty($errors)) {
            $mode = 'preview';
        } elseif ($action === 'submit' && empty($errors)) {
            // Prevent duplicate part number
            $dupCheck = $db->prepare("SELECT id FROM story_parts WHERE story_id = ? AND part_number = ?");
            $dupCheck->execute([$storyId, $nextPartNum]);
            if ($dupCheck->fetch()) {
                $errors[] = 'This part number already exists. Please refresh and try again.';
            } else {
                // Insert story part
                $ins = $db->prepare("
                    INSERT INTO story_parts (story_id, part_number, part_title, content, status, is_last_part, created_at)
                    VALUES (?, ?, ?, ?, 'pending', ?, NOW())
                ");
                $ins->execute([$storyId, $nextPartNum, $partTitle ?: null, $content, $isLastPart]);

                // Update story next-release info and is_complete flag
                $updStory = $db->prepare("
                    UPDATE stories SET
                        is_complete = ?,
                        next_release_title = ?,
                        next_release_date  = ?,
                        next_release_note  = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $isComplete = $isLastPart ? 1 : 0;
                $updStory->execute([
                    $isComplete,
                    $nextRelTitle ?: null,
                    $nextRelDate  ?: null,
                    $nextRelNote  ?: null,
                    $storyId
                ]);

                if ($isFullStory) {
                    setFlash('success', __('story_submitted_ok'));
                } else {
                    setFlash('success', __('story_part_submitted_ok'));
                }
                redirect(url('author/dashboard.php'));
            }
        }
    }
}

ob_start();
?>

<div class="container">
    <div class="author-write-page">

        <div class="author-form-header">
            <a href="<?= url('author/dashboard.php') ?>" class="back-link">&larr; <?= e(__('author_dashboard_title')) ?></a>
            <h1>
                <?php if ($isFullStory): ?>
                    <?= e(__('write_part_title')) ?>
                <?php else: ?>
                    <?= e(__('write_part_for')) ?> <?= $nextPartNum ?> &mdash; <?= e($story['title']) ?>
                <?php endif; ?>
            </h1>
            <p class="story-write-subtitle"><?= e($story['title']) ?><?php if (!$isFullStory): ?> &middot; Part <?= $nextPartNum ?><?php endif; ?></p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash flash-error">
                <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($mode === 'preview'): ?>
            <!-- PREVIEW MODE -->
            <div class="story-preview-wrapper">
                <div class="preview-header">
                    <h2>Preview</h2>
                    <button class="btn btn-secondary" onclick="showWrite()">&#9998; Edit</button>
                </div>
                <div class="story-reader story-preview-content">
                    <h1 class="story-reader-title"><?= e($story['title']) ?></h1>
                    <?php if (!$isFullStory && isset($_POST['part_title']) && trim($_POST['part_title'])): ?>
                        <p class="story-part-label">Part <?= $nextPartNum ?> &mdash; <?= e(trim($_POST['part_title'])) ?></p>
                    <?php elseif (!$isFullStory): ?>
                        <p class="story-part-label">Part <?= $nextPartNum ?></p>
                    <?php endif; ?>
                    <div class="story-content">
                        <?= strip_tags($_POST['content'] ?? '', '<p><br><strong><em><b><i><h2><h3><h4><ul><ol><li><blockquote><hr>') ?>
                    </div>
                    <?php if (isset($_POST['is_last_part'])): ?>
                        <div class="the-end-banner"><span class="the-end-text"><?= e(__('story_the_end')) ?></span></div>
                    <?php endif; ?>
                </div>
                <form method="POST" action="<?= url('author/write_part.php?story_id=' . $storyId) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="part_title" value="<?= e($_POST['part_title'] ?? '') ?>">
                    <input type="hidden" name="content" value="<?= e($_POST['content'] ?? '') ?>">
                    <?php if (isset($_POST['is_last_part'])): ?>
                        <input type="hidden" name="is_last_part" value="1">
                    <?php endif; ?>
                    <input type="hidden" name="next_release_title" value="<?= e($_POST['next_release_title'] ?? '') ?>">
                    <input type="hidden" name="next_release_date"  value="<?= e($_POST['next_release_date'] ?? '') ?>">
                    <input type="hidden" name="next_release_note"  value="<?= e($_POST['next_release_note'] ?? '') ?>">
                    <div class="preview-actions">
                        <button type="button" class="btn btn-secondary btn-lg" onclick="showWrite()">&#9998; <?= e(__('story_preview_btn')) === __('story_preview_btn') ? 'Back to Edit' : '' ?> Edit</button>
                        <button type="submit" name="action" value="submit" class="btn btn-primary btn-lg">&#9989; <?= e(__('story_submit_btn')) ?></button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- WRITE MODE (always rendered, hidden in preview) -->
        <div class="write-mode-wrapper" id="writeModeWrapper" <?= $mode === 'preview' ? 'style="display:none;"' : '' ?>>
            <form method="POST" action="<?= url('author/write_part.php?story_id=' . $storyId) ?>" id="storyWriteForm">
                <?= csrfField() ?>

                <?php if (!$isFullStory): ?>
                    <div class="form-group">
                        <label for="part_title"><?= e(__('story_part_title_label')) ?></label>
                        <input type="text" name="part_title" id="part_title" class="form-control"
                               placeholder="<?= e(__('story_part_title_placeholder')) ?>"
                               value="<?= e($_POST['part_title'] ?? '') ?>" maxlength="200">
                    </div>
                <?php endif; ?>

                <!-- Rich Text Editor -->
                <div class="form-group">
                    <label><?= e(__('story_content_label')) ?> <span class="required">*</span></label>
                    <div class="editor-wrapper">
                        <!-- Toolbar -->
                        <div class="editor-toolbar" id="editorToolbar">
                            <button type="button" class="tool-btn" onclick="fmt('bold')" title="Bold"><b>B</b></button>
                            <button type="button" class="tool-btn" onclick="fmt('italic')" title="Italic"><i>I</i></button>
                            <button type="button" class="tool-btn" onclick="fmtBlock('h2')" title="Heading 2">H2</button>
                            <button type="button" class="tool-btn" onclick="fmtBlock('h3')" title="Heading 3">H3</button>
                            <button type="button" class="tool-btn" onclick="fmtBlock('p')" title="Paragraph">&#182;</button>
                            <button type="button" class="tool-btn" onclick="fmt('insertUnorderedList')" title="Bullet List">&#8226; List</button>
                            <button type="button" class="tool-btn" onclick="fmt('insertOrderedList')" title="Numbered List">1. List</button>
                            <button type="button" class="tool-btn" onclick="insertHr()" title="Divider">&#8213; HR</button>
                            <span class="toolbar-sep"></span>
                            <span class="word-count" id="wordCount">0 words</span>
                        </div>
                        <!-- Editable area -->
                        <div class="editor-area" id="editorArea" contenteditable="true"
                             data-placeholder="<?= e(__('story_content_label')) ?>..."><?= isset($_POST['content']) ? strip_tags($_POST['content'], '<p><br><strong><em><b><i><h2><h3><h4><ul><ol><li><blockquote><hr>') : '' ?></div>
                        <!-- Hidden textarea synced to editor -->
                        <textarea name="content" id="contentHidden" style="display:none;"><?= e($_POST['content'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Last part toggle (multi-part only) -->
                <?php if (!$isFullStory): ?>
                    <div class="form-group last-part-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_last_part" id="isLastPart"
                                   <?= isset($_POST['is_last_part']) ? 'checked' : '' ?>>
                            <span><?= e(__('story_is_last_part')) ?></span>
                        </label>
                    </div>
                <?php else: ?>
                    <!-- Full story always marks as last -->
                    <input type="hidden" name="is_last_part" value="1">
                <?php endif; ?>

                <!-- Next Release Announcement -->
                <details class="next-release-section" <?= ($_POST['next_release_title'] ?? '') || ($_POST['next_release_note'] ?? '') ? 'open' : '' ?>>
                    <summary class="next-release-toggle">&#128226; <?= e(__('story_next_release_title')) ?></summary>
                    <div class="next-release-fields">
                        <div class="form-group">
                            <label for="next_release_title"><?= e(__('story_next_release_name')) ?></label>
                            <input type="text" name="next_release_title" id="next_release_title" class="form-control"
                                   placeholder="<?= e(__('story_next_release_name_ph')) ?>"
                                   value="<?= e($_POST['next_release_title'] ?? '') ?>" maxlength="200">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="next_release_date"><?= e(__('story_next_release_date')) ?></label>
                                <input type="date" name="next_release_date" id="next_release_date" class="form-control"
                                       value="<?= e($_POST['next_release_date'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="next_release_note"><?= e(__('story_next_release_note')) ?></label>
                            <textarea name="next_release_note" id="next_release_note" class="form-control" rows="3"
                                      placeholder="<?= e(__('story_next_release_note_ph')) ?>"
                                      maxlength="500"><?= e($_POST['next_release_note'] ?? '') ?></textarea>
                        </div>
                    </div>
                </details>

                <!-- Form Actions -->
                <div class="write-form-actions">
                    <button type="submit" name="action" value="preview" class="btn btn-secondary btn-lg" onclick="syncEditor()">
                        &#128065; <?= e(__('story_preview_btn')) ?>
                    </button>
                    <button type="submit" name="action" value="submit" class="btn btn-primary btn-lg" onclick="syncEditor()" id="submitBtn">
                        &#128640; <?= e(__('story_submit_btn')) ?>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
// ---- Rich text editor ----
const editor   = document.getElementById('editorArea');
const hidden   = document.getElementById('contentHidden');
const wordCnt  = document.getElementById('wordCount');

function fmt(cmd) {
    document.execCommand(cmd, false, null);
    editor.focus();
    syncEditor();
}

function fmtBlock(tag) {
    document.execCommand('formatBlock', false, '<' + tag + '>');
    editor.focus();
    syncEditor();
}

function insertHr() {
    document.execCommand('insertHTML', false, '<hr>');
    editor.focus();
    syncEditor();
}

function syncEditor() {
    if (hidden) hidden.value = editor.innerHTML;
    updateWordCount();
}

function updateWordCount() {
    if (!wordCnt) return;
    const text  = editor.innerText || editor.textContent || '';
    const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
    wordCnt.textContent = words + ' word' + (words !== 1 ? 's' : '');
}

if (editor) {
    editor.addEventListener('input', syncEditor);
    editor.addEventListener('keyup', syncEditor);
    // Initial word count
    updateWordCount();
}

// Sync before form submission
const form = document.getElementById('storyWriteForm');
if (form) {
    form.addEventListener('submit', function(e) {
        syncEditor();
    });
}

// Show/hide write mode from preview
function showWrite() {
    document.getElementById('writeModeWrapper').style.display = '';
    const previewWrapper = document.querySelector('.story-preview-wrapper');
    if (previewWrapper) previewWrapper.style.display = 'none';
}

// Confirm last-part before submitting (multi-part only)
const isLastChk = document.getElementById('isLastPart');
const submitBtn = document.getElementById('submitBtn');
if (isLastChk && submitBtn) {
    submitBtn.addEventListener('click', function(e) {
        if (isLastChk.checked) {
            if (!confirm('You have marked this as the FINAL PART. Once submitted, the story will show "THE END" to readers. Confirm?')) {
                e.preventDefault();
            }
        }
    });
}
</script>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
