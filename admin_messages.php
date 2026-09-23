<?php
$pageTitle = "Manage Website Inquiries";
require_once __DIR__ . '/header.php';

if (!$currentUser || $currentUser['role'] !== 'admin') {
    $_SESSION['flash_msg'] = "Access denied.";
    $_SESSION['flash_type'] = "danger";
    header("Location: login.php");
    exit;
}

$db = getDBConnection();

// Handle status updates and deletes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msgId = intval($_POST['message_id'] ?? 0);
    $action = $_POST['action'] ?? 'update';

    if ($action === 'delete') {
        $db->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$msgId]);
        $_SESSION['flash_msg'] = "Inquiry message deleted from database.";
        $_SESSION['flash_type'] = "info";
    } else {
        $status = $_POST['status'] ?? 'read';
        $adminReply = trim($_POST['admin_reply'] ?? '');

        if (!empty($adminReply)) {
            $stmt = $db->prepare("UPDATE contact_messages SET status = ?, admin_reply = ?, replied_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$status, $adminReply, $msgId]);
        } else {
            $stmt = $db->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
            $stmt->execute([$status, $msgId]);
        }
        $_SESSION['flash_msg'] = "Inquiry status and counselor notes updated!";
        $_SESSION['flash_type'] = "success";
    }

    header("Location: admin_messages.php");
    exit;
}

$statusFilter = $_GET['status'] ?? null;
$query = "SELECT * FROM contact_messages WHERE 1=1";
$params = [];

if ($statusFilter) {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}
$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$messages = $stmt->fetchAll();
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
        <a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Admin Dashboard</a> / 
        <span style="color: var(--text-primary);">Website Contact Inquiries</span>
    </div>

    <!-- Header & Filter Bar -->
    <div class="glass-card" style="margin-bottom: 2rem; border-color: var(--border-glass-bright); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <span class="section-tag" style="margin-bottom: 0.25rem;">Live Database Inquiries Desk</span>
            <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">Student & Visitor Contact Messages</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                Review submitted inquiries from the website, manage counselor reply statuses, and track student support tickets.
            </p>
        </div>

        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="admin_messages.php" 
               class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?>">
                All (<?= count($messages) ?>)
            </a>
            <a href="admin_messages.php?status=unread" 
               class="btn btn-sm <?= ($statusFilter === 'unread') ? 'btn-primary' : 'btn-secondary' ?>">
                <i class="fas fa-envelope"></i> Unread
            </a>
            <a href="admin_messages.php?status=read" 
               class="btn btn-sm <?= ($statusFilter === 'read') ? 'btn-primary' : 'btn-secondary' ?>">
                <i class="fas fa-envelope-open"></i> Read
            </a>
            <a href="admin_messages.php?status=replied" 
               class="btn btn-sm <?= ($statusFilter === 'replied') ? 'btn-primary' : 'btn-secondary' ?>">
                <i class="fas fa-reply"></i> Replied
            </a>
        </div>
    </div>

    <!-- Messages List -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <?php foreach ($messages as $m): ?>
            <div class="glass-card" style="border-color: <?= ($m['status'] === 'unread') ? 'rgba(244, 63, 94, 0.4)' : (($m['status'] === 'replied') ? 'rgba(16, 185, 129, 0.4)' : 'var(--border-glass)') ?>;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-glass);">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.35rem;">
                            <span class="status-badge <?= ($m['status'] === 'unread') ? 'status-unread' : (($m['status'] === 'read') ? 'status-read' : 'status-replied') ?>">
                                <i class="fas <?= ($m['status'] === 'unread') ? 'fa-envelope' : (($m['status'] === 'read') ? 'fa-envelope-open' : 'fa-reply') ?>"></i>
                                <?= strtoupper(htmlspecialchars($m['status'])) ?>
                            </span>
                            <span class="role-tag">#INQ-<?= $m['id'] ?></span>
                            <strong style="font-size: 1.15rem; color: var(--text-primary);"><?= htmlspecialchars($m['subject']) ?></strong>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                            From: <strong style="color: var(--text-primary);"><?= htmlspecialchars($m['name']) ?></strong> &lt;<?= htmlspecialchars($m['email']) ?>&gt; 
                            <?php if (!empty($m['phone'])): ?> • <i class="fas fa-phone"></i> <?= htmlspecialchars($m['phone']) ?><?php endif; ?>
                        </div>
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                        Received: <?= htmlspecialchars($m['created_at']) ?>
                    </div>
                </div>

                <!-- Message Body -->
                <div style="background: rgba(255, 255, 255, 0.02); padding: 1.15rem; border-radius: var(--radius-md); font-size: 0.95rem; color: var(--text-primary); line-height: 1.6; margin-bottom: 1.25rem;">
                    <?= nl2br(htmlspecialchars($m['message'])) ?>
                </div>

                <?php if (!empty($m['admin_reply'])): ?>
                    <div style="background: rgba(16, 185, 129, 0.08); border-left: 3px solid #10b981; padding: 0.85rem 1.15rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem;">
                        <div style="font-size: 0.8rem; font-weight: 700; color: #6ee7b7; text-transform: uppercase; margin-bottom: 0.25rem;">
                            <i class="fas fa-reply-all"></i> Counselor Response Note (<?= htmlspecialchars($m['replied_at'] ?? '') ?>):
                        </div>
                        <div style="font-size: 0.9rem; color: var(--text-primary);"><?= htmlspecialchars($m['admin_reply']) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Action Controls (Status Update & Counselor Notes) -->
                <form action="admin_messages.php" method="POST" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                    <input type="hidden" name="message_id" value="<?= $m['id'] ?>">
                    
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <label style="font-size: 0.8rem; color: var(--text-muted);">Status:</label>
                        <select name="status" class="form-control" style="padding: 0.35rem 0.75rem; font-size: 0.85rem; width: auto;">
                            <option value="unread" <?= ($m['status'] === 'unread') ? 'selected' : '' ?>>Unread</option>
                            <option value="read" <?= ($m['status'] === 'read') ? 'selected' : '' ?>>Read</option>
                            <option value="replied" <?= ($m['status'] === 'replied') ? 'selected' : '' ?>>Replied</option>
                        </select>
                    </div>

                    <div style="flex: 1; min-width: 250px;">
                        <input type="text" name="admin_reply" class="form-control" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;" 
                               placeholder="Add counselor response / feedback notes..." value="<?= htmlspecialchars($m['admin_reply'] ?? '') ?>">
                    </div>

                    <button type="submit" name="action" value="update" class="btn btn-sm btn-primary">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                    
                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete this inquiry message from the database?');">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        <?php endforeach; ?>

        <?php if (empty($messages)): ?>
            <div class="glass-card" style="text-align: center; padding: 3rem;">
                <i class="fas fa-inbox fa-3x" style="color: var(--text-muted); margin-bottom: 1rem;"></i>
                <h4>No contact inquiries matching this filter.</h4>
                <p style="color: var(--text-secondary); font-size: 0.875rem;">Messages sent from the website contact form will appear here in real time.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
