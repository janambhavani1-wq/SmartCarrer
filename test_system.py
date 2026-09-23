"""
CareerCompass – Automated Test Suite
Verifies Database Schema, User Authentication, AI Engine, Admin CRUD, and Contact Message Storage.
"""

import unittest
import json
import os
from database import init_db, get_db
import models
import ai_engine

class TestCareerCompass(unittest.TestCase):

    @classmethod
    def setUpClass(cls):
        """Initialize database before running tests."""
        init_db()

    def test_01_database_initialization(self):
        """Test if tables are initialized and seeded properly."""
        conn = get_db()
        cursor = conn.cursor()
        
        cursor.execute("SELECT COUNT(*) as count FROM users")
        user_count = cursor.fetchone()['count']
        self.assertGreaterEqual(user_count, 2, "Database should have seeded admin and demo student.")

        cursor.execute("SELECT COUNT(*) as count FROM career_paths")
        career_count = cursor.fetchone()['count']
        self.assertGreaterEqual(career_count, 5, "Database should have seeded career paths.")

        cursor.execute("SELECT COUNT(*) as count FROM skills")
        skill_count = cursor.fetchone()['count']
        self.assertGreaterEqual(skill_count, 10, "Database should have seeded skills.")
        
        conn.close()

    def test_02_authentication(self):
        """Test admin and student authentication."""
        admin = models.authenticate_user("admin@careercompass.com", "Admin@12345")
        self.assertIsNotNone(admin, "Admin should authenticate with default password.")
        self.assertEqual(admin['role'], 'admin')

        student = models.authenticate_user("student@careercompass.com", "Student@12345")
        self.assertIsNotNone(student, "Demo student should authenticate.")
        self.assertEqual(student['role'], 'student')

        invalid = models.authenticate_user("admin@careercompass.com", "WrongPass")
        self.assertIsNone(invalid, "Invalid password should fail authentication.")

    def test_03_ai_recommendation_engine(self):
        """Test AI recommendation computation and output structure."""
        student = models.authenticate_user("student@careercompass.com", "Student@12345")
        user_id = student['id']

        # Ensure student has some skills rated
        skills = models.get_all_skills(active_only=True)
        rating_map = {str(skills[0]['id']): 5, str(skills[1]['id']): 4, str(skills[2]['id']): 4}
        models.save_student_skills(user_id, rating_map)

        # Run AI recommendation
        rec = ai_engine.compute_career_recommendations(user_id)
        self.assertIsNotNone(rec, "AI recommendation output should not be None.")
        self.assertIn('top_career', rec, "Result must contain 'top_career'.")
        self.assertGreaterEqual(rec['top_career']['match_percentage'], 50.0, "Match score should be calculated.")
        self.assertIsInstance(rec['top_career']['reasoning'], list, "Reasoning should be a list of explanations.")

    def test_04_contact_messages_storage(self):
        """Test contact inquiry creation and persistence in database."""
        test_email = "test.applicant@college.edu"
        test_subject = "AI Recommendation Inquiry"
        test_message = "I would like guidance on transitioning to Machine Learning."

        msg_id = models.create_contact_message(
            name="Test Candidate",
            email=test_email,
            subject=test_subject,
            message=test_message,
            phone="+91 99999 88888"
        )
        self.assertIsNotNone(msg_id, "Contact message ID should be returned.")

        # Verify message retrieved from database
        messages = models.get_all_contact_messages()
        found = any(m['id'] == msg_id and m['email'] == test_email for m in messages)
        self.assertTrue(found, "Saved contact message must be retrievable from database.")

        # Update message status
        models.update_message_status(msg_id, 'replied', admin_reply="Recommended completing Python & ML quiz.")
        updated_msgs = models.get_all_contact_messages(status='replied')
        updated_found = any(m['id'] == msg_id for m in updated_msgs)
        self.assertTrue(updated_found, "Message status should update to replied.")

        # Cleanup test message
        models.delete_contact_message(msg_id)

    def test_05_admin_dashboard_stats(self):
        """Test admin dashboard statistics aggregation."""
        stats = models.get_admin_dashboard_stats()
        self.assertIn('total_students', stats)
        self.assertIn('total_careers', stats)
        self.assertIn('total_skills', stats)
        self.assertIn('total_recommendations', stats)
        self.assertIn('unread_messages', stats)

if __name__ == '__main__':
    unittest.main()
