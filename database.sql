-- CareerCompass MySQL Schema & Initial Data Dump
-- Compatible with XAMPP MySQL, phpMyAdmin, and InfinityFree

CREATE DATABASE IF NOT EXISTS `careercompass_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `careercompass_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
  `phone` VARCHAR(30) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT 'default_avatar.png',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Student Profiles Table
CREATE TABLE IF NOT EXISTS `student_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `education_level` VARCHAR(100) DEFAULT NULL,
  `field_of_study` VARCHAR(150) DEFAULT NULL,
  `institution` VARCHAR(200) DEFAULT NULL,
  `cgpa_percentage` DECIMAL(5,2) DEFAULT NULL,
  `graduation_year` INT DEFAULT NULL,
  `dream_role` VARCHAR(150) DEFAULT NULL,
  `preferred_industry` VARCHAR(150) DEFAULT NULL,
  `preferred_work_env` VARCHAR(50) DEFAULT 'Hybrid',
  `location_pref` VARCHAR(150) DEFAULT NULL,
  `experience_level` VARCHAR(50) DEFAULT 'Fresher',
  `bio` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Skills Master Table
CREATE TABLE IF NOT EXISTS `skills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL UNIQUE,
  `category` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `difficulty_level` VARCHAR(50) DEFAULT 'Intermediate',
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Student Skills Ratings Table
CREATE TABLE IF NOT EXISTS `student_skills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `skill_id` INT NOT NULL,
  `proficiency_level` INT NOT NULL CHECK (`proficiency_level` BETWEEN 1 AND 5),
  `assessed_score` DECIMAL(5,2) DEFAULT 0.00,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `user_skill_unique` (`user_id`, `skill_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Career Paths Table
CREATE TABLE IF NOT EXISTS `career_paths` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL UNIQUE,
  `category` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `required_skills` LONGTEXT NOT NULL,
  `average_salary_inr` VARCHAR(100) NOT NULL,
  `salary_range` VARCHAR(100) NOT NULL,
  `growth_outlook` VARCHAR(100) NOT NULL,
  `education_requirement` TEXT NOT NULL,
  `top_companies` LONGTEXT NOT NULL,
  `roadmap` LONGTEXT NOT NULL,
  `icon` VARCHAR(100) DEFAULT 'fas fa-briefcase',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Skill Assessments Table
CREATE TABLE IF NOT EXISTS `skill_assessments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category` VARCHAR(100) NOT NULL,
  `skill_tagged` VARCHAR(100) NOT NULL,
  `question_text` TEXT NOT NULL,
  `option_a` TEXT NOT NULL,
  `option_b` TEXT NOT NULL,
  `option_c` TEXT NOT NULL,
  `option_d` TEXT NOT NULL,
  `correct_option` CHAR(1) NOT NULL,
  `explanation` TEXT DEFAULT NULL,
  `difficulty` VARCHAR(50) DEFAULT 'Intermediate',
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Career RIASEC Psychometric Assessments Table
CREATE TABLE IF NOT EXISTS `career_assessments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `dimension` VARCHAR(100) NOT NULL,
  `question_text` TEXT NOT NULL,
  `scenario` TEXT DEFAULT NULL,
  `options_json` LONGTEXT NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Student Assessment Responses Table
CREATE TABLE IF NOT EXISTS `student_assessment_responses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `assessment_type` VARCHAR(50) NOT NULL,
  `score` DECIMAL(5,2) NOT NULL,
  `total_questions` INT NOT NULL,
  `correct_answers` INT DEFAULT 0,
  `response_data` LONGTEXT DEFAULT NULL,
  `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Recommendation Records Table
CREATE TABLE IF NOT EXISTS `recommendation_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `student_name` VARCHAR(150) NOT NULL,
  `top_career_id` INT NOT NULL,
  `top_career_title` VARCHAR(150) NOT NULL,
  `match_percentage` DECIMAL(5,2) NOT NULL,
  `secondary_careers` LONGTEXT DEFAULT NULL,
  `reasoning` LONGTEXT DEFAULT NULL,
  `matched_skills` LONGTEXT DEFAULT NULL,
  `missing_skills` LONGTEXT DEFAULT NULL,
  `salary_forecast` VARCHAR(100) DEFAULT NULL,
  `learning_roadmap` LONGTEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`top_career_id`) REFERENCES `career_paths` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Contact Messages Table
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('unread', 'read', 'replied') DEFAULT 'unread',
  `admin_reply` TEXT DEFAULT NULL,
  `replied_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
