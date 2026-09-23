<?php
$pageTitle = "Recommendation Audit Logs";
require_once __DIR__ . '/header.php';

if (!$currentUser || $currentUser['role'] !== 'admin') {
    $_SESSION['flash_msg'] = "Access denied.";
    $_SESSION['flash_type'] = "danger";
    header("Location: login.php");
    exit;
}

$db = getDBConnection();
$stmt = $db->query("SELECT r.*, u.email as student_email FROM recommendation_records r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 100");
$records = $stmt->fetchAll();
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
        <a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Admin Dashboard</a> / 
        <span style="color: var(--text-primary);">Recommendation Audit Logs</span>
    </div>

    <!-- Header -->
    <div class="glass-card" style="margin-bottom: 2rem; border-color: var(--border-glass-bright);">
        <span class="section-tag" style="margin-bottom: 0.25rem;">Audit & Compliance Records</span>
        <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">AI Recommendation Generation Logs</h1>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">
            Chronological registry of every AI evaluation performed, top matched career, match score, and salary forecast.
        </p>
    </div>

    <!-- Records Table -->
    <div class="glass-card">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Student Candidate</th>
                        <th>Top AI Recommended Career</th>
                        <th>Match Score</th>
                        <th>Salary Forecast</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td>
                                <span class="role-tag">#REC-<?= $r['id'] ?></span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($r['student_name']) ?></strong>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($r['student_email'] ?? '') ?></div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-magic" style="color: var(--accent-cyan); font-size: 0.85rem;"></i>
                                    <strong><?= htmlspecialchars($r['top_career_title']) ?></strong>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-replied">
                                    <?= htmlspecialchars($r['match_percentage']) ?>% Match
                                </span>
                            </td>
                            <td style="color: #10b981; font-weight: 600; font-size: 0.85rem;">
                                <?= htmlspecialchars($r['salary_forecast']) ?>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-secondary);">
                                <?= htmlspecialchars($r['created_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                                No recommendation computation logs generated yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
