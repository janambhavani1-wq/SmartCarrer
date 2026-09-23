"""
CareerCompass – Models & Data Access Layer
Encapsulates CRUD operations, database queries, and statistics calculation.
"""

import json
from werkzeug.security import generate_password_hash, check_password_hash
from database import get_db

# ----------------- USER & AUTHENTICATION -----------------

def create_user(name, email, password, role='student', phone=None):
    """Create a new user (student or admin). Returns user_id or None if duplicate email."""
    conn = get_db()
    cursor = conn.cursor()
    try:
        pw_hash = generate_password_hash(password)
        cursor.execute("""
        INSERT INTO users (name, email, password_hash, role, phone)
        VALUES (?, ?, ?, ?, ?)
        """, (name, email.lower().strip(), pw_hash, role, phone))
        user_id = cursor.lastrowid

        if role == 'student':
            # Initialize empty student profile
            cursor.execute("""
            INSERT INTO student_profiles (user_id, education_level, field_of_study)
            VALUES (?, 'B.Tech / B.E', 'Computer Science & Engineering')
            """, (user_id,))

        conn.commit()
        return user_id
    except Exception as e:
        conn.rollback()
        return None
    finally:
        conn.close()

def authenticate_user(email, password):
    """Authenticate user with email and password. Returns user dict or None."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM users WHERE email = ?", (email.lower().strip(),))
    user = cursor.fetchone()
    conn.close()

    if user and check_password_hash(user['password_hash'], password):
        return dict(user)
    return None

def get_user_by_id(user_id):
    """Fetch user dict by user ID."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("SELECT id, name, email, role, phone, avatar, created_at FROM users WHERE id = ?", (user_id,))
    user = cursor.fetchone()
    conn.close()
    return dict(user) if user else None

# ----------------- STUDENT PROFILES -----------------

def get_student_profile(user_id):
    """Retrieve full student profile combined with basic user info."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("""
    SELECT u.id, u.name, u.email, u.phone, u.avatar, u.created_at,
           p.education_level, p.field_of_study, p.institution, p.cgpa_percentage,
           p.graduation_year, p.dream_role, p.preferred_industry, p.preferred_work_env,
           p.location_pref, p.experience_level, p.bio, p.updated_at
    FROM users u
    LEFT JOIN student_profiles p ON u.id = p.user_id
    WHERE u.id = ?
    """, (user_id,))
    row = cursor.fetchone()
    conn.close()
    return dict(row) if row else None

def update_student_profile(user_id, profile_data):
    """Update student personal and academic details."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("""
    INSERT INTO student_profiles (
        user_id, education_level, field_of_study, institution, cgpa_percentage,
        graduation_year, dream_role, preferred_industry, preferred_work_env,
        location_pref, experience_level, bio, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ON CONFLICT(user_id) DO UPDATE SET
        education_level = excluded.education_level,
        field_of_study = excluded.field_of_study,
        institution = excluded.institution,
        cgpa_percentage = excluded.cgpa_percentage,
        graduation_year = excluded.graduation_year,
        dream_role = excluded.dream_role,
        preferred_industry = excluded.preferred_industry,
        preferred_work_env = excluded.preferred_work_env,
        location_pref = excluded.location_pref,
        experience_level = excluded.experience_level,
        bio = excluded.bio,
        updated_at = CURRENT_TIMESTAMP
    """, (
        user_id,
        profile_data.get('education_level'),
        profile_data.get('field_of_study'),
        profile_data.get('institution'),
        profile_data.get('cgpa_percentage'),
        profile_data.get('graduation_year'),
        profile_data.get('dream_role'),
        profile_data.get('preferred_industry'),
        profile_data.get('preferred_work_env', 'Hybrid'),
        profile_data.get('location_pref'),
        profile_data.get('experience_level', 'Fresher'),
        profile_data.get('bio')
    ))

    # Also update user phone/name if provided
    if profile_data.get('name') or profile_data.get('phone'):
        cursor.execute("""
        UPDATE users SET
            name = COALESCE(?, name),
            phone = COALESCE(?, phone)
        WHERE id = ?
        """, (profile_data.get('name'), profile_data.get('phone'), user_id))

    conn.commit()
    conn.close()
    return True

# ----------------- SKILLS MANAGEMENT -----------------

def get_all_skills(category=None, active_only=True):
    """Fetch all skills optionally filtered by category."""
    conn = get_db()
    cursor = conn.cursor()
    query = "SELECT * FROM skills WHERE 1=1"
    params = []
    if active_only:
        query += " AND is_active = 1"
    if category:
        query += " AND category = ?"
        params.append(category)
    query += " ORDER BY category, name"
    cursor.execute(query, params)
    skills = [dict(row) for row in cursor.fetchall()]
    conn.close()
    return skills

def get_skill_by_id(skill_id):
    """Fetch a single skill by ID."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM skills WHERE id = ?", (skill_id,))
    skill = cursor.fetchone()
    conn.close()
    return dict(skill) if skill else None

def save_skill(skill_data, skill_id=None):
    """Create or update a skill."""
    conn = get_db()
    cursor = conn.cursor()
    if skill_id:
        cursor.execute("""
        UPDATE skills SET
            name = ?, category = ?, description = ?, difficulty_level = ?, is_active = ?
        WHERE id = ?
        """, (
            skill_data['name'], skill_data['category'],
            skill_data.get('description', ''),
            skill_data.get('difficulty_level', 'Intermediate'),
            skill_data.get('is_active', 1),
            skill_id
        ))
    else:
        cursor.execute("""
        INSERT INTO skills (name, category, description, difficulty_level, is_active)
        VALUES (?, ?, ?, ?, ?)
        """, (
            skill_data['name'], skill_data['category'],
            skill_data.get('description', ''),
            skill_data.get('difficulty_level', 'Intermediate'),
            skill_data.get('is_active', 1)
        ))
    conn.commit()
    conn.close()
    return True

def delete_skill(skill_id):
    """Delete a skill by ID."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("DELETE FROM skills WHERE id = ?", (skill_id,))
    conn.commit()
    conn.close()
    return True

def get_student_skills(user_id):
    """Get skills evaluated by or assigned to a student."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("""
    SELECT s.id as skill_id, s.name, s.category, s.difficulty_level,
           ss.proficiency_level, ss.assessed_score, ss.updated_at
    FROM skills s
    JOIN student_skills ss ON s.id = ss.skill_id
    WHERE ss.user_id = ?
    ORDER BY s.category, s.name
    """, (user_id,))
    rows = [dict(r) for r in cursor.fetchall()]
    conn.close()
    return rows

def save_student_skills(user_id, skills_rating_map):
    """Save/update batch of student skill proficiencies (dict: {skill_id: rating_1_to_5})."""
    conn = get_db()
    cursor = conn.cursor()
    for skill_id, rating in skills_rating_map.items():
        try:
            rating_val = max(1, min(5, int(rating)))
            cursor.execute("""
            INSERT INTO student_skills (user_id, skill_id, proficiency_level, assessed_score)
            VALUES (?, ?, ?, ?)
            ON CONFLICT(user_id, skill_id) DO UPDATE SET
                proficiency_level = excluded.proficiency_level,
                assessed_score = excluded.assessed_score,
                updated_at = CURRENT_TIMESTAMP
            """, (user_id, int(skill_id), rating_val, rating_val * 20.0))
        except Exception:
            continue
    conn.commit()
    conn.close()
    return True

# ----------------- CAREERS MANAGEMENT -----------------

def get_all_careers(category=None, active_only=True):
    """Fetch all career paths."""
    conn = get_db()
    cursor = conn.cursor()
    query = "SELECT * FROM career_paths WHERE 1=1"
    params = []
    if active_only:
        query += " AND is_active = 1"
    if category:
        query += " AND category = ?"
        params.append(category)
    query += " ORDER BY title ASC"
    cursor.execute(query, params)
    careers = []
    for row in cursor.fetchall():
        c = dict(row)
        try:
            c['required_skills_parsed'] = json.loads(c['required_skills'])
        except Exception:
            c['required_skills_parsed'] = {}
        try:
            c['top_companies_parsed'] = json.loads(c['top_companies'])
        except Exception:
            c['top_companies_parsed'] = []
        try:
            c['roadmap_parsed'] = json.loads(c['roadmap'])
        except Exception:
            c['roadmap_parsed'] = []
        careers.append(c)
    conn.close()
    return careers

def get_career_by_id(career_id):
    """Fetch a single career by ID with JSON parsed."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM career_paths WHERE id = ?", (career_id,))
    row = cursor.fetchone()
    conn.close()
    if not row:
        return None
    c = dict(row)
    try:
        c['required_skills_parsed'] = json.loads(c['required_skills'])
    except Exception:
        c['required_skills_parsed'] = {}
    try:
        c['top_companies_parsed'] = json.loads(c['top_companies'])
    except Exception:
        c['top_companies_parsed'] = []
    try:
        c['roadmap_parsed'] = json.loads(c['roadmap'])
    except Exception:
        c['roadmap_parsed'] = []
    return c

def save_career(career_data, career_id=None):
    """Create or update a career path."""
    conn = get_db()
    cursor = conn.cursor()
    
    # Ensure JSON formats
    req_skills = career_data.get('required_skills', '{}')
    if isinstance(req_skills, dict):
        req_skills = json.dumps(req_skills)
        
    top_comp = career_data.get('top_companies', '[]')
    if isinstance(top_comp, list):
        top_comp = json.dumps(top_comp)
        
    roadmap = career_data.get('roadmap', '[]')
    if isinstance(roadmap, list):
        roadmap = json.dumps(roadmap)

    if career_id:
        cursor.execute("""
        UPDATE career_paths SET
            title = ?, category = ?, description = ?, required_skills = ?,
            average_salary_inr = ?, salary_range = ?, growth_outlook = ?,
            education_requirement = ?, top_companies = ?, roadmap = ?,
            icon = ?, is_active = ?
        WHERE id = ?
        """, (
            career_data['title'], career_data['category'], career_data['description'],
            req_skills, career_data['average_salary_inr'], career_data['salary_range'],
            career_data['growth_outlook'], career_data['education_requirement'],
            top_comp, roadmap, career_data.get('icon', 'fas fa-briefcase'),
            career_data.get('is_active', 1), career_id
        ))
    else:
        cursor.execute("""
        INSERT INTO career_paths (
            title, category, description, required_skills, average_salary_inr,
            salary_range, growth_outlook, education_requirement, top_companies,
            roadmap, icon, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        """, (
            career_data['title'], career_data['category'], career_data['description'],
            req_skills, career_data['average_salary_inr'], career_data['salary_range'],
            career_data['growth_outlook'], career_data['education_requirement'],
            top_comp, roadmap, career_data.get('icon', 'fas fa-briefcase'),
            career_data.get('is_active', 1)
        ))
    conn.commit()
    conn.close()
    return True

def delete_career(career_id):
    """Delete a career by ID."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("DELETE FROM career_paths WHERE id = ?", (career_id,))
    conn.commit()
    conn.close()
    return True

# ----------------- ASSESSMENTS -----------------

def get_skill_assessments(limit=20):
    """Get active skill test questions."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM skill_assessments WHERE is_active = 1 LIMIT ?", (limit,))
    rows = [dict(r) for r in cursor.fetchall()]
    conn.close()
    return rows

def get_career_assessments():
    """Get psychometric RIASEC career assessment questions."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM career_assessments WHERE is_active = 1")
    rows = []
    for r in cursor.fetchall():
        d = dict(r)
        try:
            d['options_parsed'] = json.loads(d['options_json'])
        except Exception:
            d['options_parsed'] = []
        rows.append(d)
    conn.close()
    return rows

def save_assessment_response(user_id, assessment_type, score, total_questions, correct_answers, response_data):
    """Store student test results in database."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("""
    INSERT INTO student_assessment_responses (
        user_id, assessment_type, score, total_questions, correct_answers, response_data
    ) VALUES (?, ?, ?, ?, ?, ?)
    """, (
        user_id, assessment_type, score, total_questions, correct_answers,
        json.dumps(response_data) if isinstance(response_data, (dict, list)) else response_data
    ))
    conn.commit()
    conn.close()
    return True

def get_student_assessment_history(user_id):
    """Retrieve history of assessments taken by student."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("""
    SELECT * FROM student_assessment_responses
    WHERE user_id = ?
    ORDER BY completed_at DESC
    """, (user_id,))
    rows = [dict(r) for r in cursor.fetchall()]
    conn.close()
    return rows

# ----------------- RECOMMENDATIONS -----------------

def save_recommendation_record(user_id, student_name, top_career_id, top_career_title, match_percentage,
                               secondary_careers, reasoning, matched_skills, missing_skills,
                               salary_forecast, learning_roadmap):
    """Store complete recommendation output record."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("""
    INSERT INTO recommendation_records (
        user_id, student_name, top_career_id, top_career_title, match_percentage,
        secondary_careers, reasoning, matched_skills, missing_skills,
        salary_forecast, learning_roadmap
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    """, (
        user_id, student_name, top_career_id, top_career_title, match_percentage,
        json.dumps(secondary_careers) if isinstance(secondary_careers, (dict, list)) else secondary_careers,
        json.dumps(reasoning) if isinstance(reasoning, (dict, list)) else reasoning,
        json.dumps(matched_skills) if isinstance(matched_skills, list) else matched_skills,
        json.dumps(missing_skills) if isinstance(missing_skills, list) else missing_skills,
        salary_forecast,
        json.dumps(learning_roadmap) if isinstance(learning_roadmap, list) else learning_roadmap
    ))
    rec_id = cursor.lastrowid
    conn.commit()
    conn.close()
    return rec_id

def get_latest_recommendation(user_id):
    """Get student's most recent AI recommendation."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("""
    SELECT r.*, c.icon as career_icon, c.description as career_desc, c.growth_outlook, c.top_companies
    FROM recommendation_records r
    LEFT JOIN career_paths c ON r.top_career_id = c.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
    LIMIT 1
    """, (user_id,))
    row = cursor.fetchone()
    conn.close()
    if not row:
        return None
    r = dict(row)
    for field in ['secondary_careers', 'reasoning', 'matched_skills', 'missing_skills', 'learning_roadmap', 'top_companies']:
        if r.get(field):
            try:
                r[field + '_parsed'] = json.loads(r[field])
            except Exception:
                r[field + '_parsed'] = r[field]
    return r

def get_all_recommendation_records(limit=100):
    """Get all recommendation audit records for admin view."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("""
    SELECT r.*, u.email as student_email
    FROM recommendation_records r
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
    LIMIT ?
    """, (limit,))
    rows = []
    for row in cursor.fetchall():
        r = dict(row)
        for field in ['secondary_careers', 'matched_skills', 'missing_skills']:
            if r.get(field):
                try:
                    r[field + '_parsed'] = json.loads(r[field])
                except Exception:
                    r[field + '_parsed'] = []
        rows.append(r)
    conn.close()
    return rows

# ----------------- CONTACT MESSAGES / INQUIRIES -----------------

def create_contact_message(name, email, subject, message, phone=None, user_id=None):
    """Save an inquiry / contact message to the database."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("""
    INSERT INTO contact_messages (user_id, name, email, phone, subject, message)
    VALUES (?, ?, ?, ?, ?, ?)
    """, (user_id, name, email.strip(), phone, subject, message))
    msg_id = cursor.lastrowid
    conn.commit()
    conn.close()
    return msg_id

def get_all_contact_messages(status=None):
    """Fetch contact inquiries, optionally filtered by status."""
    conn = get_db()
    cursor = conn.cursor()
    query = "SELECT * FROM contact_messages WHERE 1=1"
    params = []
    if status:
        query += " AND status = ?"
        params.append(status)
    query += " ORDER BY created_at DESC"
    cursor.execute(query, params)
    messages = [dict(r) for r in cursor.fetchall()]
    conn.close()
    return messages

def update_message_status(message_id, status, admin_reply=None):
    """Update message status ('read', 'replied') and optional admin response note."""
    conn = get_db()
    cursor = conn.cursor()
    if admin_reply:
        cursor.execute("""
        UPDATE contact_messages SET status = ?, admin_reply = ?, replied_at = CURRENT_TIMESTAMP
        WHERE id = ?
        """, (status, admin_reply, message_id))
    else:
        cursor.execute("UPDATE contact_messages SET status = ? WHERE id = ?", (status, message_id))
    conn.commit()
    conn.close()
    return True

def delete_contact_message(message_id):
    """Delete a contact message."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("DELETE FROM contact_messages WHERE id = ?", (message_id,))
    conn.commit()
    conn.close()
    return True

# ----------------- ADMIN DASHBOARD & STATS -----------------

def get_admin_dashboard_stats():
    """Aggregate statistics for admin dashboard metrics & charts."""
    conn = get_db()
    cursor = conn.cursor()

    cursor.execute("SELECT COUNT(*) as total_students FROM users WHERE role = 'student'")
    total_students = cursor.fetchone()['total_students']

    cursor.execute("SELECT COUNT(*) as total_careers FROM career_paths WHERE is_active = 1")
    total_careers = cursor.fetchone()['total_careers']

    cursor.execute("SELECT COUNT(*) as total_skills FROM skills WHERE is_active = 1")
    total_skills = cursor.fetchone()['total_skills']

    cursor.execute("SELECT COUNT(*) as total_assessments FROM student_assessment_responses")
    total_assessments = cursor.fetchone()['total_assessments']

    cursor.execute("SELECT COUNT(*) as total_recommendations FROM recommendation_records")
    total_recommendations = cursor.fetchone()['total_recommendations']

    cursor.execute("SELECT COUNT(*) as unread_messages FROM contact_messages WHERE status = 'unread'")
    unread_messages = cursor.fetchone()['unread_messages']

    # Top recommended careers
    cursor.execute("""
    SELECT top_career_title, COUNT(*) as count
    FROM recommendation_records
    GROUP BY top_career_title
    ORDER BY count DESC
    LIMIT 5
    """)
    top_recommended = [dict(r) for r in cursor.fetchall()]

    # Recent student signups
    cursor.execute("""
    SELECT u.id, u.name, u.email, u.phone, u.created_at, p.education_level, p.field_of_study
    FROM users u
    LEFT JOIN student_profiles p ON u.id = p.user_id
    WHERE u.role = 'student'
    ORDER BY u.created_at DESC
    LIMIT 6
    """)
    recent_students = [dict(r) for r in cursor.fetchall()]

    # Recent inquiries
    cursor.execute("""
    SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5
    """)
    recent_messages = [dict(r) for r in cursor.fetchall()]

    conn.close()
    return {
        'total_students': total_students,
        'total_careers': total_careers,
        'total_skills': total_skills,
        'total_assessments': total_assessments,
        'total_recommendations': total_recommendations,
        'unread_messages': unread_messages,
        'top_recommended': top_recommended,
        'recent_students': recent_students,
        'recent_messages': recent_messages
    }

def get_all_students(search_query=None):
    """List all students with profile summary for Admin Student Management."""
    conn = get_db()
    cursor = conn.cursor()
    query = """
    SELECT u.id, u.name, u.email, u.phone, u.created_at,
           p.education_level, p.field_of_study, p.institution, p.cgpa_percentage, p.dream_role,
           (SELECT COUNT(*) FROM student_assessment_responses WHERE user_id = u.id) as assessment_count,
           (SELECT top_career_title FROM recommendation_records WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as latest_recommendation,
           (SELECT match_percentage FROM recommendation_records WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as match_percentage
    FROM users u
    LEFT JOIN student_profiles p ON u.id = p.user_id
    WHERE u.role = 'student'
    """
    params = []
    if search_query:
        query += " AND (u.name LIKE ? OR u.email LIKE ? OR p.field_of_study LIKE ?)"
        term = f"%{search_query}%"
        params.extend([term, term, term])
    query += " ORDER BY u.created_at DESC"
    cursor.execute(query, params)
    students = [dict(r) for r in cursor.fetchall()]
    conn.close()
    return students

def delete_user(user_id):
    """Delete a student user and cascade delete their profile and assessments."""
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("DELETE FROM users WHERE id = ? AND role != 'admin'", (user_id,))
    conn.commit()
    conn.close()
    return True
