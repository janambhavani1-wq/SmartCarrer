<?php
$pageTitle = "Manage Skills & Questions";
require_once __DIR__ . '/header.php';

if (!$currentUser || $currentUser['role'] !== 'admin') {
    $_SESSION['flash_msg'] = "Access denied.";
    $_SESSION['flash_type'] = "danger";
    header("Location: login.php");
    exit;
}

$db = getDBConnection();

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_skill_id'])) {
    $delId = intval($_POST['delete_skill_id']);
    $db->prepare("DELETE FROM skills WHERE id = ?")->execute([$delId]);
    $_SESSION['flash_msg'] = "Skill deleted from catalog.";
    $_SESSION['flash_type'] = "info";
    header("Location: admin_skills.php");
    exit;
}

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_skill'])) {
    $skillId = !empty($_POST['skill_id']) ? intval($_POST['skill_id']) : null;
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'Technical');
    $difficulty = trim($_POST['difficulty_level'] ?? 'Intermediate');
    $description = trim($_POST['description'] ?? '');

    if ($skillId) {
        $stmt = $db->prepare("UPDATE skills SET name = ?, category = ?, difficulty_level = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $category, $difficulty, $description, $skillId]);
        $_SESSION['flash_msg'] = "Skill updated successfully!";
    } else {
        $stmt = $db->prepare("INSERT INTO skills (name, category, difficulty_level, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $category, $difficulty, $description]);
        $_SESSION['flash_msg'] = "New skill added to catalog!";
    }
    $_SESSION['flash_type'] = "success";
    header("Location: admin_skills.php");
    exit;
}

$skills = $db->query("SELECT * FROM skills ORDER BY category, name")->fetchAll();
$assessments = $db->query("SELECT * FROM skill_assessments WHERE is_active = 1 LIMIT 50")->fetchAll();
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
        <a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Admin Dashboard</a> / 
        <span style="color: var(--text-primary);">Manage Skills & Assessments</span>
    </div>

    <!-- Header -->
    <div class="glass-card" style="margin-bottom: 2rem; border-color: var(--border-glass-bright); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <span class="section-tag" style="margin-bottom: 0.25rem;">Skill Repository & Question Bank</span>
            <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">Manage Skills & Evaluation Questions</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                Maintain industry skill benchmarks, categories, difficulty tiers, and active assessment questions.
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openAddSkillModal()">
            <i class="fas fa-plus-circle"></i> Add New Skill
        </button>
    </div>

    <!-- Tabbed or Sectional View -->
    <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 2rem;">
        <!-- Left: Skills Catalog Table -->
        <div class="glass-card">
            <h3 style="font-size: 1.25rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-tools" style="color: var(--accent-cyan);"></i> Industry Skills Catalog (<?= count($skills) ?>)
            </h3>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Skill Name</th>
                            <th>Category</th>
                            <th>Difficulty</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($skills as $s): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($s['name']) ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars(substr($s['description'] ?? '', 0, 60)) ?>...</div>
                                </td>
                                <td>
                                    <span class="role-tag"><?= htmlspecialchars($s['category']) ?></span>
                                </td>
                                <td style="font-size: 0.8rem; color: var(--text-secondary);">
                                    <?= htmlspecialchars($s['difficulty_level']) ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.4rem;">
                                        <button type="button" class="btn btn-sm btn-outline" 
                                                onclick='openEditSkillModal(<?= json_encode($s) ?>)' title="Edit Skill">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="admin_skills.php" method="POST" onsubmit="return confirm('Delete this skill?');">
                                            <input type="hidden" name="delete_skill_id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete Skill">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Active Skill Assessment Question Bank -->
        <div class="glass-card">
            <h3 style="font-size: 1.25rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-question-circle" style="color: var(--accent-primary);"></i> Active Assessment Questions
            </h3>

            <div style="display: flex; flex-direction: column; gap: 1rem; max-height: 600px; overflow-y: auto; padding-right: 0.5rem;">
                <?php foreach ($assessments as $idx => $q): ?>
                    <div class="glass-card" style="padding: 1rem; border-color: var(--border-glass);">
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.35rem;">
                            <span class="role-tag"><?= htmlspecialchars($q['skill_tagged']) ?></span>
                            <span style="color: #10b981; font-weight: 700;">Ans: Option <?= htmlspecialchars($q['correct_option']) ?></span>
                        </div>
                        <div style="font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem;">
                            <?= ($idx + 1) ?>. <?= htmlspecialchars($q['question_text']) ?>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary);">
                            <div><strong>A:</strong> <?= htmlspecialchars($q['option_a']) ?></div>
                            <div><strong>B:</strong> <?= htmlspecialchars($q['option_b']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Skill Modal -->
<div class="modal-backdrop" id="skillModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 id="modalSkillTitle" style="font-size: 1.25rem;">
                <i class="fas fa-tools" style="color: var(--accent-cyan); margin-right: 0.5rem;"></i> Add New Skill
            </h3>
            <button type="button" class="flash-close" onclick="closeModal('skillModal')">&times;</button>
        </div>
        
        <form action="admin_skills.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="skill_id" id="form_skill_id" value="">

                <div class="form-group">
                    <label class="form-label" for="sk_name">Skill Name *</label>
                    <input type="text" id="sk_name" name="name" class="form-control" placeholder="e.g. PyTorch Deep Learning" required>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="sk_category">Skill Category *</label>
                        <select id="sk_category" name="category" class="form-control" required>
                            <option value="Technical">Technical</option>
                            <option value="Analytical">Analytical</option>
                            <option value="Design & Creative">Design & Creative</option>
                            <option value="Management & Soft Skills">Management & Soft Skills</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="sk_diff">Difficulty Level</label>
                        <select id="sk_diff" name="difficulty_level" class="form-control">
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="sk_desc">Description / Scope</label>
                    <textarea id="sk_desc" name="description" class="form-control" placeholder="Brief outline of concepts covered..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('skillModal')">Cancel</button>
                <button type="submit" name="save_skill" class="btn btn-primary"><i class="fas fa-save"></i> Save Skill</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddSkillModal() {
    document.getElementById('modalSkillTitle').innerHTML = '<i class="fas fa-tools" style="color: var(--accent-cyan); margin-right: 0.5rem;"></i> Add New Skill';
    document.getElementById('form_skill_id').value = '';
    document.getElementById('sk_name').value = '';
    document.getElementById('sk_category').value = 'Technical';
    document.getElementById('sk_diff').value = 'Intermediate';
    document.getElementById('sk_desc').value = '';
    openModal('skillModal');
}

function openEditSkillModal(skill) {
    document.getElementById('modalSkillTitle').innerHTML = '<i class="fas fa-edit" style="color: var(--accent-cyan); margin-right: 0.5rem;"></i> Edit Skill: ' + skill.name;
    document.getElementById('form_skill_id').value = skill.id;
    document.getElementById('sk_name').value = skill.name;
    document.getElementById('sk_category').value = skill.category;
    document.getElementById('sk_diff').value = skill.difficulty_level || 'Intermediate';
    document.getElementById('sk_desc').value = skill.description || '';
    openModal('skillModal');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
