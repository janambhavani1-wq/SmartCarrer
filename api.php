<?php
/**
 * CareerCompass – REST API Endpoints (PHP)
 * Provides chart data, student inspection modal dossiers, and stats.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? '';
$db = getDBConnection();

if ($action === 'student_details') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $studentId = intval($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT u.id, u.name, u.email, u.phone, p.* FROM users u LEFT JOIN student_profiles p ON u.id = p.user_id WHERE u.id = ?");
    $stmt->execute([$studentId]);
    $profile = $stmt->fetch();

    $stmt = $db->prepare("SELECT s.name, ss.proficiency_level FROM skills s JOIN student_skills ss ON s.id = ss.skill_id WHERE ss.user_id = ?");
    $stmt->execute([$studentId]);
    $skills = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT * FROM recommendation_records WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$studentId]);
    $rec = $stmt->fetch();

    echo json_encode([
        'profile' => $profile,
        'skills' => $skills,
        'recommendation' => $rec
    ]);
    exit;
}

if ($action === 'skill_radar') {
    $userId = $_SESSION['user_id'] ?? 0;
    $stmt = $db->prepare("SELECT s.name, ss.proficiency_level FROM skills s JOIN student_skills ss ON s.id = ss.skill_id WHERE ss.user_id = ? LIMIT 8");
    $stmt->execute([$userId]);
    $skills = $stmt->fetchAll();

    $labels = [];
    $data = [];
    foreach ($skills as $s) {
        $labels[] = $s['name'];
        $data[] = $s['proficiency_level'] * 20;
    }

    if (empty($labels)) {
        $labels = ['Python', 'SQL', 'Algorithms', 'Web Dev', 'Problem Solving', 'Data Analysis'];
        $data = [80, 75, 85, 70, 90, 65];
    }

    echo json_encode(['labels' => $labels, 'data' => $data]);
    exit;
}

if ($action === 'admin_analytics') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $stmt = $db->query("SELECT top_career_title, COUNT(*) as count FROM recommendation_records GROUP BY top_career_title ORDER BY count DESC LIMIT 5");
    $topRecs = $stmt->fetchAll();

    $stmt = $db->query("SELECT category, COUNT(*) as count FROM career_paths WHERE is_active = 1 GROUP BY category");
    $catCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo json_encode([
        'top_recommended' => $topRecs,
        'career_categories' => $catCounts
    ]);
    exit;
}

echo json_encode(['status' => 'online', 'app' => 'CareerCompass API']);
exit;
