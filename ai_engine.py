"""
CareerCompass – AI Recommendation Engine
Implements Multi-Factor Weighted Matching, Vector Similarity, Skill Gap Analysis,
Holland RIASEC Psychometric Alignment, and Explainable AI (XAI) Reasoning.
"""

import math
import json
from models import get_all_careers, get_student_skills, get_student_profile, get_student_assessment_history

# Weights for recommendation dimensions
WEIGHT_SKILLS = 0.50
WEIGHT_PSYCHOMETRIC = 0.25
WEIGHT_ASPIRATION = 0.15
WEIGHT_ACADEMIC = 0.10

# Academic Field to Career Category affinity mappings
FIELD_AFFINITIES = {
    'computer science': ['Artificial Intelligence', 'Software Engineering', 'Data & Analytics', 'Cloud & Infrastructure', 'Security & Networks'],
    'information technology': ['Software Engineering', 'Cloud & Infrastructure', 'Security & Networks', 'Data & Analytics'],
    'data science': ['Artificial Intelligence', 'Data & Analytics', 'Software Engineering'],
    'electronics': ['Cloud & Infrastructure', 'Artificial Intelligence', 'Security & Networks'],
    'design': ['Product & Design', 'Software Engineering'],
    'commerce': ['Management & Strategy', 'Data & Analytics'],
    'business': ['Management & Strategy', 'Data & Analytics', 'Product & Design'],
    'management': ['Management & Strategy', 'Product & Design']
}

def calculate_cosine_similarity(vec_a, vec_b):
    """Calculate cosine similarity between two dictionaries/vectors."""
    all_keys = set(vec_a.keys()).union(set(vec_b.keys()))
    if not all_keys:
        return 0.0

    dot_product = sum(vec_a.get(k, 0.0) * vec_b.get(k, 0.0) for k in all_keys)
    norm_a = math.sqrt(sum(val ** 2 for val in vec_a.values()))
    norm_b = math.sqrt(sum(val ** 2 for val in vec_b.values()))

    if norm_a == 0.0 or norm_b == 0.0:
        return 0.0

    return dot_product / (norm_a * norm_b)

def compute_career_recommendations(user_id):
    """
    Main recommendation algorithm:
    1. Fetches student profile, rated skills, and assessment scores.
    2. Compares against all active career paths using multi-factor AI scoring.
    3. Produces sorted recommendations with match %, skill gaps, and explanations.
    """
    profile = get_student_profile(user_id)
    student_skills = get_student_skills(user_id)
    assessment_history = get_student_assessment_history(user_id)
    careers = get_all_careers(active_only=True)

    if not careers:
        return None

    # 1. Build Student Skill Vector (normalized to 0.0 - 1.0)
    student_skill_vec = {}
    for s in student_skills:
        # proficiency_level is 1 to 5; normalize to 0.2 to 1.0
        student_skill_vec[s['name']] = s['proficiency_level'] / 5.0

    # 2. Extract Psychometric & Aptitude Scores
    psycho_traits = {
        'Investigative': 0.5,
        'Realistic': 0.5,
        'Artistic': 0.5,
        'Enterprising': 0.5,
        'Conventional': 0.5,
        'Leadership': 0.5,
        'Technical': 0.5,
        'Analytical': 0.5,
        'Design': 0.5,
        'AI_ML': 0.5,
        'DevOps': 0.5,
        'Security': 0.5,
        'FullStack': 0.5,
        'Product': 0.5
    }
    skill_quiz_score_pct = 70.0 # default fallback baseline

    for response in assessment_history:
        if response['assessment_type'] == 'skill_assessment':
            if response['total_questions'] > 0:
                skill_quiz_score_pct = (response['correct_answers'] / response['total_questions']) * 100.0
        elif response['assessment_type'] == 'career_assessment':
            try:
                data = json.loads(response['response_data']) if isinstance(response['response_data'], str) else response['response_data']
                if isinstance(data, dict):
                    for trait, val in data.items():
                        if trait in psycho_traits:
                            psycho_traits[trait] = min(1.0, max(0.1, val / 10.0))
            except Exception:
                pass

    dream_role = (profile.get('dream_role') or '').lower() if profile else ''
    field_of_study = (profile.get('field_of_study') or '').lower() if profile else ''

    scored_careers = []

    for career in careers:
        req_skills = career.get('required_skills_parsed', {})
        
        # A. Skills Match Score (Weighted Cosine + Coverage)
        matched_skills_list = []
        missing_skills_list = []

        skill_points = 0.0
        total_req_points = sum(req_skills.values()) if req_skills else 1.0

        for req_name, req_weight in req_skills.items():
            # Check direct or fuzzy match
            prof = student_skill_vec.get(req_name, 0.0)
            if prof == 0.0:
                # check partial match
                for s_name, s_prof in student_skill_vec.items():
                    if req_name.lower() in s_name.lower() or s_name.lower() in req_name.lower():
                        prof = s_prof
                        break

            if prof >= 0.6: # Proficient (level 3, 4, or 5)
                matched_skills_list.append({
                    'skill': req_name,
                    'student_level': int(prof * 5),
                    'required_weight': req_weight,
                    'status': 'Strong' if prof >= 0.8 else 'Moderate'
                })
                skill_points += prof * req_weight
            elif prof > 0:
                missing_skills_list.append({
                    'skill': req_name,
                    'current_level': int(prof * 5),
                    'target_level': 4,
                    'priority': 'Medium'
                })
                skill_points += prof * req_weight * 0.5
            else:
                missing_skills_list.append({
                    'skill': req_name,
                    'current_level': 0,
                    'target_level': 4,
                    'priority': 'High' if req_weight >= 0.85 else 'Medium'
                })

        raw_skill_score = (skill_points / total_req_points) if total_req_points > 0 else 0.5
        # Blend with quiz performance
        skill_score = (raw_skill_score * 0.75) + ((skill_quiz_score_pct / 100.0) * 0.25)

        # B. Psychometric Match Score
        category = career['category']
        title = career['title'].lower()

        psycho_score = 0.5
        if 'artificial intelligence' in title or 'ai' in title or 'machine learning' in title:
            psycho_score = (psycho_traits['Investigative'] + psycho_traits['AI_ML'] + psycho_traits['Analytical']) / 3.0
        elif 'web' in title or 'full stack' in title or 'developer' in title:
            psycho_score = (psycho_traits['Realistic'] + psycho_traits['FullStack'] + psycho_traits['Technical']) / 3.0
        elif 'data' in title or 'analyst' in title:
            psycho_score = (psycho_traits['Investigative'] + psycho_traits['Analytical'] + psycho_traits['Conventional']) / 3.0
        elif 'cloud' in title or 'devops' in title:
            psycho_score = (psycho_traits['Realistic'] + psycho_traits['DevOps'] + psycho_traits['Technical']) / 3.0
        elif 'security' in title or 'cyber' in title:
            psycho_score = (psycho_traits['Conventional'] + psycho_traits['Security'] + psycho_traits['Investigative']) / 3.0
        elif 'design' in title or 'ui/ux' in title:
            psycho_score = (psycho_traits['Artistic'] + psycho_traits['Design']) / 2.0
        elif 'product' in title or 'manager' in title:
            psycho_score = (psycho_traits['Enterprising'] + psycho_traits['Leadership'] + psycho_traits['Product']) / 3.0
        else:
            psycho_score = (psycho_traits['Technical'] + psycho_traits['Investigative']) / 2.0

        # C. Aspiration Match Score
        aspiration_score = 0.5
        if dream_role:
            if dream_role in title or title in dream_role:
                aspiration_score = 0.98
            elif any(w in title for w in dream_role.split()):
                aspiration_score = 0.80

        # D. Academic Field Affinity
        academic_score = 0.6
        for key_field, affin_categories in FIELD_AFFINITIES.items():
            if key_field in field_of_study:
                if category in affin_categories:
                    academic_score = 0.90
                    break

        # Final Weighted AI Score
        composite_score = (
            (skill_score * WEIGHT_SKILLS) +
            (psycho_score * WEIGHT_PSYCHOMETRIC) +
            (aspiration_score * WEIGHT_ASPIRATION) +
            (academic_score * WEIGHT_ACADEMIC)
        )

        # Scale to 55% - 98% realistic career suitability spectrum
        match_percentage = round(min(98.5, max(52.0, composite_score * 100)), 1)

        # Generate Explainable AI (XAI) Insight
        reasoning = generate_xai_explanation(
            career_title=career['title'],
            category=career['category'],
            match_pct=match_percentage,
            matched_skills=matched_skills_list,
            missing_skills=missing_skills_list,
            profile=profile,
            psycho_score=psycho_score
        )

        scored_careers.append({
            'career_id': career['id'],
            'title': career['title'],
            'category': career['category'],
            'description': career['description'],
            'icon': career['icon'],
            'average_salary_inr': career['average_salary_inr'],
            'salary_range': career['salary_range'],
            'growth_outlook': career['growth_outlook'],
            'education_requirement': career['education_requirement'],
            'top_companies': career.get('top_companies_parsed', []),
            'roadmap': career.get('roadmap_parsed', []),
            'match_percentage': match_percentage,
            'skill_match_score': round(skill_score * 100, 1),
            'psycho_match_score': round(psycho_score * 100, 1),
            'matched_skills': matched_skills_list,
            'missing_skills': missing_skills_list,
            'reasoning': reasoning
        })

    # Sort descending by match percentage
    scored_careers.sort(key=lambda x: x['match_percentage'], reverse=True)

    top_career = scored_careers[0]
    secondary_careers = scored_careers[1:5]

    return {
        'top_career': top_career,
        'secondary_careers': secondary_careers,
        'all_evaluated_count': len(scored_careers),
        'generated_for': profile.get('name') if profile else 'Student'
    }

def generate_xai_explanation(career_title, category, match_pct, matched_skills, missing_skills, profile, psycho_score):
    """Generate explainable AI rationale explaining WHY this recommendation was formulated."""
    matched_names = [m['skill'] for m in matched_skills[:4]]
    missing_names = [m['skill'] for m in missing_skills[:3]]

    points = []
    
    # 1. Primary Alignment
    if match_pct >= 85:
        points.append(f"**Exceptional Career Fit ({match_pct}%)**: Your technical aptitude, academic background, and problem-solving inclinations strongly match the industry profile of a **{career_title}**.")
    else:
        points.append(f"**Promising Career Match ({match_pct}%)**: You exhibit key foundational capabilities required for **{career_title}**, with direct growth pathways.")

    # 2. Skill Strength Evidence
    if matched_names:
        points.append(f"**Identified Strengths**: You demonstrated strong command in **{', '.join(matched_names)}**, which are core prerequisites for top-tier hiring.")
    else:
        points.append(f"**Foundational Readiness**: Your academic discipline provides the core baseline needed to accelerate into this domain.")

    # 3. Psychometric & Mindset Fit
    if psycho_score >= 0.7:
        points.append(f"**Psychometric Trait Synergy**: Your assessment reveals strong investigative curiosity and structured analytical thinking, aligning with successful practitioners in {category}.")
    
    # 4. Actionable Skill Gap Guidance
    if missing_names:
        points.append(f"**Priority Upskilling Targets**: Closing skill gaps in **{', '.join(missing_names)}** will increase your job readiness and placement competitiveness.")

    return points
