<?php
$pageTitle = "Explore Career Tracks";
require_once __DIR__ . '/header.php';

$db = getDBConnection();
$category = $_GET['category'] ?? null;

$query = "SELECT * FROM career_paths WHERE is_active = 1";
$params = [];
if ($category) {
    $query .= " AND category = ?";
    $params[] = $category;
}
$query .= " ORDER BY title ASC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$careers = $stmt->fetchAll();

// Get unique categories
$catStmt = $db->query("SELECT DISTINCT category FROM career_paths WHERE is_active = 1 ORDER BY category ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <div class="section-header" style="text-align: left; margin-bottom: 2rem;">
        <span class="section-tag">Career Repository</span>
        <h1 class="section-title">Explore High-Impact Industry Career Tracks</h1>
        <p class="section-desc">Comprehensive guide to modern job roles, required technical skills, salary benchmarks, and hiring companies.</p>
    </div>

    <!-- Category Filter Pills -->
    <div style="display: flex; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 2.5rem;">
        <a href="careers.php" 
           class="btn btn-sm <?= empty($category) ? 'btn-primary' : 'btn-secondary' ?>">
            All Categories (<?= count($careers) ?>)
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="careers.php?category=<?= urlencode($cat) ?>" 
               class="btn btn-sm <?= ($category === $cat) ? 'btn-primary' : 'btn-secondary' ?>">
                <?= htmlspecialchars($cat) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Careers Grid -->
    <div class="careers-grid">
        <?php foreach ($careers as $career): 
            $skillsMap = json_decode($career['required_skills'], true) ?: [];
        ?>
            <div class="glass-card glass-card-hover career-card">
                <div class="career-card-top">
                    <div class="career-icon-box">
                        <i class="<?= htmlspecialchars($career['icon']) ?>"></i>
                    </div>
                    <div class="career-meta">
                        <div class="career-category"><?= htmlspecialchars($career['category']) ?></div>
                        <h3 class="career-title"><?= htmlspecialchars($career['title']) ?></h3>
                    </div>
                </div>

                <p class="career-desc"><?= htmlspecialchars($career['description']) ?></p>

                <div class="skills-pill-wrap">
                    <?php foreach (array_keys($skillsMap) as $skillName): ?>
                        <span class="skill-pill"><?= htmlspecialchars($skillName) ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="career-details-list">
                    <div class="career-detail-row">
                        <span class="career-detail-label">Avg. Salary (India):</span>
                        <span class="career-detail-val"><?= htmlspecialchars($career['average_salary_inr']) ?></span>
                    </div>
                    <div class="career-detail-row">
                        <span class="career-detail-label">Salary Range:</span>
                        <span class="career-detail-val" style="font-size: 0.8rem;"><?= htmlspecialchars($career['salary_range']) ?></span>
                    </div>
                    <div class="career-detail-row">
                        <span class="career-detail-label">Growth Outlook:</span>
                        <span class="badge-growth"><i class="fas fa-arrow-trend-up"></i> <?= htmlspecialchars($career['growth_outlook']) ?></span>
                    </div>
                </div>

                <div style="margin-top: auto;">
                    <a href="career_detail.php?id=<?= $career['id'] ?>" class="btn btn-sm btn-outline" style="width: 100%;">
                        <i class="fas fa-eye"></i> View Learning Roadmap & Companies
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
