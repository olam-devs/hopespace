<?php
/**
 * Admin Dashboard
 * Space of Hope - Message & Comment Moderation
 */

require_once __DIR__ . '/../../app/config/i18n.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/config/security.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$lang = getCurrentLanguage();

// Admin auth check
if (!isset($_SESSION['admin_id'])) {
    header('Location: /SpaceofHope/public/admin/login.php?lang=' . $lang);
    exit;
}

$pdo = getDB();

// Get counts for dashboard
$pending_messages = $pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'pending'")->fetchColumn();
$approved_messages = $pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'approved'")->fetchColumn();
$pending_comments = $pdo->query("SELECT COUNT(*) FROM anonymous_comments WHERE status = 'pending'")->fetchColumn();
$approved_comments = $pdo->query("SELECT COUNT(*) FROM anonymous_comments WHERE status = 'approved'")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();

// Handle message approval/rejection from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $msg_id = (int)($_POST['message_id'] ?? 0);
        $action = $_POST['action'];

        if ($msg_id && in_array($action, ['approve', 'reject'])) {
            $new_status = $action === 'approve' ? 'approved' : 'rejected';
            $published = $action === 'approve' ? ', published_at = NOW()' : '';

            $stmt = $pdo->prepare("UPDATE messages SET status = ?" . $published . " WHERE id = ?");
            $stmt->execute([$new_status, $msg_id]);

            // Audit log
            $audit_action = $action === 'approve' ? 'approved' : 'rejected';
            $stmt = $pdo->prepare("INSERT INTO audit_log (admin_id, message_id, action) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['admin_id'], $msg_id, $audit_action]);

            header('Location: /SpaceofHope/public/admin/index.php?lang=' . $lang . '&tab=' . ($_GET['tab'] ?? 'pending'));
            exit;
        }
    }
}

$csrf_token = Security::generateCSRFToken();

// Get tab
$tab = $_GET['tab'] ?? 'pending';

// Fetch messages based on tab
if ($tab === 'pending') {
    $stmt = $pdo->query("SELECT * FROM messages WHERE status = 'pending' ORDER BY created_at DESC");
} elseif ($tab === 'approved') {
    $stmt = $pdo->query("SELECT * FROM messages WHERE status = 'approved' ORDER BY published_at DESC LIMIT 50");
} else {
    $stmt = $pdo->query("SELECT * FROM messages WHERE status = 'rejected' ORDER BY created_at DESC LIMIT 50");
}
$messages = $stmt->fetchAll();

$categories = [
    'life' => t('cat_life'), 'faith' => t('cat_faith'), 'marriage' => t('cat_marriage'),
    'mental_health' => t('cat_mental_health'), 'education' => t('cat_education'),
    'finance' => t('cat_finance'), 'encouragement' => t('cat_encouragement'),
    'love' => t('cat_love'), 'investment_tips' => t('cat_investment_tips'),
];
$formats = [
    'quote' => t('format_quote'), 'paragraph' => t('format_paragraph'),
    'lesson' => t('format_lesson'), 'question' => t('format_question'),
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo t('site_title'); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0; top: 0;
            width: 240px;
            height: 100vh;
            background: #1e293b;
            color: white;
            padding: 24px 0;
            overflow-y: auto;
        }
        .sidebar-logo {
            font-size: 20px;
            font-weight: 700;
            padding: 0 24px;
            margin-bottom: 30px;
            color: #10b981;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: #334155;
            color: white;
        }
        .sidebar-nav a .badge-count {
            background: #ef4444;
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: auto;
        }
        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 0 24px;
        }
        .sidebar-footer a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
        }

        /* Main */
        .main {
            margin-left: 240px;
            padding: 30px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .page-header h1 {
            font-size: 24px;
        }

        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
        }
        .stat-card .label {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }
        .stat-pending .number { color: #f59e0b; }
        .stat-approved .number { color: #10b981; }
        .stat-comments .number { color: #3b82f6; }
        .stat-users .number { color: #8b5cf6; }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 20px;
            background: white;
            padding: 4px;
            border-radius: 10px;
            width: fit-content;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .tabs a {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            transition: all 0.2s;
        }
        .tabs a.active {
            background: #1e293b;
            color: white;
        }
        .tabs a:hover:not(.active) {
            background: #f1f5f9;
        }

        /* Message Cards */
        .msg-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .msg-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .msg-badges {
            display: flex;
            gap: 6px;
        }
        .badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-cat { background: #d1fae5; color: #065f46; }
        .badge-fmt { background: #e0e7ff; color: #3730a3; }
        .badge-q { background: #fef3c7; color: #92400e; }
        .badge-lang { background: #f3f4f6; color: #4b5563; }
        .msg-date {
            font-size: 12px;
            color: #9ca3af;
        }
        .msg-content {
            font-size: 15px;
            line-height: 1.6;
            color: #374151;
            margin-bottom: 14px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
        }
        .msg-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .btn-action {
            padding: 8px 18px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-approve { background: #10b981; color: white; }
        .btn-approve:hover { background: #059669; }
        .btn-reject { background: #ef4444; color: white; }
        .btn-reject:hover { background: #dc2626; }
        .btn-edit { background: #3b82f6; color: white; }
        .btn-edit:hover { background: #2563eb; }
        .btn-comments {
            background: #f1f5f9;
            color: #475569;
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            margin-left: auto;
        }
        .btn-comments:hover { background: #e2e8f0; }

        .empty-state {
            text-align: center;
            padding: 50px;
            color: #94a3b8;
        }

        /* Edit modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: white;
            border-radius: 16px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal h2 {
            margin-bottom: 20px;
            font-size: 20px;
        }
        .modal textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
        }
        .modal textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 16px;
            justify-content: flex-end;
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">HopeSpace Admin</div>
        <div class="sidebar-nav">
            <a href="/SpaceofHope/public/admin/index.php?lang=<?php echo $lang; ?>&tab=pending" class="<?php echo $tab === 'pending' ? 'active' : ''; ?>">
                Messages
                <?php if ($pending_messages > 0): ?>
                    <span class="badge-count"><?php echo $pending_messages; ?></span>
                <?php endif; ?>
            </a>
            <a href="/SpaceofHope/public/admin/comments.php?lang=<?php echo $lang; ?>">
                Comments
                <?php if ($pending_comments > 0): ?>
                    <span class="badge-count"><?php echo $pending_comments; ?></span>
                <?php endif; ?>
            </a>
            <a href="/SpaceofHope/public/admin/stories.php?lang=<?php echo $lang; ?>">
                Stories &amp; Content
                <?php
                try {
                    $pendingStories = $pdo->query("SELECT COUNT(*) FROM story_parts WHERE status='pending'")->fetchColumn()
                                   + $pdo->query("SELECT COUNT(*) FROM testimonies WHERE status='pending'")->fetchColumn();
                    if ($pendingStories > 0) echo '<span class="badge-count">' . $pendingStories . '</span>';
                } catch(Exception $e) {}
                ?>
            </a>
            <a href="/SpaceofHope/public/messages.php?lang=<?php echo $lang; ?>">View Site</a>
        </div>
        <div class="sidebar-footer">
            <a href="/SpaceofHope/public/admin/logout.php"><?php echo t('logout'); ?></a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="page-header">
            <h1>Dashboard</h1>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-card stat-pending">
                <div class="number"><?php echo $pending_messages; ?></div>
                <div class="label">Pending Messages</div>
            </div>
            <div class="stat-card stat-approved">
                <div class="number"><?php echo $approved_messages; ?></div>
                <div class="label">Approved Messages</div>
            </div>
            <div class="stat-card stat-comments">
                <div class="number"><?php echo $pending_comments; ?></div>
                <div class="label">Pending Comments</div>
            </div>
            <div class="stat-card stat-users">
                <div class="number"><?php echo $total_users; ?></div>
                <div class="label">Users</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <a href="?lang=<?php echo $lang; ?>&tab=pending" class="<?php echo $tab === 'pending' ? 'active' : ''; ?>">
                Pending (<?php echo $pending_messages; ?>)
            </a>
            <a href="?lang=<?php echo $lang; ?>&tab=approved" class="<?php echo $tab === 'approved' ? 'active' : ''; ?>">
                Approved
            </a>
            <a href="?lang=<?php echo $lang; ?>&tab=rejected" class="<?php echo $tab === 'rejected' ? 'active' : ''; ?>">
                Rejected
            </a>
        </div>

        <!-- Messages List -->
        <?php if (empty($messages)): ?>
            <div class="empty-state">
                <p>No <?php echo $tab; ?> messages</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <?php
                $comment_count = $pdo->prepare("SELECT COUNT(*) FROM anonymous_comments WHERE message_id = ?");
                $comment_count->execute([$msg['id']]);
                $num_comments = $comment_count->fetchColumn();
                ?>
                <div class="msg-card">
                    <div class="msg-top">
                        <div class="msg-badges">
                            <span class="badge badge-cat"><?php echo $categories[$msg['category']] ?? $msg['category']; ?></span>
                            <span class="badge <?php echo $msg['format'] === 'question' ? 'badge-q' : 'badge-fmt'; ?>">
                                <?php echo $formats[$msg['format']] ?? $msg['format']; ?>
                            </span>
                            <span class="badge badge-lang"><?php echo strtoupper($msg['language']); ?></span>
                        </div>
                        <span class="msg-date"><?php echo date('M j, Y H:i', strtotime($msg['created_at'])); ?></span>
                    </div>
                    <div class="msg-content"><?php echo Security::sanitize($msg['content']); ?></div>
                    <div class="msg-actions">
                        <?php if ($tab === 'pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn-action btn-approve">Approve</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn-action btn-reject">Reject</button>
                            </form>
                            <button class="btn-action btn-edit" onclick="openEditModal(<?php echo $msg['id']; ?>, this)">Edit</button>
                        <?php endif; ?>

                        <?php if ($num_comments > 0): ?>
                            <a href="/SpaceofHope/public/admin/comments.php?lang=<?php echo $lang; ?>&message_id=<?php echo $msg['id']; ?>" class="btn-comments">
                                <?php echo $num_comments; ?> <?php echo t('comments_count'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <h2>Edit Message</h2>
            <form method="POST" id="editForm" action="/SpaceofHope/public/admin/edit_message.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="message_id" id="editMsgId">
                <input type="hidden" name="redirect" value="<?php echo Security::sanitize($_SERVER['REQUEST_URI']); ?>">
                <textarea name="content" id="editContent"></textarea>
                <div class="modal-actions">
                    <button type="button" class="btn-action" style="background:#e5e7eb;color:#374151;" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-action btn-edit">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openEditModal(msgId, btn) {
        const card = btn.closest('.msg-card');
        const content = card.querySelector('.msg-content').textContent.trim();
        document.getElementById('editMsgId').value = msgId;
        document.getElementById('editContent').value = content;
        document.getElementById('editModal').classList.add('active');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }

    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
    </script>
</body>
</html>
