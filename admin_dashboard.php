<?php
$pageTitle = "Admin Control Center";
require_once __DIR__ . '/header.php';

if (!$currentUser || $currentUser['role'] !== 'admin') {
    $_SESSION['flash_msg'] = "Access denied. Administrator privileges required.";
    $_SESSION['flash_type'] = "danger";
    header("Location: login.php");
    exit;
}

$db = getDBConnection();

// Aggregate KPIs
$totalStudents = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalCareers = $db->query("SELECT COUNT(*) FROM career_paths WHERE is_active = 1")->fetchColumn();
$totalSkills = $db->query("SELECT COUNT(*) FROM skills WHERE is_active = 1")->fetchColumn();
$totalAssessments = $db->query("SELECT COUNT(*) FROM student_assessment_responses")->fetchColumn();
$totalRecs = $db->query("SELECT COUNT(*) FROM recommendation_records")->fetchColumn();
$unreadMsgs = $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();

// Recent students
$stmt = $db->query("SELECT u.name, u.email, u.created_at, p.field_of_study FROM users u LEFT JOIN student_profiles p ON u.id = p.user_id WHERE u.role = 'student' ORDER BY u.created_at DESC LIMIT 6");
$recentStudents = $stmt->fetchAll();

// Recent inquiries
$stmt = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");
$recentMessages = $stmt->fetchAll();
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <!-- Admin Header -->
    <div class="glass-card" style="margin-bottom: 2rem; border-color: var(--border-glass-bright); background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(244, 63, 94, 0.1) 100%);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="role-tag admin" style="margin-bottom: 0.25rem; display: inline-block;">System Administrator</span>
                <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">Administrator Analytics Dashboard</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">
                    Platform oversight, student engagement metrics, career database maintenance, and user inquiry tracker.
                </p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <a href="admin_careers.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Add Career
                </a>
                <a href="admin_messages.php" class="btn btn-secondary">
                    <i class="fas fa-envelope"></i> Inquiries (<?= $unreadMsgs ?>)
                </a>
            </div>
        </div>
    </div>

    <!-- 6 KPI Metrics Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
        <div class="glass-card glass-card-hover" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Total Students</div>
                <i class="fas fa-user-graduate" style="color: var(--accent-cyan);"></i>
            </div>
            <div style="font-size: 1.85rem; font-weight: 800; margin-top: 0.5rem; color: var(--text-primary);">
                <?= $totalStudents ?>
            </div>
        </div>

        <div class="glass-card glass-card-hover" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Active Careers</div>
                <i class="fas fa-briefcase" style="color: var(--accent-primary);"></i>
            </div>
            <div style="font-size: 1.85rem; font-weight: 800; margin-top: 0.5rem; color: var(--text-primary);">
                <?= $totalCareers ?>
            </div>
        </div>

        <div class="glass-card glass-card-hover" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Indexed Skills</div>
                <i class="fas fa-tools" style="color: var(--accent-emerald);"></i>
            </div>
            <div style="font-size: 1.85rem; font-weight: 800; margin-top: 0.5rem; color: var(--text-primary);">
                <?= $totalSkills ?>
            </div>
        </div>

        <div class="glass-card glass-card-hover" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Tests Completed</div>
                <i class="fas fa-clipboard-check" style="color: var(--accent-amber);"></i>
            </div>
            <div style="font-size: 1.85rem; font-weight: 800; margin-top: 0.5rem; color: var(--text-primary);">
                <?= $totalAssessments ?>
            </div>
        </div>

        <div class="glass-card glass-card-hover" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">AI Recs Computed</div>
                <i class="fas fa-magic" style="color: var(--accent-secondary);"></i>
            </div>
            <div style="font-size: 1.85rem; font-weight: 800; margin-top: 0.5rem; color: var(--text-primary);">
                <?= $totalRecs ?>
            </div>
        </div>

        <div class="glass-card glass-card-hover" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Unread Queries</div>
                <i class="fas fa-envelope-open-text" style="color: var(--accent-rose);"></i>
            </div>
            <div style="font-size: 1.85rem; font-weight: 800; margin-top: 0.5rem; color: #fb7185;">
                <?= $unreadMsgs ?>
            </div>
        </div>
    </div>

    <!-- Chart Analytics Section -->
    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem; margin-bottom: 2.5rem;">
        <!-- Career Demand Bar Chart -->
        <div class="glass-card">
            <h3 style="font-size: 1.2rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-chart-bar" style="color: var(--accent-cyan);"></i> Top AI Recommended Careers Distribution
            </h3>
            <div style="height: 280px; position: relative;">
                <canvas id="adminCareerDemandChart"></canvas>
            </div>
        </div>

        <!-- Career Category Doughnut Chart -->
        <div class="glass-card">
            <h3 style="font-size: 1.2rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-chart-pie" style="color: var(--accent-secondary);"></i> Career Track Distribution
            </h3>
            <div style="height: 280px; position: relative;">
                <canvas id="adminCategoryPieChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Tables (Students & Inquiries) -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Recent Student Signups -->
        <div class="glass-card">
            <div class="card-header-flex">
                <h3 style="font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-users" style="color: var(--accent-emerald);"></i> Recent Student Registrations
                </h3>
                <a href="admin_students.php" class="btn btn-sm btn-outline">View All</a>
            </div>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Field</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentStudents as $s): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($s['name']) ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($s['email']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($s['field_of_study'] ?? 'Undergraduate') ?></td>
                                <td style="font-size: 0.8rem; color: var(--text-muted);"><?= substr($s['created_at'], 0, 10) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentStudents)): ?>
                            <tr><td colspan="3" style="text-align: center; color: var(--text-muted);">No student registrations found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Contact Inquiries -->
        <div class="glass-card">
            <div class="card-header-flex">
                <h3 style="font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-inbox" style="color: var(--accent-amber);"></i> Recent Website Messages
                </h3>
                <a href="admin_messages.php" class="btn btn-sm btn-outline">View All</a>
            </div>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Sender</th>
                            <th>Subject</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentMessages as $m): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($m['name']) ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($m['email']) ?></div>
                                </td>
                                <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($m['subject']) ?></td>
                                <td>
                                    <span class="status-badge <?= ($m['status'] === 'unread') ? 'status-unread' : (($m['status'] === 'read') ? 'status-read' : 'status-replied') ?>">
                                        <?= htmlspecialchars($m['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentMessages)): ?>
                            <tr><td colspan="3" style="text-align: center; color: var(--text-muted);">No inquiries logged in database.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
