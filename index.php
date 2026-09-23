<?php
$pageTitle = "AI-Powered Smart Career Recommendation System";
require_once __DIR__ . '/header.php';

$db = getDBConnection();

// Fetch featured careers
$stmt = $db->query("SELECT * FROM career_paths WHERE is_active = 1 LIMIT 6");
$featured_careers = $stmt->fetchAll();

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? 'General Inquiry');
    $message = trim($_POST['message'] ?? '');
    $userId = $_SESSION['user_id'] ?? null;

    if (!empty($name) && !empty($email) && !empty($message)) {
        $ins = $db->prepare("INSERT INTO contact_messages (user_id, name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->execute([$userId, $name, $email, $phone, $subject, $message]);

        $_SESSION['flash_msg'] = "Thank you! Your message has been saved to the database. An academic counselor will review it shortly.";
        $_SESSION['flash_type'] = "success";
        header("Location: index.php#contact");
        exit;
    } else {
        $_SESSION['flash_msg'] = "Please fill in all required fields (Name, Email, Message).";
        $_SESSION['flash_type'] = "danger";
    }
}
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-badge">
            <i class="fas fa-sparkles" style="color: #fbbf24;"></i> Next-Gen AI Career Recommendation Engine
        </div>
        
        <h1 class="hero-title">
            Discover Your Ideal Career with <br>
            <span class="gradient-text">Explainable AI & Smart Analytics</span>
        </h1>
        
        <p class="hero-subtitle">
            Bridge the gap between your academic journey and high-impact industry roles. CareerCompass analyzes your skills, psychometric personality traits, and aspirations to chart personalized learning roadmaps.
        </p>

        <div class="hero-cta">
            <?php if ($currentUser && $currentUser['role'] === 'student'): ?>
                <a href="student_recommendations.php" class="btn btn-lg btn-primary">
                    <i class="fas fa-magic"></i> View My AI Recommendations
                </a>
                <a href="student_dashboard.php" class="btn btn-lg btn-secondary">
                    <i class="fas fa-th-large"></i> Student Dashboard
                </a>
            <?php elseif ($currentUser && $currentUser['role'] === 'admin'): ?>
                <a href="admin_dashboard.php" class="btn btn-lg btn-primary">
                    <i class="fas fa-shield-alt"></i> Access Admin Portal
                </a>
            <?php else: ?>
                <a href="register.php" class="btn btn-lg btn-primary">
                    <i class="fas fa-rocket"></i> Get Started Free
                </a>
                <a href="careers.php" class="btn btn-lg btn-secondary">
                    <i class="fas fa-compass"></i> Explore Career Tracks
                </a>
            <?php endif; ?>
        </div>

        <!-- Live Metrics Grid -->
        <div class="stats-grid">
            <div class="stat-item glass-card-hover">
                <div class="stat-value">20+</div>
                <div class="stat-label">Curated Career Tracks</div>
            </div>
            <div class="stat-item glass-card-hover">
                <div class="stat-value">45+</div>
                <div class="stat-label">Industry Skills Assessed</div>
            </div>
            <div class="stat-item glass-card-hover">
                <div class="stat-value">94.8%</div>
                <div class="stat-label">AI Matching Precision</div>
            </div>
            <div class="stat-item glass-card-hover">
                <div class="stat-value">100%</div>
                <div class="stat-label">Explainable AI Insights</div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="section" style="background: rgba(15, 23, 42, 0.4);">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Methodology</span>
            <h2 class="section-title">How CareerCompass Formulates Your Path</h2>
            <p class="section-desc">A multi-factor hybrid AI recommendation framework designed to evaluate talent holistically.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem;">
            <div class="glass-card glass-card-hover">
                <div class="brand-icon" style="background: var(--gradient-cyan); margin-bottom: 1.25rem;">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;">1. Academic & Profile Mapping</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">
                    We capture your degree level, branch of study, GPA, target industries, and dream career aspirations.
                </p>
            </div>

            <div class="glass-card glass-card-hover">
                <div class="brand-icon" style="background: var(--gradient-brand); margin-bottom: 1.25rem;">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;">2. Interactive Skill Rating & Quiz</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">
                    Self-rate your proficiencies and test problem-solving through timed 10-question technical benchmarks.
                </p>
            </div>

            <div class="glass-card glass-card-hover">
                <div class="brand-icon" style="background: var(--gradient-amber); margin-bottom: 1.25rem;">
                    <i class="fas fa-brain"></i>
                </div>
                <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;">3. Holland RIASEC Psychometrics</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">
                    Evaluate work-style preferences, analytical inclinations, creative drive, and leadership traits.
                </p>
            </div>

            <div class="glass-card glass-card-hover">
                <div class="brand-icon" style="background: var(--gradient-emerald); margin-bottom: 1.25rem;">
                    <i class="fas fa-route"></i>
                </div>
                <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;">4. AI Roadmap & Skill Gaps</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">
                    Receive match percentages, radar graphs, skill-gap mitigation priorities, and step-by-step learning roadmaps.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Career Tracks -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">In-Demand Domains</span>
            <h2 class="section-title">Explore Featured Career Paths</h2>
            <p class="section-desc">High-growth technology, data, and management domains aligned with current industry standards.</p>
        </div>

        <div class="careers-grid">
            <?php foreach ($featured_careers as $career): 
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
                            <span class="career-detail-label">Growth Outlook:</span>
                            <span class="badge-growth"><i class="fas fa-arrow-trend-up"></i> <?= htmlspecialchars($career['growth_outlook']) ?></span>
                        </div>
                    </div>

                    <div style="margin-top: auto;">
                        <a href="career_detail.php?id=<?= $career['id'] ?>" class="btn btn-sm btn-outline" style="width: 100%;">
                            <i class="fas fa-eye"></i> View Full Roadmap & Skills
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-top: 2.5rem;">
            <a href="careers.php" class="btn btn-secondary">
                <i class="fas fa-th-list"></i> View All Career Tracks
            </a>
        </div>
    </div>
</section>

<!-- Contact & Inquiry Form (Direct Database Storage) -->
<section class="section" id="contact" style="background: rgba(15, 23, 42, 0.6);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center;">
            <div>
                <span class="section-tag">Get in Touch</span>
                <h2 class="section-title">Have Questions About Your Career Roadmap?</h2>
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem; line-height: 1.7;">
                    Leave a message for our academic guidance counselors and platform administrators. All inquiries are saved directly to the database and reviewed by our counselor team.
                </p>

                <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem;">
                    <div class="glass-card" style="padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                        <div class="brand-icon" style="background: var(--gradient-brand);"><i class="fas fa-database"></i></div>
                        <div>
                            <strong style="display: block; font-size: 0.95rem;">Database Integrated</strong>
                            <span style="font-size: 0.85rem; color: var(--text-secondary);">Messages logged directly to Admin Inquiries Desk</span>
                        </div>
                    </div>
                    <div class="glass-card" style="padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                        <div class="brand-icon" style="background: var(--gradient-cyan);"><i class="fas fa-user-shield"></i></div>
                        <div>
                            <strong style="display: block; font-size: 0.95rem;">Counselor Review</strong>
                            <span style="font-size: 0.85rem; color: var(--text-secondary);">Tailored guidance and career clarity</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form Card -->
            <div class="glass-card" style="border-color: var(--border-glass-bright);">
                <h3 style="font-size: 1.4rem; margin-bottom: 1.25rem;">
                    <i class="fas fa-paper-plane" style="color: var(--accent-cyan); margin-right: 0.5rem;"></i> Send an Inquiry Message
                </h3>
                
                <form action="index.php#contact" method="POST">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="contact_name">Full Name *</label>
                            <input type="text" id="contact_name" name="name" class="form-control" 
                                   value="<?= htmlspecialchars($currentUser['name'] ?? '') ?>" placeholder="e.g. Rahul Sharma" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="contact_email">Email Address *</label>
                            <input type="email" id="contact_email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" placeholder="e.g. rahul@example.com" required>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="contact_phone">Phone Number</label>
                            <input type="text" id="contact_phone" name="phone" class="form-control" 
                                   value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>" placeholder="+91 98765 43210">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="contact_subject">Subject</label>
                            <input type="text" id="contact_subject" name="subject" class="form-control" 
                                   placeholder="e.g. Data Science Roadmap Guidance">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contact_message">Your Message / Query *</label>
                        <textarea id="contact_message" name="message" class="form-control" 
                                  placeholder="Describe your question, transition goal, or inquiry in detail..." required></textarea>
                    </div>

                    <button type="submit" name="submit_contact" class="btn btn-primary btn-lg" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Submit Inquiry to Database
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
