<?php
/**
 * Base Layout Template
 * Hope Space Platform
 */
$currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');
$switchLang = currentLang() === 'en' ? 'sw' : 'en';
$switchUrl = strtok($_SERVER['REQUEST_URI'], '?');
$params = $_GET;
$params['lang'] = $switchLang;
$switchLink = $switchUrl . '?' . http_build_query($params);
$siteLogo = getSetting('site_logo');
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e(__('tagline')) ?>">
    <title><?= e($pageTitle ?? __('site_name')) ?> — <?= e(__('site_name')) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <?php if ($siteLogo): ?>
        <link rel="icon" href="<?= BASE_URL ?>/assets/uploads/<?= e($siteLogo) ?>" type="image/png">
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <a href="<?= url('index.php') ?>" class="logo">
                <?php if ($siteLogo): ?>
                    <img src="<?= BASE_URL ?>/assets/uploads/<?= e($siteLogo) ?>" alt="Hope Space" class="logo-img">
                <?php else: ?>
                    <span class="logo-icon">&#127807;</span>
                <?php endif; ?>
                <span class="logo-text">
                    <span class="logo-hope">Hope</span><span class="logo-space">Space</span>
                </span>
            </a>

            <div class="header-actions">
                <a href="<?= e($switchLink) ?>" class="lang-switch"><?= e(__('switch_lang')) ?></a>
                <button class="nav-toggle" onclick="document.querySelector('.nav').classList.toggle('open')" aria-label="Menu">
                    &#9776;
                </button>
            </div>

            <nav class="nav">
                <a href="<?= url('index.php') ?>" class="<?= $currentPage === 'index' ? 'active' : '' ?>"><?= e(__('home')) ?></a>
                <a href="<?= url('messages.php') ?>" class="<?= $currentPage === 'messages' ? 'active' : '' ?>"><?= e(__('messages')) ?></a>
                <a href="<?= url('submit.php') ?>" class="<?= $currentPage === 'submit' ? 'active' : '' ?>"><?= e(__('submit')) ?></a>
                <a href="<?= url('resources.php') ?>" class="<?= $currentPage === 'resources' ? 'active' : '' ?>"><?= e(__('resources')) ?></a>
                <a href="<?= url('partners.php') ?>" class="<?= $currentPage === 'partners' ? 'active' : '' ?>"><?= e(__('partners')) ?></a>
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
            <div class="footer-brand">
                <?php if ($siteLogo): ?>
                    <img src="<?= BASE_URL ?>/assets/uploads/<?= e($siteLogo) ?>" alt="Hope Space" class="footer-logo">
                <?php endif; ?>
                <p><?= __('footer_text') ?></p>
            </div>
            <p><?= e(__('footer_privacy')) ?></p>
        </div>
    </footer>

    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
