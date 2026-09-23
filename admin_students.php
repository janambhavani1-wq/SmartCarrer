<?php
$pageTitle = "Manage Students";
require_once __DIR__ . '/header.php';

if (!$currentUser || $currentUser['role'] !== 'admin') {
    $_SESSION['flash_msg'] = "Access denied.";
    $_SESSION['flash_type'] = "danger";
    header("Location: login.php");
    exit;
}

$db = getDBConnection();

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $delId = intval($_POST['delete_user_id']);
    $db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$delId]);
    $_SESSION['flash_msg'] = "Student account deleted successfully.";
    $_SESSION['flash_type'] = "info";
    header("Location: admin_students.php");
    exit;
}

$searchQuery = trim($_GET['q'] ?? '');
$query = "SELECT u.id, u.name, u.email, u.phone, u.created_at,
          p.education_level, p.field_of_study, p.institution, p.cgpa_percentage, p.dream_role,
          (SELECT COUNT(*) FROM student_assessment_responses WHERE user_id = u.id) as assessment_count,
          (SELECT top_career_title FROM recommendation_records WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as latest_recommendation,
          (SELECT match_percentage FROM recommendation_records WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as match_percentage
          FROM users u
          LEFT JOIN student_profiles p ON u.id = p.user_id
          WHERE u.role = 'student'";

$params = [];
if (!empty($searchQuery)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR p.field_of_study LIKE ?)";
    $term = "%{$searchQuery}%";
    $params = [$term, $term, $term];
}
$query .= " ORDER BY u.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
        <a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Admin Dashboard</a> / 
        <span style="color: var(--text-primary);">Manage Students</span>
    </div>

    <!-- Header & Search Bar -->
    <div class="glass-card" style="margin-bottom: 2rem; border-color: var(--border-glass-bright);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
            <div>
                <span class="section-tag" style="margin-bottom: 0.25rem;">Student Administration</span>
                <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">Manage Registered Students</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">
                    Inspect student profiles, review their assessment attempts, AI career matches, and account records.
                </p>
            </div>

            <!-- Search Filter Form -->
            <form action="admin_students.php" method="GET" style="display: flex; gap: 0.5rem; min-width: 320px;">
                <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" class="form-control" placeholder="Search by name, email, major...">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                <?php if (!empty($searchQuery)): ?>
                    <a href="admin_students.php" class="btn btn-secondary" title="Clear search"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Students Table -->
    <div class="glass-card">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Student Name & Email</th>
                        <th>Academic Profile</th>
                        <th>Dream Role</th>
                        <th>Tests Taken</th>
                        <th>Latest AI Match</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($s['name']) ?></strong>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($s['email']) ?>
                                    <?php if (!empty($s['phone'])): ?> • <i class="fas fa-phone"></i> <?= htmlspecialchars($s['phone']) ?><?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($s['field_of_study'] ?? 'Not specified') ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">
                                    <?= htmlspecialchars($s['education_level'] ?? 'Undergraduate') ?> <?php if (!empty($s['cgpa_percentage'])): ?>(CGPA: <?= htmlspecialchars($s['cgpa_percentage']) ?>)<?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span style="color: var(--accent-cyan); font-weight: 500;"><?= htmlspecialchars($s['dream_role'] ?? 'Undecided') ?></span>
                            </td>
                            <td>
                                <span class="role-tag" style="background: rgba(99, 102, 241, 0.15);">
                                    <?= $s['assessment_count'] ?> Completed
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($s['latest_recommendation'])): ?>
                                    <span class="status-badge status-replied">
                                        <?= htmlspecialchars($s['latest_recommendation']) ?> (<?= htmlspecialchars($s['match_percentage']) ?>%)
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">No recs yet</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button type="button" class="btn btn-sm btn-outline" onclick="inspectStudentPHP(<?= $s['id'] ?>)" title="Inspect Full Dossier">
                                        <i class="fas fa-eye"></i> View Profile
                                    </button>
                                    <form action="admin_students.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this student account and all related records?');">
                                        <input type="hidden" name="delete_user_id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete Student">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                                No students found matching your criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Inspect Student Detail Modal -->
<div class="modal-backdrop" id="studentDetailModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 style="font-size: 1.2rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-user-graduate" style="color: var(--accent-cyan);"></i> Student Dossier & Assessment History
            </h3>
            <button type="button" class="flash-close" onclick="closeModal('studentDetailModal')">&times;</button>
        </div>
        <div class="modal-body" id="studentDetailContent">
            <!-- Populated dynamically via AJAX -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('studentDetailModal')">Close</button>
        </div>
    </div>
</div>

<script>
function inspectStudentPHP(studentId) {
    openModal('studentDetailModal');
    const modalBody = document.getElementById('studentDetailContent');
    if (!modalBody) return;

    modalBody.innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--accent-cyan);"></i>
            <p style="margin-top: 0.75rem; color: var(--text-secondary);">Loading student dossier...</p>
        </div>
    `;

    fetch(`api.php?action=student_details&id=${studentId}`)
        .then(res => res.json())
        .then(data => {
            const p = data.profile || {};
            const r = data.recommendation || {};
            const skills = data.skills || [];

            let skillsHtml = skills.map(s => `
                <span class="skill-pill matched">
                    ${s.name} (${s.proficiency_level}/5)
                </span>
            `).join('') || '<span style="color: var(--text-muted);">No skills rated yet.</span>';

            modalBody.innerHTML = `
                <div style="display: flex; gap: 1.25rem; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-glass);">
                    <div class="brand-icon" style="width: 3.5rem; height: 3.5rem; font-size: 1.5rem;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.35rem;">${p.name || 'Student'}</h3>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">${p.email} | ${p.phone || 'No Phone'}</p>
                        <span class="role-tag" style="margin-top: 0.25rem; display: inline-block;">${p.education_level || 'Undergraduate'} • ${p.field_of_study || 'General'}</span>
                    </div>
                </div>

                <div class="form-grid-2" style="margin-bottom: 1.25rem;">
                    <div class="glass-card" style="padding: 1rem;">
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Institution & CGPA</div>
                        <div style="font-weight: 600; margin-top: 0.25rem;">${p.institution || 'N/A'} (CGPA: ${p.cgpa_percentage || 'N/A'})</div>
                    </div>
                    <div class="glass-card" style="padding: 1rem;">
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Aspiration & Work Style</div>
                        <div style="font-weight: 600; margin-top: 0.25rem;">${p.dream_role || 'Undecided'} (${p.preferred_work_env || 'Hybrid'})</div>
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <h4 style="font-size: 0.95rem; margin-bottom: 0.5rem; color: var(--accent-cyan);"><i class="fas fa-tools"></i> Evaluated Skills</h4>
                    <div class="skills-pill-wrap">${skillsHtml}</div>
                </div>

                <div>
                    <h4 style="font-size: 0.95rem; margin-bottom: 0.5rem; color: var(--accent-primary);"><i class="fas fa-magic"></i> AI Recommendation Match</h4>
                    ${r.top_career_title ? `
                        <div class="glass-card" style="padding: 1rem; border-color: var(--border-glass-bright);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <strong>${r.top_career_title}</strong>
                                <span class="status-badge status-replied">${r.match_percentage}% Match</span>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">
                                Avg. Salary: <strong>${r.salary_forecast || 'Competitive'}</strong>
                            </div>
                        </div>
                    ` : '<p style="color: var(--text-muted); font-size: 0.875rem;">No recommendation generated yet.</p>'}
                </div>
            `;
        })
        .catch(err => {
            modalBody.innerHTML = `<div style="color: #f43f5e; padding: 1rem;">Error loading student dossier.</div>`;
        });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
