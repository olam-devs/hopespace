<?php
/**
 * Author Registration Page
 */
require_once __DIR__ . '/../../app/config/init.php';
require_once APP_PATH . '/middleware/Auth.php';

$pageTitle = __('author_register_title');

// Already logged in — redirect to dashboard
if (Auth::isAuthenticated()) {
    redirect(url('author/dashboard.php'));
}

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = __('submit_error');
    } else {
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';
        $bio       = trim($_POST['bio'] ?? '');

        $formData = compact('username', 'email', 'bio');

        // Validate
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $errors[] = 'Username must be 3–50 characters (letters, numbers, underscore only).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $db = getDB();

            // Check username uniqueness
            $chk = $db->prepare("SELECT id FROM users WHERE username = ?");
            $chk->execute([$username]);
            if ($chk->fetch()) {
                $errors[] = 'This username is already taken. Please choose another.';
            }

            // Check email uniqueness
            $chkEmail = $db->prepare("SELECT id FROM users WHERE email = ?");
            $chkEmail->execute([$email]);
            if ($chkEmail->fetch()) {
                $errors[] = 'This email is already registered.';
            }
        }

        if (empty($errors)) {
            $db = getDB();
            $passHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("
                INSERT INTO users (username, email, password_hash, full_name, is_reader, is_author, language_preference, created_at)
                VALUES (?, ?, ?, ?, 1, 1, ?, NOW())
            ");
            $stmt->execute([$username, $email, $passHash, $username, currentLang()]);
            $userId = $db->lastInsertId();

            // Create profile row
            $db->prepare("INSERT INTO user_profiles (user_id, bio, avatar_type) VALUES (?, ?, 'generated')")
               ->execute([$userId, $bio ?: null]);

            // Auto log in
            Auth::login($userId, false);
            setFlash('success', 'Welcome, ' . $username . '! Your author account has been created.');
            redirect(url('author/dashboard.php'));
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
                <h1><?= e(__('author_register_title')) ?></h1>
                <p><?= e(__('author_register_subtitle')) ?></p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="flash flash-error">
                    <?php foreach ($errors as $err): ?>
                        <p><?= e($err) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('author/register.php') ?>">
                <?= csrfField() ?>

                <div class="form-group">
                    <label for="username"><?= e(__('author_username_label')) ?></label>
                    <input type="text" name="username" id="username" class="form-control"
                           placeholder="<?= e(__('author_username_placeholder')) ?>"
                           value="<?= e($formData['username'] ?? '') ?>" required
                           pattern="[a-zA-Z0-9_]{3,50}" maxlength="50">
                    <small class="form-hint">Letters, numbers and underscore only (3–50 chars)</small>
                </div>

                <div class="form-group">
                    <label for="email"><?= e(__('author_email_label')) ?></label>
                    <input type="email" name="email" id="email" class="form-control"
                           placeholder="<?= e(__('author_email_placeholder')) ?>"
                           value="<?= e($formData['email'] ?? '') ?>" required maxlength="255">
                </div>

                <div class="form-group">
                    <label for="password"><?= e(__('author_password_label')) ?></label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="password" class="form-control"
                               required minlength="8" placeholder="Min. 8 characters">
                        <button type="button" class="pwd-toggle" onclick="togglePwd('password', this)" aria-label="Show password">&#128065;</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password"><?= e(__('author_confirm_password_label')) ?></label>
                    <div class="password-wrap">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                               required minlength="8" placeholder="Repeat your password">
                        <button type="button" class="pwd-toggle" onclick="togglePwd('confirm_password', this)" aria-label="Show password">&#128065;</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="bio"><?= e(__('author_bio_label')) ?></label>
                    <textarea name="bio" id="bio" class="form-control" rows="3"
                              placeholder="<?= e(__('author_bio_placeholder')) ?>"
                              maxlength="500"><?= e($formData['bio'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg"><?= e(__('author_register_btn')) ?></button>
            </form>

            <p class="auth-switch-link">
                <?= e(__('author_already_account')) ?>
                <a href="<?= url('author/login.php') ?>"><?= e(__('author_sign_in_link')) ?></a>
            </p>
        </div>
    </div>
</div>

<script>
function togglePwd(id, btn) {
    const inp = document.getElementById(id);
    if (inp.type === 'password') { inp.type = 'text'; btn.textContent = '🙈'; }
    else { inp.type = 'password'; btn.textContent = '👁'; }
}
</script>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
