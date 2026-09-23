<?php
$pageTitle = "AI Career Recommendations";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/ai_engine.php';

if (!$currentUser || $currentUser['role'] !== 'student') {
    $_SESSION['flash_msg'] = "Please log in as a student to view recommendations.";
    $_SESSION['flash_type'] = "warning";
    header("Location: login.php");
    exit;
}

$db = getDBConnection();
$userId = $currentUser['id'];

// If recalculate requested or no recs exist, compute now
$forceRecalculate = isset($_GET['recalculate']);

$stmt = $db->prepare("SELECT r.*, c.icon as career_icon, c.description as career_desc FROM recommendation_records r LEFT JOIN career_paths c ON r.top_career_id = c.id WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 1");
$stmt->execute([$userId]);
$rec = $stmt->fetch();

if (!$rec || $forceRecalculate) {
    computeCareerRecommendationsPHP($userId);
    $stmt->execute([$userId]);
    $rec = $stmt->fetch();
}

// Fetch profile
$stmt = $db->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

$reasoningList = json_decode($rec['reasoning'] ?? '[]', true) ?: [];
$matchedSkills = json_decode($rec['matched_skills'] ?? '[]', true) ?: [];
$missingSkills = json_decode($rec['missing_skills'] ?? '[]', true) ?: [];
$roadmapSteps = json_decode($rec['learning_roadmap'] ?? '[]', true) ?: [];
$secondaryCareers = json_decode($rec['secondary_careers'] ?? '[]', true) ?: [];
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <!-- Action Header with Print / Export -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;" class="no-print">
        <div>
            <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">
                <a href="student_dashboard.php"><i class="fas fa-arrow-left"></i> Dashboard</a> / AI Recommendations
            </div>
            <h1 style="font-size: 1.85rem;">Personalized AI Career Roadmap</h1>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print / Save Academic PDF
            </button>
            <a href="student_recommendations.php?recalculate=1" class="btn btn-primary">
                <i class="fas fa-sync-alt"></i> Refresh AI Scoring
            </a>
        </div>
    </div>

    <!-- Student Dossier Header (Visible in Print / Academic Report) -->
    <div class="glass-card" style="margin-bottom: 2rem; border-color: var(--border-glass-bright); padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="role-tag" style="background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border-color: rgba(16, 185, 129, 0.4);">
                    Verified AI Recommendation Report
                </span>
                <h2 style="font-size: 1.4rem; margin: 0.4rem 0 0.2rem;">Candidate: <?= htmlspecialchars($currentUser['name']) ?></h2>
                <p style="color: var(--text-secondary); font-size: 0.85rem;">
                    <?= htmlspecialchars($profile['education_level'] ?? 'B.Tech') ?> in <?= htmlspecialchars($profile['field_of_study'] ?? 'Computer Science') ?> • <?= htmlspecialchars($profile['institution'] ?? 'University') ?> (CGPA: <?= htmlspecialchars($profile['cgpa_percentage'] ?? 'N/A') ?>)
                </p>
            </div>
            <div style="text-align: right; font-size: 0.8rem; color: var(--text-muted);">
                <div>Assessment Date: <?= htmlspecialchars($rec['created_at'] ?? date('Y-m-d')) ?></div>
                <div>Engine: Multi-Factor Cosine & RIASEC Scoring</div>
            </div>
        </div>
    </div>

    <!-- #1 Top AI Recommended Career Hero -->
    <div class="top-rec-hero">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 2rem;">
            <div style="display: flex; gap: 1.5rem; align-items: center; flex: 1; min-width: 300px;">
                <div class="career-icon-box" style="width: 4.5rem; height: 4.5rem; font-size: 2.25rem;">
                    <i class="<?= htmlspecialchars($rec['career_icon'] ?? 'fas fa-brain') ?>"></i>
                </div>
                <div>
                    <span class="section-tag" style="margin-bottom: 0.2rem;">#1 Primary Career Match</span>
                    <h2 style="font-size: 2.25rem; margin-bottom: 0.25rem;"><?= htmlspecialchars($rec['top_career_title'] ?? '') ?></h2>
                    <div style="color: #10b981; font-size: 1.15rem; font-weight: 700;">
                        <i class="fas fa-indian-rupee-sign"></i> <?= htmlspecialchars($rec['salary_forecast'] ?? '') ?>
                    </div>
                </div>
            </div>

            <!-- Match Score Circle -->
            <div class="glass-card" style="padding: 1.25rem 2rem; text-align: center; background: rgba(15, 23, 42, 0.9); border-color: var(--border-glass-bright);">
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Composite AI Match</div>
                <div class="match-percentage-badge"><?= htmlspecialchars($rec['match_percentage'] ?? '90') ?>%</div>
                <div class="badge-growth" style="font-size: 0.8rem;"><i class="fas fa-check-circle"></i> High Suitability</div>
            </div>
        </div>

        <p style="color: var(--text-secondary); font-size: 1rem; line-height: 1.6; margin: 1.5rem 0 1.25rem;">
            <?= htmlspecialchars($rec['career_desc'] ?? 'Tailored career path based on your multi-factor talent evaluation.') ?>
        </p>

        <!-- Explainable AI (XAI) Insight Points -->
        <div class="glass-card" style="background: rgba(15, 23, 42, 0.8); padding: 1.25rem; border-color: rgba(99, 102, 241, 0.3);">
            <h4 style="font-size: 0.95rem; color: #a5b4fc; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-lightbulb"></i> Explainable AI (XAI) Recommendation Rationale
            </h4>
            <div style="display: flex; flex-direction: column; gap: 0.6rem; font-size: 0.875rem; color: var(--text-primary);">
                <?php if (!empty($reasoningList)): ?>
                    <?php foreach ($reasoningList as $point): ?>
                        <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                            <i class="fas fa-check-circle" style="color: var(--accent-cyan); margin-top: 0.2rem; font-size: 0.8rem;"></i>
                            <div><?= $point ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div>Calculated via weighted cosine similarity and RIASEC psychometrics.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Skills Gap Analysis & Radar Chart -->
    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 2rem; margin-bottom: 2.5rem;">
        <!-- Left: Skill Matrix Breakdown (Strengths vs Gaps) -->
        <div class="glass-card">
            <h3 style="font-size: 1.25rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-balance-scale" style="color: var(--accent-cyan);"></i> Skill Matrix & Gap Analysis
            </h3>

            <!-- Matched Skills -->
            <div style="margin-bottom: 1.5rem;">
                <h4 style="font-size: 0.95rem; color: #6ee7b7; margin-bottom: 0.5rem;">
                    <i class="fas fa-check-circle"></i> Identified Strengths (Matched Skills)
                </h4>
                <div class="skills-pill-wrap">
                    <?php if (!empty($matchedSkills)): ?>
                        <?php foreach ($matchedSkills as $m): ?>
                            <span class="skill-pill matched">
                                <i class="fas fa-check"></i> <?= htmlspecialchars($m['skill']) ?> (Level <?= $m['student_level'] ?>/5)
                            </span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span style="color: var(--text-muted); font-size: 0.85rem;">No direct matching skills recorded yet.</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Missing / Gap Skills -->
            <div>
                <h4 style="font-size: 0.95rem; color: #fda4af; margin-bottom: 0.5rem;">
                    <i class="fas fa-exclamation-circle"></i> Recommended Upskilling Targets (Skill Gaps)
                </h4>
                <div class="skills-pill-wrap">
                    <?php if (!empty($missingSkills)): ?>
                        <?php foreach ($missingSkills as $gap): ?>
                            <span class="skill-pill missing">
                                <i class="fas fa-arrow-up"></i> <?= htmlspecialchars($gap['skill']) ?> (Priority: <?= $gap['priority'] ?>)
                            </span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span style="color: #6ee7b7; font-size: 0.85rem;">All prerequisite skills matched! You are ready to apply.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Radar Chart Visualization -->
        <div class="glass-card">
            <h3 style="font-size: 1.25rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-chart-pie" style="color: var(--accent-primary);"></i> Evaluated Competency Graph
            </h3>
            <div style="height: 260px; position: relative;">
                <canvas id="studentSkillRadarChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Step-by-Step Personalized Learning Roadmap -->
    <div class="glass-card" style="margin-bottom: 2.5rem;">
        <h3 style="font-size: 1.35rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-route" style="color: var(--accent-primary);"></i> Step-by-Step Personalized Learning Roadmap
        </h3>
        
        <div class="roadmap-timeline">
            <?php if (!empty($roadmapSteps)): ?>
                <?php foreach ($roadmapSteps as $step): ?>
                    <div class="roadmap-step">
                        <div class="roadmap-step-dot">
                            <i class="fas fa-check" style="font-size: 0.6rem; color: var(--accent-primary);"></i>
                        </div>
                        <div class="roadmap-step-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <strong style="color: var(--text-primary); font-size: 1.05rem;"><?= htmlspecialchars($step['phase'] ?? '') ?></strong>
                                <span class="role-tag" style="background: rgba(99, 102, 241, 0.15);"><?= htmlspecialchars($step['duration'] ?? '') ?></span>
                            </div>
                            <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;"><?= htmlspecialchars($step['focus'] ?? '') ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Secondary / Alternative Recommended Career Paths -->
    <?php if (!empty($secondaryCareers)): ?>
        <div style="margin-bottom: 2.5rem;">
            <div class="section-header" style="text-align: left; margin-bottom: 1.5rem;">
                <span class="section-tag">Alternative Pathways</span>
                <h3 class="section-title" style="font-size: 1.6rem;">Secondary Career Options Evaluated</h3>
                <p class="section-desc">Additional high-fit roles matching your profile in adjacent domains.</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <?php foreach ($secondaryCareers as $sec): ?>
                    <div class="glass-card glass-card-hover" style="display: flex; flex-direction: column;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                            <span class="career-category"><?= htmlspecialchars($sec['category']) ?></span>
                            <span class="status-badge status-read"><?= htmlspecialchars($sec['match_percentage']) ?>% Match</span>
                        </div>
                        <h4 style="font-size: 1.15rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($sec['title']) ?></h4>
                        <div style="color: #10b981; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.75rem;"><?= htmlspecialchars($sec['average_salary_inr']) ?></div>
                        <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.5; flex-grow: 1; margin-bottom: 1rem;">
                            <?= htmlspecialchars(substr($sec['description'], 0, 120)) ?>...
                        </p>
                        <a href="career_detail.php?id=<?= $sec['career_id'] ?>" class="btn btn-sm btn-outline" style="width: 100%;">
                            <i class="fas fa-eye"></i> View Roadmap
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
