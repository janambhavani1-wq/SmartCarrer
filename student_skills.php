<?php
$pageTitle = "Skill Self-Evaluation Matrix";
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
    foreach ($_POST as $key => $val) {
        if (strpos($key, 'skill_') === 0) {
            $skillId = intval(str_replace('skill_', '', $key));
            $rating = max(1, min(5, intval($val)));

            // Check if exists
            $check = $db->prepare("SELECT id FROM student_skills WHERE user_id = ? AND skill_id = ?");
            $check->execute([$userId, $skillId]);
            if ($check->fetch()) {
                $upd = $db->prepare("UPDATE student_skills SET proficiency_level = ?, assessed_score = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND skill_id = ?");
                $upd->execute([$rating, $rating * 20.0, $userId, $skillId]);
            } else {
                $ins = $db->prepare("INSERT INTO student_skills (user_id, skill_id, proficiency_level, assessed_score) VALUES (?, ?, ?, ?)");
                $ins->execute([$userId, $skillId, $rating, $rating * 20.0]);
            }
        }
    }

    $_SESSION['flash_msg'] = "Skill proficiencies saved successfully! Recalculating AI career matches...";
    $_SESSION['flash_type'] = "success";
    header("Location: student_recommendations.php?recalculate=1");
    exit;
}

// Fetch all active skills
$stmt = $db->query("SELECT * FROM skills WHERE is_active = 1 ORDER BY category, name");
$allSkills = $stmt->fetchAll();

// Fetch student rated skills
$stmt = $db->prepare("SELECT skill_id, proficiency_level FROM student_skills WHERE user_id = ?");
$stmt->execute([$userId]);
$userRatings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Group by category
$skillsByCategory = [];
foreach ($allSkills as $sk) {
    $cat = $sk['category'];
    if (!isset($skillsByCategory[$cat])) {
        $skillsByCategory[$cat] = [];
    }
    $sk['current_rating'] = $userRatings[$sk['id']] ?? 1;
    $skillsByCategory[$cat][] = $sk;
}
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
        <a href="student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a> / 
        <span style="color: var(--text-primary);">Skill Proficiency Evaluation</span>
    </div>

    <div class="glass-card" style="margin-bottom: 2rem; border-color: var(--border-glass-bright);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="section-tag" style="margin-bottom: 0.25rem;">Talent Matrix</span>
                <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Skill Self-Evaluation Matrix</h1>
                <p style="color: var(--text-secondary); font-size: 0.95rem;">
                    Rate your confidence and hands-on proficiency across technical, analytical, and design skills.
                </p>
            </div>
            <div>
                <a href="student_skill_assessment.php" class="btn btn-primary">
                    <i class="fas fa-stopwatch"></i> Take Timed Skill Quiz
                </a>
            </div>
        </div>
    </div>

    <form action="student_skills.php" method="POST">
        <?php foreach ($skillsByCategory as $categoryName => $skills): ?>
            <div class="glass-card" style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.3rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.6rem; color: var(--accent-cyan);">
                    <i class="fas <?php 
                        if (strpos($categoryName, 'Technical') !== false) echo 'fa-laptop-code';
                        elseif (strpos($categoryName, 'Analytical') !== false) echo 'fa-chart-pie';
                        elseif (strpos($categoryName, 'Design') !== false) echo 'fa-palette';
                        else echo 'fa-users-cog';
                    ?>"></i>
                    <?= htmlspecialchars($categoryName) ?>
                </h2>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.25rem;">
                    <?php foreach ($skills as $skill): ?>
                        <div class="skill-slider-box">
                            <div class="skill-slider-top">
                                <div>
                                    <strong style="font-size: 0.95rem; color: var(--text-primary);"><?= htmlspecialchars($skill['name']) ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($skill['description']) ?></div>
                                </div>
                                <span class="slider-val-badge" id="badge_<?= $skill['id'] ?>"><?= $skill['current_rating'] ?>/5</span>
                            </div>
                            
                            <div style="margin-top: 0.75rem;">
                                <input type="range" 
                                       name="skill_<?= $skill['id'] ?>" 
                                       data-skill-id="<?= $skill['id'] ?>" 
                                       class="skill-range"
                                       min="1" max="5" step="1" 
                                       value="<?= $skill['current_rating'] ?>">
                            </div>

                            <div style="display: flex; justify-content: space-between; font-size: 0.7rem; color: var(--text-muted); margin-top: 0.35rem;">
                                <span>1 (Novice)</span>
                                <span>3 (Intermediate)</span>
                                <span>5 (Expert)</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Sticky Bottom Save Bar -->
        <div class="glass-card" style="position: sticky; bottom: 1.5rem; z-index: 100; display: flex; justify-content: space-between; align-items: center; border-color: var(--border-glass-bright); background: rgba(15, 23, 42, 0.95);">
            <div>
                <strong style="color: var(--text-primary); font-size: 0.95rem;">Ready to save your skill ratings?</strong>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">Updates your vector profile for AI career matching.</div>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <a href="student_dashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Save Skills & Recalculate
                </button>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
