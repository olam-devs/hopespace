<?php
/**
 * Admin - Analytics Dashboard
 */
require_once __DIR__ . '/../app/config/init.php';

$pageTitle = 'Analytics';
$db = getDB();
requireAdmin();

// Date range
$dateFrom = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['to'] ?? date('Y-m-d');

// --- HANDLE LOGO UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', __('submit_error'));
        redirect(url('admin_analytics.php'));
    }

    if ($_POST['mod_action'] === 'upload_logo') {
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                // Delete old logo
                $oldLogo = getSetting('site_logo');
                if ($oldLogo && file_exists(PUBLIC_PATH . '/assets/uploads/' . $oldLogo)) {
                    unlink(PUBLIC_PATH . '/assets/uploads/' . $oldLogo);
                }

                $filename = 'hopespace_logo_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['logo']['tmp_name'], PUBLIC_PATH . '/assets/uploads/' . $filename);

                $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('site_logo', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$filename, $filename]);
                setFlash('success', 'Logo updated!');
            }
        }
    }

    if ($_POST['mod_action'] === 'remove_logo') {
        $oldLogo = getSetting('site_logo');
        if ($oldLogo && file_exists(PUBLIC_PATH . '/assets/uploads/' . $oldLogo)) {
            unlink(PUBLIC_PATH . '/assets/uploads/' . $oldLogo);
        }
        $db->exec("UPDATE site_settings SET setting_value = NULL WHERE setting_key = 'site_logo'");
        setFlash('success', 'Logo removed.');
    }

    unset($_SESSION['csrf_token']);
    redirect(url('admin_analytics.php'));
}

// --- ANALYTICS DATA ---

// Today's visits
$todayVisits = $db->query("SELECT COUNT(*) FROM page_visits WHERE DATE(visited_at) = CURDATE()")->fetchColumn();
$todayUnique = $db->query("SELECT COUNT(DISTINCT ip_hash) FROM page_visits WHERE DATE(visited_at) = CURDATE()")->fetchColumn();

// Total visits
$totalVisits = $db->query("SELECT COUNT(*) FROM page_visits")->fetchColumn();
$totalUnique = $db->query("SELECT COUNT(DISTINCT ip_hash) FROM page_visits")->fetchColumn();

// Visits in date range
$stmtRange = $db->prepare("SELECT COUNT(*) FROM page_visits WHERE DATE(visited_at) BETWEEN ? AND ?");
$stmtRange->execute([$dateFrom, $dateTo]);
$rangeVisits = $stmtRange->fetchColumn();

$stmtRangeUnique = $db->prepare("SELECT COUNT(DISTINCT ip_hash) FROM page_visits WHERE DATE(visited_at) BETWEEN ? AND ?");
$stmtRangeUnique->execute([$dateFrom, $dateTo]);
$rangeUnique = $stmtRangeUnique->fetchColumn();

// Daily visits chart data (last 30 days or date range)
$stmtDaily = $db->prepare("SELECT DATE(visited_at) as day, COUNT(*) as visits, COUNT(DISTINCT ip_hash) as unique_visitors FROM page_visits WHERE DATE(visited_at) BETWEEN ? AND ? GROUP BY DATE(visited_at) ORDER BY day ASC");
$stmtDaily->execute([$dateFrom, $dateTo]);
$dailyData = $stmtDaily->fetchAll();

$chartLabels = [];
$chartVisits = [];
$chartUnique = [];
foreach ($dailyData as $d) {
    $chartLabels[] = $d['day'];
    $chartVisits[] = (int)$d['visits'];
    $chartUnique[] = (int)$d['unique_visitors'];
}

// Page breakdown
$stmtPages = $db->prepare("SELECT page, COUNT(*) as visits FROM page_visits WHERE DATE(visited_at) BETWEEN ? AND ? GROUP BY page ORDER BY visits DESC");
$stmtPages->execute([$dateFrom, $dateTo]);
$pageBreakdown = $stmtPages->fetchAll();

$pageLabels = [];
$pageValues = [];
foreach ($pageBreakdown as $pb) {
    $pageLabels[] = ucfirst($pb['page']);
    $pageValues[] = (int)$pb['visits'];
}

// Messages stats
$totalMessages = $db->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$approvedMessages = $db->query("SELECT COUNT(*) FROM messages WHERE status = 'approved'")->fetchColumn();
$pendingMessages = $db->query("SELECT COUNT(*) FROM messages WHERE status = 'pending'")->fetchColumn();
$totalReactions = $db->query("SELECT COUNT(*) FROM reactions")->fetchColumn();
$totalPartners = $db->query("SELECT COUNT(*) FROM partners")->fetchColumn();

// New messages per day in range
$stmtNewMsgs = $db->prepare("SELECT DATE(created_at) as day, COUNT(*) as count FROM messages WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY day ASC");
$stmtNewMsgs->execute([$dateFrom, $dateTo]);
$newMsgsData = $stmtNewMsgs->fetchAll();

$msgLabels = [];
$msgValues = [];
foreach ($newMsgsData as $nm) {
    $msgLabels[] = $nm['day'];
    $msgValues[] = (int)$nm['count'];
}

// Reactions per day
$stmtReactDaily = $db->prepare("SELECT DATE(created_at) as day, COUNT(*) as count FROM reactions WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY day ASC");
$stmtReactDaily->execute([$dateFrom, $dateTo]);
$reactDailyData = $stmtReactDaily->fetchAll();

$reactLabels = [];
$reactValues = [];
foreach ($reactDailyData as $rd) {
    $reactLabels[] = $rd['day'];
    $reactValues[] = (int)$rd['count'];
}

// Category distribution
$catDist = $db->query("SELECT category, COUNT(*) as count FROM messages WHERE status = 'approved' GROUP BY category ORDER BY count DESC")->fetchAll();
$catLabels = [];
$catValues = [];
foreach ($catDist as $cd) {
    $catLabels[] = __('cat_' . $cd['category']);
    $catValues[] = (int)$cd['count'];
}

$siteLogo = getSetting('site_logo');

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
            <a href="<?= url('admin_analytics.php') ?>" class="active"><?= e(__('admin_analytics')) ?></a>
            <a href="<?= url('admin_resources.php') ?>"><?= e(__('admin_resources')) ?></a>
            <a href="<?= url('admin_partners.php') ?>"><?= e(__('admin_partners')) ?></a>
            <hr style="margin: 1rem 0; border-color: var(--border-light);">
            <a href="<?= url('admin.php?action=admins') ?>"><?= e(__('admin_manage_admins')) ?></a>
            <a href="<?= url('admin.php?action=password') ?>"><?= e(__('admin_change_password')) ?></a>
            <hr style="margin: 1rem 0; border-color: var(--border-light);">
            <p class="text-muted" style="font-size: 0.8rem; padding: 0 1rem;"><?= e($_SESSION['admin_email']) ?></p>
            <a href="<?= url('admin.php?action=logout') ?>"><?= e(__('admin_logout')) ?></a>
        </aside>

        <div class="admin-content">
            <!-- Logo Management -->
            <div class="card mb-3">
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Site Logo</h3>
                <div style="display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
                    <?php if ($siteLogo): ?>
                        <div style="text-align:center;">
                            <img src="<?= BASE_URL ?>/assets/uploads/<?= e($siteLogo) ?>" style="width:80px;height:80px;border-radius:12px;object-fit:contain;border:2px solid var(--border-light);padding:4px;background:white;">
                            <form method="POST" action="<?= url('admin_analytics.php') ?>" style="margin-top:0.5rem;">
                                <?= csrfField() ?>
                                <input type="hidden" name="mod_action" value="remove_logo">
                                <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div style="width:80px;height:80px;border-radius:12px;background:var(--bg-warm);display:flex;align-items:center;justify-content:center;font-size:2.5rem;border:2px dashed var(--border);">&#127807;</div>
                    <?php endif; ?>
                    <form method="POST" action="<?= url('admin_analytics.php') ?>" enctype="multipart/form-data" style="flex:1;">
                        <?= csrfField() ?>
                        <input type="hidden" name="mod_action" value="upload_logo">
                        <label style="font-size:0.9rem;font-weight:600;display:block;margin-bottom:0.4rem;">Upload New Logo</label>
                        <div style="display:flex;gap:0.75rem;align-items:center;">
                            <input type="file" name="logo" accept="image/*" required style="font-size:0.85rem;">
                            <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                        </div>
                        <p class="form-hint">PNG, JPG, SVG or WebP. Recommended: 200x200px</p>
                    </form>
                </div>
            </div>

            <h2 class="section-title">Analytics Dashboard</h2>

            <!-- Date Range Filter -->
            <form class="filters" method="GET" action="<?= BASE_URL ?>/admin_analytics.php">
                <input type="hidden" name="lang" value="<?= currentLang() ?>">
                <label style="font-weight:600;font-size:0.9rem;">From:</label>
                <input type="date" name="from" value="<?= e($dateFrom) ?>" class="form-control" style="width:auto;" onchange="this.form.submit()">
                <label style="font-weight:600;font-size:0.9rem;">To:</label>
                <input type="date" name="to" value="<?= e($dateTo) ?>" class="form-control" style="width:auto;" onchange="this.form.submit()">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            </form>

            <!-- Overview Stats -->
            <div class="stat-cards">
                <div class="stat-card">
                    <div class="stat-number"><?= $todayVisits ?></div>
                    <div class="stat-label">Visits Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $todayUnique ?></div>
                    <div class="stat-label">Unique Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $rangeVisits ?></div>
                    <div class="stat-label">Range Visits</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $rangeUnique ?></div>
                    <div class="stat-label">Range Unique</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $totalVisits ?></div>
                    <div class="stat-label">All-Time Visits</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $totalMessages ?></div>
                    <div class="stat-label">Total Messages</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $totalReactions ?></div>
                    <div class="stat-label">Total Reactions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $totalPartners ?></div>
                    <div class="stat-label">Partners</div>
                </div>
            </div>

            <!-- Charts -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
                <!-- Visits Chart -->
                <div class="card">
                    <h3 style="font-size:1rem; margin-bottom:1rem; color:var(--primary-dark);">Daily Visits</h3>
                    <canvas id="visitsChart" height="200"></canvas>
                </div>

                <!-- New Messages Chart -->
                <div class="card">
                    <h3 style="font-size:1rem; margin-bottom:1rem; color:var(--primary-dark);">New Messages Per Day</h3>
                    <canvas id="messagesChart" height="200"></canvas>
                </div>

                <!-- Reactions Chart -->
                <div class="card">
                    <h3 style="font-size:1rem; margin-bottom:1rem; color:var(--primary-dark);">Reactions Per Day</h3>
                    <canvas id="reactionsChart" height="200"></canvas>
                </div>

                <!-- Category Distribution -->
                <div class="card">
                    <h3 style="font-size:1rem; margin-bottom:1rem; color:var(--primary-dark);">Messages by Category</h3>
                    <canvas id="categoryChart" height="200"></canvas>
                </div>

                <!-- Page Breakdown -->
                <div class="card">
                    <h3 style="font-size:1rem; margin-bottom:1rem; color:var(--primary-dark);">Page Visits Breakdown</h3>
                    <canvas id="pagesChart" height="200"></canvas>
                </div>

                <!-- Message Status -->
                <div class="card">
                    <h3 style="font-size:1rem; margin-bottom:1rem; color:var(--primary-dark);">Message Status</h3>
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const chartColors = {
    primary: '#2d6a4f',
    accent: '#74c69d',
    light: '#b7e4c7',
    warm: '#40916c',
    danger: '#d63031',
    warning: '#fdcb6e',
    blue: '#1a56db',
    purple: '#7c3aed',
    orange: '#f59e0b',
    pink: '#ec4899'
};

// Daily Visits
new Chart(document.getElementById('visitsChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Total Visits',
            data: <?= json_encode($chartVisits) ?>,
            borderColor: chartColors.primary,
            backgroundColor: chartColors.light + '40',
            fill: true,
            tension: 0.3
        }, {
            label: 'Unique Visitors',
            data: <?= json_encode($chartUnique) ?>,
            borderColor: chartColors.accent,
            backgroundColor: 'transparent',
            borderDash: [5, 5],
            tension: 0.3
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
});

// New Messages
new Chart(document.getElementById('messagesChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($msgLabels) ?>,
        datasets: [{
            label: 'New Messages',
            data: <?= json_encode($msgValues) ?>,
            backgroundColor: chartColors.primary,
            borderRadius: 4
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

// Reactions
new Chart(document.getElementById('reactionsChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($reactLabels) ?>,
        datasets: [{
            label: 'Reactions',
            data: <?= json_encode($reactValues) ?>,
            borderColor: chartColors.purple,
            backgroundColor: chartColors.purple + '20',
            fill: true,
            tension: 0.3
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

// Category Distribution
new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($catLabels) ?>,
        datasets: [{
            data: <?= json_encode($catValues) ?>,
            backgroundColor: [chartColors.primary, chartColors.accent, chartColors.blue, chartColors.warning, chartColors.purple, chartColors.orange, chartColors.pink, chartColors.warm, chartColors.danger]
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'right' } } }
});

// Page Breakdown
new Chart(document.getElementById('pagesChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($pageLabels) ?>,
        datasets: [{
            label: 'Visits',
            data: <?= json_encode($pageValues) ?>,
            backgroundColor: [chartColors.primary, chartColors.accent, chartColors.blue, chartColors.warning, chartColors.purple, chartColors.orange],
            borderRadius: 4
        }]
    },
    options: { responsive: true, indexAxis: 'y', plugins: { legend: { display: false } } }
});

// Message Status
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Approved', 'Pending', 'Rejected'],
        datasets: [{
            data: [<?= $approvedMessages ?>, <?= $pendingMessages ?>, <?= (int)$db->query("SELECT COUNT(*) FROM messages WHERE status = 'rejected'")->fetchColumn() ?>],
            backgroundColor: [chartColors.primary, chartColors.warning, chartColors.danger]
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'right' } } }
});
</script>

<?php
$content = ob_get_clean();
require_once APP_PATH . '/views/layout.php';
