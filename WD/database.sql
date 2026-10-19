-- ============================================================================
-- CHARUSAT - ITUE203: Web Development Frameworks
-- StudentHub Portal Database Schema & Seed Data
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `studenthub_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `studenthub_db`;

-- ----------------------------------------------------------------------------
-- Table structure for `users` (Practical 9, 10 Authentication & RBAC)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `event_categories`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'student') NOT NULL DEFAULT 'student',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `remember_token` VARCHAR(64) NULL DEFAULT NULL,
  `token_expiry` DATETIME NULL DEFAULT NULL,
  `last_login` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table structure for `students` (Practical 8, 11 Student Management CRUD)
-- ----------------------------------------------------------------------------
CREATE TABLE `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL UNIQUE,
  `student_id` VARCHAR(20) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(15) NOT NULL,
  `department` VARCHAR(80) NOT NULL DEFAULT 'Computer Engineering',
  `course` VARCHAR(80) NOT NULL,
  `semester` INT NOT NULL DEFAULT 3,
  `academic_year` VARCHAR(20) NOT NULL DEFAULT '2nd Year',
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL DEFAULT 'Male',
  `cgpa` DECIMAL(3, 2) NOT NULL DEFAULT 8.00,
  `status` ENUM('Active', 'Inactive', 'Deleted') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_student_dept` (`department`),
  INDEX `idx_student_course` (`course`),
  INDEX `idx_student_status` (`status`),
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table structure for `event_categories`
-- ----------------------------------------------------------------------------
CREATE TABLE `event_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table structure for `events` (Practical 8, 12 Event Management CRUD & Poster Upload)
-- ----------------------------------------------------------------------------
CREATE TABLE `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `description` TEXT NOT NULL,
  `event_date` DATE NOT NULL,
  `event_time` TIME NOT NULL,
  `venue` VARCHAR(100) NOT NULL,
  `max_participants` INT NOT NULL DEFAULT 100,
  `organizer` VARCHAR(100) NOT NULL DEFAULT 'CHARUSAT Student Council',
  `poster_path` VARCHAR(255) NULL DEFAULT NULL,
  `status` ENUM('Draft', 'Upcoming', 'Ongoing', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Upcoming',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_events_category` (`category_id`),
  INDEX `idx_events_status` (`status`),
  INDEX `idx_events_date` (`event_date`),
  CONSTRAINT `fk_events_category` FOREIGN KEY (`category_id`)
    REFERENCES `event_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table structure for `registrations` (Practical 8 ER Model relationships)
-- ----------------------------------------------------------------------------
CREATE TABLE `registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `attendance_status` ENUM('Registered', 'Attended', 'Absent', 'Cancelled') DEFAULT 'Registered',
  `notes` VARCHAR(255) NULL,
  UNIQUE KEY `unique_student_event` (`event_id`, `student_id`),
  INDEX `idx_reg_event` (`event_id`),
  INDEX `idx_reg_student` (`student_id`),
  CONSTRAINT `fk_registrations_event` FOREIGN KEY (`event_id`)
    REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_registrations_student` FOREIGN KEY (`student_id`)
    REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table structure for `audit_logs` (Audit logging extension)
-- ----------------------------------------------------------------------------
CREATE TABLE `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_audit_user` (`user_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA
-- ============================================================================

-- 1. Insert Default Users (Admin: Admin@12345 | Student: Student@12345)
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `is_active`) VALUES
(1, 'admin', 'admin@charusat.ac.in', '$2y$10$3G.yIaprQOhCIfChmZt7FOB1iF2TRh9pn6UGAdbQ1D7NdpOEmBOAy', 'System Administrator', 'admin', 1),
(2, 'smitpopat', 'smit.popat@charusat.edu.in', '$2y$10$EX2ykOR..h5rrcCos3IjGefPi5nQymGiscW6WiHuSaHci5wEJ7s/e', 'Smit Popat', 'student', 1),
(3, 'priyashah', 'priya.shah@charusat.edu.in', '$2y$10$EX2ykOR..h5rrcCos3IjGefPi5nQymGiscW6WiHuSaHci5wEJ7s/e', 'Priya Shah', 'student', 1);

-- 2. Insert Event Categories
INSERT INTO `event_categories` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Technical', 'technical', 'Coding competitions, hackathons, and tech seminars'),
(2, 'Cultural', 'cultural', 'Music, dance, drama festivals, and campus celebrations'),
(3, 'Workshop', 'workshop', 'Hands-on skill building and industry expert masterclasses'),
(4, 'Sports', 'sports', 'Inter-department and university athletic tournaments'),
(5, 'Academic', 'academic', 'Research symposia, project expos, and guest lectures');

-- 3. Insert Minimum 20 Seeded Students (Satisfies Practical 11 requirement)
INSERT INTO `students` (`id`, `user_id`, `student_id`, `full_name`, `email`, `phone`, `department`, `course`, `semester`, `academic_year`, `gender`, `cgpa`, `status`) VALUES
(1, 2, 'D26DCE173', 'Smit Popat', 'smit.popat@charusat.edu.in', '9876543210', 'Computer Engineering', 'B.Tech Computer Engineering', 3, '2nd Year', 'Male', 9.45, 'Active'),
(2, 3, 'D26DCE102', 'Priya Shah', 'priya.shah@charusat.edu.in', '9823456781', 'Computer Engineering', 'B.Tech Computer Engineering', 3, '2nd Year', 'Female', 9.15, 'Active'),
(3, NULL, 'D26DIT103', 'Aarav Patel', 'aarav.patel@charusat.edu.in', '9898123456', 'Information Technology', 'B.Tech IT Engineering', 3, '2nd Year', 'Male', 8.85, 'Active'),
(4, NULL, 'D26DIT104', 'Ananya Desai', 'ananya.desai@charusat.edu.in', '9879012345', 'Information Technology', 'B.Tech IT Engineering', 3, '2nd Year', 'Female', 9.30, 'Active'),
(5, NULL, 'D26DEC105', 'Rohan Mehta', 'rohan.mehta@charusat.edu.in', '9723456789', 'Electronics & Comm.', 'B.Tech Electrical', 3, '2nd Year', 'Male', 8.40, 'Active'),
(6, NULL, 'D26DEC106', 'Diya Joshi', 'diya.joshi@charusat.edu.in', '9825123456', 'Electronics & Comm.', 'B.Tech Electrical', 3, '2nd Year', 'Female', 8.75, 'Active'),
(7, NULL, 'D26DME107', 'Kavya Trivedi', 'kavya.trivedi@charusat.edu.in', '9898765432', 'Mechanical Engineering', 'B.Tech Mechanical', 5, '3rd Year', 'Female', 8.60, 'Active'),
(8, NULL, 'D26DME108', 'Dev Vora', 'dev.vora@charusat.edu.in', '9712345678', 'Mechanical Engineering', 'B.Tech Mechanical', 5, '3rd Year', 'Male', 7.95, 'Active'),
(9, NULL, 'D26DCL109', 'Isha Dave', 'isha.dave@charusat.edu.in', '9824098765', 'Civil Engineering', 'B.Tech Civil', 5, '3rd Year', 'Female', 8.20, 'Active'),
(10, NULL, 'D26DCL110', 'Harsh Parikh', 'harsh.parikh@charusat.edu.in', '9876123490', 'Civil Engineering', 'B.Tech Civil', 5, '3rd Year', 'Male', 7.80, 'Active'),
(11, NULL, 'D26DCE111', 'Tanvi Bhatia', 'tanvi.bhatia@charusat.edu.in', '9898011223', 'Computer Engineering', 'B.Tech Computer Engineering', 3, '2nd Year', 'Female', 9.50, 'Active'),
(12, NULL, 'D26DCE112', 'Yash Solanki', 'yash.solanki@charusat.edu.in', '9724112233', 'Computer Engineering', 'B.Tech Computer Engineering', 3, '2nd Year', 'Male', 8.10, 'Active'),
(13, NULL, 'D26DIT113', 'Riddhi Vyas', 'riddhi.vyas@charusat.edu.in', '9825223344', 'Information Technology', 'B.Tech IT Engineering', 3, '2nd Year', 'Female', 8.90, 'Active'),
(14, NULL, 'D26DIT114', 'Manan Shah', 'manan.shah@charusat.edu.in', '9898334455', 'Information Technology', 'B.Tech IT Engineering', 1, '1st Year', 'Male', 8.45, 'Active'),
(15, NULL, 'D26DEC115', 'Nidhi Chauhan', 'nidhi.chauhan@charusat.edu.in', '9712445566', 'Electronics & Comm.', 'B.Tech Electrical', 1, '1st Year', 'Female', 8.70, 'Active'),
(16, NULL, 'D26DME116', 'Karan Makwana', 'karan.makwana@charusat.edu.in', '9824556677', 'Mechanical Engineering', 'B.Tech Mechanical', 7, '4th Year', 'Male', 8.15, 'Active'),
(17, NULL, 'D26DCL117', 'Sneha Parmar', 'sneha.parmar@charusat.edu.in', '9876667788', 'Civil Engineering', 'B.Tech Civil', 7, '4th Year', 'Female', 8.35, 'Active'),
(18, NULL, 'D26DCE118', 'Vivek Panchal', 'vivek.panchal@charusat.edu.in', '9898778899', 'Computer Engineering', 'B.Tech Computer Engineering', 7, '4th Year', 'Male', 9.20, 'Active'),
(19, NULL, 'D26DIT119', 'Khushi Gandhi', 'khushi.gandhi@charusat.edu.in', '9724889900', 'Information Technology', 'B.Tech IT Engineering', 1, '1st Year', 'Female', 8.65, 'Active'),
(20, NULL, 'D26DEC120', 'Aditya Prajapati', 'aditya.prajapati@charusat.edu.in', '9825990011', 'Electronics & Comm.', 'B.Tech Electrical', 1, '1st Year', 'Male', 7.90, 'Active'),
(21, NULL, 'D26DCE121', 'Bhavya Doshi', 'bhavya.doshi@charusat.edu.in', '9898112244', 'Computer Engineering', 'B.Tech Computer Engineering', 5, '3rd Year', 'Male', 8.55, 'Inactive');

-- 4. Insert Seed Campus Events (Practical 12)
INSERT INTO `events` (`id`, `category_id`, `title`, `slug`, `description`, `event_date`, `event_time`, `venue`, `max_participants`, `organizer`, `poster_path`, `status`) VALUES
(1, 1, 'HackCHARUSAT 2026', 'hackcharusat-2026', 'A 36-hour annual national hackathon bringing top student coders to build innovative AI and Web solutions.', '2026-11-15', '09:00:00', 'FTE Central Computer Center', 200, 'CSPIT CE/IT Department', NULL, 'Upcoming'),
(2, 3, 'Full-Stack Web Dev Bootcamp', 'web-dev-bootcamp', 'Interactive hands-on masterclass on modern responsive frontend frameworks, PHP 8 backend, and MySQL database modeling.', '2026-10-25', '10:00:00', 'Seminar Hall 3, FTE', 80, 'Web Dev Club CHARUSAT', NULL, 'Upcoming'),
(3, 2, 'Spoural Cultural Fest 2026', 'spoural-2026', 'The annual university cultural celebration with music, dance, battle of bands, and art exhibitions.', '2026-12-05', '16:00:00', 'University Open Air Amphitheatre', 1500, 'Student Welfare Board', NULL, 'Upcoming'),
(4, 4, 'CHARUSAT Inter-College Cricket Trophy', 'cricket-trophy-2026', 'Premier athletic showdown featuring 16 department teams competing for the annual championship banner.', '2026-11-20', '08:00:00', 'University Sports Ground', 250, 'Physical Education Council', NULL, 'Upcoming'),
(5, 5, 'AI & Robotics Research Symposium', 'ai-robotics-symposium', 'Keynotes by renowned machine learning scientists, project poster presentations, and live robotics demonstrations.', '2026-11-02', '11:00:00', 'Auditorium 1, FTE Building', 120, 'IEEE Student Branch', NULL, 'Upcoming'),
(6, 1, 'CyberSec Capture The Flag (CTF)', 'cybersec-ctf', 'Intense competitive ethical hacking challenge testing web vulnerability exploitation, cryptography, and forensics.', '2026-10-30', '13:30:00', 'Network Security Lab, IT Dept', 60, 'Null Chapter CHARUSAT', NULL, 'Upcoming');

-- 5. Insert Sample Registrations (Across multiple months to feed Practical 14 Analytics Chart)
INSERT INTO `registrations` (`event_id`, `student_id`, `registration_date`, `attendance_status`, `notes`) VALUES
(1, 1, '2026-07-15 10:30:00', 'Attended', 'Team Lead - Web track'),
(2, 1, '2026-07-28 14:15:00', 'Attended', 'Interested in PHP 8 features'),
(3, 2, '2026-08-05 09:20:00', 'Attended', 'Classical dance performance'),
(4, 3, '2026-08-18 16:45:00', 'Attended', 'Athletics participant'),
(1, 2, '2026-08-25 11:10:00', 'Attended', 'Team member - Frontend'),
(2, 4, '2026-09-02 12:00:00', 'Registered', 'Web workshop attendee'),
(5, 5, '2026-09-12 15:30:00', 'Registered', 'Robotics symposium poster'),
(6, 6, '2026-09-22 17:00:00', 'Registered', 'CTF team member'),
(1, 7, '2026-10-02 08:45:00', 'Registered', 'Hackathon junior participant'),
(2, 8, '2026-10-06 14:00:00', 'Registered', 'PHP backend enthusiast'),
(3, 9, '2026-10-10 16:20:00', 'Registered', 'Cultural vocal singer'),
(4, 10, '2026-10-14 10:15:00', 'Registered', 'Cricket team bowler'),
(5, 11, '2026-10-16 11:30:00', 'Registered', 'AI researcher'),
(6, 12, '2026-10-18 09:10:00', 'Registered', 'Security analyst'),
(1, 13, '2026-11-01 13:45:00', 'Registered', 'Advance hackathon registration'),
(2, 14, '2026-11-05 15:00:00', 'Registered', 'Web dev cohort 2'),
(3, 15, '2026-12-01 10:00:00', 'Registered', 'Spoural stage management');

-- 6. Insert Initial Audit Log
INSERT INTO `audit_logs` (`user_id`, `action`, `details`, `ip_address`) VALUES
(1, 'SYSTEM_INITIALIZATION', 'StudentHub database schema and seed data initialized successfully.', '127.0.0.1');
