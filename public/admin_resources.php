<?php
/**
 * Admin - Manage Resources & Resource Categories
 */
require_once __DIR__ . '/../app/config/init.php';

$pageTitle = __('admin_resources');
$db = getDB();
requireAdmin();

$subAction = $_GET['sub'] ?? 'list';

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', __('submit_error'));
        redirect(url('admin_resources.php'));
    }

    $modAction = $_POST['mod_action'] ?? '';

    // Add category
    if ($modAction === 'add_category') {
        $name = trim($_POST['cat_name'] ?? '');
        if ($name) {
            $order = $db->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM resource_categories")->fetchColumn();
            $stmt = $db->prepare("INSERT INTO resource_categories (name, sort_order) VALUES (?, ?)");
            $stmt->execute([$name, $order]);
            setFlash('success', __('admin_res_added'));
        }
    }

    // Edit category
    if ($modAction === 'edit_category') {
        $catId = (int)($_POST['cat_id'] ?? 0);
        $name = trim($_POST['cat_name'] ?? '');
        if ($catId && $name) {
            $stmt = $db->prepare("UPDATE resource_categories SET name = ? WHERE id = ?");
            $stmt->execute([$name, $catId]);
            setFlash('success', __('admin_res_updated'));
        }
    }

    // Delete category
    if ($modAction === 'delete_category') {
        $catId = (int)($_POST['cat_id'] ?? 0);
        if ($catId) {
            $db->prepare("DELETE FROM resource_categories WHERE id = ?")->execute([$catId]);
            setFlash('success', __('admin_res_deleted'));
        }
    }

    // Add resource
    if ($modAction === 'add_resource') {
        $catId = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($catId && $name) {
            $logo = null;
            if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $logo = 'res_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    move_uploaded_file($_FILES['logo']['tmp_name'], PUBLIC_PATH . '/assets/uploads/' . $logo);
                }
            }

            $order = $db->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM resources WHERE category_id = $catId")->fetchColumn();
            $stmt = $db->prepare("INSERT INTO resources (category_id, name, description, phone, logo, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$catId, $name, $description ?: null, $phone ?: null, $logo, $order]);
            setFlash('success', __('admin_res_added'));
        }
    }

    // Edit resource
    if ($modAction === 'edit_resource') {
        $resId = (int)($_POST['res_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $catId = (int)($_POST['category_id'] ?? 0);

        if ($resId && $name) {
            // Handle logo upload
            if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    // Delete old logo
                    $old = $db->prepare("SELECT logo FROM resources WHERE id = ?");
                    $old->execute([$resId]);
                    $oldLogo = $old->fetchColumn();
                    if ($oldLogo && file_exists(PUBLIC_PATH . '/assets/uploads/' . $oldLogo)) {
                        unlink(PUBLIC_PATH . '/assets/uploads/' . $oldLogo);
                    }

                    $logo = 'res_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    move_uploaded_file($_FILES['logo']['tmp_name'], PUBLIC_PATH . '/assets/uploads/' . $logo);
                    $db->prepare("UPDATE resources SET logo = ? WHERE id = ?")->execute([$logo, $resId]);
                }
            }

            $stmt = $db->prepare("UPDATE resources SET category_id = ?, name = ?, description = ?, phone = ? WHERE id = ?");
            $stmt->execute([$catId, $name, $description ?: null, $phone ?: null, $resId]);
            setFlash('success', __('admin_res_updated'));
        }
    }

    // Delete resource
    if ($modAction === 'delete_resource') {
        $resId = (int)($_POST['res_id'] ?? 0);
        if ($resId) {
            // Delete logo file
            $old = $db->prepare("SELECT logo FROM resources WHERE id = ?");
            $old->execute([$resId]);
            $oldLogo = $old->fetchColumn();
            if ($oldLogo && file_exists(PUBLIC_PATH . '/assets/uploads/' . $oldLogo)) {
                unlink(PUBLIC_PATH . '/assets/uploads/' . $oldLogo);
            }
            $db->prepare("DELETE FROM resources WHERE id = ?")->execute([$resId]);
            setFlash('success', __('admin_res_deleted'));
        }
    }

    unset($_SESSION['csrf_token']);
    redirect(url('admin_resources.php'));
}

// Fetch data
$categories = $db->query("SELECT * FROM resource_categories ORDER BY sort_order ASC, id ASC")->fetchAll();
$allResources = $db->query("SELECT r.*, rc.name as category_name FROM resources r JOIN resource_categories rc ON r.category_id = rc.id ORDER BY rc.sort_order, r.sort_order ASC")->fetchAll();

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
            <a href="<?= url('admin_resources.php') ?>" class="active"><?= e(__('admin_resources')) ?></a>
            <a href="<?= url('admin_partners.php') ?>"><?= e(__('admin_partners')) ?></a>
            <hr style="margin: 1rem 0; border-color: var(--border-light);">
            <a href="<?= url('admin.php?action=admins') ?>"><?= e(__('admin_manage_admins')) ?></a>
            <a href="<?= url('admin.php?action=password') ?>"><?= e(__('admin_change_password')) ?></a>
            <hr style="margin: 1rem 0; border-color: var(--border-light);">
            <p class="text-muted" style="font-size: 0.8rem; padding: 0 1rem;"><?= e($_SESSION['admin_email']) ?></p>
            <a href="<?= url('admin.php?action=logout') ?>"><?= e(__('admin_logout')) ?></a>
        </aside>

        <div class="admin-content">
            <h2 class="section-title"><?= e(__('admin_resources')) ?></h2>

            <!-- Add Category -->
            <div class="card mb-3">
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;"><?= e(__('admin_res_add_category')) ?></h3>
                <form method="POST" action="<?= url('admin_resources.php') ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="mod_action" value="add_category">
                    <div style="display: flex; gap: 0.75rem; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0; flex: 1;">
                            <label><?= e(__('admin_res_category_name')) ?></label>
                            <input type="text" name="cat_name" class="form-control" required placeholder="e.g. Hospitals & Clinics">
                        </div>
                        <button type="submit" class="btn btn-primary"><?= e(__('admin_add_btn')) ?></button>
                    </div>
                </form>
            </div>

            <!-- Existing Categories -->
            <?php foreach ($categories as $cat): ?>
                <div class="admin-message mb-2">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <div id="cat-view-<?= $cat['id'] ?>">
                            <strong style="font-size: 1.1rem;"><?= e($cat['name']) ?></strong>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button class="btn btn-secondary btn-sm" onclick="document.getElementById('cat-edit-<?= $cat['id'] ?>').style.display='flex'; document.getElementById('cat-view-<?= $cat['id'] ?>').style.display='none';"><?= e(__('admin_edit')) ?></button>
                            <form method="POST" action="<?= url('admin_resources.php') ?>" style="display:inline;" onsubmit="return confirm('Delete this category and all its resources?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="mod_action" value="delete_category">
                                <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm"><?= e(__('admin_remove')) ?></button>
                            </form>
                        </div>
                    </div>

                    <!-- Edit category inline -->
                    <form method="POST" action="<?= url('admin_resources.php') ?>" id="cat-edit-<?= $cat['id'] ?>" style="display: none; gap: 0.75rem; align-items: end; margin-bottom: 0.75rem;">
                        <?= csrfField() ?>
                        <input type="hidden" name="mod_action" value="edit_category">
                        <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                        <input type="text" name="cat_name" class="form-control" value="<?= e($cat['name']) ?>" style="flex:1;">
                        <button type="submit" class="btn btn-primary btn-sm"><?= e(__('admin_save')) ?></button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('cat-edit-<?= $cat['id'] ?>').style.display='none'; document.getElementById('cat-view-<?= $cat['id'] ?>').style.display='block';"><?= e(__('admin_cancel')) ?></button>
                    </form>

                    <!-- Resources in this category -->
                    <?php
                    $catResources = array_filter($allResources, fn($r) => (int)$r['category_id'] === (int)$cat['id']);
                    ?>
                    <?php foreach ($catResources as $res): ?>
                        <div style="padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-sm); margin-bottom: 0.5rem; background: var(--bg);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <?php if ($res['logo']): ?>
                                        <img src="<?= BASE_URL ?>/assets/uploads/<?= e($res['logo']) ?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover;">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= e($res['name']) ?></strong>
                                        <?php if ($res['phone']): ?><span class="text-muted"> — <?= e($res['phone']) ?></span><?php endif; ?>
                                        <?php if ($res['description']): ?><p class="text-muted" style="font-size:0.85rem;margin-top:0.15rem;"><?= e($res['description']) ?></p><?php endif; ?>
                                    </div>
                                </div>
                                <div style="display:flex; gap:0.5rem;">
                                    <button class="btn btn-secondary btn-sm" onclick="document.getElementById('res-edit-<?= $res['id'] ?>').style.display='block';"><?= e(__('admin_edit')) ?></button>
                                    <form method="POST" action="<?= url('admin_resources.php') ?>" style="display:inline;" onsubmit="return confirm('Remove this resource?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="mod_action" value="delete_resource">
                                        <input type="hidden" name="res_id" value="<?= $res['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><?= e(__('admin_remove')) ?></button>
                                    </form>
                                </div>
                            </div>

                            <!-- Edit resource form -->
                            <form method="POST" action="<?= url('admin_resources.php') ?>" enctype="multipart/form-data" id="res-edit-<?= $res['id'] ?>" style="display:none; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border-light);">
                                <?= csrfField() ?>
                                <input type="hidden" name="mod_action" value="edit_resource">
                                <input type="hidden" name="res_id" value="<?= $res['id'] ?>">
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                    <div class="form-group" style="margin-bottom:0.5rem;">
                                        <label><?= e(__('admin_res_name')) ?></label>
                                        <input type="text" name="name" class="form-control" value="<?= e($res['name']) ?>" required>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0.5rem;">
                                        <label><?= e(__('admin_res_phone')) ?></label>
                                        <input type="text" name="phone" class="form-control" value="<?= e($res['phone'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="form-group" style="margin-bottom:0.5rem;">
                                    <label><?= e(__('admin_res_category')) ?></label>
                                    <select name="category_id" class="form-control">
                                        <?php foreach ($categories as $c): ?>
                                            <option value="<?= $c['id'] ?>" <?= (int)$c['id'] === (int)$res['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom:0.5rem;">
                                    <label><?= e(__('admin_res_description')) ?></label>
                                    <textarea name="description" class="form-control" rows="2"><?= e($res['description'] ?? '') ?></textarea>
                                </div>
                                <div class="form-group" style="margin-bottom:0.5rem;">
                                    <label><?= e(__('admin_res_logo')) ?></label>
                                    <input type="file" name="logo" class="form-control" accept="image/*">
                                </div>
                                <div style="display:flex;gap:0.5rem;">
                                    <button type="submit" class="btn btn-primary btn-sm"><?= e(__('admin_save')) ?></button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('res-edit-<?= $res['id'] ?>').style.display='none';"><?= e(__('admin_cancel')) ?></button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>

                    <!-- Add resource to this category -->
                    <form method="POST" action="<?= url('admin_resources.php') ?>" enctype="multipart/form-data" style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed var(--border);">
                        <?= csrfField() ?>
                        <input type="hidden" name="mod_action" value="add_resource">
                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                        <p style="font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem;">+ <?= e(__('admin_res_add')) ?></p>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                            <div class="form-group" style="margin-bottom:0.5rem;">
                                <input type="text" name="name" class="form-control" required placeholder="<?= e(__('admin_res_name')) ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0.5rem;">
                                <input type="text" name="phone" class="form-control" placeholder="<?= e(__('admin_res_phone')) ?>">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0.5rem;">
                            <textarea name="description" class="form-control" rows="2" placeholder="<?= e(__('admin_res_description')) ?>"></textarea>
                        </div>
                        <div style="display:flex; gap:0.75rem; align-items: center;">
                            <input type="file" name="logo" accept="image/*" style="font-size: 0.85rem;">
                            <button type="submit" class="btn btn-success btn-sm"><?= e(__('admin_res_add')) ?></button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
