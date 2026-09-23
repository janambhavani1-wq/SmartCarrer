<?php
$pageTitle = "Skill Assessment Quiz";
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
$stmt = $db->query("SELECT * FROM skill_assessments WHERE is_active = 1 LIMIT 10");
$questions = $stmt->fetchAll();

$showResult = false;
$scorePct = 0;
$correctCount = 0;
$total = count($questions);
$detailed = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($questions as $q) {
        $qId = $q['id'];
        $userChoice = $_POST['q_' . $qId] ?? '';
        $isCorrect = ($userChoice === $q['correct_option']);
        if ($isCorrect) $correctCount++;

        $detailed[] = [
            'question_id' => $q['id'],
            'question_text' => $q['question_text'],
            'user_choice' => $userChoice,
            'correct_option' => $q['correct_option'],
            'is_correct' => $isCorrect,
            'explanation' => $q['explanation'],
            'skill_tagged' => $q['skill_tagged']
        ];
    }

    $scorePct = ($total > 0) ? round(($correctCount / $total) * 100, 1) : 0;

    // Save assessment response
    $ins = $db->prepare("INSERT INTO student_assessment_responses (user_id, assessment_type, score, total_questions, correct_answers, response_data) VALUES (?, 'skill_assessment', ?, ?, ?, ?)");
    $ins->execute([$userId, $scorePct, $total, $correctCount, json_encode($detailed)]);

    $showResult = true;
}
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
        <a href="student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a> / 
        <span style="color: var(--text-primary);">Skill Assessment Quiz</span>
    </div>

    <?php if ($showResult): ?>
        <!-- Result Hero Banner -->
        <div class="top-rec-hero" style="text-align: center;">
            <span class="section-tag">Scorecard & Performance</span>
            <h1 style="font-size: 2.25rem; margin-bottom: 0.5rem;">Skill Assessment Completed!</h1>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                Your responses have been recorded and incorporated into your AI recommendation vector.
            </p>

            <div style="display: flex; justify-content: center; gap: 2rem; margin-bottom: 1.75rem; flex-wrap: wrap;">
                <div class="glass-card" style="padding: 1rem 2rem; min-width: 180px;">
                    <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Score Percentage</div>
                    <div style="font-size: 2.25rem; font-weight: 800; color: #10b981;"><?= $scorePct ?>%</div>
                </div>
                <div class="glass-card" style="padding: 1rem 2rem; min-width: 180px;">
                    <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Correct Answers</div>
                    <div style="font-size: 2.25rem; font-weight: 800; color: #06b6d4;"><?= $correctCount ?> / <?= $total ?></div>
                </div>
            </div>

            <div style="display: flex; justify-content: center; gap: 1rem;">
                <a href="student_recommendations.php?recalculate=1" class="btn btn-primary btn-lg">
                    <i class="fas fa-magic"></i> Generate Refreshed AI Recommendations
                </a>
                <a href="student_dashboard.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-th-large"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Detailed Solutions Review -->
        <div class="glass-card" style="margin-top: 2.5rem;">
            <h3 style="font-size: 1.35rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-clipboard-check" style="color: var(--accent-cyan);"></i> Question-by-Question Solution Review
            </h3>

            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <?php foreach ($detailed as $idx => $item): ?>
                    <div class="glass-card" style="padding: 1.25rem; border-color: <?= $item['is_correct'] ? 'rgba(16, 185, 129, 0.4)' : 'rgba(244, 63, 94, 0.4)' ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.5rem;">
                            <span class="role-tag"><?= htmlspecialchars($item['skill_tagged']) ?></span>
                            <span class="status-badge <?= $item['is_correct'] ? 'status-replied' : 'status-unread' ?>">
                                <i class="fas <?= $item['is_correct'] ? 'fa-check' : 'fa-times' ?>"></i>
                                <?= $item['is_correct'] ? 'Correct' : 'Incorrect' ?>
                            </span>
                        </div>

                        <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.75rem;">
                            <?= ($idx + 1) ?>. <?= htmlspecialchars($item['question_text']) ?>
                        </div>

                        <div style="display: flex; gap: 1.5rem; font-size: 0.875rem; margin-bottom: 0.5rem;">
                            <div>Your Choice: <strong style="color: <?= $item['is_correct'] ? '#10b981' : '#f43f5e' ?>;">Option <?= htmlspecialchars($item['user_choice']) ?></strong></div>
                            <div>Correct Answer: <strong style="color: #10b981;">Option <?= htmlspecialchars($item['correct_option']) ?></strong></div>
                        </div>

                        <div style="font-size: 0.85rem; color: var(--text-secondary); background: rgba(255, 255, 255, 0.03); padding: 0.6rem 0.85rem; border-radius: var(--radius-sm);">
                            <i class="fas fa-lightbulb" style="color: #fbbf24; margin-right: 0.35rem;"></i> <strong>Explanation:</strong> <?= htmlspecialchars($item['explanation']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php else: ?>

        <!-- Quiz Header Banner with Live Timer -->
        <div class="glass-card" style="margin-bottom: 2rem; border-color: var(--border-glass-bright); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
            <div>
                <span class="section-tag" style="margin-bottom: 0.25rem;">Live Technical Evaluation</span>
                <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Skill Assessment Quiz</h1>
                <p style="color: var(--text-secondary); font-size: 0.95rem;">
                    Answer 10 multiple-choice questions covering algorithms, web tech, databases, and problem solving.
                </p>
            </div>

            <!-- Timer Box -->
            <div class="glass-card" style="padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.75rem; background: rgba(15, 23, 42, 0.9);">
                <i class="fas fa-clock fa-2x" style="color: var(--accent-amber);"></i>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Time Remaining</div>
                    <div id="quizTimer" style="font-size: 1.4rem; font-family: var(--font-heading); font-weight: 800; color: #10b981;">10:00</div>
                </div>
            </div>
        </div>

        <form id="quizForm" action="student_skill_assessment.php" method="POST">
            <?php foreach ($questions as $idx => $q): ?>
                <div class="quiz-card">
                    <div class="quiz-q-header">
                        <div class="quiz-q-number"><?= ($idx + 1) ?></div>
                        <div style="flex: 1;">
                            <span class="role-tag" style="margin-bottom: 0.35rem; display: inline-block;"><?= htmlspecialchars($q['category']) ?> • <?= htmlspecialchars($q['skill_tagged']) ?></span>
                            <div class="quiz-q-text"><?= htmlspecialchars($q['question_text']) ?></div>
                        </div>
                    </div>

                    <div class="quiz-options-list">
                        <label class="quiz-option-label">
                            <input type="radio" name="q_<?= $q['id'] ?>" value="A" required>
                            <span><strong>A.</strong> <?= htmlspecialchars($q['option_a']) ?></span>
                        </label>
                        <label class="quiz-option-label">
                            <input type="radio" name="q_<?= $q['id'] ?>" value="B">
                            <span><strong>B.</strong> <?= htmlspecialchars($q['option_b']) ?></span>
                        </label>
                        <label class="quiz-option-label">
                            <input type="radio" name="q_<?= $q['id'] ?>" value="C">
                            <span><strong>C.</strong> <?= htmlspecialchars($q['option_c']) ?></span>
                        </label>
                        <label class="quiz-option-label">
                            <input type="radio" name="q_<?= $q['id'] ?>" value="D">
                            <span><strong>D.</strong> <?= htmlspecialchars($q['option_d']) ?></span>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem;">
                <a href="student_dashboard.php" class="btn btn-secondary">Exit Quiz</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-check-double"></i> Submit & Grade Assessment
                </button>
            </div>
        </form>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
