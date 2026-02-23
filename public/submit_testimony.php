<?php
/**
 * Submit Testimony Page
 */
require_once __DIR__ . '/../app/config/init.php';

$pageTitle = __('testimony_submit_title');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', __('submit_error'));
        redirect(url('submit_testimony.php'));
    }

    $content = trim($_POST['content'] ?? '');
    $alias   = trim($_POST['alias'] ?? '');
    $lang    = in_array($_POST['language'] ?? '', ['en', 'sw']) ? $_POST['language'] : 'en';

    if (empty($content) || mb_strlen($content) < 20) {
        setFlash('error', __('submit_error'));
        redirect(url('submit_testimony.php'));
    }

    $alias = $alias !== '' ? mb_substr($alias, 0, 100) : null;
    $content = mb_substr($content, 0, 3000);

    $stmt = $db->prepare("INSERT INTO testimonies (alias, content, language, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->execute([$alias, $content, $lang]);

    unset($_SESSION['csrf_token']);
    setFlash('success', __('testimony_submitted'));
    redirect(url('testimonies.php'));
}

ob_start();
?>

<div class="container">
    <div class="page-hero text-center">
        <h1 class="section-title"><?= e(__('testimony_submit_title')) ?></h1>
        <p class="section-subtitle"><?= e(__('testimony_submit_prompt')) ?></p>
    </div>

    <div class="form-card">
        <form method="POST" action="<?= url('submit_testimony.php') ?>">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="language"><?= e(__('select_language')) ?></label>
                <select name="language" id="language" class="form-control">
                    <option value="en" <?= currentLang() === 'en' ? 'selected' : '' ?>><?= e(__('lang_en')) ?></option>
                    <option value="sw" <?= currentLang() === 'sw' ? 'selected' : '' ?>><?= e(__('lang_sw')) ?></option>
                </select>
            </div>

            <div class="form-group">
                <label for="alias"><?= e(__('testimony_alias_label')) ?></label>
                <input type="text" name="alias" id="alias" class="form-control"
                       placeholder="<?= e(__('testimony_alias_placeholder')) ?>"
                       maxlength="100" value="<?= e($_POST['alias'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="content"><?= e(__('testimony_content_label')) ?></label>
                <textarea name="content" id="content" class="form-control testimony-textarea"
                          placeholder="<?= e(__('testimony_content_placeholder')) ?>"
                          maxlength="3000" required rows="8"><?= e($_POST['content'] ?? '') ?></textarea>
                <p class="char-counter" id="testimonyCount">3000 <?= e(__('char_remaining')) ?></p>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg"><?= e(__('testimony_submit_btn')) ?></button>
                <a href="<?= url('testimonies.php') ?>" class="btn btn-secondary"><?= e(__('testimonies_title')) ?></a>
            </div>
        </form>
    </div>
</div>

<script>
const ta = document.getElementById('content');
const counter = document.getElementById('testimonyCount');
if (ta && counter) {
    ta.addEventListener('input', function() {
        const rem = 3000 - this.value.length;
        counter.textContent = rem + ' <?= currentLang() === 'sw' ? 'herufi zimebaki' : 'characters remaining' ?>';
        counter.classList.toggle('warning', rem < 50);
    });
}
</script>

<?php
$content_html = ob_get_clean();
$content = $content_html;
require_once APP_PATH . '/views/layout.php';
