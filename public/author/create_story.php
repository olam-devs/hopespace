<?php
/**
 * Author — Create New Story (details step)
 */
require_once __DIR__ . '/../../app/config/init.php';
require_once APP_PATH . '/middleware/Auth.php';

$pageTitle = __('create_story_title');

if (!Auth::isAuthenticated()) {
    redirect(url('author/login.php'));
}

$db     = getDB();
$userId = Auth::getCurrentUserId();

$chk = $db->prepare("SELECT is_author FROM users WHERE id = ? AND is_active = 1");
$chk->execute([$userId]);
$row = $chk->fetch();
if (!$row || !$row['is_author']) {
    redirect(url('author/login.php'));
}

$errors   = [];
$formData = [];

function generateStorySlug(string $title, \PDO $db): string {
    $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
    $base = substr($base, 0, 100);
    $slug = $base;
    $i    = 0;
    do {
        $check = $db->prepare("SELECT id FROM stories WHERE slug = ?");
        $check->execute([$slug]);
        if (!$check->fetch()) break;
        $slug = $base . '-' . (++$i);
    } while (true);
    return $slug;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = __('submit_error');
    } else {
        $title      = trim($_POST['title'] ?? '');
        $desc       = trim($_POST['description'] ?? '');
        $storyType  = in_array($_POST['story_type'] ?? '', ['full', 'parts']) ? $_POST['story_type'] : 'full';
        $language   = in_array($_POST['language'] ?? '', ['en', 'sw']) ? $_POST['language'] : 'en';

        $formData = compact('title', 'desc', 'storyType', 'language');

        if (mb_strlen($title) < 3 || mb_strlen($title) > 200) {
            $errors[] = 'Title must be between 3 and 200 characters.';
        }
        if (mb_strlen($desc) < 20 || mb_strlen($desc) > 500) {
            $errors[] = 'Description must be between 20 and 500 characters.';
        }

        if (empty($errors)) {
            $slug = generateStorySlug($title, $db);

            $stmt = $db->prepare("
                INSERT INTO stories (author_id, title, slug, language, description, story_type, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())
            ");
            $stmt->execute([$userId, $title, $slug, $language, $desc, $storyType]);
            $storyId = $db->lastInsertId();

            // Proceed to write the first part
            redirect(url('author/write_part.php?story_id=' . $storyId . '&new=1'));
        }
    }
}

ob_start();
?>

<div class="container">
    <div class="author-form-page">
        <div class="author-form-header">
            <a href="<?= url('author/dashboard.php') ?>" class="back-link">&larr; <?= e(__('author_dashboard_title')) ?></a>
            <h1><?= e(__('create_story_title')) ?></h1>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash flash-error">
                <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="author-form-card card">
            <form method="POST" action="<?= url('author/create_story.php') ?>">
                <?= csrfField() ?>

                <div class="form-group">
                    <label for="title"><?= e(__('story_title_label')) ?> <span class="required">*</span></label>
                    <input type="text" name="title" id="title" class="form-control form-lg"
                           placeholder="<?= e(__('story_title_placeholder')) ?>"
                           value="<?= e($formData['title'] ?? '') ?>"
                           required maxlength="200">
                </div>

                <div class="form-group">
                    <label for="description"><?= e(__('story_desc_label')) ?> <span class="required">*</span></label>
                    <textarea name="description" id="description" class="form-control" rows="4"
                              placeholder="<?= e(__('story_desc_placeholder')) ?>"
                              required maxlength="500"><?= e($formData['desc'] ?? '') ?></textarea>
                    <p class="char-counter" id="descCount">500 <?= e(__('char_remaining')) ?></p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><?= e(__('story_type_label')) ?> <span class="required">*</span></label>
                        <div class="story-type-options">
                            <label class="type-option <?= ($formData['storyType'] ?? 'full') === 'full' ? 'selected' : '' ?>">
                                <input type="radio" name="story_type" value="full"
                                       <?= ($formData['storyType'] ?? 'full') === 'full' ? 'checked' : '' ?>
                                       onchange="highlightType(this)">
                                <span class="type-icon">&#128218;</span>
                                <span class="type-label"><?= e(__('story_type_full')) ?></span>
                                <span class="type-desc"><?= e(__('story_type_full_desc')) ?></span>
                            </label>
                            <label class="type-option <?= ($formData['storyType'] ?? '') === 'parts' ? 'selected' : '' ?>">
                                <input type="radio" name="story_type" value="parts"
                                       <?= ($formData['storyType'] ?? '') === 'parts' ? 'checked' : '' ?>
                                       onchange="highlightType(this)">
                                <span class="type-icon">&#128196;</span>
                                <span class="type-label"><?= e(__('story_type_parts')) ?></span>
                                <span class="type-desc"><?= e(__('story_type_parts_desc')) ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="language"><?= e(__('story_lang_label')) ?> <span class="required">*</span></label>
                        <select name="language" id="language" class="form-control">
                            <option value="en" <?= ($formData['language'] ?? currentLang()) === 'en' ? 'selected' : '' ?>><?= e(__('lang_en')) ?></option>
                            <option value="sw" <?= ($formData['language'] ?? currentLang()) === 'sw' ? 'selected' : '' ?>><?= e(__('lang_sw')) ?></option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg"><?= e(__('story_next_btn')) ?> &rarr;</button>
            </form>
        </div>
    </div>
</div>

<script>
const descTA = document.getElementById('description');
const descCnt = document.getElementById('descCount');
if (descTA && descCnt) {
    descTA.addEventListener('input', () => {
        const rem = 500 - descTA.value.length;
        descCnt.textContent = rem + ' <?= currentLang() === 'sw' ? 'herufi zimebaki' : 'characters remaining' ?>';
        descCnt.classList.toggle('warning', rem < 30);
    });
}
function highlightType(radio) {
    document.querySelectorAll('.type-option').forEach(el => el.classList.remove('selected'));
    radio.closest('.type-option').classList.add('selected');
}
</script>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
