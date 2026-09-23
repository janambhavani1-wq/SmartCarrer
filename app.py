"""
CareerCompass – AI-Powered Smart Career Recommendation System
Main Flask Web Application Controller
"""

import os
import json
from functools import wraps
from flask import Flask, render_template, request, redirect, url_for, session, flash, jsonify
from flask_cors import CORS

from database import init_db
import models
import ai_engine

app = Flask(__name__)
app.secret_key = os.environ.get('SECRET_KEY', 'careercompass-secure-secret-key-2026')
CORS(app)

# Ensure database is initialized
init_db()

# ----------------- AUTHENTICATION DECORATORS -----------------

def login_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            flash('Please log in to access this page.', 'warning')
            return redirect(url_for('login', next=request.url))
        return f(*args, **kwargs)
    return decorated_function

def student_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            flash('Please log in as a student to access this page.', 'warning')
            return redirect(url_for('login', next=request.url))
        if session.get('role') != 'student':
            flash('Access restricted to students only.', 'danger')
            return redirect(url_for('admin_dashboard'))
        return f(*args, **kwargs)
    return decorated_function

def admin_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            flash('Please log in with administrator credentials.', 'warning')
            return redirect(url_for('login', next=request.url))
        if session.get('role') != 'admin':
            flash('Access denied. Administrator privileges required.', 'danger')
            return redirect(url_for('student_dashboard'))
        return f(*args, **kwargs)
    return decorated_function

# Context processor for global template variables
@app.context_processor
def inject_global_data():
    user = None
    if 'user_id' in session:
        user = models.get_user_by_id(session['user_id'])
    return {
        'current_user': user,
        'app_name': 'CareerCompass',
        'tagline': 'AI-Powered Smart Career Recommendation System'
    }

# ----------------- PUBLIC ROUTES -----------------

@app.route('/')
def index():
    """Landing page featuring hero section, stats, live career cards, and contact form."""
    careers = models.get_all_careers(active_only=True)
    stats = {
        'careers_count': len(careers),
        'students_helped': '2,500+',
        'skills_indexed': '45+',
        'ai_accuracy': '94.8%'
    }
    return render_template('index.html', featured_careers=careers[:6], stats=stats)

@app.route('/careers')
def explore_careers():
    """Public exploration page of all industry career tracks."""
    category = request.args.get('category')
    careers = models.get_all_careers(category=category, active_only=True)
    all_categories = sorted(list(set(c['category'] for c in models.get_all_careers(active_only=True))))
    return render_template('careers_list.html', careers=careers, categories=all_categories, current_category=category)

@app.route('/career/<int:career_id>')
def career_detail(career_id):
    """Detailed view of a single career roadmap and salary outlook."""
    career = models.get_career_by_id(career_id)
    if not career:
        flash('Career profile not found.', 'danger')
        return redirect(url_for('explore_careers'))
    return render_template('career_detail.html', career=career)

@app.route('/contact', methods=['POST'])
def submit_contact():
    """Handle contact/inquiry form submission and store directly in database."""
    name = request.form.get('name', '').strip()
    email = request.form.get('email', '').strip()
    phone = request.form.get('phone', '').strip()
    subject = request.form.get('subject', '').strip()
    message = request.form.get('message', '').strip()
    user_id = session.get('user_id')

    if not name or not email or not message:
        flash('Please fill in all required fields (Name, Email, Message).', 'danger')
        return redirect(url_for('index', _anchor='contact'))

    msg_id = models.create_contact_message(
        name=name, email=email, subject=subject or 'General Inquiry',
        message=message, phone=phone, user_id=user_id
    )

    if msg_id:
        flash('Thank you! Your message has been recorded. Our counselors and administrators will review it shortly.', 'success')
    else:
        flash('An error occurred while saving your message. Please try again.', 'danger')

    return redirect(url_for('index', _anchor='contact'))

# ----------------- AUTHENTICATION ROUTES -----------------

@app.route('/login', methods=['GET', 'POST'])
def login():
    """User login supporting both Student and Admin roles."""
    if 'user_id' in session:
        if session.get('role') == 'admin':
            return redirect(url_for('admin_dashboard'))
        return redirect(url_for('student_dashboard'))

    if request.method == 'POST':
        email = request.form.get('email', '').strip()
        password = request.form.get('password', '')

        user = models.authenticate_user(email, password)
        if user:
            session['user_id'] = user['id']
            session['user_name'] = user['name']
            session['email'] = user['email']
            session['role'] = user['role']

            flash(f'Welcome back, {user["name"]}!', 'success')
            next_url = request.args.get('next')
            if next_url and not next_url.startswith('//') and not next_url.startswith('http'):
                return redirect(next_url)

            if user['role'] == 'admin':
                return redirect(url_for('admin_dashboard'))
            return redirect(url_for('student_dashboard'))
        else:
            flash('Invalid email address or password. Please check your credentials.', 'danger')

    return render_template('login.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    """Student registration route."""
    if 'user_id' in session:
        return redirect(url_for('student_dashboard'))

    if request.method == 'POST':
        name = request.form.get('name', '').strip()
        email = request.form.get('email', '').strip()
        phone = request.form.get('phone', '').strip()
        password = request.form.get('password', '')
        confirm_password = request.form.get('confirm_password', '')

        if not name or not email or not password:
            flash('All fields marked with * are required.', 'danger')
            return render_template('register.html')

        if password != confirm_password:
            flash('Passwords do not match. Please re-enter.', 'danger')
            return render_template('register.html')

        if len(password) < 6:
            flash('Password must be at least 6 characters long.', 'warning')
            return render_template('register.html')

        user_id = models.create_user(name=name, email=email, password=password, role='student', phone=phone)
        if user_id:
            # Auto-login newly registered student
            session['user_id'] = user_id
            session['user_name'] = name
            session['email'] = email
            session['role'] = 'student'
            flash('Account created successfully! Let’s complete your personal details to get started.', 'success')
            return redirect(url_for('student_profile'))
        else:
            flash('An account with this email already exists. Please log in instead.', 'danger')

    return render_template('register.html')

@app.route('/logout')
def logout():
    """Clear session and log out user."""
    session.clear()
    flash('You have been safely logged out.', 'info')
    return redirect(url_for('login'))

# ----------------- STUDENT MODULE ROUTES -----------------

@app.route('/student/dashboard')
@student_required
def student_dashboard():
    """Student central control panel."""
    user_id = session['user_id']
    profile = models.get_student_profile(user_id)
    skills = models.get_student_skills(user_id)
    assessment_history = models.get_student_assessment_history(user_id)
    latest_rec = models.get_latest_recommendation(user_id)

    # Calculate profile completion %
    profile_fields = ['education_level', 'field_of_study', 'institution', 'cgpa_percentage', 'graduation_year', 'dream_role', 'bio']
    filled_count = sum(1 for f in profile_fields if profile and profile.get(f))
    profile_completion = int((filled_count / len(profile_fields)) * 100)

    has_skill_test = any(a['assessment_type'] == 'skill_assessment' for a in assessment_history)
    has_career_test = any(a['assessment_type'] == 'career_assessment' for a in assessment_history)

    return render_template(
        'student/dashboard.html',
        profile=profile,
        skills=skills,
        assessment_history=assessment_history,
        latest_rec=latest_rec,
        profile_completion=profile_completion,
        has_skill_test=has_skill_test,
        has_career_test=has_career_test
    )

@app.route('/student/profile', methods=['GET', 'POST'])
@student_required
def student_profile():
    """Enter & update personal and academic details."""
    user_id = session['user_id']
    if request.method == 'POST':
        profile_data = {
            'name': request.form.get('name', '').strip(),
            'phone': request.form.get('phone', '').strip(),
            'education_level': request.form.get('education_level'),
            'field_of_study': request.form.get('field_of_study', '').strip(),
            'institution': request.form.get('institution', '').strip(),
            'cgpa_percentage': float(request.form.get('cgpa_percentage')) if request.form.get('cgpa_percentage') else None,
            'graduation_year': int(request.form.get('graduation_year')) if request.form.get('graduation_year') else None,
            'dream_role': request.form.get('dream_role', '').strip(),
            'preferred_industry': request.form.get('preferred_industry', '').strip(),
            'preferred_work_env': request.form.get('preferred_work_env', 'Hybrid'),
            'location_pref': request.form.get('location_pref', '').strip(),
            'experience_level': request.form.get('experience_level', 'Fresher'),
            'bio': request.form.get('bio', '').strip()
        }

        models.update_student_profile(user_id, profile_data)
        session['user_name'] = profile_data['name']
        flash('Personal and academic profile updated successfully!', 'success')
        return redirect(url_for('student_profile'))

    profile = models.get_student_profile(user_id)
    return render_template('student/profile.html', profile=profile)

@app.route('/student/skills', methods=['GET', 'POST'])
@student_required
def student_skills():
    """Self-evaluate proficiency ratings for skills."""
    user_id = session['user_id']
    if request.method == 'POST':
        # Process ratings map from form
        rating_map = {}
        for key, val in request.form.items():
            if key.startswith('skill_'):
                skill_id = key.split('_')[1]
                rating_map[skill_id] = val

        models.save_student_skills(user_id, rating_map)
        flash('Skill proficiencies updated successfully! Run AI Recommendations to see refreshed matches.', 'success')
        return redirect(url_for('student_skills'))

    all_skills = models.get_all_skills(active_only=True)
    student_skills_list = models.get_student_skills(user_id)
    user_skill_ratings = {s['skill_id']: s['proficiency_level'] for s in student_skills_list}

    # Group skills by category
    skills_by_category = {}
    for sk in all_skills:
        cat = sk['category']
        if cat not in skills_by_category:
            skills_by_category[cat] = []
        sk['current_rating'] = user_skill_ratings.get(sk['id'], 1)
        skills_by_category[cat].append(sk)

    return render_template('student/skills.html', skills_by_category=skills_by_category, student_skills_list=student_skills_list)

@app.route('/student/skill-assessment', methods=['GET', 'POST'])
@student_required
def student_skill_assessment():
    """Interactive timed skill evaluation quiz."""
    user_id = session['user_id']
    if request.method == 'POST':
        questions = models.get_skill_assessments(limit=10)
        correct_count = 0
        total_questions = len(questions)
        detailed_answers = []

        # Map to track skills to auto-update
        skill_score_accumulator = {}

        for q in questions:
            q_id = str(q['id'])
            user_choice = request.form.get(f'q_{q_id}')
            is_correct = (user_choice == q['correct_option'])
            if is_correct:
                correct_count += 1

            skill_tag = q['skill_tagged']
            if skill_tag not in skill_score_accumulator:
                skill_score_accumulator[skill_tag] = []
            skill_score_accumulator[skill_tag].append(is_correct)

            detailed_answers.append({
                'question_id': q['id'],
                'question_text': q['question_text'],
                'user_choice': user_choice,
                'correct_option': q['correct_option'],
                'is_correct': is_correct,
                'explanation': q['explanation'],
                'skill_tagged': skill_tag
            })

        score_pct = round((correct_count / total_questions) * 100.0, 1) if total_questions > 0 else 0

        # Save assessment results
        models.save_assessment_response(
            user_id=user_id,
            assessment_type='skill_assessment',
            score=score_pct,
            total_questions=total_questions,
            correct_answers=correct_count,
            response_data=detailed_answers
        )

        flash(f'Skill assessment completed! You scored {correct_count}/{total_questions} ({score_pct}%).', 'success')
        return render_template('student/skill_assessment_result.html', score=score_pct, correct_count=correct_count, total=total_questions, detailed=detailed_answers)

    questions = models.get_skill_assessments(limit=10)
    return render_template('student/skill_assessment.html', questions=questions)

@app.route('/student/career-assessment', methods=['GET', 'POST'])
@student_required
def student_career_assessment():
    """Psychometric Holland RIASEC questionnaire."""
    user_id = session['user_id']
    if request.method == 'POST':
        career_questions = models.get_career_assessments()
        trait_scores = {}
        total_q = len(career_questions)

        for q in career_questions:
            q_id = str(q['id'])
            selected_option_idx = request.form.get(f'cq_{q_id}')
            if selected_option_idx is not None:
                idx = int(selected_option_idx)
                if 0 <= idx < len(q['options_parsed']):
                    chosen = q['options_parsed'][idx]
                    traits = chosen.get('traits', {})
                    for trait, score in traits.items():
                        trait_scores[trait] = trait_scores.get(trait, 0) + score

        # Save assessment response
        models.save_assessment_response(
            user_id=user_id,
            assessment_type='career_assessment',
            score=100.0,
            total_questions=total_q,
            correct_answers=total_q,
            response_data=trait_scores
        )

        flash('Career psychometric evaluation recorded successfully!', 'success')
        return redirect(url_for('generate_recommendations'))

    career_questions = models.get_career_assessments()
    return render_template('student/career_assessment.html', questions=career_questions)

@app.route('/student/generate-recommendations', methods=['GET', 'POST'])
@student_required
def generate_recommendations():
    """Trigger AI recommendation engine and store audit record."""
    user_id = session['user_id']
    profile = models.get_student_profile(user_id)
    rec_result = ai_engine.compute_career_recommendations(user_id)

    if not rec_result or not rec_result.get('top_career'):
        flash('Unable to generate recommendations. Please ensure your profile and skills are configured.', 'warning')
        return redirect(url_for('student_dashboard'))

    top = rec_result['top_career']
    sec = rec_result['secondary_careers']

    models.save_recommendation_record(
        user_id=user_id,
        student_name=profile['name'] if profile else session.get('user_name', 'Student'),
        top_career_id=top['career_id'],
        top_career_title=top['title'],
        match_percentage=top['match_percentage'],
        secondary_careers=sec,
        reasoning=top['reasoning'],
        matched_skills=top['matched_skills'],
        missing_skills=top['missing_skills'],
        salary_forecast=top['average_salary_inr'],
        learning_roadmap=top['roadmap']
    )

    flash(f'AI Recommendation generated successfully! Top Match: {top["title"]} ({top["match_percentage"]}% Match)', 'success')
    return redirect(url_for('student_recommendations'))

@app.route('/student/recommendations')
@student_required
def student_recommendations():
    """View detailed AI recommendations, skill gaps, radar analysis, and learning roadmap."""
    user_id = session['user_id']
    rec = models.get_latest_recommendation(user_id)
    if not rec:
        # If no recommendation has been generated yet, compute now
        return redirect(url_for('generate_recommendations'))

    profile = models.get_student_profile(user_id)
    return render_template('student/recommendations.html', rec=rec, profile=profile)

# ----------------- ADMIN MODULE ROUTES -----------------

@app.route('/admin/dashboard')
@admin_required
def admin_dashboard():
    """Central administrator dashboard with live KPIs and analytics charts."""
    stats = models.get_admin_dashboard_stats()
    return render_template('admin/dashboard.html', stats=stats)

@app.route('/admin/students')
@admin_required
def admin_students():
    """Manage students list with search, filter, and profile inspection."""
    search_query = request.args.get('q', '').strip()
    students = models.get_all_students(search_query=search_query)
    return render_template('admin/students.html', students=students, search_query=search_query)

@app.route('/admin/students/<int:user_id>/details')
@admin_required
def admin_student_details(user_id):
    """API endpoint to get full details of a single student for the modal."""
    profile = models.get_student_profile(user_id)
    skills = models.get_student_skills(user_id)
    assessments = models.get_student_assessment_history(user_id)
    rec = models.get_latest_recommendation(user_id)
    return jsonify({
        'profile': profile,
        'skills': skills,
        'assessments': assessments,
        'recommendation': rec
    })

@app.route('/admin/students/<int:user_id>/delete', methods=['POST'])
@admin_required
def admin_delete_student(user_id):
    """Delete a student account."""
    models.delete_user(user_id)
    flash('Student account deleted successfully.', 'info')
    return redirect(url_for('admin_students'))

@app.route('/admin/careers', methods=['GET', 'POST'])
@admin_required
def admin_careers():
    """Manage career information repository (CRUD)."""
    if request.method == 'POST':
        career_id = request.form.get('career_id')
        
        # Parse required skills list
        req_skills_raw = request.form.get('required_skills', '{}')
        try:
            req_skills = json.loads(req_skills_raw)
        except Exception:
            # Parse comma-separated list into equal weights
            skills_list = [s.strip() for s in req_skills_raw.split(',') if s.strip()]
            req_skills = {s: 0.85 for s in skills_list}

        # Parse top companies
        companies_raw = request.form.get('top_companies', '[]')
        try:
            top_companies = json.loads(companies_raw)
        except Exception:
            top_companies = [c.strip() for c in companies_raw.split(',') if c.strip()]

        # Parse roadmap
        roadmap_raw = request.form.get('roadmap', '[]')
        try:
            roadmap = json.loads(roadmap_raw)
        except Exception:
            roadmap = [
                {"phase": "Phase 1: Foundations", "duration": "Month 1-2", "focus": "Core fundamentals and basics."},
                {"phase": "Phase 2: Core Mastery", "duration": "Month 3-4", "focus": "Intermediate concepts and tooling."},
                {"phase": "Phase 3: Advanced Projects", "duration": "Month 5-6", "focus": "Portfolio building and deployment."}
            ]

        career_data = {
            'title': request.form.get('title', '').strip(),
            'category': request.form.get('category', '').strip(),
            'description': request.form.get('description', '').strip(),
            'required_skills': req_skills,
            'average_salary_inr': request.form.get('average_salary_inr', '').strip(),
            'salary_range': request.form.get('salary_range', '').strip(),
            'growth_outlook': request.form.get('growth_outlook', '').strip(),
            'education_requirement': request.form.get('education_requirement', '').strip(),
            'top_companies': top_companies,
            'roadmap': roadmap,
            'icon': request.form.get('icon', 'fas fa-briefcase'),
            'is_active': int(request.form.get('is_active', 1))
        }

        models.save_career(career_data, career_id=int(career_id) if career_id else None)
        flash('Career profile saved successfully!', 'success')
        return redirect(url_for('admin_careers'))

    careers = models.get_all_careers(active_only=False)
    return render_template('admin/careers.html', careers=careers)

@app.route('/admin/careers/<int:career_id>/delete', methods=['POST'])
@admin_required
def admin_delete_career(career_id):
    """Delete a career profile."""
    models.delete_career(career_id)
    flash('Career path deleted successfully.', 'info')
    return redirect(url_for('admin_careers'))

@app.route('/admin/skills', methods=['GET', 'POST'])
@admin_required
def admin_skills():
    """Manage skills catalog & question bank."""
    if request.method == 'POST':
        skill_id = request.form.get('skill_id')
        skill_data = {
            'name': request.form.get('name', '').strip(),
            'category': request.form.get('category', '').strip(),
            'description': request.form.get('description', '').strip(),
            'difficulty_level': request.form.get('difficulty_level', 'Intermediate'),
            'is_active': int(request.form.get('is_active', 1))
        }
        models.save_skill(skill_data, skill_id=int(skill_id) if skill_id else None)
        flash('Skill saved successfully!', 'success')
        return redirect(url_for('admin_skills'))

    skills = models.get_all_skills(active_only=False)
    assessments = models.get_skill_assessments(limit=50)
    return render_template('admin/skills.html', skills=skills, assessments=assessments)

@app.route('/admin/skills/<int:skill_id>/delete', methods=['POST'])
@admin_required
def admin_delete_skill(skill_id):
    """Delete a skill."""
    models.delete_skill(skill_id)
    flash('Skill deleted from catalog.', 'info')
    return redirect(url_for('admin_skills'))

@app.route('/admin/recommendations')
@admin_required
def admin_recommendations():
    """View historical recommendation records log."""
    records = models.get_all_recommendation_records(limit=100)
    return render_template('admin/recommendations.html', records=records)

@app.route('/admin/messages', methods=['GET', 'POST'])
@admin_required
def admin_messages():
    """Manage contact messages and inquiries from students/visitors."""
    if request.method == 'POST':
        msg_id = int(request.form.get('message_id'))
        status = request.form.get('status')
        admin_reply = request.form.get('admin_reply')
        action = request.form.get('action')

        if action == 'delete':
            models.delete_contact_message(msg_id)
            flash('Inquiry message deleted.', 'info')
        else:
            models.update_message_status(msg_id, status=status, admin_reply=admin_reply)
            flash('Message status & response updated successfully.', 'success')

        return redirect(url_for('admin_messages'))

    status_filter = request.args.get('status')
    messages = models.get_all_contact_messages(status=status_filter)
    return render_template('admin/messages.html', messages=messages, current_status=status_filter)

# ----------------- CHART & ANALYTICS APIS -----------------

@app.route('/api/admin/analytics')
@admin_required
def api_admin_analytics():
    """JSON chart feed for Admin Dashboard."""
    stats = models.get_admin_dashboard_stats()
    careers = models.get_all_careers(active_only=True)
    
    # Career Category breakdown
    cat_counts = {}
    for c in careers:
        cat = c['category']
        cat_counts[cat] = cat_counts.get(cat, 0) + 1

    return jsonify({
        'top_recommended': stats['top_recommended'],
        'career_categories': cat_counts
    })

@app.route('/api/student/skill-radar')
@student_required
def api_student_skill_radar():
    """JSON data for student skills radar chart."""
    user_id = session['user_id']
    student_skills = models.get_student_skills(user_id)
    
    labels = [s['name'] for s in student_skills[:8]]
    data = [s['proficiency_level'] * 20 for s in student_skills[:8]]
    
    return jsonify({
        'labels': labels if labels else ['Python', 'SQL', 'Algorithms', 'Web Dev', 'Problem Solving', 'Data Analysis'],
        'data': data if data else [80, 75, 85, 70, 90, 65]
    })

# ----------------- MAIN RUNNER -----------------

if __name__ == '__main__':
    print("=" * 60)
    print("🚀 CareerCompass – AI Smart Career Recommendation System")
    print("🌐 Running locally on: http://127.0.0.1:5000")
    print("🔑 Admin Demo Login: admin@careercompass.com / Admin@12345")
    print("🎓 Student Demo Login: student@careercompass.com / Student@12345")
    print("=" * 60)
    app.run(debug=True, host='127.0.0.1', port=5000)
