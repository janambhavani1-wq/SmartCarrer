<?php
require_once __DIR__ . '/header.php';

$db = getDBConnection();
$careerId = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM career_paths WHERE id = ?");
$stmt->execute([$careerId]);
$career = $stmt->fetch();

if (!$career) {
    $_SESSION['flash_msg'] = "Career profile not found.";
    $_SESSION['flash_type'] = "danger";
    header("Location: careers.php");
    exit;
}

$pageTitle = $career['title'];
$skillsMap = json_decode($career['required_skills'], true) ?: [];
$topCompanies = json_decode($career['top_companies'], true) ?: [];
$roadmapSteps = json_decode($career['roadmap'], true) ?: [];
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
        <a href="careers.php"><i class="fas fa-arrow-left"></i> Back to All Careers</a> / 
        <span style="color: var(--text-secondary);"><?= htmlspecialchars($career['category']) ?></span> / 
        <span style="color: var(--text-primary);"><?= htmlspecialchars($career['title']) ?></span>
    </div>

    <!-- Career Hero Card -->
    <div class="top-rec-hero">
        <div style="display: flex; gap: 2rem; align-items: flex-start; flex-wrap: wrap;">
            <div class="career-icon-box" style="width: 4.5rem; height: 4.5rem; font-size: 2rem;">
                <i class="<?= htmlspecialchars($career['icon']) ?>"></i>
            </div>
            <div style="flex: 1; min-width: 280px;">
                <span class="role-tag" style="background: rgba(6, 182, 212, 0.2); color: #67e8f9; border-color: rgba(6, 182, 212, 0.4);">
                    <?= htmlspecialchars($career['category']) ?>
                </span>
                <h1 style="font-size: 2.25rem; margin: 0.5rem 0 1rem;"><?= htmlspecialchars($career['title']) ?></h1>
                <p style="color: var(--text-secondary); font-size: 1.05rem; line-height: 1.6;"><?= htmlspecialchars($career['description']) ?></p>
            </div>
            <div class="glass-card" style="padding: 1.25rem 1.75rem; min-width: 260px; background: rgba(15, 23, 42, 0.9);">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Average Compensation</div>
                <div style="font-size: 1.4rem; font-weight: 800; color: #10b981; margin: 0.25rem 0;"><?= htmlspecialchars($career['average_salary_inr']) ?></div>
                <div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.75rem;">Range: <?= htmlspecialchars($career['salary_range']) ?></div>
                <div class="badge-growth"><i class="fas fa-arrow-trend-up"></i> <?= htmlspecialchars($career['growth_outlook']) ?></div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <!-- Left Column: Required Skills & Step-by-Step Roadmap -->
        <div>
            <!-- Required Skills Breakdown -->
            <div class="glass-card" style="margin-bottom: 2rem;">
                <h3 style="font-size: 1.35rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-cogs" style="color: var(--accent-cyan);"></i> Required Core Skills & Competency Benchmarks
                </h3>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($skillsMap as $skillName => $weight): 
                        $weightPct = intval($weight * 100);
                    ?>
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.35rem;">
                                <strong><?= htmlspecialchars($skillName) ?></strong>
                                <span style="color: var(--text-secondary);">Weight: <?= $weightPct ?>% Importance</span>
                            </div>
                            <div style="height: 8px; background: rgba(255, 255, 255, 0.08); border-radius: 4px; overflow: hidden;">
                                <div style="width: <?= $weightPct ?>%; height: 100%; background: var(--gradient-brand); border-radius: 4px;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Learning Roadmap Timeline -->
            <div class="glass-card">
                <h3 style="font-size: 1.35rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-route" style="color: var(--accent-primary);"></i> Step-by-Step Milestone Learning Roadmap
                </h3>
                
                <div class="roadmap-timeline">
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
                </div>
            </div>
        </div>

        <!-- Right Column: Educational Requirements & Top Hiring Companies -->
        <div>
            <!-- Education Requirement -->
            <div class="glass-card" style="margin-bottom: 1.5rem;">
                <h4 style="font-size: 1.1rem; margin-bottom: 0.75rem; color: var(--accent-cyan);">
                    <i class="fas fa-graduation-cap"></i> Academic Eligibility
                </h4>
                <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6;">
                    <?= htmlspecialchars($career['education_requirement']) ?>
                </p>
            </div>

            <!-- Top Companies Hiring -->
            <div class="glass-card" style="margin-bottom: 1.5rem;">
                <h4 style="font-size: 1.1rem; margin-bottom: 0.75rem; color: var(--accent-emerald);">
                    <i class="fas fa-building"></i> Top Hiring Companies
                </h4>
                <div class="skills-pill-wrap">
                    <?php foreach ($topCompanies as $comp): ?>
                        <span class="skill-pill" style="border-color: rgba(16, 185, 129, 0.3); color: #a7f3d0;">
                            <i class="fas fa-briefcase" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= htmlspecialchars($comp) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Action Prompt -->
            <div class="glass-card" style="border-color: var(--border-glass-bright); text-align: center; padding: 1.75rem;">
                <i class="fas fa-magic fa-2x" style="color: var(--accent-secondary); margin-bottom: 0.75rem;"></i>
                <h4 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Check Your AI Fit Score</h4>
                <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.25rem;">
                    Evaluate how your personal skills and aptitude match this exact career track.
                </p>
                <?php if ($currentUser && $currentUser['role'] === 'student'): ?>
                    <a href="student_recommendations.php" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-calculator"></i> Run AI Recommendation
                    </a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-user-plus"></i> Sign Up to Test Fit
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
