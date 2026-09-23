<?php
require_once __DIR__ . '/db.php';

$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT id, name, email, role, phone FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CareerCompass – AI-Powered Smart Career Recommendation System for Students">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' – ' : '' ?>CareerCompass</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>

    <!-- Flash Notification Alerts -->
    <?php if (isset($_SESSION['flash_msg'])): ?>
        <div class="flash-messages no-print">
            <div class="flash-alert flash-<?= htmlspecialchars($_SESSION['flash_type'] ?? 'info') ?>">
                <i class="fas fa-info-circle"></i>
                <span><?= htmlspecialchars($_SESSION['flash_msg']) ?></span>
                <button type="button" class="flash-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        </div>
        <?php 
            unset($_SESSION['flash_msg']);
            unset($_SESSION['flash_type']);
        ?>
    <?php endif; ?>

    <!-- Navigation Bar -->
    <nav class="navbar no-print">
        <div class="container navbar-inner">
            <a href="index.php" class="brand-logo">
                <div class="brand-icon">
                    <i class="fas fa-compass"></i>
                </div>
                <div class="brand-text">Career<span>Compass</span></div>
            </a>

            <ul class="nav-links" id="navLinks">
                <li><a href="index.php" class="nav-link <?= ($currentPage == 'index.php') ? 'active' : '' ?>">Home</a></li>
                <li><a href="careers.php" class="nav-link <?= ($currentPage == 'careers.php' || $currentPage == 'career_detail.php') ? 'active' : '' ?>">Career Tracks</a></li>
                
                <?php if ($currentUser && $currentUser['role'] === 'student'): ?>
                    <li><a href="student_dashboard.php" class="nav-link <?= (strpos($currentPage, 'student_') === 0 && $currentPage != 'student_recommendations.php') ? 'active' : '' ?>">Student Portal</a></li>
                    <li><a href="student_recommendations.php" class="nav-link <?= ($currentPage == 'student_recommendations.php') ? 'active' : '' ?>">AI Recommendations</a></li>
                <?php elseif ($currentUser && $currentUser['role'] === 'admin'): ?>
                    <li><a href="admin_dashboard.php" class="nav-link <?= ($currentPage == 'admin_dashboard.php') ? 'active' : '' ?>">Admin Control</a></li>
                    <li><a href="admin_students.php" class="nav-link <?= ($currentPage == 'admin_students.php') ? 'active' : '' ?>">Students</a></li>
                    <li><a href="admin_careers.php" class="nav-link <?= ($currentPage == 'admin_careers.php') ? 'active' : '' ?>">Careers</a></li>
                    <li><a href="admin_messages.php" class="nav-link <?= ($currentPage == 'admin_messages.php') ? 'active' : '' ?>">Inquiries</a></li>
                <?php endif; ?>
                
                <li><a href="index.php#contact" class="nav-link">Contact Counselor</a></li>
            </ul>

            <div class="nav-actions">
                <?php if ($currentUser): ?>
                    <div class="user-badge">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($currentUser['name']) ?></span>
                        <span class="role-tag <?= ($currentUser['role'] === 'admin') ? 'admin' : '' ?>">
                            <?= htmlspecialchars($currentUser['role']) ?>
                        </span>
                    </div>
                    <a href="logout.php" class="btn btn-sm btn-secondary" title="Logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-sm btn-outline">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="register.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-user-plus"></i> Get Started
                    </a>
                <?php endif; ?>

                <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle Navigation">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main style="flex: 1;">
