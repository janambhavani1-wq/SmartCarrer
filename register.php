<?php
$pageTitle = "Register Student Account";
require_once __DIR__ . '/header.php';

if ($currentUser) {
    header("Location: student_dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($name) && !empty($email) && !empty($password)) {
        if ($password !== $confirm_password) {
            $_SESSION['flash_msg'] = "Passwords do not match. Please re-enter.";
            $_SESSION['flash_type'] = "danger";
        } elseif (strlen($password) < 6) {
            $_SESSION['flash_msg'] = "Password must be at least 6 characters long.";
            $_SESSION['flash_type'] = "warning";
        } else {
            $db = getDBConnection();
            $check = $db->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $_SESSION['flash_msg'] = "An account with this email already exists. Please log in.";
                $_SESSION['flash_type'] = "danger";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $db->prepare("INSERT INTO users (name, email, password_hash, role, phone) VALUES (?, ?, ?, 'student', ?)");
                $ins->execute([$name, $email, $hash, $phone]);
                $userId = $db->lastInsertId();

                $db->prepare("INSERT INTO student_profiles (user_id, education_level, field_of_study) VALUES (?, 'B.Tech / B.E', 'Computer Science')")->execute([$userId]);

                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['role'] = 'student';
                $_SESSION['email'] = $email;

                $_SESSION['flash_msg'] = "Account registered successfully! Please complete your academic details.";
                $_SESSION['flash_type'] = "success";
                header("Location: student_profile.php");
                exit;
            }
        }
    } else {
        $_SESSION['flash_msg'] = "All required fields marked with * must be filled.";
        $_SESSION['flash_type'] = "danger";
    }
}
?>

<div class="container" style="padding: 4rem 0; display: flex; justify-content: center;">
    <div class="glass-card" style="width: 100%; max-width: 520px; border-color: var(--border-glass-bright);">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div class="brand-icon" style="margin: 0 auto 1rem; width: 3.5rem; height: 3.5rem; font-size: 1.5rem; background: var(--gradient-brand);">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1 style="font-size: 1.75rem; margin-bottom: 0.5rem;">Join CareerCompass</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Register to take AI skill assessments and unlock personalized career roadmaps.</p>
        </div>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="reg_name">Full Name *</label>
                <input type="text" id="reg_name" name="name" class="form-control" placeholder="e.g. Priya Sharma" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="reg_email">Email Address *</label>
                <input type="email" id="reg_email" name="email" class="form-control" placeholder="student@example.com" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="reg_phone">Phone Number</label>
                <input type="tel" id="reg_phone" name="phone" class="form-control" placeholder="+91 98765 43210">
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="reg_pass">Password *</label>
                    <input type="password" id="reg_pass" name="password" class="form-control" placeholder="Min 6 characters" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="reg_confirm">Confirm Password *</label>
                    <input type="password" id="reg_confirm" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 1rem;">
                <i class="fas fa-arrow-right"></i> Register & Setup Profile
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-glass); font-size: 0.9rem; color: var(--text-secondary);">
            Already have an account? <a href="login.php" style="font-weight: 600; color: var(--accent-cyan);">Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
