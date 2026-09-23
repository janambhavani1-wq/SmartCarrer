<?php
$pageTitle = "Personal & Academic Profile";
require_once __DIR__ . '/header.php';

if (!$currentUser || $currentUser['role'] !== 'student') {
    $_SESSION['flash_msg'] = "Please log in as a student.";
    $_SESSION['flash_type'] = "warning";
    header("Location: login.php");
    exit;
}

$db = getDBConnection();
$userId = $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $education_level = $_POST['education_level'] ?? '';
    $field_of_study = trim($_POST['field_of_study'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $cgpa_percentage = !empty($_POST['cgpa_percentage']) ? floatval($_POST['cgpa_percentage']) : null;
    $graduation_year = !empty($_POST['graduation_year']) ? intval($_POST['graduation_year']) : null;
    $dream_role = trim($_POST['dream_role'] ?? '');
    $preferred_industry = trim($_POST['preferred_industry'] ?? '');
    $preferred_work_env = $_POST['preferred_work_env'] ?? 'Hybrid';
    $location_pref = trim($_POST['location_pref'] ?? '');
    $experience_level = $_POST['experience_level'] ?? 'Fresher';
    $bio = trim($_POST['bio'] ?? '');

    // Update user table
    $db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?")->execute([$name, $phone, $userId]);
    $_SESSION['user_name'] = $name;

    // Update or insert student_profile
    $check = $db->prepare("SELECT id FROM student_profiles WHERE user_id = ?");
    $check->execute([$userId]);
    if ($check->fetch()) {
        $stmt = $db->prepare("UPDATE student_profiles SET 
            education_level = ?, field_of_study = ?, institution = ?, cgpa_percentage = ?,
            graduation_year = ?, dream_role = ?, preferred_industry = ?, preferred_work_env = ?,
            location_pref = ?, experience_level = ?, bio = ?, updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ?");
        $stmt->execute([
            $education_level, $field_of_study, $institution, $cgpa_percentage,
            $graduation_year, $dream_role, $preferred_industry, $preferred_work_env,
            $location_pref, $experience_level, $bio, $userId
        ]);
    } else {
        $stmt = $db->prepare("INSERT INTO student_profiles (
            user_id, education_level, field_of_study, institution, cgpa_percentage,
            graduation_year, dream_role, preferred_industry, preferred_work_env,
            location_pref, experience_level, bio
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId, $education_level, $field_of_study, $institution, $cgpa_percentage,
            $graduation_year, $dream_role, $preferred_industry, $preferred_work_env,
            $location_pref, $experience_level, $bio
        ]);
    }

    $_SESSION['flash_msg'] = "Personal and academic details saved successfully!";
    $_SESSION['flash_type'] = "success";
    header("Location: student_profile.php");
    exit;
}

$stmt = $db->prepare("SELECT u.name, u.phone, p.* FROM users u LEFT JOIN student_profiles p ON u.id = p.user_id WHERE u.id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
        <a href="student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a> / 
        <span style="color: var(--text-primary);">Personal & Academic Details</span>
    </div>

    <div class="glass-card" style="border-color: var(--border-glass-bright); max-width: 900px; margin: 0 auto;">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--border-glass);">
            <div class="brand-icon" style="background: var(--gradient-cyan); width: 3.5rem; height: 3.5rem; font-size: 1.5rem;">
                <i class="fas fa-user-edit"></i>
            </div>
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Enter Personal & Academic Details</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">
                    This data feeds the AI matching algorithm to determine academic affinity and domain eligibility.
                </p>
            </div>
        </div>

        <form action="student_profile.php" method="POST">
            <!-- Section 1: Basic Information -->
            <h3 style="font-size: 1.15rem; color: var(--accent-cyan); margin-bottom: 1.25rem;">
                <i class="fas fa-id-card"></i> 1. Basic Information
            </h3>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="prof_name">Full Name *</label>
                    <input type="text" id="prof_name" name="name" class="form-control" 
                           value="<?= htmlspecialchars($profile['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="prof_phone">Phone / WhatsApp Number</label>
                    <input type="tel" id="prof_phone" name="phone" class="form-control" 
                           value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" placeholder="+91 98765 43210">
                </div>
            </div>

            <!-- Section 2: Educational Details -->
            <h3 style="font-size: 1.15rem; color: var(--accent-primary); margin: 1.75rem 0 1.25rem;">
                <i class="fas fa-graduation-cap"></i> 2. Academic Background
            </h3>

            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label" for="education_level">Current Degree Level *</label>
                    <select id="education_level" name="education_level" class="form-control" required>
                        <?php 
                        $levels = ['High School / 12th', 'B.Tech / B.E', 'BCA / B.Sc Computer Science', 'MCA / M.Sc IT', 'M.Tech / M.E', 'MBA / Post Graduate', 'Other Degree'];
                        $currentLevel = $profile['education_level'] ?? 'B.Tech / B.E';
                        foreach ($levels as $lvl):
                        ?>
                            <option value="<?= $lvl ?>" <?= ($currentLevel === $lvl) ? 'selected' : '' ?>><?= $lvl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="field_of_study">Major / Branch of Study *</label>
                    <input type="text" id="field_of_study" name="field_of_study" class="form-control" 
                           value="<?= htmlspecialchars($profile['field_of_study'] ?? '') ?>" placeholder="e.g. Computer Science & Engineering" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="graduation_year">Expected Passing Year</label>
                    <input type="number" id="graduation_year" name="graduation_year" class="form-control" 
                           value="<?= htmlspecialchars($profile['graduation_year'] ?? 2026) ?>" min="2020" max="2035">
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="institution">College / University Name</label>
                    <input type="text" id="institution" name="institution" class="form-control" 
                           value="<?= htmlspecialchars($profile['institution'] ?? '') ?>" placeholder="e.g. National Institute of Technology">
                </div>
                <div class="form-group">
                    <label class="form-label" for="cgpa_percentage">Current CGPA (Scale of 10) or Percentage</label>
                    <input type="number" step="0.01" id="cgpa_percentage" name="cgpa_percentage" class="form-control" 
                           value="<?= htmlspecialchars($profile['cgpa_percentage'] ?? '') ?>" placeholder="e.g. 8.75">
                </div>
            </div>

            <!-- Section 3: Aspirations & Preferences -->
            <h3 style="font-size: 1.15rem; color: var(--accent-emerald); margin: 1.75rem 0 1.25rem;">
                <i class="fas fa-bullseye"></i> 3. Career Aspirations & Work Preferences
            </h3>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="dream_role">Target Dream Role / Specialization</label>
                    <input type="text" id="dream_role" name="dream_role" class="form-control" 
                           value="<?= htmlspecialchars($profile['dream_role'] ?? '') ?>" placeholder="e.g. AI Machine Learning Engineer, Cloud Architect">
                    <div class="form-helper">The AI uses this to calculate aspiration alignment weights.</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="preferred_industry">Preferred Target Industry</label>
                    <input type="text" id="preferred_industry" name="preferred_industry" class="form-control" 
                           value="<?= htmlspecialchars($profile['preferred_industry'] ?? '') ?>" placeholder="e.g. Fintech, EdTech, Big Tech, Healthcare AI">
                </div>
            </div>

            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label" for="preferred_work_env">Work Environment</label>
                    <select id="preferred_work_env" name="preferred_work_env" class="form-control">
                        <option value="Hybrid" <?= (($profile['preferred_work_env'] ?? '') === 'Hybrid') ? 'selected' : '' ?>>Hybrid (Remote + Office)</option>
                        <option value="Remote" <?= (($profile['preferred_work_env'] ?? '') === 'Remote') ? 'selected' : '' ?>>Fully Remote</option>
                        <option value="On-site" <?= (($profile['preferred_work_env'] ?? '') === 'On-site') ? 'selected' : '' ?>>On-site (Office based)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="experience_level">Current Experience Level</label>
                    <select id="experience_level" name="experience_level" class="form-control">
                        <option value="Fresher" <?= (($profile['experience_level'] ?? '') === 'Fresher') ? 'selected' : '' ?>>Student / Fresher</option>
                        <option value="Internship" <?= (($profile['experience_level'] ?? '') === 'Internship') ? 'selected' : '' ?>>1-2 Internships Done</option>
                        <option value="Experienced" <?= (($profile['experience_level'] ?? '') === 'Experienced') ? 'selected' : '' ?>>1-3 Years Professional</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="location_pref">Preferred Location</label>
                    <input type="text" id="location_pref" name="location_pref" class="form-control" 
                           value="<?= htmlspecialchars($profile['location_pref'] ?? '') ?>" placeholder="e.g. Bengaluru, Hyderabad, Pune, Remote">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="bio">Professional Summary / Bio</label>
                <textarea id="bio" name="bio" class="form-control" 
                          placeholder="Briefly describe your interests, core technical strengths, and project experiences..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                <a href="student_dashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Save Personal Details
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
