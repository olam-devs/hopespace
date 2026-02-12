<?php
/**
 * Submit a Hope Message
 */
require_once __DIR__ . '/../app/config/init.php';

$pageTitle = __('submit_title');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', __('submit_error'));
        redirect(url('submit.php'));
    }

    $language = $_POST['language'] ?? '';
    $category = $_POST['category'] ?? '';
    $format = $_POST['format'] ?? '';
    $content = trim($_POST['content'] ?? '');

    // Validate inputs
    $errors = [];

    if (!in_array($language, ['en', 'sw'])) {
        $errors[] = 'Invalid language.';
    }

    if (!in_array($category, getCategories())) {
        $errors[] = 'Invalid category.';
    }

    if (!in_array($format, ['quote', 'paragraph', 'lesson'])) {
        $errors[] = 'Invalid format.';
    }

    $maxLen = ($format === 'quote') ? 200 : 600;
    if (empty($content)) {
        $errors[] = 'Message content is required.';
    } elseif (mb_strlen($content) > $maxLen) {
        $errors[] = "Message exceeds {$maxLen} characters.";
    }

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO messages (language, category, `format`, content, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$language, $category, $format, $content]);

        // Reset CSRF token after successful submission
        unset($_SESSION['csrf_token']);

        setFlash('success', __('submit_success'));
        redirect(url('submit.php'));
    } else {
        setFlash('error', implode(' ', $errors));
    }
}

ob_start();
?>

<div class="container">
    <h1 class="section-title"><?= e(__('submit_title')) ?></h1>

    <div class="card" style="max-width: 650px; margin: 0 auto;">
        <p class="section-subtitle" style="text-align:center; font-style:italic; margin-bottom: 0.5rem;">
            &#127807; "<?= e(__('submit_prompt')) ?>"
        </p>
        <p class="form-hint text-center mb-3">
            <?= e(__('submit_guideline')) ?>
        </p>

        <form method="POST" action="<?= url('submit.php') ?>" id="submitForm">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="language"><?= e(__('select_language')) ?></label>
                <select name="language" id="language" class="form-control" required>
                    <option value="en" <?= ($_POST['language'] ?? '') === 'en' ? 'selected' : '' ?>><?= e(__('lang_en')) ?></option>
                    <option value="sw" <?= ($_POST['language'] ?? '') === 'sw' ? 'selected' : '' ?>><?= e(__('lang_sw')) ?></option>
                </select>
            </div>

            <div class="form-group">
                <label for="category"><?= e(__('select_category')) ?></label>
                <select name="category" id="category" class="form-control" required>
                    <?php foreach (getCategories() as $cat): ?>
                        <option value="<?= $cat ?>" <?= ($_POST['category'] ?? '') === $cat ? 'selected' : '' ?>><?= e(__('cat_' . $cat)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="format"><?= e(__('select_format')) ?></label>
                <select name="format" id="format" class="form-control" required>
                    <option value="quote" <?= ($_POST['format'] ?? '') === 'quote' ? 'selected' : '' ?>><?= e(__('format_quote')) ?> (≤ 200)</option>
                    <option value="paragraph" <?= ($_POST['format'] ?? '') === 'paragraph' ? 'selected' : '' ?>><?= e(__('format_paragraph')) ?> (≤ 600)</option>
                    <option value="lesson" <?= ($_POST['format'] ?? '') === 'lesson' ? 'selected' : '' ?>><?= e(__('format_lesson')) ?> (≤ 600)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="content"><?= e(__('message_content')) ?></label>
                <textarea name="content" id="content" class="form-control" required
                    maxlength="200" placeholder="<?= e(__('submit_prompt')) ?>"><?= e($_POST['content'] ?? '') ?></textarea>
                <div class="char-count" id="charCount">200 <?= e(__('char_remaining')) ?></div>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?= e(__('submit_button')) ?></button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
