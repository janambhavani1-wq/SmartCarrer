# CareerCompass – AI-Powered Smart Career Recommendation System

> **A Full-Stack AI Career Guidance & Institutional Administration Platform**  
> Built using **Python (Flask)**, **SQLite Database**, **HTML5**, **Vanilla CSS3 (Glassmorphism)**, and **JavaScript (Chart.js)**.

---

## 🚀 Key Features

### 🎓 1. Student Module
- **Registration & Secure Authentication**: Student account setup with encrypted password security.
- **Personal & Academic Details**: Capture degree level, CGPA/percentage, dream career, and work preferences.
- **Skill Proficiency Evaluation**: Interactive slider matrix across 40+ Technical, Analytical, Creative, and Management skills.
- **Timed Skill Assessment Quiz**: 10-question timed technical benchmark with live timer and explanation review.
- **Psychometric Career Assessment**: Holland RIASEC behavioral scenarios evaluating work-style personality.
- **Explainable AI Recommendations**: Multi-factor Cosine matching algorithm delivering match %, XAI rationale, skill gap matrix, interactive radar charts, and personalized learning roadmaps.
- **PDF / Print Academic Report**: One-click printable career roadmap dossier for academic submission.
- **Contact Counselor Form**: Website messages stored directly in the database for institutional review.

### 🛡️ 2. Administrator Module
- **Analytics Dashboard**: Live KPI metrics, Chart.js career demand distribution, and category breakdowns.
- **Manage Students**: Search & filter students, view full dossier via modal, and manage student accounts.
- **Manage Careers (CRUD)**: Add, edit, or delete career roles, required skills, average salaries, and roadmap milestones.
- **Manage Skills & Question Bank**: Maintain skill definitions and assessment questions.
- **Recommendation Records Audit**: View historical audit logs of all student AI recommendation runs.
- **Inquiry Messages Desk**: Review all website contact inquiries, update statuses (`unread`, `read`, `replied`), and add counselor response notes.

---

## 🔑 Default Evaluation Credentials

| Role | Email | Password | Access Portal |
| :--- | :--- | :--- | :--- |
| **System Admin** | `admin@careercompass.com` | `Admin@12345` | `/login` $\rightarrow$ `/admin/dashboard` |
| **Demo Student** | `student@careercompass.com` | `Student@12345` | `/login` $\rightarrow$ `/student/dashboard` |

*(Quick-fill demo buttons are also integrated directly into the login screen!)*

---

## 🛠️ Technology Stack
- **Backend**: Python 3.13, Flask 3.1, Werkzeug (PBKDF2 Password Hashing)
- **Database**: SQLite3 (`careercompass.db`) with Foreign Keys & JSON Serialization
- **Frontend**: Semantic HTML5, Vanilla CSS3 (Custom Glassmorphism Design System, CSS Grid & Flexbox)
- **Visualizations**: Chart.js 4.x (Radar Competency Charts, Bar & Doughnut Analytics)
- **Icons & Fonts**: FontAwesome 6.4, Google Fonts (Plus Jakarta Sans & Outfit)

---

## 📦 How to Run Locally

### Option 1: One-Click Runner (Windows)
Double-click `run.bat` or run:
```bash
run.bat
```

### Option 2: Manual Terminal Execution
1. Install dependencies:
   ```bash
   pip install -r requirements.txt
   ```
2. Initialize Database and seed data:
   ```bash
   python database.py
   ```
3. Start the Flask application:
   ```bash
   python app.py
   ```
4. Open your browser and navigate to:
   **`http://127.0.0.1:5000`**

---

## 🧪 Automated Testing
To run the automated verification test suite:
```bash
python test_system.py
```
Outputs:
```text
Ran 5 tests in 0.990s
OK
```

---

## 📂 Project Directory Structure

```text
Smartcarrer/
├── app.py                     # Main Flask Controller & REST API Endpoints
├── database.py                # Database Schema Migrations & Initial Seeder
├── models.py                  # Data Access Layer & Business Logic
├── ai_engine.py               # AI Recommendation Engine & Vector Scoring
├── test_system.py             # Automated Verification Test Suite
├── requirements.txt           # Python Dependencies
├── run.bat                    # One-Click Windows Startup Script
├── PROJECT_REPORT.md          # Comprehensive Academic Project Report
├── README.md                  # Documentation & Setup Manual
├── static/
│   ├── css/
│   │   └── style.css          # Master Glassmorphism & Responsive CSS
│   └── js/
│       ├── main.js            # UI Interactivity, Modals, Quiz Timer, Sliders
│       └── charts.js          # Chart.js Radar & Bar Chart Analytics
└── templates/
    ├── base.html              # Master Base Layout & Navigation
    ├── index.html             # High-Impact Landing Page & Contact Form
    ├── login.html             # Login with Demo Credentials Quick-Fill
    ├── register.html          # Student Registration Page
    ├── careers_list.html      # Public Career Exploration Tracks
    ├── career_detail.html     # Single Career Roadmap & Salary View
    ├── student/
    │   ├── dashboard.html     # Student Command Center & Radar Overview
    │   ├── profile.html       # Personal & Academic Details Form
    │   ├── skills.html        # Skill Self-Evaluation Matrix (Sliders)
    │   ├── skill_assessment.html       # Timed 10-Q Technical Quiz
    │   ├── skill_assessment_result.html# Score Review & Explanations
    │   ├── career_assessment.html      # Holland RIASEC Questionnaire
    │   └── recommendations.html        # AI Recommendations & PDF Report
    └── admin/
        ├── dashboard.html     # Admin Analytics & KPI Metrics
        ├── students.html      # Student Directory & Inspection Modal
        ├── careers.html       # Career Repository CRUD
        ├── skills.html        # Skills & Question Bank CRUD
        ├── recommendations.html# Recommendation Audit Logs
        └── messages.html      # Website Inquiries & Messages Desk
```
