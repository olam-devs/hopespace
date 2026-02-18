<?php
/**
 * Admin Panel - Moderation Dashboard
 */
require_once __DIR__ . '/../app/config/init.php';

$pageTitle = __('admin_dashboard');
$action = $_GET['action'] ?? 'dashboard';
$db = getDB();

// --- LOGIN ---
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', __('submit_error'));
            redirect(url('admin.php?action=login'));
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $db->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'];
            unset($_SESSION['csrf_token']);
            redirect(url('admin.php'));
        } else {
            setFlash('error', __('admin_login_error'));
            redirect(url('admin.php?action=login'));
        }
    }

    ob_start();
    ?>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <h2 class="section-title text-center"><?= e(__('admin_login')) ?></h2>
                <form method="POST" action="<?= url('admin.php?action=login') ?>">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label for="email"><?= e(__('admin_email')) ?></label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password"><?= e(__('admin_password')) ?></label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><?= e(__('admin_login_btn')) ?></button>
                </form>
            </div>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    require_once APP_PATH . '/views/layout.php';
    exit;
}

// --- LOGOUT ---
if ($action === 'logout') {
    unset($_SESSION['admin_id'], $_SESSION['admin_email'], $_SESSION['admin_role']);
    setFlash('success', 'Logged out.');
    redirect(url('admin.php?action=login'));
}

requireAdmin();

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', __('submit_error'));
        redirect(url('admin.php'));
    }

    $modAction = $_POST['mod_action'] ?? '';

    // --- Message moderation ---
    if (in_array($modAction, ['approve', 'reject', 'edit'])) {
        $messageId = (int)($_POST['message_id'] ?? 0);
        if ($messageId) {
            if ($modAction === 'approve') {
                $stmt = $db->prepare("UPDATE messages SET status = 'approved', published_at = NOW() WHERE id = ?");
                $stmt->execute([$messageId]);
                $stmt = $db->prepare("INSERT INTO audit_log (admin_id, message_id, action, created_at) VALUES (?, ?, 'approved', NOW())");
                $stmt->execute([$_SESSION['admin_id'], $messageId]);
                setFlash('success', 'Message approved.');
            } elseif ($modAction === 'reject') {
                $stmt = $db->prepare("UPDATE messages SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$messageId]);
                $stmt = $db->prepare("INSERT INTO audit_log (admin_id, message_id, action, created_at) VALUES (?, ?, 'rejected', NOW())");
                $stmt->execute([$_SESSION['admin_id'], $messageId]);
                setFlash('success', 'Message rejected.');
            } elseif ($modAction === 'edit') {
                $newContent = trim($_POST['content'] ?? '');
                if (!empty($newContent)) {
                    $stmt = $db->prepare("UPDATE messages SET content = ? WHERE id = ?");
                    $stmt->execute([$newContent, $messageId]);
                    $stmt = $db->prepare("INSERT INTO audit_log (admin_id, message_id, action, details, created_at) VALUES (?, ?, 'edited', ?, NOW())");
                    $stmt->execute([$_SESSION['admin_id'], $messageId, 'Content edited by admin.']);
                    setFlash('success', 'Message updated.');
                }
            }
        }
    }

    // --- Comment moderation ---
    if ($modAction === 'approve_comment') {
        $commentId = (int)($_POST['comment_id'] ?? 0);
        if ($commentId) {
            $db->prepare("UPDATE anonymous_comments SET status = 'approved', reviewed_at = NOW(), reviewed_by = ? WHERE id = ?")->execute([$_SESSION['admin_id'], $commentId]);
            $db->prepare("INSERT INTO audit_log (admin_id, comment_id, action, created_at) VALUES (?, ?, 'comment_approved', NOW())")->execute([$_SESSION['admin_id'], $commentId]);
            setFlash('success', 'Comment approved.');
        }
    }
    if ($modAction === 'reject_comment') {
        $commentId = (int)($_POST['comment_id'] ?? 0);
        if ($commentId) {
            $db->prepare("UPDATE anonymous_comments SET status = 'rejected', reviewed_at = NOW(), reviewed_by = ? WHERE id = ?")->execute([$_SESSION['admin_id'], $commentId]);
            $db->prepare("INSERT INTO audit_log (admin_id, comment_id, action, created_at) VALUES (?, ?, 'comment_rejected', NOW())")->execute([$_SESSION['admin_id'], $commentId]);
            setFlash('success', 'Comment rejected.');
        }
    }
    if ($modAction === 'delete_comment') {
        $commentId = (int)($_POST['comment_id'] ?? 0);
        if ($commentId) {
            $db->prepare("INSERT INTO audit_log (admin_id, comment_id, action, created_at) VALUES (?, ?, 'comment_deleted', NOW())")->execute([$_SESSION['admin_id'], $commentId]);
            $db->prepare("DELETE FROM anonymous_comments WHERE id = ?")->execute([$commentId]);
            setFlash('success', 'Comment deleted.');
        }
    }
    if ($modAction === 'edit_comment') {
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $newContent = trim($_POST['comment_content'] ?? '');
        if ($commentId && !empty($newContent)) {
            $db->prepare("UPDATE anonymous_comments SET content = ? WHERE id = ?")->execute([$newContent, $commentId]);
            $db->prepare("INSERT INTO audit_log (admin_id, comment_id, action, details, created_at) VALUES (?, ?, 'comment_edited', 'Comment edited by admin.', NOW())")->execute([$_SESSION['admin_id'], $commentId]);
            setFlash('success', 'Comment updated.');
        }
    }

    // --- Bulk comment actions ---
    if (in_array($modAction, ['bulk_approve_comments', 'bulk_reject_comments', 'bulk_delete_comments'])) {
        $selectedIds = $_POST['comment_ids'] ?? [];
        if (!empty($selectedIds)) {
            $selectedIds = array_map('intval', $selectedIds);
            $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
            if ($modAction === 'bulk_approve_comments') {
                $db->prepare("UPDATE anonymous_comments SET status = 'approved', reviewed_at = NOW(), reviewed_by = ? WHERE id IN ($placeholders)")->execute(array_merge([$_SESSION['admin_id']], $selectedIds));
                setFlash('success', count($selectedIds) . ' comment(s) approved.');
            } elseif ($modAction === 'bulk_reject_comments') {
                $db->prepare("UPDATE anonymous_comments SET status = 'rejected', reviewed_at = NOW(), reviewed_by = ? WHERE id IN ($placeholders)")->execute(array_merge([$_SESSION['admin_id']], $selectedIds));
                setFlash('success', count($selectedIds) . ' comment(s) rejected.');
            } elseif ($modAction === 'bulk_delete_comments') {
                $db->prepare("DELETE FROM anonymous_comments WHERE id IN ($placeholders)")->execute($selectedIds);
                setFlash('success', count($selectedIds) . ' comment(s) deleted.');
            }
        }
    }

    // --- Add admin ---
    if ($modAction === 'add_admin') {
        $newEmail = trim($_POST['new_email'] ?? '');
        $newPass = $_POST['new_password'] ?? '';
        $newRole = $_POST['new_role'] ?? 'moderator';
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', __('admin_invalid_email'));
        } elseif (strlen($newPass) < 6) {
            setFlash('error', __('admin_pass_short'));
        } else {
            $stmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$newEmail]);
            if ($stmt->fetch()) {
                setFlash('error', __('admin_email_exists'));
            } else {
                $db->prepare("INSERT INTO admins (email, password_hash, role, created_at) VALUES (?, ?, ?, NOW())")->execute([$newEmail, password_hash($newPass, PASSWORD_DEFAULT), $newRole]);
                setFlash('success', __('admin_added'));
            }
        }
    }

    // --- Reset password ---
    if ($modAction === 'reset_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';
        $stmt = $db->prepare("SELECT password_hash FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        if (!$admin || !password_verify($currentPass, $admin['password_hash'])) {
            setFlash('error', __('admin_wrong_password'));
        } elseif (strlen($newPass) < 6) {
            setFlash('error', __('admin_pass_short'));
        } elseif ($newPass !== $confirmPass) {
            setFlash('error', __('admin_pass_mismatch'));
        } else {
            $db->prepare("UPDATE admins SET password_hash = ? WHERE id = ?")->execute([password_hash($newPass, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
            setFlash('success', __('admin_pass_changed'));
        }
    }

    // --- Delete admin ---
    if ($modAction === 'delete_admin') {
        $deleteId = (int)($_POST['admin_id'] ?? 0);
        if ($deleteId && $deleteId !== (int)$_SESSION['admin_id']) {
            $db->prepare("DELETE FROM admins WHERE id = ?")->execute([$deleteId]);
            setFlash('success', __('admin_deleted'));
        } else {
            setFlash('error', __('admin_cant_delete_self'));
        }
    }

    unset($_SESSION['csrf_token']);
    $redirectAction = $action === 'dashboard' ? '' : $action;
    $redirectUrl = 'admin.php?action=' . $redirectAction;
    if (!empty($_GET['post_id'])) $redirectUrl .= '&post_id=' . (int)$_GET['post_id'];
    redirect(url($redirectUrl));
}

// --- DASHBOARD STATS ---
$pendingCount = $db->query("SELECT COUNT(*) FROM messages WHERE status = 'pending'")->fetchColumn();
$approvedCount = $db->query("SELECT COUNT(*) FROM messages WHERE status = 'approved'")->fetchColumn();
$rejectedCount = $db->query("SELECT COUNT(*) FROM messages WHERE status = 'rejected'")->fetchColumn();
$totalReactions = $db->query("SELECT COUNT(*) FROM reactions")->fetchColumn();
$pendingCommentsCount = $db->query("SELECT COUNT(*) FROM anonymous_comments WHERE status = 'pending'")->fetchColumn();

// --- FETCH MESSAGES ---
$search = trim($_GET['search'] ?? '');
$adminFilterLang = $_GET['filter_lang'] ?? '';
$adminFilterCat = $_GET['filter_cat'] ?? '';
$filterStatus = $action;

if (in_array($action, ['dashboard', 'admins', 'audit', 'password', 'top_reacted', 'comments', 'post_comments'])) {
    $filterStatus = 'pending';
}

$isTopReacted = ($action === 'top_reacted');
$validStatuses = ['pending', 'approved', 'rejected'];
if (!in_array($filterStatus, $validStatuses) && !$isTopReacted) $filterStatus = 'pending';

$where = [];
$qParams = [];
if ($isTopReacted) { $where[] = "m.status = 'approved'"; } else { $where[] = "m.status = ?"; $qParams[] = $filterStatus; }
if ($search) { $where[] = "m.content LIKE ?"; $qParams[] = '%' . $search . '%'; }
if ($adminFilterLang && in_array($adminFilterLang, ['en', 'sw'])) { $where[] = "m.language = ?"; $qParams[] = $adminFilterLang; }
if ($adminFilterCat && in_array($adminFilterCat, getCategories())) { $where[] = "m.category = ?"; $qParams[] = $adminFilterCat; }

$whereSQL = implode(' AND ', $where);
$orderBy = $isTopReacted ? "reaction_count DESC, m.created_at DESC" : "m.created_at DESC";

$query = "SELECT m.*, COALESCE(rc.reaction_count, 0) AS reaction_count, COALESCE(rc.helped_count, 0) AS helped_count, COALESCE(rc.hope_count, 0) AS hope_count, COALESCE(rc.not_alone_count, 0) AS not_alone_count
FROM messages m LEFT JOIN (SELECT message_id, COUNT(*) AS reaction_count, SUM(CASE WHEN type = 'helped' THEN 1 ELSE 0 END) AS helped_count, SUM(CASE WHEN type = 'hope' THEN 1 ELSE 0 END) AS hope_count, SUM(CASE WHEN type = 'not_alone' THEN 1 ELSE 0 END) AS not_alone_count FROM reactions GROUP BY message_id) rc ON rc.message_id = m.id
WHERE $whereSQL ORDER BY $orderBy LIMIT 50";
$stmt = $db->prepare($query);
$stmt->execute($qParams);
$messages = $stmt->fetchAll();

$topMsgId = 0;
if (!empty($messages)) { $topCount = 0; foreach ($messages as $msg) { if ($msg['reaction_count'] > $topCount) { $topCount = $msg['reaction_count']; $topMsgId = $msg['id']; } } if ($topCount === 0) $topMsgId = 0; }

$auditLog = [];
if ($action === 'audit') {
    $auditLog = $db->query("SELECT al.*, a.email, COALESCE(m.content, '') AS message_content, COALESCE(c.content, '') AS comment_content FROM audit_log al JOIN admins a ON al.admin_id = a.id LEFT JOIN messages m ON al.message_id = m.id LEFT JOIN anonymous_comments c ON al.comment_id = c.id ORDER BY al.created_at DESC LIMIT 100")->fetchAll();
}

$adminsList = [];
if ($action === 'admins') { $adminsList = $db->query("SELECT id, email, role, created_at FROM admins ORDER BY created_at ASC")->fetchAll(); }

$pendingComments = [];
if ($action === 'comments') {
    $pendingComments = $db->query("SELECT ac.*, m.content AS message_content, m.category, m.format FROM anonymous_comments ac JOIN messages m ON ac.message_id = m.id WHERE ac.status = 'pending' ORDER BY ac.created_at DESC LIMIT 100")->fetchAll();
}

$postComments = [];
$postMessage = null;
if ($action === 'post_comments') {
    $postId = (int)($_GET['post_id'] ?? 0);
    if ($postId) {
        $stmt = $db->prepare("SELECT * FROM messages WHERE id = ?"); $stmt->execute([$postId]); $postMessage = $stmt->fetch();
        if ($postMessage) {
            $commentStatus = $_GET['comment_status'] ?? '';
            $cWhere = "ac.message_id = ?"; $cParams = [$postId];
            if ($commentStatus && in_array($commentStatus, ['pending', 'approved', 'rejected'])) { $cWhere .= " AND ac.status = ?"; $cParams[] = $commentStatus; }
            $stmt = $db->prepare("SELECT ac.* FROM anonymous_comments ac WHERE $cWhere ORDER BY ac.created_at DESC"); $stmt->execute($cParams); $postComments = $stmt->fetchAll();
        }
    }
}

ob_start();
?>
<div class="container">
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <h3 style="margin-bottom: 1rem; color: var(--primary-dark);"><?= e(__('admin_dashboard')) ?></h3>
            <a href="<?= url('admin.php') ?>" class="<?= $action === 'dashboard' ? 'active' : '' ?>"><?= e(__('admin_pending')) ?> (<?= $pendingCount ?>)</a>
            <a href="<?= url('admin.php?action=approved') ?>" class="<?= $action === 'approved' ? 'active' : '' ?>"><?= e(__('admin_approved')) ?> (<?= $approvedCount ?>)</a>
            <a href="<?= url('admin.php?action=rejected') ?>" class="<?= $action === 'rejected' ? 'active' : '' ?>"><?= e(__('admin_rejected')) ?> (<?= $rejectedCount ?>)</a>
            <a href="<?= url('admin.php?action=comments') ?>" class="<?= $action === 'comments' ? 'active' : '' ?>"><?= e(__('admin_comments')) ?> (<?= $pendingCommentsCount ?>)</a>
            <a href="<?= url('admin.php?action=top_reacted') ?>" class="<?= $action === 'top_reacted' ? 'active' : '' ?>"><?= e(__('admin_top_reacted')) ?></a>
            <a href="<?= url('admin.php?action=audit') ?>" class="<?= $action === 'audit' ? 'active' : '' ?>"><?= e(__('admin_audit')) ?></a>
            <hr style="margin: 1rem 0; border-color: var(--border-light);">
            <a href="<?= url('admin_analytics.php') ?>"><?= e(__('admin_analytics')) ?></a>
            <a href="<?= url('admin_resources.php') ?>"><?= e(__('admin_resources')) ?></a>
            <a href="<?= url('admin_partners.php') ?>"><?= e(__('admin_partners')) ?></a>
            <hr style="margin: 1rem 0; border-color: var(--border-light);">
            <a href="<?= url('admin.php?action=admins') ?>" class="<?= $action === 'admins' ? 'active' : '' ?>"><?= e(__('admin_manage_admins')) ?></a>
            <a href="<?= url('admin.php?action=password') ?>" class="<?= $action === 'password' ? 'active' : '' ?>"><?= e(__('admin_change_password')) ?></a>
            <hr style="margin: 1rem 0; border-color: var(--border-light);">
            <p class="text-muted" style="font-size: 0.8rem; padding: 0 1rem;"><?= e($_SESSION['admin_email']) ?></p>
            <a href="<?= url('admin.php?action=logout') ?>"><?= e(__('admin_logout')) ?></a>
        </aside>

        <div class="admin-content">
            <div class="stat-cards">
                <div class="stat-card"><div class="stat-number"><?= $pendingCount ?></div><div class="stat-label"><?= e(__('admin_pending')) ?></div></div>
                <div class="stat-card"><div class="stat-number"><?= $approvedCount ?></div><div class="stat-label"><?= e(__('admin_approved')) ?></div></div>
                <div class="stat-card"><div class="stat-number"><?= $pendingCommentsCount ?></div><div class="stat-label"><?= e(__('admin_comments')) ?></div></div>
                <div class="stat-card"><div class="stat-number"><?= $totalReactions ?></div><div class="stat-label"><?= e(__('admin_total_reactions')) ?></div></div>
            </div>

            <?php if ($action === 'password'): ?>
                <h2 class="section-title"><?= e(__('admin_change_password')) ?></h2>
                <div class="card" style="max-width: 450px;">
                    <form method="POST" action="<?= url('admin.php?action=password') ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="mod_action" value="reset_password">
                        <div class="form-group"><label><?= e(__('admin_current_password')) ?></label><input type="password" name="current_password" class="form-control" required></div>
                        <div class="form-group"><label><?= e(__('admin_new_password')) ?></label><input type="password" name="new_password" class="form-control" required minlength="6"><p class="form-hint"><?= e(__('admin_pass_hint')) ?></p></div>
                        <div class="form-group"><label><?= e(__('admin_confirm_password')) ?></label><input type="password" name="confirm_password" class="form-control" required minlength="6"></div>
                        <button type="submit" class="btn btn-primary"><?= e(__('admin_save')) ?></button>
                    </form>
                </div>

            <?php elseif ($action === 'admins'): ?>
                <h2 class="section-title"><?= e(__('admin_manage_admins')) ?></h2>
                <div class="card mb-3">
                    <h3 style="margin-bottom: 1rem; font-size: 1.1rem;"><?= e(__('admin_add_new')) ?></h3>
                    <form method="POST" action="<?= url('admin.php?action=admins') ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="mod_action" value="add_admin">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 150px auto; gap: 0.75rem; align-items: end;">
                            <div class="form-group" style="margin-bottom: 0;"><label><?= e(__('admin_email')) ?></label><input type="email" name="new_email" class="form-control" required></div>
                            <div class="form-group" style="margin-bottom: 0;"><label><?= e(__('admin_password')) ?></label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
                            <div class="form-group" style="margin-bottom: 0;"><label><?= e(__('admin_role_label')) ?></label><select name="new_role" class="form-control"><option value="admin">Admin</option><option value="moderator">Moderator</option></select></div>
                            <button type="submit" class="btn btn-primary"><?= e(__('admin_add_btn')) ?></button>
                        </div>
                    </form>
                </div>
                <?php foreach ($adminsList as $adm): ?>
                    <div class="admin-message">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                            <div>
                                <strong><?= e($adm['email']) ?></strong>
                                <span class="badge badge-category" style="margin-left: 0.5rem;"><?= e($adm['role']) ?></span>
                                <?php if ((int)$adm['id'] === (int)$_SESSION['admin_id']): ?><span class="badge badge-approved" style="margin-left: 0.25rem;"><?= e(__('admin_you')) ?></span><?php endif; ?>
                                <p class="text-muted" style="font-size: 0.8rem; margin-top: 0.25rem;"><?= e(__('admin_joined')) ?>: <?= date('M j, Y', strtotime($adm['created_at'])) ?></p>
                            </div>
                            <?php if ((int)$adm['id'] !== (int)$_SESSION['admin_id']): ?>
                                <form method="POST" action="<?= url('admin.php?action=admins') ?>" onsubmit="return confirm('<?= e(__('admin_delete_confirm')) ?>');">
                                    <?= csrfField() ?><input type="hidden" name="mod_action" value="delete_admin"><input type="hidden" name="admin_id" value="<?= $adm['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><?= e(__('admin_remove')) ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php elseif ($action === 'audit'): ?>
                <h2 class="section-title"><?= e(__('admin_audit')) ?></h2>
                <?php if (empty($auditLog)): ?><p class="text-muted">No audit records yet.</p>
                <?php else: ?>
                    <?php foreach ($auditLog as $log): ?>
                        <div class="admin-message">
                            <div class="message-meta">
                                <?php $bc = 'pending'; if (strpos($log['action'], 'approved') !== false) $bc = 'approved'; elseif (strpos($log['action'], 'rejected') !== false || strpos($log['action'], 'deleted') !== false) $bc = 'rejected'; ?>
                                <span class="badge badge-<?= $bc ?>"><?= e($log['action']) ?></span>
                                <span class="text-muted"><?= e($log['email']) ?></span>
                                <span class="message-date"><?= date('M j, Y H:i', strtotime($log['created_at'])) ?></span>
                            </div>
                            <?php $lc = !empty($log['message_content']) ? $log['message_content'] : $log['comment_content']; ?>
                            <?php if ($lc): ?><p class="message-content" style="font-size: 0.9rem; margin-top: 0.5rem;"><?= e(mb_substr($lc, 0, 150)) ?><?= mb_strlen($lc) > 150 ? '...' : '' ?></p><?php endif; ?>
                            <?php if (!empty($log['details'])): ?><p class="form-hint"><?= e($log['details']) ?></p><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php elseif ($action === 'comments'): ?>
                <h2 class="section-title"><?= e(__('admin_comments')) ?></h2>
                <?php if (empty($pendingComments)): ?>
                    <div class="card text-center" style="padding: 2rem;"><p class="text-muted"><?= e(__('admin_no_pending_comments')) ?></p></div>
                <?php else: ?>
                    <form method="POST" action="<?= url('admin.php?action=comments') ?>" id="bulkCommentForm">
                        <?= csrfField() ?>
                        <div class="bulk-actions">
                            <label><input type="checkbox" id="selectAllComments" onclick="toggleAllComments(this)"> <?= e(__('admin_select_all')) ?></label>
                            <button type="submit" name="mod_action" value="bulk_approve_comments" class="btn btn-success btn-sm"><?= e(__('admin_approve_selected')) ?></button>
                            <button type="submit" name="mod_action" value="bulk_reject_comments" class="btn btn-danger btn-sm"><?= e(__('admin_reject_selected')) ?></button>
                            <button type="submit" name="mod_action" value="bulk_delete_comments" class="btn btn-secondary btn-sm" onclick="return confirm('Delete selected?')"><?= e(__('admin_delete_selected')) ?></button>
                        </div>
                        <?php foreach ($pendingComments as $pc): ?>
                            <div class="comment-admin-card">
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                    <input type="checkbox" name="comment_ids[]" value="<?= $pc['id'] ?>" class="comment-checkbox" style="margin-top: 0.3rem;">
                                    <div style="flex: 1;">
                                        <div style="background: var(--bg-warm); padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); margin-bottom: 0.5rem; font-size: 0.85rem;">
                                            <span class="badge badge-format" style="font-size: 0.7rem;"><?= e(__('format_' . $pc['format'])) ?></span>
                                            <span class="badge badge-category" style="font-size: 0.7rem;"><?= e(__('cat_' . $pc['category'])) ?></span>
                                            <span class="text-muted" style="font-size: 0.8rem;"><?= e(mb_substr($pc['message_content'], 0, 100)) ?><?= mb_strlen($pc['message_content']) > 100 ? '...' : '' ?></span>
                                            <a href="<?= url('admin.php?action=post_comments&post_id=' . $pc['message_id']) ?>" class="btn btn-sm" style="padding: 0.15rem 0.5rem; font-size: 0.75rem; float: right;"><?= e(__('admin_view_comments')) ?></a>
                                        </div>
                                        <div class="comment-header"><span class="comment-alias"><?= e($pc['anonymous_alias']) ?></span><span class="comment-date"><?= date('M j, Y H:i', strtotime($pc['created_at'])) ?></span></div>
                                        <p class="comment-text"><?= e($pc['content']) ?></p>
                                    </div>
                                </div>
                                <div class="admin-actions" style="margin-left: 1.75rem;">
                                    <form method="POST" action="<?= url('admin.php?action=comments') ?>" style="display:inline;"><?= csrfField() ?><input type="hidden" name="mod_action" value="approve_comment"><input type="hidden" name="comment_id" value="<?= $pc['id'] ?>"><button type="submit" class="btn btn-success btn-sm"><?= e(__('admin_approve')) ?></button></form>
                                    <form method="POST" action="<?= url('admin.php?action=comments') ?>" style="display:inline;"><?= csrfField() ?><input type="hidden" name="mod_action" value="reject_comment"><input type="hidden" name="comment_id" value="<?= $pc['id'] ?>"><button type="submit" class="btn btn-danger btn-sm"><?= e(__('admin_reject')) ?></button></form>
                                    <form method="POST" action="<?= url('admin.php?action=comments') ?>" style="display:inline;" onclick="return confirm('Delete permanently?')"><?= csrfField() ?><input type="hidden" name="mod_action" value="delete_comment"><input type="hidden" name="comment_id" value="<?= $pc['id'] ?>"><button type="submit" class="btn btn-secondary btn-sm">&#128465; Delete</button></form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </form>
                <?php endif; ?>

            <?php elseif ($action === 'post_comments' && $postMessage): ?>
                <h2 class="section-title"><?= e(__('admin_manage_post_comments')) ?></h2>
                <div class="admin-message mb-3">
                    <div class="message-meta">
                        <span class="badge badge-category"><?= e(__('cat_' . $postMessage['category'])) ?></span>
                        <span class="badge badge-format"><?= e(__('format_' . $postMessage['format'])) ?></span>
                        <span class="badge badge-<?= $postMessage['status'] ?>"><?= e($postMessage['status']) ?></span>
                    </div>
                    <div class="message-content <?= $postMessage['format'] === 'quote' ? 'quote' : ($postMessage['format'] === 'question' ? 'question' : '') ?>" style="margin-top: 0.75rem;"><?= e($postMessage['content']) ?></div>
                </div>
                <div class="filters" style="margin-bottom: 1rem;">
                    <?php foreach (['', 'pending', 'approved', 'rejected'] as $cs): ?>
                        <a href="<?= url('admin.php?action=post_comments&post_id=' . $postMessage['id'] . ($cs ? '&comment_status=' . $cs : '')) ?>" class="btn btn-sm <?= ($_GET['comment_status'] ?? '') === $cs ? 'btn-primary' : 'btn-secondary' ?>"><?= e(ucfirst($cs ?: 'All')) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($postComments)): ?>
                    <div class="card text-center" style="padding: 2rem;"><p class="text-muted"><?= e(__('admin_no_pending_comments')) ?></p></div>
                <?php else: ?>
                    <form method="POST" action="<?= url('admin.php?action=post_comments&post_id=' . $postMessage['id']) ?>">
                        <?= csrfField() ?>
                        <div class="bulk-actions">
                            <label><input type="checkbox" onclick="toggleAllComments(this)"> <?= e(__('admin_select_all')) ?></label>
                            <button type="submit" name="mod_action" value="bulk_approve_comments" class="btn btn-success btn-sm"><?= e(__('admin_approve_selected')) ?></button>
                            <button type="submit" name="mod_action" value="bulk_reject_comments" class="btn btn-danger btn-sm"><?= e(__('admin_reject_selected')) ?></button>
                            <button type="submit" name="mod_action" value="bulk_delete_comments" class="btn btn-secondary btn-sm" onclick="return confirm('Delete selected?')"><?= e(__('admin_delete_selected')) ?></button>
                        </div>
                        <?php foreach ($postComments as $pc): ?>
                            <div class="comment-admin-card">
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                    <input type="checkbox" name="comment_ids[]" value="<?= $pc['id'] ?>" class="comment-checkbox" style="margin-top: 0.3rem;">
                                    <div style="flex: 1;">
                                        <div class="comment-header">
                                            <span class="comment-alias"><?= e($pc['anonymous_alias']) ?></span>
                                            <div><span class="badge badge-<?= $pc['status'] ?>" style="font-size: 0.7rem;"><?= e($pc['status']) ?></span> <span class="comment-date"><?= date('M j, Y H:i', strtotime($pc['created_at'])) ?></span></div>
                                        </div>
                                        <p class="comment-text"><?= e($pc['content']) ?></p>
                                        <div class="edit-form" id="edit-comment-<?= $pc['id'] ?>" style="display: none; margin-top: 0.5rem;">
                                            <form method="POST" action="<?= url('admin.php?action=post_comments&post_id=' . $postMessage['id']) ?>">
                                                <?= csrfField() ?><input type="hidden" name="mod_action" value="edit_comment"><input type="hidden" name="comment_id" value="<?= $pc['id'] ?>">
                                                <textarea name="comment_content" class="form-control" style="min-height: 60px;"><?= e($pc['content']) ?></textarea>
                                                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                                    <button type="submit" class="btn btn-primary btn-sm"><?= e(__('admin_save')) ?></button>
                                                    <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('edit-comment-<?= $pc['id'] ?>').style.display='none'"><?= e(__('admin_cancel')) ?></button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="admin-actions" style="margin-left: 1.75rem;">
                                    <?php if ($pc['status'] !== 'approved'): ?><form method="POST" action="<?= url('admin.php?action=post_comments&post_id=' . $postMessage['id']) ?>" style="display:inline;"><?= csrfField() ?><input type="hidden" name="mod_action" value="approve_comment"><input type="hidden" name="comment_id" value="<?= $pc['id'] ?>"><button type="submit" class="btn btn-success btn-sm"><?= e(__('admin_approve')) ?></button></form><?php endif; ?>
                                    <button class="btn btn-secondary btn-sm" onclick="document.getElementById('edit-comment-<?= $pc['id'] ?>').style.display='block'"><?= e(__('admin_edit')) ?></button>
                                    <?php if ($pc['status'] !== 'rejected'): ?><form method="POST" action="<?= url('admin.php?action=post_comments&post_id=' . $postMessage['id']) ?>" style="display:inline;"><?= csrfField() ?><input type="hidden" name="mod_action" value="reject_comment"><input type="hidden" name="comment_id" value="<?= $pc['id'] ?>"><button type="submit" class="btn btn-danger btn-sm"><?= e(__('admin_reject')) ?></button></form><?php endif; ?>
                                    <form method="POST" action="<?= url('admin.php?action=post_comments&post_id=' . $postMessage['id']) ?>" style="display:inline;" onclick="return confirm('Delete permanently?')"><?= csrfField() ?><input type="hidden" name="mod_action" value="delete_comment"><input type="hidden" name="comment_id" value="<?= $pc['id'] ?>"><button type="submit" class="btn btn-secondary btn-sm">&#128465;</button></form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </form>
                <?php endif; ?>
                <a href="<?= url('admin.php?action=comments') ?>" class="btn btn-secondary mt-2">&larr; <?= e(__('admin_comments')) ?></a>

            <?php else: ?>
                <!-- Search & Filters -->
                <form class="filters" method="GET" action="<?= BASE_URL ?>/admin.php">
                    <input type="hidden" name="lang" value="<?= currentLang() ?>">
                    <input type="hidden" name="action" value="<?= e($action) ?>">
                    <input type="text" name="search" class="form-control" placeholder="<?= e(__('admin_search')) ?>" value="<?= e($search) ?>" style="flex: 1;">
                    <select name="filter_lang" onchange="this.form.submit()">
                        <option value=""><?= e(__('admin_filter_language')) ?>: <?= e(__('filter_all')) ?></option>
                        <option value="en" <?= $adminFilterLang === 'en' ? 'selected' : '' ?>><?= e(__('lang_en')) ?></option>
                        <option value="sw" <?= $adminFilterLang === 'sw' ? 'selected' : '' ?>><?= e(__('lang_sw')) ?></option>
                    </select>
                    <select name="filter_cat" onchange="this.form.submit()">
                        <option value=""><?= e(__('admin_filter_category')) ?>: <?= e(__('filter_all')) ?></option>
                        <?php foreach (getCategories() as $cat): ?><option value="<?= $cat ?>" <?= $adminFilterCat === $cat ? 'selected' : '' ?>><?= e(__('cat_' . $cat)) ?></option><?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm"><?= e(__('admin_search')) ?></button>
                </form>

                <?php if (empty($messages)): ?>
                    <div class="card text-center" style="padding: 2rem;"><p class="text-muted"><?= e(__('admin_no_pending')) ?></p></div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php $ccStmt = $db->prepare("SELECT COUNT(*) FROM anonymous_comments WHERE message_id = ?"); $ccStmt->execute([$msg['id']]); $msgCC = $ccStmt->fetchColumn(); ?>
                        <div class="admin-message<?= ($topMsgId === (int)$msg['id'] && $msg['reaction_count'] > 0) ? ' top-reacted' : '' ?>">
                            <div class="message-meta">
                                <span class="badge badge-category"><?= e(__('cat_' . $msg['category'])) ?></span>
                                <span class="badge badge-language"><?= e(__('lang_' . $msg['language'])) ?></span>
                                <span class="badge badge-format"><?= e(__('format_' . $msg['format'])) ?></span>
                                <span class="badge badge-<?= $msg['status'] ?>"><?= e($msg['status']) ?></span>
                                <?php if ($topMsgId === (int)$msg['id'] && $msg['reaction_count'] > 0): ?><span class="badge badge-top-reacted"><?= e(__('admin_most_reacted_badge')) ?></span><?php endif; ?>
                                <span class="message-date"><?= date('M j, Y H:i', strtotime($msg['created_at'])) ?></span>
                            </div>
                            <div class="message-content <?= $msg['format'] === 'quote' ? 'quote' : ($msg['format'] === 'question' ? 'question' : '') ?>" style="margin-top: 0.75rem;"><?= e($msg['content']) ?></div>
                            <?php if ($msg['reaction_count'] > 0): ?>
                                <div class="reaction-stats">
                                    <span class="reaction-stat">&#128156; <?= $msg['helped_count'] ?></span>
                                    <span class="reaction-stat">&#127793; <?= $msg['hope_count'] ?></span>
                                    <span class="reaction-stat">&#129309; <?= $msg['not_alone_count'] ?></span>
                                    <span class="reaction-stat-total"><?= e(__('admin_total')) ?>: <?= $msg['reaction_count'] ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($msgCC > 0): ?><a href="<?= url('admin.php?action=post_comments&post_id=' . $msg['id']) ?>" class="btn btn-sm btn-secondary mt-1">&#128172; <?= e(__('admin_view_comments')) ?> (<?= $msgCC ?>)</a><?php endif; ?>
                            <div class="edit-form" id="edit-<?= $msg['id'] ?>" style="display: none; margin-top: 0.75rem;">
                                <form method="POST" action="<?= url('admin.php?action=' . $action) ?>">
                                    <?= csrfField() ?><input type="hidden" name="mod_action" value="edit"><input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                    <textarea name="content" class="form-control"><?= e($msg['content']) ?></textarea>
                                    <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                        <button type="submit" class="btn btn-primary btn-sm"><?= e(__('admin_save')) ?></button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('edit-<?= $msg['id'] ?>').style.display='none'"><?= e(__('admin_cancel')) ?></button>
                                    </div>
                                </form>
                            </div>
                            <div class="admin-actions">
                                <?php if ($msg['status'] !== 'approved'): ?><form method="POST" action="<?= url('admin.php?action=' . $action) ?>" style="display:inline;"><?= csrfField() ?><input type="hidden" name="mod_action" value="approve"><input type="hidden" name="message_id" value="<?= $msg['id'] ?>"><button type="submit" class="btn btn-success btn-sm"><?= e(__('admin_approve')) ?></button></form><?php endif; ?>
                                <button class="btn btn-secondary btn-sm" onclick="document.getElementById('edit-<?= $msg['id'] ?>').style.display='block'"><?= e(__('admin_edit')) ?></button>
                                <?php if ($msg['status'] !== 'rejected'): ?><form method="POST" action="<?= url('admin.php?action=' . $action) ?>" style="display:inline;"><?= csrfField() ?><input type="hidden" name="mod_action" value="reject"><input type="hidden" name="message_id" value="<?= $msg['id'] ?>"><button type="submit" class="btn btn-danger btn-sm"><?= e(__('admin_reject')) ?></button></form><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>function toggleAllComments(master) { const form = master.closest('form'); form.querySelectorAll('.comment-checkbox').forEach(cb => cb.checked = master.checked); }</script>
<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
