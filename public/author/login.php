<?php
/**
 * Author Login Page
 */
require_once __DIR__ . '/../../app/config/init.php';
require_once APP_PATH . '/middleware/Auth.php';
require_once APP_PATH . '/models/User.php';

$pageTitle = __('author_login_title');

// Already logged in — redirect to dashboard
if (Auth::isAuthenticated()) {
    redirect(url('author/dashboard.php'));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = __('submit_error');
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $error = 'Please enter your username and password.';
        } else {
            $userModel = new User();
            $result = $userModel->authenticate($identifier, $password);

            if ($result['success']) {
                $user = $result['user'];

                // Check the user is an author
                // Re-fetch is_author from DB (User::authenticate doesn't return it)
                $db   = getDB();
                $chk  = $db->prepare("SELECT is_author FROM users WHERE id = ?");
                $chk->execute([$user['id']]);
                $row  = $chk->fetch();

                if (!$row || !$row['is_author']) {
                    $error = 'Your account does not have author access. Please register as an author.';
                } else {
                    $remember = isset($_POST['remember_me']);
                    Auth::login($user['id'], $remember);
                    Auth::cleanupExpiredSessions();
                    redirect(url('author/dashboard.php'));
                }
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}

ob_start();
?>

<div class="container">
    <div class="auth-page">
        <div class="auth-card card">
            <div class="auth-header">
                <div class="auth-icon">&#9997;</div>
                <h1><?= e(__('author_login_title')) ?></h1>
                <p><?= e(__('author_login_subtitle')) ?></p>
            </div>

            <?php if ($error): ?>
                <div class="flash flash-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= url('author/login.php') ?>">
                <?= csrfField() ?>

                <div class="form-group">
                    <label for="identifier"><?= e(__('author_username_label')) ?></label>
                    <input type="text" name="identifier" id="identifier" class="form-control"
                           placeholder="<?= e(__('author_username_placeholder')) ?>"
                           value="<?= e($_POST['identifier'] ?? '') ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password"><?= e(__('author_password_label')) ?></label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="password" class="form-control"
                               required placeholder="Your password">
                        <button type="button" class="pwd-toggle" onclick="togglePwd('password',this)" aria-label="Show">&#128065;</button>
                    </div>
                </div>

                <label class="remember-label">
                    <input type="checkbox" name="remember_me"> Remember me for 30 days
                </label>

                <button type="submit" class="btn btn-primary btn-full btn-lg"><?= e(__('author_login_btn')) ?></button>
            </form>

            <p class="auth-switch-link">
                <?= e(__('author_no_account')) ?>
                <a href="<?= url('author/register.php') ?>"><?= e(__('author_register_link')) ?></a>
            </p>
        </div>
    </div>
</div>

<script>
function togglePwd(id, btn) {
    const inp = document.getElementById(id);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}
</script>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
