<?php
$pageTitle = "Student Dashboard";
require_once __DIR__ . '/header.php';

if (!$currentUser || $currentUser['role'] !== 'student') {
    $_SESSION['flash_msg'] = "Please log in as a student to access the student portal.";
    $_SESSION['flash_type'] = "warning";
    header("Location: login.php");
    exit;
}

$db = getDBConnection();
$userId = $currentUser['id'];

// Get Profile
$stmt = $db->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

// Get Skills
$stmt = $db->prepare("SELECT s.name, ss.proficiency_level FROM skills s JOIN student_skills ss ON s.id = ss.skill_id WHERE ss.user_id = ?");
$stmt->execute([$userId]);
$skills = $stmt->fetchAll();

// Get Assessment History
$stmt = $db->prepare("SELECT * FROM student_assessment_responses WHERE user_id = ? ORDER BY completed_at DESC");
$stmt->execute([$userId]);
$assessmentHistory = $stmt->fetchAll();

// Get Latest Recommendation
$stmt = $db->prepare("SELECT r.*, c.icon as career_icon, c.description as career_desc FROM recommendation_records r LEFT JOIN career_paths c ON r.top_career_id = c.id WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 1");
$stmt->execute([$userId]);
$latestRec = $stmt->fetch();

// Profile completion %
$profileFields = ['education_level', 'field_of_study', 'institution', 'cgpa_percentage', 'graduation_year', 'dream_role', 'bio'];
$filledCount = 0;
foreach ($profileFields as $f) {
    if (!empty($profile[$f])) $filledCount++;
}
$profileCompletion = intval(($filledCount / count($profileFields)) * 100);

$hasSkillTest = false;
$hasCareerTest = false;
foreach ($assessmentHistory as $a) {
    if ($a['assessment_type'] === 'skill_assessment') $hasSkillTest = true;
    if ($a['assessment_type'] === 'career_assessment') $hasCareerTest = true;
}
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <!-- Welcome Banner -->
    <div class="glass-card" style="margin-bottom: 2rem; border-color: var(--border-glass-bright); background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(99, 102, 241, 0.1) 100%);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
            <div>
                <span class="section-tag" style="margin-bottom: 0.25rem;">Student Command Center</span>
                <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h1>
                <p style="color: var(--text-secondary); font-size: 0.95rem;">
                    <?= htmlspecialchars($profile['education_level'] ?? 'Undergraduate') ?> in <?= htmlspecialchars($profile['field_of_study'] ?? 'Engineering/Science') ?> • <?= htmlspecialchars($profile['institution'] ?? 'University') ?>
                </p>
            </div>
            <div>
                <a href="student_recommendations.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-magic"></i> View AI Career Matches
                </a>
            </div>
        </div>
    </div>

    <!-- Progress / Onboarding Meter -->
    <div class="glass-card" style="margin-bottom: 2rem; padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-tasks" style="color: var(--accent-cyan);"></i>
                <span style="font-weight: 700; font-size: 0.95rem;">Profile & Assessment Completion</span>
            </div>
            <span style="font-weight: 800; color: var(--accent-cyan);"><?= $profileCompletion ?>% Completed</span>
        </div>
        <div style="height: 10px; background: rgba(255, 255, 255, 0.08); border-radius: 5px; overflow: hidden; margin-bottom: 1rem;">
            <div style="width: <?= $profileCompletion ?>%; height: 100%; background: var(--gradient-brand); border-radius: 5px; transition: width 0.5s ease;"></div>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; font-size: 0.85rem;">
            <div style="color: <?= !empty($profile['field_of_study']) ? '#10b981' : 'var(--text-muted)' ?>;">
                <i class="fas fa-<?= !empty($profile['field_of_study']) ? 'check-circle' : 'circle' ?>"></i> Personal Details
            </div>
            <div style="color: <?= !empty($skills) ? '#10b981' : 'var(--text-muted)' ?>;">
                <i class="fas fa-<?= !empty($skills) ? 'check-circle' : 'circle' ?>"></i> Skill Self-Ratings (<?= count($skills) ?> rated)
            </div>
            <div style="color: <?= $hasSkillTest ? '#10b981' : 'var(--text-muted)' ?>;">
                <i class="fas fa-<?= $hasSkillTest ? 'check-circle' : 'circle' ?>"></i> Skill Assessment Quiz
            </div>
            <div style="color: <?= $hasCareerTest ? '#10b981' : 'var(--text-muted)' ?>;">
                <i class="fas fa-<?= $hasCareerTest ? 'check-circle' : 'circle' ?>"></i> RIASEC Career Assessment
            </div>
        </div>
    </div>

    <!-- Quick Action Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <a href="student_profile.php" class="glass-card glass-card-hover" style="display: block;">
            <div class="brand-icon" style="background: var(--gradient-cyan); margin-bottom: 1rem;">
                <i class="fas fa-user-edit"></i>
            </div>
            <h3 style="font-size: 1.15rem; margin-bottom: 0.35rem; color: var(--text-primary);">Personal Details</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem;">Update academic qualifications, dream role, and bio.</p>
        </a>

        <a href="student_skills.php" class="glass-card glass-card-hover" style="display: block;">
            <div class="brand-icon" style="background: var(--gradient-brand); margin-bottom: 1rem;">
                <i class="fas fa-sliders-h"></i>
            </div>
            <h3 style="font-size: 1.15rem; margin-bottom: 0.35rem; color: var(--text-primary);">Skill Evaluation</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem;">Rate your proficiency in 40+ technical & analytical skills.</p>
        </a>

        <a href="student_skill_assessment.php" class="glass-card glass-card-hover" style="display: block;">
            <div class="brand-icon" style="background: var(--gradient-amber); margin-bottom: 1rem;">
                <i class="fas fa-stopwatch"></i>
            </div>
            <h3 style="font-size: 1.15rem; margin-bottom: 0.35rem; color: var(--text-primary);">Skill Quiz Test</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem;">Take a 10-question timed aptitude & tech test.</p>
        </a>

        <a href="student_career_assessment.php" class="glass-card glass-card-hover" style="display: block;">
            <div class="brand-icon" style="background: var(--gradient-emerald); margin-bottom: 1rem;">
                <i class="fas fa-brain"></i>
            </div>
            <h3 style="font-size: 1.15rem; margin-bottom: 0.35rem; color: var(--text-primary);">Career Psychometrics</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem;">Evaluate your Holland RIASEC work style personality.</p>
        </a>
    </div>

    <!-- Latest AI Recommendation & Skills Radar Section -->
    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 2rem;">
        <!-- Left: Top AI Recommendation Spotlight -->
        <div class="glass-card">
            <div class="card-header-flex">
                <h3 style="font-size: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-star" style="color: #fbbf24;"></i> Latest AI Recommendation
                </h3>
                <?php if ($latestRec): ?>
                    <span class="status-badge status-replied"><?= htmlspecialchars($latestRec['match_percentage']) ?>% Match</span>
                <?php endif; ?>
            </div>

            <?php if ($latestRec): ?>
                <div style="display: flex; gap: 1.25rem; align-items: center; margin-bottom: 1.25rem;">
                    <div class="career-icon-box" style="width: 3.75rem; height: 3.75rem; font-size: 1.6rem;">
                        <i class="<?= htmlspecialchars($latestRec['career_icon'] ?? 'fas fa-briefcase') ?>"></i>
                    </div>
                    <div>
                        <h4 style="font-size: 1.35rem; margin-bottom: 0.2rem;"><?= htmlspecialchars($latestRec['top_career_title']) ?></h4>
                        <div style="color: #10b981; font-weight: 700; font-size: 0.95rem;"><?= htmlspecialchars($latestRec['salary_forecast']) ?></div>
                    </div>
                </div>

                <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.5rem;">
                    <?= htmlspecialchars($latestRec['career_desc'] ?? 'AI-matched based on your technical competencies and work preferences.') ?>
                </p>

                <div style="display: flex; gap: 0.75rem;">
                    <a href="student_recommendations.php" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-file-alt"></i> Full AI Report & Roadmap
                    </a>
                    <a href="student_recommendations.php?recalculate=1" class="btn btn-secondary" title="Recalculate with latest skills">
                        <i class="fas fa-sync-alt"></i> Recalculate
                    </a>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2.5rem 1rem;">
                    <i class="fas fa-compass fa-3x" style="color: var(--accent-cyan); margin-bottom: 1rem; opacity: 0.7;"></i>
                    <h4 style="margin-bottom: 0.5rem;">No Career Recommendation Computed Yet</h4>
                    <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 1.5rem;">
                        Complete your skills self-evaluation or assessments to generate your personalized AI recommendation.
                    </p>
                    <a href="student_recommendations.php" class="btn btn-primary">
                        <i class="fas fa-magic"></i> Generate First AI Recommendation
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Student Skill Radar Visualization -->
        <div class="glass-card">
            <div class="card-header-flex">
                <h3 style="font-size: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-chart-pie" style="color: var(--accent-cyan);"></i> Skills Competency Radar
                </h3>
                <a href="student_skills.php" class="btn btn-sm btn-outline">Edit Skills</a>
            </div>
            <div style="height: 280px; position: relative;">
                <canvas id="studentSkillRadarChart"></canvas>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
