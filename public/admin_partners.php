<?php
/**
 * Admin - Manage Partners
 */
require_once __DIR__ . '/../app/config/init.php';

$pageTitle = __('admin_partners');
$db = getDB();
requireAdmin();

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', __('submit_error'));
        redirect(url('admin_partners.php'));
    }

    $modAction = $_POST['mod_action'] ?? '';

    // Add partner
    if ($modAction === 'add_partner') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $website = trim($_POST['website'] ?? '');

        if ($name) {
            $image = null;
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $image = 'partner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], PUBLIC_PATH . '/assets/uploads/' . $image);
                }
            }

            $order = $db->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM partners")->fetchColumn();
            $stmt = $db->prepare("INSERT INTO partners (name, description, website, image, sort_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description ?: null, $website ?: null, $image, $order]);
            setFlash('success', __('admin_partner_added'));
        }
    }

    // Edit partner
    if ($modAction === 'edit_partner') {
        $partnerId = (int)($_POST['partner_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $website = trim($_POST['website'] ?? '');

        if ($partnerId && $name) {
            // Handle image upload
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    // Delete old image
                    $old = $db->prepare("SELECT image FROM partners WHERE id = ?");
                    $old->execute([$partnerId]);
                    $oldImg = $old->fetchColumn();
                    if ($oldImg && file_exists(PUBLIC_PATH . '/assets/uploads/' . $oldImg)) {
                        unlink(PUBLIC_PATH . '/assets/uploads/' . $oldImg);
                    }

                    $image = 'partner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], PUBLIC_PATH . '/assets/uploads/' . $image);
                    $db->prepare("UPDATE partners SET image = ? WHERE id = ?")->execute([$image, $partnerId]);
                }
            }

            $stmt = $db->prepare("UPDATE partners SET name = ?, description = ?, website = ? WHERE id = ?");
            $stmt->execute([$name, $description ?: null, $website ?: null, $partnerId]);
            setFlash('success', __('admin_partner_updated'));
        }
    }

    // Delete partner
    if ($modAction === 'delete_partner') {
        $partnerId = (int)($_POST['partner_id'] ?? 0);
        if ($partnerId) {
            $old = $db->prepare("SELECT image FROM partners WHERE id = ?");
            $old->execute([$partnerId]);
            $oldImg = $old->fetchColumn();
            if ($oldImg && file_exists(PUBLIC_PATH . '/assets/uploads/' . $oldImg)) {
                unlink(PUBLIC_PATH . '/assets/uploads/' . $oldImg);
            }
            $db->prepare("DELETE FROM partners WHERE id = ?")->execute([$partnerId]);
            setFlash('success', __('admin_partner_deleted'));
        }
    }

    unset($_SESSION['csrf_token']);
    redirect(url('admin_partners.php'));
}

$partners = $db->query("SELECT * FROM partners ORDER BY sort_order ASC, id ASC")->fetchAll();

ob_start();
?>

<div class="container">
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <h3 style="margin-bottom: 1rem; color: var(--primary-dark);"><?= e(__('admin_dashboard')) ?></h3>
            <a href="<?= url('admin.php') ?>"><?= e(__('admin_pending')) ?></a>
            <a href="<?= url('admin.php?action=approved') ?>"><?= e(__('admin_approved')) ?></a>
            <a href="<?= url('admin.php?action=top_reacted') ?>"><?= e(__('admin_top_reacted')) ?></a>
            <a href="<?= url('admin.php?action=audit') ?>"><?= e(__('admin_audit')) ?></a>
            <hr style="margin: 1rem 0; border-color: var(--border-light);">
            <a href="<?= url('admin_analytics.php') ?>"><?= e(__('admin_analytics')) ?></a>
            <a href="<?= url('admin_resources.php') ?>"><?= e(__('admin_resources')) ?></a>
            <a href="<?= url('admin_partners.php') ?>" class="active"><?= e(__('admin_partners')) ?></a>
            <hr style="margin: 1rem 0; border-color: var(--border-light);">
            <a href="<?= url('admin.php?action=admins') ?>"><?= e(__('admin_manage_admins')) ?></a>
            <a href="<?= url('admin.php?action=password') ?>"><?= e(__('admin_change_password')) ?></a>
            <hr style="margin: 1rem 0; border-color: var(--border-light);">
            <p class="text-muted" style="font-size: 0.8rem; padding: 0 1rem;"><?= e($_SESSION['admin_email']) ?></p>
            <a href="<?= url('admin.php?action=logout') ?>"><?= e(__('admin_logout')) ?></a>
        </aside>

        <div class="admin-content">
            <h2 class="section-title"><?= e(__('admin_partners')) ?></h2>

            <!-- Add Partner -->
            <div class="card mb-3">
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">+ <?= e(__('admin_partner_add')) ?></h3>
                <form method="POST" action="<?= url('admin_partners.php') ?>" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="mod_action" value="add_partner">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group" style="margin-bottom:0.5rem;">
                            <label><?= e(__('admin_partner_name')) ?></label>
                            <input type="text" name="name" class="form-control" required placeholder="Organization name">
                        </div>
                        <div class="form-group" style="margin-bottom:0.5rem;">
                            <label><?= e(__('admin_partner_website')) ?></label>
                            <input type="url" name="website" class="form-control" placeholder="https://example.com">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0.5rem;">
                        <label><?= e(__('admin_partner_description')) ?></label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief description of the partner"></textarea>
                    </div>
                    <div style="display:flex; gap:0.75rem; align-items: center;">
                        <div>
                            <label style="font-size: 0.85rem; font-weight: 600;"><?= e(__('admin_partner_image')) ?></label>
                            <input type="file" name="image" accept="image/*" style="font-size: 0.85rem;">
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-left: auto;"><?= e(__('admin_partner_add')) ?></button>
                    </div>
                </form>
            </div>

            <!-- Existing Partners -->
            <?php if (empty($partners)): ?>
                <p class="text-muted"><?= e(__('partners_no_items')) ?></p>
            <?php else: ?>
                <?php foreach ($partners as $p): ?>
                    <div class="admin-message">
                        <div style="display: flex; gap: 1rem; align-items: flex-start; flex-wrap: wrap;">
                            <?php if ($p['image']): ?>
                                <img src="<?= BASE_URL ?>/assets/uploads/<?= e($p['image']) ?>" style="width:80px;height:80px;border-radius:var(--radius-sm);object-fit:cover;">
                            <?php else: ?>
                                <div style="width:80px;height:80px;border-radius:var(--radius-sm);background:var(--bg-warm);display:flex;align-items:center;justify-content:center;font-size:2rem;">&#129309;</div>
                            <?php endif; ?>
                            <div style="flex:1;">
                                <strong style="font-size: 1.05rem;"><?= e($p['name']) ?></strong>
                                <?php if ($p['website']): ?><br><a href="<?= e($p['website']) ?>" target="_blank" class="text-muted" style="font-size:0.85rem;"><?= e($p['website']) ?></a><?php endif; ?>
                                <?php if ($p['description']): ?><p class="text-muted" style="font-size:0.85rem;margin-top:0.25rem;"><?= e($p['description']) ?></p><?php endif; ?>
                            </div>
                            <div style="display:flex;gap:0.5rem;">
                                <button class="btn btn-secondary btn-sm" onclick="document.getElementById('partner-edit-<?= $p['id'] ?>').style.display='block';"><?= e(__('admin_edit')) ?></button>
                                <form method="POST" action="<?= url('admin_partners.php') ?>" style="display:inline;" onsubmit="return confirm('Remove this partner?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="mod_action" value="delete_partner">
                                    <input type="hidden" name="partner_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><?= e(__('admin_remove')) ?></button>
                                </form>
                            </div>
                        </div>

                        <!-- Edit form -->
                        <form method="POST" action="<?= url('admin_partners.php') ?>" enctype="multipart/form-data" id="partner-edit-<?= $p['id'] ?>" style="display:none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-light);">
                            <?= csrfField() ?>
                            <input type="hidden" name="mod_action" value="edit_partner">
                            <input type="hidden" name="partner_id" value="<?= $p['id'] ?>">
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                <div class="form-group" style="margin-bottom:0.5rem;">
                                    <label><?= e(__('admin_partner_name')) ?></label>
                                    <input type="text" name="name" class="form-control" value="<?= e($p['name']) ?>" required>
                                </div>
                                <div class="form-group" style="margin-bottom:0.5rem;">
                                    <label><?= e(__('admin_partner_website')) ?></label>
                                    <input type="url" name="website" class="form-control" value="<?= e($p['website'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom:0.5rem;">
                                <label><?= e(__('admin_partner_description')) ?></label>
                                <textarea name="description" class="form-control" rows="2"><?= e($p['description'] ?? '') ?></textarea>
                            </div>
                            <div style="display:flex; gap:0.75rem; align-items: center;">
                                <div>
                                    <label style="font-size:0.85rem;font-weight:600;"><?= e(__('admin_partner_image')) ?></label>
                                    <input type="file" name="image" accept="image/*" style="font-size: 0.85rem;">
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm"><?= e(__('admin_save')) ?></button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('partner-edit-<?= $p['id'] ?>').style.display='none';"><?= e(__('admin_cancel')) ?></button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
