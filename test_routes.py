"""
CareerCompass – Route Smoke Test
Validates all public, student, and admin endpoints return HTTP 200 / 302 without Jinja syntax errors.
"""

import unittest
from app import app
from database import init_db

class RouteSmokeTestCase(unittest.TestCase):
    def setUp(self):
        init_db()
        self.client = app.test_client()
        self.client.testing = True

    def test_public_routes(self):
        """Verify public landing and exploration pages."""
        # 1. Landing Page
        res = self.client.get('/')
        self.assertEqual(res.status_code, 200)
        self.assertIn(b'CareerCompass', res.data)

        # 2. Careers list
        res = self.client.get('/careers')
        self.assertEqual(res.status_code, 200)

        # 3. Career detail (ID: 1)
        res = self.client.get('/career/1')
        self.assertEqual(res.status_code, 200)

        # 4. Login page
        res = self.client.get('/login')
        self.assertEqual(res.status_code, 200)

        # 5. Register page
        res = self.client.get('/register')
        self.assertEqual(res.status_code, 200)

    def test_contact_form_submission(self):
        """Verify contact form stores message to database and redirects."""
        res = self.client.post('/contact', data={
            'name': 'Pooja Nair',
            'email': 'pooja.nair@college.edu',
            'phone': '+91 98765 00000',
            'subject': 'AI Roadmap Inquiry',
            'message': 'Can I take Machine Learning without prior C++ knowledge?'
        }, follow_redirects=True)
        self.assertEqual(res.status_code, 200)
        self.assertIn(b'Thank you! Your message has been recorded', res.data)

    def test_student_flow(self):
        """Verify student login, profile, skills, and recommendations."""
        # Login as student
        res = self.client.post('/login', data={
            'email': 'student@careercompass.com',
            'password': 'Student@12345'
        }, follow_redirects=True)
        self.assertEqual(res.status_code, 200)
        self.assertIn(b'Student Command Center', res.data)

        # Student Dashboard
        res = self.client.get('/student/dashboard')
        self.assertEqual(res.status_code, 200)

        # Student Profile
        res = self.client.get('/student/profile')
        self.assertEqual(res.status_code, 200)

        # Student Skills
        res = self.client.get('/student/skills')
        self.assertEqual(res.status_code, 200)

        # Student Skill Assessment
        res = self.client.get('/student/skill-assessment')
        self.assertEqual(res.status_code, 200)

        # Student Career Assessment
        res = self.client.get('/student/career-assessment')
        self.assertEqual(res.status_code, 200)

        # Student Recommendations (follows redirect to auto-compute if not yet computed)
        res = self.client.get('/student/recommendations', follow_redirects=True)
        self.assertEqual(res.status_code, 200)
        self.assertIn(b'Personalized AI Career Roadmap', res.data)

    def test_admin_flow(self):
        """Verify admin login, dashboard, student management, careers, skills, recommendations, messages."""
        # Login as admin
        res = self.client.post('/login', data={
            'email': 'admin@careercompass.com',
            'password': 'Admin@12345'
        }, follow_redirects=True)
        self.assertEqual(res.status_code, 200)
        self.assertIn(b'Administrator Analytics Dashboard', res.data)

        # Admin Dashboard
        res = self.client.get('/admin/dashboard')
        self.assertEqual(res.status_code, 200)

        # Admin Students
        res = self.client.get('/admin/students')
        self.assertEqual(res.status_code, 200)

        # Admin Careers
        res = self.client.get('/admin/careers')
        self.assertEqual(res.status_code, 200)

        # Admin Skills
        res = self.client.get('/admin/skills')
        self.assertEqual(res.status_code, 200)

        # Admin Recommendations
        res = self.client.get('/admin/recommendations')
        self.assertEqual(res.status_code, 200)

        # Admin Messages
        res = self.client.get('/admin/messages')
        self.assertEqual(res.status_code, 200)

if __name__ == '__main__':
    unittest.main()
