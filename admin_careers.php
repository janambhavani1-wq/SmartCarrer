<?php
$pageTitle = "Manage Career Paths";
require_once __DIR__ . '/header.php';

if (!$currentUser || $currentUser['role'] !== 'admin') {
    $_SESSION['flash_msg'] = "Access denied.";
    $_SESSION['flash_type'] = "danger";
    header("Location: login.php");
    exit;
}

$db = getDBConnection();

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_career_id'])) {
    $delId = intval($_POST['delete_career_id']);
    $db->prepare("DELETE FROM career_paths WHERE id = ?")->execute([$delId]);
    $_SESSION['flash_msg'] = "Career track deleted.";
    $_SESSION['flash_type'] = "info";
    header("Location: admin_careers.php");
    exit;
}

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_career'])) {
    $careerId = !empty($_POST['career_id']) ? intval($_POST['career_id']) : null;
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $skillsRaw = trim($_POST['required_skills'] ?? '');
    $avgSalary = trim($_POST['average_salary_inr'] ?? '');
    $salaryRange = trim($_POST['salary_range'] ?? '');
    $growth = trim($_POST['growth_outlook'] ?? '');
    $edu = trim($_POST['education_requirement'] ?? '');
    $companiesRaw = trim($_POST['top_companies'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fas fa-briefcase');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Parse skills
    $skillsMap = [];
    $explodedSkills = explode(',', $skillsRaw);
    foreach ($explodedSkills as $s) {
        $s = trim($s);
        if (!empty($s)) {
            $skillsMap[$s] = 0.85;
        }
    }

    // Parse companies
    $companiesList = [];
    $explodedCompanies = explode(',', $companiesRaw);
    foreach ($explodedCompanies as $c) {
        $c = trim($c);
        if (!empty($c)) {
            $companiesList[] = $c;
        }
    }

    $defaultRoadmap = [
        ["phase" => "Phase 1: Foundation", "duration" => "Month 1-2", "focus" => "Core fundamentals and basics."],
        ["phase" => "Phase 2: Core Mastery", "duration" => "Month 3-4", "focus" => "Intermediate concepts and tooling."],
        ["phase" => "Phase 3: Advanced Projects", "duration" => "Month 5-6", "focus" => "Portfolio building and deployment."]
    ];

    if ($careerId) {
        $stmt = $db->prepare("UPDATE career_paths SET title = ?, category = ?, description = ?, required_skills = ?, average_salary_inr = ?, salary_range = ?, growth_outlook = ?, education_requirement = ?, top_companies = ?, icon = ?, is_active = ? WHERE id = ?");
        $stmt->execute([
            $title, $category, $description, json_encode($skillsMap),
            $avgSalary, $salaryRange, $growth, $edu,
            json_encode($companiesList), $icon, $isActive, $careerId
        ]);
        $_SESSION['flash_msg'] = "Career track updated successfully!";
    } else {
        $stmt = $db->prepare("INSERT INTO career_paths (title, category, description, required_skills, average_salary_inr, salary_range, growth_outlook, education_requirement, top_companies, roadmap, icon, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $title, $category, $description, json_encode($skillsMap),
            $avgSalary, $salaryRange, $growth, $edu,
            json_encode($companiesList), json_encode($defaultRoadmap), $icon, $isActive
        ]);
        $_SESSION['flash_msg'] = "New career track added successfully!";
    }
    $_SESSION['flash_type'] = "success";
    header("Location: admin_careers.php");
    exit;
}

$stmt = $db->query("SELECT * FROM career_paths ORDER BY title ASC");
$careers = $stmt->fetchAll();
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
        <a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Admin Dashboard</a> / 
        <span style="color: var(--text-primary);">Manage Career Information</span>
    </div>

    <!-- Header -->
    <div class="glass-card" style="margin-bottom: 2rem; border-color: var(--border-glass-bright); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <span class="section-tag" style="margin-bottom: 0.25rem;">Curated Career Database</span>
            <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">Manage Industry Career Tracks</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                Add, modify, or retire career roles, required skills, average salaries, and learning roadmap steps.
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openAddCareerModal()">
            <i class="fas fa-plus-circle"></i> Add New Career Track
        </button>
    </div>

    <!-- Careers Table -->
    <div class="glass-card">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Career Role & Category</th>
                        <th>Required Skills</th>
                        <th>Avg. Salary</th>
                        <th>Growth Outlook</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($careers as $c): 
                        $skillsMap = json_decode($c['required_skills'], true) ?: [];
                    ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div class="career-icon-box" style="width: 2.5rem; height: 2.5rem; font-size: 1.1rem;">
                                        <i class="<?= htmlspecialchars($c['icon']) ?>"></i>
                                    </div>
                                    <div>
                                        <strong><?= htmlspecialchars($c['title']) ?></strong>
                                        <div style="font-size: 0.75rem; color: var(--accent-cyan);"><?= htmlspecialchars($c['category']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="skills-pill-wrap" style="max-width: 260px; margin: 0;">
                                    <?php foreach (array_keys($skillsMap) as $s): ?>
                                        <span class="skill-pill" style="font-size: 0.7rem;"><?= htmlspecialchars($s) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td style="color: #10b981; font-weight: 600; font-size: 0.85rem;">
                                <?= htmlspecialchars($c['average_salary_inr']) ?>
                            </td>
                            <td>
                                <span class="badge-growth" style="font-size: 0.8rem;"><?= htmlspecialchars($c['growth_outlook']) ?></span>
                            </td>
                            <td>
                                <span class="status-badge <?= $c['is_active'] ? 'status-replied' : 'status-unread' ?>">
                                    <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button type="button" class="btn btn-sm btn-outline" 
                                            onclick='openEditCareerModal(<?= json_encode($c) ?>)' title="Edit Career">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form action="admin_careers.php" method="POST" onsubmit="return confirm('Delete this career path permanently?');">
                                        <input type="hidden" name="delete_career_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete Career">
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
</div>

<!-- Add / Edit Career Modal -->
<div class="modal-backdrop" id="careerModal">
    <div class="modal-dialog" style="max-width: 750px;">
        <div class="modal-header">
            <h3 id="modalCareerTitle" style="font-size: 1.25rem;">
                <i class="fas fa-briefcase" style="color: var(--accent-cyan); margin-right: 0.5rem;"></i> Add Career Profile
            </h3>
            <button type="button" class="flash-close" onclick="closeModal('careerModal')">&times;</button>
        </div>
        
        <form action="admin_careers.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="career_id" id="form_career_id" value="">

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="c_title">Career Title *</label>
                        <input type="text" id="c_title" name="title" class="form-control" placeholder="e.g. AI & Machine Learning Engineer" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="c_category">Domain Category *</label>
                        <input type="text" id="c_category" name="category" class="form-control" placeholder="e.g. Artificial Intelligence, Software Engineering" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="c_desc">Role Description *</label>
                    <textarea id="c_desc" name="description" class="form-control" placeholder="Detailed career description..." required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="c_skills">Required Skills (Comma separated) *</label>
                    <input type="text" id="c_skills" name="required_skills" class="form-control" placeholder="Python, SQL, Machine Learning, Deep Learning" required>
                    <div class="form-helper">Enter skill names separated by commas (e.g. Python, SQL, Docker).</div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="c_salary">Average Salary (INR) *</label>
                        <input type="text" id="c_salary" name="average_salary_inr" class="form-control" placeholder="e.g. ₹ 12,00,000 - ₹ 30,00,000 / annum" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="c_range">Salary Range</label>
                        <input type="text" id="c_range" name="salary_range" class="form-control" placeholder="e.g. ₹ 8 LPA - ₹ 45+ LPA" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="c_growth">Growth Outlook *</label>
                        <input type="text" id="c_growth" name="growth_outlook" class="form-control" placeholder="e.g. Exponential Growth (+35% YoY)" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="c_icon">FontAwesome Icon Class</label>
                        <input type="text" id="c_icon" name="icon" class="form-control" placeholder="fas fa-brain" value="fas fa-briefcase">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="c_edu">Academic / Degree Requirement *</label>
                    <input type="text" id="c_edu" name="education_requirement" class="form-control" placeholder="e.g. B.Tech/B.E in CS/IT or BCA/MCA" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="c_comp">Top Hiring Companies (Comma separated)</label>
                    <input type="text" id="c_comp" name="top_companies" class="form-control" placeholder="Google, Microsoft, Amazon, TCS, Infosys">
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_active" id="c_active" value="1" checked style="accent-color: var(--accent-primary); width: 1.1rem; height: 1.1rem;">
                        <span style="font-weight: 600; font-size: 0.9rem;">Active in AI Recommendations & Catalog</span>
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('careerModal')">Cancel</button>
                <button type="submit" name="save_career" class="btn btn-primary"><i class="fas fa-save"></i> Save Career Record</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddCareerModal() {
    document.getElementById('modalCareerTitle').innerHTML = '<i class="fas fa-briefcase" style="color: var(--accent-cyan); margin-right: 0.5rem;"></i> Add New Career Track';
    document.getElementById('form_career_id').value = '';
    document.getElementById('c_title').value = '';
    document.getElementById('c_category').value = '';
    document.getElementById('c_desc').value = '';
    document.getElementById('c_skills').value = '';
    document.getElementById('c_salary').value = '';
    document.getElementById('c_range').value = '';
    document.getElementById('c_growth').value = '';
    document.getElementById('c_edu').value = '';
    document.getElementById('c_comp').value = '';
    document.getElementById('c_icon').value = 'fas fa-briefcase';
    document.getElementById('c_active').checked = true;
    openModal('careerModal');
}

function openEditCareerModal(career) {
    document.getElementById('modalCareerTitle').innerHTML = '<i class="fas fa-edit" style="color: var(--accent-cyan); margin-right: 0.5rem;"></i> Edit Career: ' + career.title;
    document.getElementById('form_career_id').value = career.id;
    document.getElementById('c_title').value = career.title;
    document.getElementById('c_category').value = career.category;
    document.getElementById('c_desc').value = career.description;
    
    let skillsParsed = {};
    try { skillsParsed = JSON.parse(career.required_skills); } catch(e){}
    document.getElementById('c_skills').value = Object.keys(skillsParsed).join(', ');
    
    document.getElementById('c_salary').value = career.average_salary_inr;
    document.getElementById('c_range').value = career.salary_range;
    document.getElementById('c_growth').value = career.growth_outlook;
    document.getElementById('c_edu').value = career.education_requirement;
    
    let compsParsed = [];
    try { compsParsed = JSON.parse(career.top_companies); } catch(e){}
    document.getElementById('c_comp').value = compsParsed.join(', ');
    document.getElementById('c_icon').value = career.icon || 'fas fa-briefcase';
    document.getElementById('c_active').checked = (career.is_active == 1);
    openModal('careerModal');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
