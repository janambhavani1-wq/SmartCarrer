<?php
/**
 * CareerCompass – AI Recommendation Engine (PHP Implementation)
 * Multi-Factor Weighted Vector Cosine Matching, Holland RIASEC Psychometrics,
 * and Explainable AI Reasoning.
 */

require_once __DIR__ . '/db.php';

function computeCareerRecommendationsPHP($userId) {
    $db = getDBConnection();

    // 1. Fetch Student Profile
    $stmt = $db->prepare("SELECT u.name, u.email, p.* FROM users u LEFT JOIN student_profiles p ON u.id = p.user_id WHERE u.id = ?");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();

    // 2. Fetch Rated Skills
    $stmt = $db->prepare("SELECT s.name, ss.proficiency_level FROM skills s JOIN student_skills ss ON s.id = ss.skill_id WHERE ss.user_id = ?");
    $stmt->execute([$userId]);
    $studentSkillsRows = $stmt->fetchAll();

    $studentSkillVec = [];
    foreach ($studentSkillsRows as $row) {
        $studentSkillVec[$row['name']] = $row['proficiency_level'] / 5.0; // 0.2 to 1.0
    }

    // 3. Fetch Assessment History
    $stmt = $db->prepare("SELECT * FROM student_assessment_responses WHERE user_id = ? ORDER BY completed_at DESC");
    $stmt->execute([$userId]);
    $assessments = $stmt->fetchAll();

    $skillQuizPct = 70.0;
    $psychoTraits = [
        'Investigative' => 0.5, 'Realistic' => 0.5, 'Artistic' => 0.5,
        'Enterprising' => 0.5, 'Conventional' => 0.5, 'Leadership' => 0.5,
        'Technical' => 0.5, 'Analytical' => 0.5, 'Design' => 0.5,
        'AI_ML' => 0.5, 'DevOps' => 0.5, 'Security' => 0.5,
        'FullStack' => 0.5, 'Product' => 0.5
    ];

    foreach ($assessments as $a) {
        if ($a['assessment_type'] === 'skill_assessment' && $a['total_questions'] > 0) {
            $skillQuizPct = ($a['correct_answers'] / $a['total_questions']) * 100.0;
        } elseif ($a['assessment_type'] === 'career_assessment') {
            $data = json_decode($a['response_data'], true);
            if (is_array($data)) {
                foreach ($data as $trait => $val) {
                    if (isset($psychoTraits[$trait])) {
                        $psychoTraits[$trait] = min(1.0, max(0.1, $val / 10.0));
                    }
                }
            }
        }
    }

    // 4. Fetch Active Careers
    $stmt = $db->query("SELECT * FROM career_paths WHERE is_active = 1");
    $careers = $stmt->fetchAll();

    if (empty($careers)) {
        return null;
    }

    $dreamRole = strtolower($profile['dream_role'] ?? '');
    $fieldOfStudy = strtolower($profile['field_of_study'] ?? '');

    $fieldAffinities = [
        'computer' => ['Artificial Intelligence', 'Software Engineering', 'Data & Analytics', 'Cloud & Infrastructure', 'Security & Networks'],
        'information' => ['Software Engineering', 'Cloud & Infrastructure', 'Security & Networks', 'Data & Analytics'],
        'data' => ['Artificial Intelligence', 'Data & Analytics', 'Software Engineering'],
        'electronics' => ['Cloud & Infrastructure', 'Artificial Intelligence', 'Security & Networks'],
        'design' => ['Product & Design', 'Software Engineering'],
        'business' => ['Management & Strategy', 'Data & Analytics', 'Product & Design']
    ];

    $scoredCareers = [];

    foreach ($careers as $career) {
        $reqSkills = json_decode($career['required_skills'], true) ?: [];
        $matchedSkills = [];
        $missingSkills = [];
        $skillPoints = 0.0;
        $totalReqPoints = array_sum($reqSkills) ?: 1.0;

        foreach ($reqSkills as $reqName => $reqWeight) {
            $prof = $studentSkillVec[$reqName] ?? 0.0;
            if ($prof == 0.0) {
                // Partial match
                foreach ($studentSkillVec as $sName => $sProf) {
                    if (stripos($reqName, $sName) !== false || stripos($sName, $reqName) !== false) {
                        $prof = $sProf;
                        break;
                    }
                }
            }

            if ($prof >= 0.6) {
                $matchedSkills[] = [
                    'skill' => $reqName,
                    'student_level' => intval($prof * 5),
                    'required_weight' => $reqWeight,
                    'status' => ($prof >= 0.8) ? 'Strong' : 'Moderate'
                ];
                $skillPoints += $prof * $reqWeight;
            } elseif ($prof > 0) {
                $missingSkills[] = [
                    'skill' => $reqName,
                    'current_level' => intval($prof * 5),
                    'target_level' => 4,
                    'priority' => 'Medium'
                ];
                $skillPoints += $prof * $reqWeight * 0.5;
            } else {
                $missingSkills[] = [
                    'skill' => $reqName,
                    'current_level' => 0,
                    'target_level' => 4,
                    'priority' => ($reqWeight >= 0.85) ? 'High' : 'Medium'
                ];
            }
        }

        $rawSkillScore = ($totalReqPoints > 0) ? ($skillPoints / $totalReqPoints) : 0.5;
        $skillScore = ($rawSkillScore * 0.75) + (($skillQuizPct / 100.0) * 0.25);

        // Psychometric scoring
        $title = strtolower($career['title']);
        $cat = $career['category'];
        $psychoScore = 0.5;

        if (strpos($title, 'ai') !== false || strpos($title, 'machine learning') !== false) {
            $psychoScore = ($psychoTraits['Investigative'] + $psychoTraits['AI_ML'] + $psychoTraits['Analytical']) / 3.0;
        } elseif (strpos($title, 'web') !== false || strpos($title, 'full stack') !== false) {
            $psychoScore = ($psychoTraits['Realistic'] + $psychoTraits['FullStack'] + $psychoTraits['Technical']) / 3.0;
        } elseif (strpos($title, 'data') !== false) {
            $psychoScore = ($psychoTraits['Investigative'] + $psychoTraits['Analytical'] + $psychoTraits['Conventional']) / 3.0;
        } elseif (strpos($title, 'cloud') !== false || strpos($title, 'devops') !== false) {
            $psychoScore = ($psychoTraits['Realistic'] + $psychoTraits['DevOps'] + $psychoTraits['Technical']) / 3.0;
        } elseif (strpos($title, 'security') !== false || strpos($title, 'cyber') !== false) {
            $psychoScore = ($psychoTraits['Conventional'] + $psychoTraits['Security'] + $psychoTraits['Investigative']) / 3.0;
        } elseif (strpos($title, 'design') !== false || strpos($title, 'ui/ux') !== false) {
            $psychoScore = ($psychoTraits['Artistic'] + $psychoTraits['Design']) / 2.0;
        } else {
            $psychoScore = ($psychoTraits['Technical'] + $psychoTraits['Investigative']) / 2.0;
        }

        // Aspiration score
        $aspirationScore = 0.5;
        if (!empty($dreamRole)) {
            if (strpos($title, $dreamRole) !== false || strpos($dreamRole, $title) !== false) {
                $aspirationScore = 0.98;
            } elseif (stripos($title, 'engineer') !== false && stripos($dreamRole, 'engineer') !== false) {
                $aspirationScore = 0.80;
            }
        }

        // Academic score
        $academicScore = 0.6;
        foreach ($fieldAffinities as $keyField => $affinCategories) {
            if (strpos($fieldOfStudy, $keyField) !== false && in_array($cat, $affinCategories)) {
                $academicScore = 0.90;
                break;
            }
        }

        // Composite calculation
        $composite = ($skillScore * 0.50) + ($psychoScore * 0.25) + ($aspirationScore * 0.15) + ($academicScore * 0.10);
        $matchPct = round(min(98.5, max(52.0, $composite * 100)), 1);

        // Generate XAI Explanations
        $matchedNames = array_slice(array_column($matchedSkills, 'skill'), 0, 4);
        $missingNames = array_slice(array_column($missingSkills, 'skill'), 0, 3);
        $reasoning = [];

        if ($matchPct >= 85) {
            $reasoning[] = "<strong>Exceptional Career Fit ({$matchPct}%)</strong>: Your technical aptitude, academic background, and problem-solving inclinations strongly match the industry profile of a <strong>{$career['title']}</strong>.";
        } else {
            $reasoning[] = "<strong>Promising Career Match ({$matchPct}%)</strong>: You exhibit key foundational capabilities required for <strong>{$career['title']}</strong>, with direct growth pathways.";
        }

        if (!empty($matchedNames)) {
            $reasoning[] = "<strong>Identified Strengths</strong>: You demonstrated strong command in <strong>" . implode(', ', $matchedNames) . "</strong>, which are core prerequisites for top-tier hiring.";
        }

        if (!empty($missingNames)) {
            $reasoning[] = "<strong>Priority Upskilling Targets</strong>: Closing skill gaps in <strong>" . implode(', ', $missingNames) . "</strong> will increase your job readiness and placement competitiveness.";
        }

        $scoredCareers[] = [
            'career_id' => $career['id'],
            'title' => $career['title'],
            'category' => $career['category'],
            'description' => $career['description'],
            'icon' => $career['icon'],
            'average_salary_inr' => $career['average_salary_inr'],
            'salary_range' => $career['salary_range'],
            'growth_outlook' => $career['growth_outlook'],
            'education_requirement' => $career['education_requirement'],
            'top_companies' => json_decode($career['top_companies'], true) ?: [],
            'roadmap' => json_decode($career['roadmap'], true) ?: [],
            'match_percentage' => $matchPct,
            'matched_skills' => $matchedSkills,
            'missing_skills' => $missingSkills,
            'reasoning' => $reasoning
        ];
    }

    // Sort descending by match percentage
    usort($scoredCareers, function($a, $b) {
        return $b['match_percentage'] <=> $a['match_percentage'];
    });

    $topCareer = $scoredCareers[0];
    $secondaryCareers = array_slice($scoredCareers, 1, 4);

    // Save recommendation to database
    $ins = $db->prepare("INSERT INTO recommendation_records (
        user_id, student_name, top_career_id, top_career_title, match_percentage,
        secondary_careers, reasoning, matched_skills, missing_skills,
        salary_forecast, learning_roadmap
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $ins->execute([
        $userId,
        $profile['name'] ?? 'Student',
        $topCareer['career_id'],
        $topCareer['title'],
        $topCareer['match_percentage'],
        json_encode($secondaryCareers),
        json_encode($topCareer['reasoning']),
        json_encode($topCareer['matched_skills']),
        json_encode($topCareer['missing_skills']),
        $topCareer['average_salary_inr'],
        json_encode($topCareer['roadmap'])
    ]);

    return [
        'top_career' => $topCareer,
        'secondary_careers' => $secondaryCareers
    ];
}
