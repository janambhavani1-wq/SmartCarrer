<?php
$pageTitle = "Career Psychometric Assessment";
require_once __DIR__ . '/header.php';

if (!$currentUser || $currentUser['role'] !== 'student') {
    $_SESSION['flash_msg'] = "Please log in as a student.";
    $_SESSION['flash_type'] = "warning";
    header("Location: login.php");
    exit;
}

$db = getDBConnection();
$userId = $currentUser['id'];

// Fetch questions
$stmt = $db->query("SELECT * FROM career_assessments WHERE is_active = 1");
$questions = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $traitScores = [];

    foreach ($questions as $q) {
        $qId = $q['id'];
        $selectedIdx = $_POST['cq_' . $qId] ?? null;
        if ($selectedIdx !== null) {
            $options = json_decode($q['options_json'], true) ?: [];
            $idx = intval($selectedIdx);
            if (isset($options[$idx])) {
                $chosen = $options[$idx];
                $traits = $chosen['traits'] ?? [];
                foreach ($traits as $trait => $score) {
                    $traitScores[$trait] = ($traitScores[$trait] ?? 0) + $score;
                }
            }
        }
    }

    $ins = $db->prepare("INSERT INTO student_assessment_responses (user_id, assessment_type, score, total_questions, correct_answers, response_data) VALUES (?, 'career_assessment', 100.0, ?, ?, ?)");
    $ins->execute([$userId, count($questions), count($questions), json_encode($traitScores)]);

    $_SESSION['flash_msg'] = "Career psychometric assessment recorded! Recalculating AI career matches...";
    $_SESSION['flash_type'] = "success";
    header("Location: student_recommendations.php?recalculate=1");
    exit;
}
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
        <a href="student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a> / 
        <span style="color: var(--text-primary);">Career Psychometric Assessment</span>
    </div>

    <!-- Header Banner -->
    <div class="glass-card" style="margin-bottom: 2rem; border-color: var(--border-glass-bright);">
        <span class="section-tag" style="margin-bottom: 0.25rem;">Holland RIASEC & Work-Style Framework</span>
        <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Career Psychometric Assessment</h1>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            Select the scenario responses that best describe your natural instincts, problem-solving style, and collaborative motivations.
        </p>
    </div>

    <form action="student_career_assessment.php" method="POST">
        <?php foreach ($questions as $idx => $q): 
            $options = json_decode($q['options_json'], true) ?: [];
        ?>
            <div class="quiz-card">
                <div class="quiz-q-header">
                    <div class="quiz-q-number" style="background: var(--gradient-brand);"><?= ($idx + 1) ?></div>
                    <div style="flex: 1;">
                        <span class="role-tag" style="margin-bottom: 0.35rem; display: inline-block;"><?= htmlspecialchars($q['dimension']) ?></span>
                        <div class="quiz-q-text"><?= htmlspecialchars($q['question_text']) ?></div>
                        <?php if (!empty($q['scenario'])): ?>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">
                                <em>Context: <?= htmlspecialchars($q['scenario']) ?></em>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="quiz-options-list">
                    <?php foreach ($options as $optIdx => $opt): ?>
                        <label class="quiz-option-label">
                            <input type="radio" name="cq_<?= $q['id'] ?>" value="<?= $optIdx ?>" required>
                            <span><?= htmlspecialchars($opt['label'] ?? '') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem;">
            <a href="student_dashboard.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-magic"></i> Submit & Generate AI Career Match
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
