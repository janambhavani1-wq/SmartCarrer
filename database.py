"""
CareerCompass – Database Module
Handles SQLite connection, schema creation, and database seeding with rich academic & career data.
"""

import sqlite3
import os
import json
from werkzeug.security import generate_password_hash
from datetime import datetime

DB_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'careercompass.db')

def get_db():
    """Get a database connection with row factory enabled."""
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA foreign_keys = ON")
    return conn

def init_db():
    """Create all required tables and seed initial data if empty."""
    conn = get_db()
    cursor = conn.cursor()

    # 1. Users Table
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'student', -- 'student' or 'admin'
        phone TEXT,
        avatar TEXT DEFAULT 'default_avatar.png',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    """)

    # 2. Student Profiles Table
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS student_profiles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER UNIQUE NOT NULL,
        education_level TEXT, -- High School, B.Tech/B.E, BCA, B.Sc, MCA, MBA, M.Tech, Other
        field_of_study TEXT,  -- Computer Science, IT, Electronics, Mechanical, Commerce, etc.
        institution TEXT,
        cgpa_percentage REAL,
        graduation_year INTEGER,
        dream_role TEXT,
        preferred_industry TEXT,
        preferred_work_env TEXT DEFAULT 'Hybrid', -- Remote, On-site, Hybrid
        location_pref TEXT,
        experience_level TEXT DEFAULT 'Fresher', -- Fresher, Intermediate, Experienced
        bio TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    );
    """)

    # 3. Skills Table
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS skills (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        category TEXT NOT NULL, -- Technical, Analytical, Design & Creative, Management & Soft Skills
        description TEXT,
        difficulty_level TEXT DEFAULT 'Intermediate', -- Beginner, Intermediate, Advanced
        is_active INTEGER DEFAULT 1
    );
    """)

    # 4. Student Skills (Proficiency ratings)
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS student_skills (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        skill_id INTEGER NOT NULL,
        proficiency_level INTEGER NOT NULL CHECK (proficiency_level BETWEEN 1 AND 5), -- 1 (Novice) to 5 (Expert)
        assessed_score REAL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, skill_id),
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
        FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE
    );
    """)

    # 5. Career Paths Table
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS career_paths (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT UNIQUE NOT NULL,
        category TEXT NOT NULL,
        description TEXT NOT NULL,
        required_skills TEXT NOT NULL, -- JSON list of skill names with weight { "Python": 0.9, "SQL": 0.8 }
        average_salary_inr TEXT NOT NULL,
        salary_range TEXT NOT NULL,
        growth_outlook TEXT NOT NULL, -- e.g. "Very High (28% growth over next decade)"
        education_requirement TEXT NOT NULL,
        top_companies TEXT NOT NULL, -- JSON list
        roadmap TEXT NOT NULL, -- JSON list of milestones
        icon TEXT DEFAULT 'fas fa-briefcase',
        is_active INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    """)

    # 6. Skill Assessment Questions
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS skill_assessments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category TEXT NOT NULL,
        skill_tagged TEXT NOT NULL,
        question_text TEXT NOT NULL,
        option_a TEXT NOT NULL,
        option_b TEXT NOT NULL,
        option_c TEXT NOT NULL,
        option_d TEXT NOT NULL,
        correct_option TEXT NOT NULL, -- 'A', 'B', 'C', 'D'
        explanation TEXT,
        difficulty TEXT DEFAULT 'Intermediate',
        is_active INTEGER DEFAULT 1
    );
    """)

    # 7. Career Psychometric Questions (RIASEC / Work Style)
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS career_assessments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        dimension TEXT NOT NULL, -- Realistic, Investigative, Artistic, Social, Enterprising, Conventional, Leadership, Analytical
        question_text TEXT NOT NULL,
        scenario TEXT,
        options_json TEXT NOT NULL, -- JSON with option label and trait weights
        is_active INTEGER DEFAULT 1
    );
    """)

    # 8. Student Assessment Responses
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS student_assessment_responses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        assessment_type TEXT NOT NULL, -- 'skill_assessment' or 'career_assessment'
        score REAL NOT NULL,
        total_questions INTEGER NOT NULL,
        correct_answers INTEGER DEFAULT 0,
        response_data TEXT, -- JSON detailed responses
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    );
    """)

    # 9. Recommendation Records
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS recommendation_records (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        student_name TEXT NOT NULL,
        top_career_id INTEGER NOT NULL,
        top_career_title TEXT NOT NULL,
        match_percentage REAL NOT NULL,
        secondary_careers TEXT, -- JSON list of other matches
        reasoning TEXT, -- JSON or markdown explanation
        matched_skills TEXT, -- JSON list
        missing_skills TEXT, -- JSON list
        salary_forecast TEXT,
        learning_roadmap TEXT, -- JSON list
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
        FOREIGN KEY (top_career_id) REFERENCES career_paths (id) ON DELETE CASCADE
    );
    """)

    # 10. Contact Messages (Inquiries / Feedback)
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS contact_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        phone TEXT,
        subject TEXT NOT NULL,
        message TEXT NOT NULL,
        status TEXT DEFAULT 'unread', -- 'unread', 'read', 'replied'
        admin_reply TEXT,
        replied_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
    );
    """)

    conn.commit()
    seed_database(conn)
    conn.close()

def seed_database(conn):
    """Seed initial essential dataset for admin, careers, skills, assessments, and sample student."""
    cursor = conn.cursor()

    # Check if admin already exists
    cursor.execute("SELECT id FROM users WHERE email = 'admin@careercompass.com'")
    if cursor.fetchone() is None:
        # Seed Admin User
        admin_pass = generate_password_hash("Admin@12345")
        cursor.execute("""
        INSERT INTO users (name, email, password_hash, role, phone)
        VALUES ('System Administrator', 'admin@careercompass.com', ?, 'admin', '+91 9876543210')
        """, (admin_pass,))

        # Seed Demo Student User
        student_pass = generate_password_hash("Student@12345")
        cursor.execute("""
        INSERT INTO users (name, email, password_hash, role, phone)
        VALUES ('Aarav Sharma', 'student@careercompass.com', ?, 'student', '+91 9123456780')
        """, (student_pass,))
        student_id = cursor.lastrowid

        # Profile for Demo Student
        cursor.execute("""
        INSERT INTO student_profiles (
            user_id, education_level, field_of_study, institution, cgpa_percentage,
            graduation_year, dream_role, preferred_industry, preferred_work_env, location_pref, bio
        ) VALUES (
            ?, 'B.Tech / B.E', 'Computer Science & Engineering', 'National Institute of Technology',
            8.75, 2026, 'AI & Machine Learning Engineer', 'Information Technology & AI',
            'Hybrid', 'Bengaluru / Hyderabad / Remote',
            'Passionate about building intelligent systems, data engineering, and solving real-world challenges through AI.'
        )
        """, (student_id,))

    # Seed Skills
    cursor.execute("SELECT COUNT(*) as count FROM skills")
    if cursor.fetchone()['count'] == 0:
        skills_data = [
            # Technical Skills
            ("Python", "Technical", "Core programming, object-oriented concepts, scripting & data libraries", "Intermediate"),
            ("Data Structures & Algorithms", "Technical", "Trees, graphs, dynamic programming, algorithmic complexity", "Advanced"),
            ("SQL & Relational Databases", "Technical", "Complex queries, indexing, normalization, transactions", "Intermediate"),
            ("Machine Learning & Deep Learning", "Technical", "Supervised/unsupervised algorithms, neural networks, PyTorch/TensorFlow", "Advanced"),
            ("JavaScript & Web Development", "Technical", "ES6+, DOM manipulation, asynchronous programming, frontend frameworks", "Intermediate"),
            ("HTML5 & CSS3 Responsive Design", "Technical", "Semantic markup, modern flexbox, CSS grid, animations", "Beginner"),
            ("React.js", "Technical", "Component architecture, hooks, state management, SPA development", "Intermediate"),
            ("Node.js & Express", "Technical", "Backend REST APIs, event loop, middleware, authentication", "Intermediate"),
            ("Cloud Computing (AWS / Azure / GCP)", "Technical", "Virtual machines, serverless, S3, IAM, container deployment", "Intermediate"),
            ("Docker & Kubernetes", "Technical", "Containerization, microservices orchestration, CI/CD pipelines", "Advanced"),
            ("Cybersecurity & Ethical Hacking", "Technical", "Network protocols, vulnerability scanning, encryption, OWASP Top 10", "Advanced"),
            ("Git & Version Control", "Technical", "Branching workflows, pull requests, merge conflict resolution", "Beginner"),
            ("C / C++ Programming", "Technical", "Memory management, pointers, low-level systems programming", "Advanced"),
            ("Java & Spring Boot", "Technical", "Enterprise microservices, dependency injection, JVM tuning", "Intermediate"),
            ("Mobile App Dev (Flutter/React Native)", "Technical", "Cross-platform mobile UI, state management, device APIs", "Intermediate"),
            ("Natural Language Processing (NLP)", "Technical", "Tokenization, transformers, LLMs, LangChain, semantic search", "Advanced"),

            # Analytical Skills
            ("Data Analysis & Visualization", "Analytical", "Statistical analysis, Pandas, Tableau, PowerBI, exploratory data analysis", "Intermediate"),
            ("Probability & Statistics", "Analytical", "Hypothesis testing, distributions, regression modeling, inferential stats", "Intermediate"),
            ("Critical Problem Solving", "Analytical", "Root-cause analysis, structured deductive reasoning, logic puzzles", "Intermediate"),
            ("Business Acumen & Market Research", "Analytical", "Market trends, financial fundamentals, ROI evaluation, KPI tracking", "Intermediate"),
            ("Quantitative Aptitude", "Analytical", "Numerical analysis, algebra, arithmetic shortcuts, estimation", "Beginner"),

            # Design & Creative Skills
            ("UI/UX Design & Wireframing", "Design & Creative", "User journeys, Figma prototyping, typography, usability testing", "Intermediate"),
            ("Interaction & Motion Design", "Design & Creative", "Micro-interactions, animation curves, accessibility compliance", "Intermediate"),
            ("Graphic Design & Branding", "Design & Creative", "Color theory, visual balance, asset creation, Photoshop/Illustrator", "Beginner"),

            # Management & Soft Skills
            ("Agile & Scrum Methodologies", "Management & Soft Skills", "Sprint planning, user stories, Jira workflows, backlog grooming", "Beginner"),
            ("Communication & Technical Presentation", "Management & Soft Skills", "Articulating complex architectures, team pitching, report writing", "Beginner"),
            ("Leadership & Team Collaboration", "Management & Soft Skills", "Mentorship, conflict resolution, project delegation, cross-functional sync", "Intermediate"),
            ("Time Management & Prioritization", "Management & Soft Skills", "Eisenhower matrix, sprint delivery, deadline management", "Beginner")
        ]
        cursor.executemany("""
        INSERT INTO skills (name, category, description, difficulty_level)
        VALUES (?, ?, ?, ?)
        """, skills_data)

    # Seed Career Paths
    cursor.execute("SELECT COUNT(*) as count FROM career_paths")
    if cursor.fetchone()['count'] == 0:
        careers_data = [
            (
                "AI & Machine Learning Engineer",
                "Artificial Intelligence",
                "Design, develop, and deploy scalable machine learning models, neural networks, and generative AI systems to solve complex business automation challenges.",
                json.dumps({"Python": 0.95, "Machine Learning & Deep Learning": 0.95, "Data Structures & Algorithms": 0.85, "SQL & Relational Databases": 0.75, "Cloud Computing (AWS / Azure / GCP)": 0.7, "Natural Language Processing (NLP)": 0.8}),
                "₹ 12,00,000 - ₹ 32,00,000 / annum",
                "₹ 8.5 LPA - ₹ 45+ LPA",
                "Exponential Growth (+38% CAGR)",
                "B.Tech/B.E, BCA/MCA, or M.Tech in CS/IT/AI/Data Science",
                json.dumps(["Google", "Microsoft", "NVIDIA", "Amazon AWS", "OpenAI", "TCS AI Labs", "Flipkart"]),
                json.dumps([
                    {"phase": "Foundation", "duration": "Month 1-2", "focus": "Master Python, Linear Algebra, Multivariable Calculus, and Data Manipulation (NumPy, Pandas)."},
                    {"phase": "Core ML", "duration": "Month 3-4", "focus": "Learn Supervised/Unsupervised Algorithms (Scikit-Learn) and feature engineering techniques."},
                    {"phase": "Deep Learning & NLP", "duration": "Month 5-6", "focus": "Build Neural Networks, CNNs, Transformers, and LLMs using PyTorch/TensorFlow."},
                    {"phase": "MLOps & Deployment", "duration": "Month 7-8", "focus": "Deploy models as Dockerized REST APIs on AWS EC2/SageMaker and build CI/CD pipelines."}
                ]),
                "fas fa-brain"
            ),
            (
                "Full Stack Web Developer",
                "Software Engineering",
                "Architect and build comprehensive client-facing and server-side web applications with robust databases, responsive UI, and secure API integrations.",
                json.dumps({"JavaScript & Web Development": 0.95, "HTML5 & CSS3 Responsive Design": 0.9, "React.js": 0.85, "Node.js & Express": 0.85, "SQL & Relational Databases": 0.8, "Git & Version Control": 0.8}),
                "₹ 7,50,000 - ₹ 22,00,000 / annum",
                "₹ 5.5 LPA - ₹ 30 LPA",
                "High Steady Demand (+22% YoY)",
                "B.Tech/B.E, BCA, B.Sc Computer Science, or Coding Bootcamp",
                json.dumps(["Adobe", "Swiggy", "Zomato", "Accenture", "Infosys", "Razorpay", "Atlassian"]),
                json.dumps([
                    {"phase": "Frontend Fundamentals", "duration": "Month 1-2", "focus": "Master HTML5 semantic layout, modern CSS Grid/Flexbox, ES6+ JavaScript & DOM."},
                    {"phase": "Frontend Frameworks", "duration": "Month 3-4", "focus": "Build scalable SPAs using React.js, TailwindCSS/Vanilla CSS, and Redux/Context."},
                    {"phase": "Backend Architecture", "duration": "Month 5-6", "focus": "Develop REST APIs with Node.js/Express or Python Flask/FastAPI, SQL & MongoDB."},
                    {"phase": "Full Stack & DevOps", "duration": "Month 7-8", "focus": "Implement JWT authentication, Docker containers, Cloud deployment, and Unit testing."}
                ]),
                "fas fa-code"
            ),
            (
                "Data Scientist & Analyst",
                "Data & Analytics",
                "Transform raw multi-source enterprise data into actionable statistical insights, predictive analytics, and executive business intelligence dashboards.",
                json.dumps({"Python": 0.9, "Data Analysis & Visualization": 0.95, "Probability & Statistics": 0.9, "SQL & Relational Databases": 0.9, "Machine Learning & Deep Learning": 0.75, "Business Acumen & Market Research": 0.8}),
                "₹ 9,00,000 - ₹ 26,00,000 / annum",
                "₹ 6.5 LPA - ₹ 35 LPA",
                "Very High (+31% Growth)",
                "B.Tech/B.E, B.Sc Stats/Maths, BCA/MCA, MBA Data Analytics",
                json.dumps(["Mu Sigma", "Fractal Analytics", "Tiger Analytics", "Deloitte", "Goldman Sachs", "McKinsey"]),
                json.dumps([
                    {"phase": "Data Foundations", "duration": "Month 1-2", "focus": "Master Advanced SQL, Pandas, NumPy, and Statistical Hypothesis Testing."},
                    {"phase": "Visualization & BI", "duration": "Month 3-4", "focus": "Design executive interactive dashboards in Tableau, PowerBI, and Matplotlib/Seaborn."},
                    {"phase": "Predictive Modeling", "duration": "Month 5-6", "focus": "Apply Regression, Classification, Clustering, Time-Series forecasting in Scikit-Learn."},
                    {"phase": "Big Data & Storytelling", "duration": "Month 7-8", "focus": "Learn PySpark basics, A/B Testing design, and executive data presentation."}
                ]),
                "fas fa-chart-line"
            ),
            (
                "Cloud DevOps & Platform Engineer",
                "Cloud & Infrastructure",
                "Automate software delivery pipelines, ensure 99.99% high availability cloud infrastructure, container orchestration, and cloud security governance.",
                json.dumps({"Cloud Computing (AWS / Azure / GCP)": 0.95, "Docker & Kubernetes": 0.95, "Git & Version Control": 0.9, "Python": 0.75, "Agile & Scrum Methodologies": 0.7}),
                "₹ 10,00,000 - ₹ 28,00,000 / annum",
                "₹ 7.0 LPA - ₹ 38 LPA",
                "Exceptional Industry Demand (+29%)",
                "B.Tech/B.E in CS/IT/ECE or Systems Engineering background",
                json.dumps(["Amazon AWS", "Red Hat", "Cisco", "IBM", "Wipro", "Capgemini", "Oracle"]),
                json.dumps([
                    {"phase": "Linux & Networking", "duration": "Month 1-2", "focus": "Master Linux CLI, Bash Scripting, DNS, TCP/IP, VPCs, and Firewalls."},
                    {"phase": "Cloud Architecture", "duration": "Month 3-4", "focus": "Obtain AWS Certified Solutions Architect or Azure Administrator credentials."},
                    {"phase": "Containers & Orchestration", "duration": "Month 5-6", "focus": "Build Docker images, configure Kubernetes Pods, Services, and Helm Charts."},
                    {"phase": "CI/CD & IaC", "duration": "Month 7-8", "focus": "Implement Terraform Infrastructure-as-Code and GitHub Actions / Jenkins pipelines."}
                ]),
                "fas fa-cloud"
            ),
            (
                "Cybersecurity & Information Security Analyst",
                "Security & Networks",
                "Protect organizational networks, digital assets, and sensitive user data against sophisticated cyber threats, vulnerabilities, and ransomware attacks.",
                json.dumps({"Cybersecurity & Ethical Hacking": 0.95, "SQL & Relational Databases": 0.75, "Python": 0.75, "Critical Problem Solving": 0.9, "Cloud Computing (AWS / Azure / GCP)": 0.7}),
                "₹ 8,50,000 - ₹ 24,00,000 / annum",
                "₹ 6.0 LPA - ₹ 32 LPA",
                "Critical Global Shortage (+35% Growth)",
                "B.Tech/B.E, BCA/MCA, CEH / CompTIA Security+ certified",
                json.dumps(["Palo Alto Networks", "CrowdStrike", "KPMG", "EY Cyber", "PwC", "QuickHeal", "Cisco"]),
                json.dumps([
                    {"phase": "Networking & Fundamentals", "duration": "Month 1-2", "focus": "Master OSI Model, Wireshark packet capture, Linux security, and Cryptography."},
                    {"phase": "Vulnerability Assessment", "duration": "Month 3-4", "focus": "Conduct OWASP Top 10 web app testing with Burp Suite and Nessus vulnerability scans."},
                    {"phase": "SOC & Incident Response", "duration": "Month 5-6", "focus": "Analyze SIEM logs (Splunk/ELK), malware behavior, and incident triage protocols."},
                    {"phase": "Certifications & Defense", "duration": "Month 7-8", "focus": "Prepare for CompTIA Security+, CEH, or OSCP hands-on cyber ranges."}
                ]),
                "fas fa-shield-alt"
            ),
            (
                "UI/UX Product Designer",
                "Product & Design",
                "Research user behaviors, formulate intuitive user journeys, and craft visually striking design systems, wireframes, and prototypes for digital products.",
                json.dumps({"UI/UX Design & Wireframing": 0.95, "Interaction & Motion Design": 0.85, "Graphic Design & Branding": 0.8, "HTML5 & CSS3 Responsive Design": 0.75, "Communication & Technical Presentation": 0.85}),
                "₹ 6,50,000 - ₹ 19,00,000 / annum",
                "₹ 4.8 LPA - ₹ 26 LPA",
                "High Demand (+20% Growth)",
                "B.Des, B.Tech, BCA, Fine Arts, or certified Design portfolio",
                json.dumps(["CRED", "PhonePe", "Urban Company", "Google Design", "MakeMyTrip", "Zoho"]),
                json.dumps([
                    {"phase": "Design Foundations", "duration": "Month 1-2", "focus": "Master Design Thinking, User Research, Personas, and Figma mastery."},
                    {"phase": "Wireframing & UI Kit", "duration": "Month 3-4", "focus": "Build responsive design systems, Auto-layout, Typography scales, and Color harmony."},
                    {"phase": "Prototyping & Usability", "duration": "Month 5-6", "focus": "Create interactive high-fidelity clickable prototypes and run user testing sessions."},
                    {"phase": "Portfolio & Handoff", "duration": "Month 7-8", "focus": "Publish 3 comprehensive case studies on Behance/Portfolio and developer handoff."}
                ]),
                "fas fa-palette"
            ),
            (
                "Cross-Platform Mobile App Developer",
                "Software Engineering",
                "Build fluid, high-performance mobile applications for Android and iOS devices using modern frameworks like Flutter, React Native, and native APIs.",
                json.dumps({"Mobile App Dev (Flutter/React Native)": 0.95, "JavaScript & Web Development": 0.85, "Data Structures & Algorithms": 0.75, "Git & Version Control": 0.8, "UI/UX Design & Wireframing": 0.7}),
                "₹ 7,00,000 - ₹ 21,00,000 / annum",
                "₹ 5.0 LPA - ₹ 28 LPA",
                "Steady Expansion (+24%)",
                "B.Tech/B.E, BCA/MCA, B.Sc Computer Science",
                json.dumps(["Paytm", "Ola", "Jio Platforms", "Dream11", "Swiggy", "Postman"]),
                json.dumps([
                    {"phase": "Language & Basics", "duration": "Month 1-2", "focus": "Master Dart for Flutter or ES6+/TypeScript for React Native."},
                    {"phase": "Mobile UI & State", "duration": "Month 3-4", "focus": "Build responsive responsive mobile screens, handle Navigation & BLoC/Provider/Redux state."},
                    {"phase": "Device APIs & Backend", "duration": "Month 5-6", "focus": "Integrate Camera, GPS, Push Notifications (FCM), Offline SQLite & Firebase."},
                    {"phase": "Publishing & Optimization", "duration": "Month 7-8", "focus": "Publish apps on Google Play Store & Apple App Store with CI/CD Fastlane."}
                ]),
                "fas fa-mobile-alt"
            ),
            (
                "Technical Product Manager",
                "Management & Strategy",
                "Bridge technology, business strategy, and user experience to guide product vision, roadmaps, sprint execution, and market launch success.",
                json.dumps({"Agile & Scrum Methodologies": 0.95, "Business Acumen & Market Research": 0.9, "Leadership & Team Collaboration": 0.9, "Communication & Technical Presentation": 0.95, "Data Analysis & Visualization": 0.8}),
                "₹ 14,00,000 - ₹ 35,00,000 / annum",
                "₹ 10 LPA - ₹ 50+ LPA",
                "Prestigious High Demand (+26%)",
                "B.Tech + MBA or equivalent Product Track experience",
                json.dumps(["Microsoft", "Amazon", "Flipkart", "Atlassian", "Uber", "Intuit"]),
                json.dumps([
                    {"phase": "Product Fundamentals", "duration": "Month 1-2", "focus": "Master PRDs, User Story mapping, Wireframing, and Competitor Benchmark analysis."},
                    {"phase": "Metrics & Analytics", "duration": "Month 3-4", "focus": "Define North Star metrics, Funnel Conversion, Cohort Retention, and SQL querying."},
                    {"phase": "Agile Execution", "duration": "Month 5-6", "focus": "Manage Scrums, Backlog grooming, Jira boards, and Cross-functional stakeholder sync."},
                    {"phase": "Go-To-Market & Growth", "duration": "Month 7-8", "focus": "Execute Product launches, Pricing strategy, and A/B Testing experimentation."}
                ]),
                "fas fa-tasks"
            )
        ]
        cursor.executemany("""
        INSERT INTO career_paths (
            title, category, description, required_skills, average_salary_inr,
            salary_range, growth_outlook, education_requirement, top_companies, roadmap, icon
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        """, careers_data)

    # Seed Skill Assessment Questions
    cursor.execute("SELECT COUNT(*) as count FROM skill_assessments")
    if cursor.fetchone()['count'] == 0:
        questions = [
            (
                "Technical",
                "Python",
                "What is the output of `[i**2 for i in range(5) if i % 2 == 0]` in Python?",
                "[0, 1, 4, 9, 16]",
                "[0, 4, 16]",
                "[1, 9]",
                "[4, 16]",
                "B",
                "The list comprehension evaluates even numbers from 0 to 4 (0, 2, 4) and squares them: 0^2=0, 2^2=4, 4^2=16.",
                "Beginner"
            ),
            (
                "Technical",
                "Data Structures & Algorithms",
                "What is the average time complexity of searching an element in a balanced Binary Search Tree (BST)?",
                "O(1)",
                "O(N)",
                "O(log N)",
                "O(N log N)",
                "C",
                "In a balanced BST with N nodes, each search comparison cuts the search space in half, resulting in O(log N) time complexity.",
                "Intermediate"
            ),
            (
                "Technical",
                "SQL & Relational Databases",
                "Which SQL clause is used to filter records resulting from an aggregate function like `COUNT()` or `AVG()`?",
                "WHERE",
                "HAVING",
                "GROUP BY",
                "ORDER BY",
                "B",
                "`HAVING` filters aggregated group records, whereas `WHERE` filters individual rows before aggregation.",
                "Intermediate"
            ),
            (
                "Technical",
                "Machine Learning & Deep Learning",
                "Which technique is primarily used to prevent overfitting in deep neural networks?",
                "Increasing the learning rate",
                "Dropout and L2 Regularization",
                "Removing activation functions",
                "Increasing batch size to infinity",
                "B",
                "Dropout randomly deactivates neurons during training, forcing the network to learn redundant and robust representations, mitigating overfitting.",
                "Advanced"
            ),
            (
                "Technical",
                "JavaScript & Web Development",
                "What will `console.log(typeof NaN)` return in JavaScript?",
                "'nan'",
                "'undefined'",
                "'number'",
                "'object'",
                "C",
                "In JavaScript, NaN (Not-a-Number) is a special numeric value conforming to IEEE-754 floating-point standard, so `typeof NaN` is 'number'.",
                "Beginner"
            ),
            (
                "Analytical",
                "Probability & Statistics",
                "If two fair six-sided dice are rolled simultaneously, what is the probability that the sum of the numbers is 7?",
                "1/12",
                "1/6",
                "7/36",
                "5/36",
                "B",
                "There are 6 winning combinations ((1,6),(2,5),(3,4),(4,3),(5,2),(6,1)) out of 36 total outcomes: 6/36 = 1/6.",
                "Intermediate"
            ),
            (
                "Technical",
                "Cloud Computing (AWS / Azure / GCP)",
                "Which AWS service provides scalable object storage accessible over the internet via HTTP REST API?",
                "Amazon EC2",
                "Amazon RDS",
                "Amazon S3",
                "Amazon EBS",
                "C",
                "Amazon Simple Storage Service (S3) is an industry-standard scalable cloud object storage system.",
                "Beginner"
            ),
            (
                "Technical",
                "Cybersecurity & Ethical Hacking",
                "What type of attack involves injecting malicious SQL queries into database inputs to manipulate database execution?",
                "Cross-Site Scripting (XSS)",
                "SQL Injection (SQLi)",
                "Man-in-the-Middle (MitM)",
                "DDoS Attack",
                "B",
                "SQL Injection occurs when untrusted user input is directly concatenated into SQL queries without parameterized binding.",
                "Intermediate"
            ),
            (
                "Design & Creative",
                "UI/UX Design & Wireframing",
                "Which usability principle states that the time required to rapidly move to a target area is a function of the target's distance and width?",
                "Hick's Law",
                "Fitts's Law",
                "Miller's Rule",
                "Jakob's Law",
                "B",
                "Fitts's Law models human movement to target elements; larger and closer buttons are significantly faster and easier to click.",
                "Intermediate"
            ),
            (
                "Management & Soft Skills",
                "Agile & Scrum Methodologies",
                "In Scrum, what is the primary purpose of the Daily Standup meeting?",
                "Detailed architecture design review",
                "Performance review of individual team members",
                "15-minute sync to align on daily goals and identify blockers",
                "Long-term budget estimation",
                "C",
                "The Daily Standup is a timeboxed 15-minute sync where developers share what they accomplished, what they will do today, and any impediments/blockers.",
                "Beginner"
            )
        ]
        cursor.executemany("""
        INSERT INTO skill_assessments (
            category, skill_tagged, question_text, option_a, option_b, option_c, option_d,
            correct_option, explanation, difficulty
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        """, questions)

    # Seed Career Assessment Questions (Psychometric / RIASEC)
    cursor.execute("SELECT COUNT(*) as count FROM career_assessments")
    if cursor.fetchone()['count'] == 0:
        career_questions = [
            (
                "Investigative",
                "When encountering a complex technical problem or mysterious bug, what is your natural instinct?",
                "A system in production is failing with unexpected outputs.",
                json.dumps([
                    {"label": "Dive deep into logs, formulate hypotheses, analyze mathematical data, and test root causes systematically.", "traits": {"Investigative": 5, "Analytical": 5, "Technical": 4}},
                    {"label": "Quickly write a creative workaround or script to get it working and test user experience.", "traits": {"Artistic": 4, "Technical": 3}},
                    {"label": "Organize a team sync, assign roles, and coordinate resolution across departments.", "traits": {"Enterprising": 5, "Leadership": 4}},
                    {"label": "Review standard operating procedures, documentation, and verify security protocols.", "traits": {"Conventional": 5, "Security": 4}}
                ])
            ),
            (
                "Work Style",
                "What kind of daily work environment stimulates your best performance?",
                "Choosing your ideal project and team setting.",
                json.dumps([
                    {"label": "Building smart algorithms, training models, and researching state-of-the-art tech.", "traits": {"Investigative": 5, "AI_ML": 5}},
                    {"label": "Designing intuitive interfaces, choosing aesthetic color palettes, and creating visual delight.", "traits": {"Artistic": 5, "Design": 5}},
                    {"label": "Architecting robust cloud infrastructure, networks, and automated CI/CD pipelines.", "traits": {"Realistic": 4, "DevOps": 5, "Technical": 5}},
                    {"label": "Leading product roadmap decisions, negotiating timelines, and presenting to stakeholders.", "traits": {"Enterprising": 5, "Management": 5}}
                ])
            ),
            (
                "Artistic & Design",
                "How do you approach creating a new digital product or application?",
                "Starting a fresh project from scratch.",
                json.dumps([
                    {"label": "Focus first on the user experience, typography, micro-interactions, and visual harmony.", "traits": {"Artistic": 5, "Design": 5}},
                    {"label": "Focus on database schemas, backend APIs, speed, and clean code architecture.", "traits": {"Realistic": 4, "FullStack": 5, "Technical": 4}},
                    {"label": "Focus on the data pipeline, statistical predictive capabilities, and machine learning models.", "traits": {"Investigative": 5, "DataScience": 5}},
                    {"label": "Focus on market viability, monetisation strategy, user acquisition, and business metrics.", "traits": {"Enterprising": 5, "Product": 5}}
                ])
            ),
            (
                "Realistic & Technical",
                "Which activity sounds most rewarding for a weekend hackathon or side project?",
                "Choosing your weekend project topic.",
                json.dumps([
                    {"label": "Training a custom neural network or fine-tuning an AI language model to automate tasks.", "traits": {"Investigative": 5, "AI_ML": 5}},
                    {"label": "Deploying a high-availability distributed server cluster on AWS with Docker and Terraform.", "traits": {"Realistic": 5, "DevOps": 5}},
                    {"label": "Conducting a security penetration test, capturing flags (CTF), and patching vulnerabilities.", "traits": {"Investigative": 4, "Security": 5}},
                    {"label": "Building a full-stack e-commerce web/mobile application with interactive payments.", "traits": {"Realistic": 4, "FullStack": 5}}
                ])
            ),
            (
                "Enterprising & Leadership",
                "When working in a team project, which role do you naturally gravitate towards?",
                "Group project milestone delivery.",
                json.dumps([
                    {"label": "The Team Lead: Defining the vision, removing blockers, and communicating progress.", "traits": {"Enterprising": 5, "Leadership": 5}},
                    {"label": "The Core Architect: Writing the most critical backend code and solving hard algorithms.", "traits": {"Investigative": 5, "Technical": 5}},
                    {"label": "The Creative Lead: Crafting the UI design, slides, and public demo presentation.", "traits": {"Artistic": 5, "Design": 4}},
                    {"label": "The Quality & Security Champion: Rigorously testing edge cases, validating data, and securing endpoints.", "traits": {"Conventional": 5, "Security": 4}}
                ])
            )
        ]
        cursor.executemany("""
        INSERT INTO career_assessments (dimension, question_text, scenario, options_json)
        VALUES (?, ?, ?, ?)
        """, career_questions)

    # Seed Sample Contact Messages
    cursor.execute("SELECT COUNT(*) as count FROM contact_messages")
    if cursor.fetchone()['count'] == 0:
        sample_messages = [
            (
                None,
                "Priya Patel",
                "priya.patel@example.com",
                "+91 9823456789",
                "Guidance on Transitioning to Data Science",
                "Hello CareerCompass Team, I am currently a 3rd-year student in Electronics and want to know which specific mathematics and Python courses I should complete to prepare for Data Science placements.",
                "unread"
            ),
            (
                None,
                "Rohan Verma",
                "rohan.v@example.com",
                "+91 9712345670",
                "Cloud DevOps Certification Inquiry",
                "Is AWS Solutions Architect Associate sufficient for landing a fresher DevOps role, or should I also complete Kubernetes CKA certification?",
                "read"
            )
        ]
        cursor.executemany("""
        INSERT INTO contact_messages (user_id, name, email, phone, subject, message, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        """, sample_messages)

    conn.commit()

if __name__ == '__main__':
    print(f"Initializing database at: {DB_PATH}")
    init_db()
    print("Database initialized and seeded successfully!")
