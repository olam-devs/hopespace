<?php
/**
 * Base Layout Template
 * Wraps all pages with header, footer, disclaimer
 */
$currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');
$switchLang = currentLang() === 'en' ? 'sw' : 'en';
$switchUrl = strtok($_SERVER['REQUEST_URI'], '?');
// Preserve existing query params but swap lang
$params = $_GET;
$params['lang'] = $switchLang;
$switchLink = $switchUrl . '?' . http_build_query($params);
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e(__('tagline')) ?>">
    <title><?= e($pageTitle ?? __('site_name')) ?> — <?= e(__('site_name')) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <a href="<?= url('index.php') ?>" class="logo">
                <span class="logo-icon">&#127807;</span>
                <span><?= e(__('site_name')) ?></span>
            </a>

            <button class="nav-toggle" onclick="document.querySelector('.nav').classList.toggle('open')" aria-label="Menu">
                &#9776;
            </button>

            <nav class="nav">
                <a href="<?= url('index.php') ?>" class="<?= $currentPage === 'index' ? 'active' : '' ?>"><?= e(__('home')) ?></a>
                <a href="<?= url('messages.php') ?>" class="<?= $currentPage === 'messages' ? 'active' : '' ?>"><?= e(__('messages')) ?></a>
                <a href="<?= url('submit.php') ?>" class="<?= $currentPage === 'submit' ? 'active' : '' ?>"><?= e(__('submit')) ?></a>
                <a href="<?= url('resources.php') ?>" class="<?= $currentPage === 'resources' ? 'active' : '' ?>"><?= e(__('resources')) ?></a>
                <a href="<?= url('partners.php') ?>" class="<?= $currentPage === 'partners' ? 'active' : '' ?>"><?= e(__('partners')) ?></a>
                <a href="<?= e($switchLink) ?>" class="lang-switch"><?= e(__('switch_lang')) ?></a>
            </nav>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php $flash = getFlash(); ?>
    <?php if ($flash): ?>
        <div class="container mt-2">
            <div class="flash flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
                <?= e($flash['message']) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Page Content -->
    <main>
        <?= $content ?? '' ?>
    </main>

    <!-- Disclaimer -->
    <div class="container">
        <div class="disclaimer">
            <?= e(__('disclaimer')) ?>
            — <a href="<?= url('resources.php') ?>"><?= e(__('resources')) ?></a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p><?= __('footer_text') ?></p>
            <p><?= e(__('footer_privacy')) ?></p>
        </div>
    </footer>

    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
