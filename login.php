<?php
$pageTitle = "Sign In";
require_once __DIR__ . '/header.php';

if ($currentUser) {
    if ($currentUser['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: student_dashboard.php");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([strtolower($email)]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        $_SESSION['flash_msg'] = "Welcome back, " . $user['name'] . "!";
        $_SESSION['flash_type'] = "success";

        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: student_dashboard.php");
        }
        exit;
    } else {
        $_SESSION['flash_msg'] = "Invalid email or password. Please check your credentials.";
        $_SESSION['flash_type'] = "danger";
    }
}
?>

<div class="container" style="padding: 4rem 0; display: flex; justify-content: center;">
    <div class="glass-card" style="width: 100%; max-width: 480px; border-color: var(--border-glass-bright);">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div class="brand-icon" style="margin: 0 auto 1rem; width: 3.5rem; height: 3.5rem; font-size: 1.5rem;">
                <i class="fas fa-lock"></i>
            </div>
            <h1 style="font-size: 1.75rem; margin-bottom: 0.5rem;">Welcome to CareerCompass</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Sign in to access your student guidance or administrative portal.</p>
        </div>

        <!-- Quick Demo Credentials Fill -->
        <div class="glass-card" style="background: rgba(99, 102, 241, 0.08); border-color: rgba(99, 102, 241, 0.25); padding: 1rem; margin-bottom: 1.5rem;">
            <div style="font-size: 0.8rem; font-weight: 700; color: #c7d2fe; text-transform: uppercase; margin-bottom: 0.5rem;">
                <i class="fas fa-key"></i> Quick Demo Logins (College Evaluation)
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" class="btn btn-sm btn-outline" style="flex: 1; font-size: 0.75rem;" onclick="fillDemo('student@careercompass.com', 'Student@12345')">
                    <i class="fas fa-user-graduate"></i> Demo Student
                </button>
                <button type="button" class="btn btn-sm btn-outline" style="flex: 1; font-size: 0.75rem; border-color: rgba(244, 63, 94, 0.4); color: #fda4af;" onclick="fillDemo('admin@careercompass.com', 'Admin@12345')">
                    <i class="fas fa-shield-alt"></i> Demo Admin
                </button>
            </div>
        </div>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 1rem;">
                <i class="fas fa-sign-in-alt"></i> Sign In to Portal
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-glass); font-size: 0.9rem; color: var(--text-secondary);">
            New student? <a href="register.php" style="font-weight: 600; color: var(--accent-cyan);">Create an Account</a>
        </div>
    </div>
</div>

<script>
function fillDemo(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
