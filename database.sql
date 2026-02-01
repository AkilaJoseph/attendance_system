-- Attendance System Database Schema
-- Run this SQL in phpMyAdmin to create the database and tables

-- Create Database
CREATE DATABASE IF NOT EXISTS attendance_system;
USE attendance_system;

-- 1. Users Table (Handles Students, Lecturers, Admin)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('student', 'lecturer', 'admin') NOT NULL
);

-- 2. Courses Table
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100),
    course_code VARCHAR(20),
    lecturer_id INT,
    FOREIGN KEY (lecturer_id) REFERENCES users(user_id)
);

-- 3. Enrollments (Links Students to Courses)
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    course_id INT,
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
);

-- 4. Attendance (The History & Calculation Source)
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    course_id INT,
    date DATE,
    status ENUM('present', 'absent') DEFAULT 'absent',
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
);

-- 5. Events Table (Personal + Course Events for Timetable)
CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NULL,
    title VARCHAR(150) NOT NULL,
    event_type ENUM('class', 'ue', 'ca', 'assignment', 'other') DEFAULT 'class',
    event_date DATE NOT NULL,
    event_time TIME,
    description TEXT,
    is_course_event BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE SET NULL
);

-- 6. Reminders Table (Custom timing per event)
CREATE TABLE reminders (
    reminder_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    remind_before INT NOT NULL,
    remind_unit ENUM('hours', 'days') DEFAULT 'days',
    is_seen BOOLEAN DEFAULT FALSE,
    notified_at DATETIME NULL,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 7. Attendance Snapshots (For tracking attendance trends over time)
CREATE TABLE attendance_snapshots (
    snapshot_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    snapshot_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY unique_snapshot (student_id, course_id, snapshot_date)
);

-- Index for efficient trend queries
CREATE INDEX idx_snapshot_date ON attendance_snapshots(student_id, course_id, snapshot_date);

-- 8. User Notification Settings (Per-user notification preferences)
CREATE TABLE user_notification_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type ENUM(
        'class_reminder',
        'ca_reminder',
        'ue_reminder',
        'assignment_reminder',
        'event_reminder',
        'attendance_alert',
        'low_attendance_warning',
        'course_announcement'
    ) NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    default_remind_before INT DEFAULT 1,
    default_remind_unit ENUM('hours', 'days') DEFAULT 'days',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_type (user_id, notification_type)
);

-- 9. Class Schedule (Recurring weekly class times)
CREATE TABLE class_schedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(50),
    schedule_type ENUM('lecture', 'tutorial', 'lab', 'other') DEFAULT 'lecture',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY unique_schedule (course_id, day_of_week, start_time)
);

-- Index for efficient schedule lookups
CREATE INDEX idx_schedule_day ON class_schedule(day_of_week, is_active);

-- 10. Personal Schedule (For both students and lecturers to create their own timetable)
CREATE TABLE personal_schedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    schedule_type ENUM('class', 'ue', 'ca', 'assignment', 'study', 'personal', 'other') DEFAULT 'class',
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(100),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_personal_schedule (user_id, day_of_week, start_time)
);

-- Index for personal schedule lookups
CREATE INDEX idx_personal_schedule ON personal_schedule(user_id, day_of_week, is_active);

-- Sample Data for Testing
-- Password is 'password' (not 'password123') hashed with password_hash()
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('John Lecturer', 'lecturer@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer'),
('Jane Student', 'student@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Bob Student', 'bob@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- Sample Courses
INSERT INTO courses (course_name, course_code, lecturer_id) VALUES
('Software Engineering', 'SE101', 2),
('Database Systems', 'DB201', 2),
('Web Development', 'WD301', 2);

-- Enroll students in courses
INSERT INTO enrollments (student_id, course_id) VALUES
(3, 1), (3, 2), (3, 3),
(4, 1), (4, 2);

-- Sample Attendance Records
INSERT INTO attendance (student_id, course_id, date, status) VALUES
(3, 1, '2024-01-15', 'present'),
(3, 1, '2024-01-22', 'present'),
(3, 1, '2024-01-29', 'absent'),
(3, 1, '2024-02-05', 'present'),
(4, 1, '2024-01-15', 'present'),
(4, 1, '2024-01-22', 'absent'),
(4, 1, '2024-01-29', 'absent'),
(4, 1, '2024-02-05', 'absent');

-- Sample Class Schedules (Recurring weekly timetable)
INSERT INTO class_schedule (course_id, day_of_week, start_time, end_time, room, schedule_type) VALUES
-- Software Engineering SE101
(1, 'Monday', '08:00:00', '10:00:00', 'Room 201', 'lecture'),
(1, 'Wednesday', '08:00:00', '10:00:00', 'Room 201', 'lecture'),
(1, 'Friday', '14:00:00', '16:00:00', 'Lab A', 'lab'),
-- Database Systems DB201
(2, 'Tuesday', '10:00:00', '12:00:00', 'Room 105', 'lecture'),
(2, 'Thursday', '10:00:00', '12:00:00', 'Lab B', 'lab'),
-- Web Development WD301
(3, 'Monday', '14:00:00', '16:00:00', 'Room 301', 'lecture'),
(3, 'Wednesday', '14:00:00', '16:00:00', 'Lab C', 'tutorial'),
(3, 'Thursday', '16:00:00', '18:00:00', 'Lab C', 'lab');

-- Sample Notification Settings for existing users
INSERT INTO user_notification_settings (user_id, notification_type, is_enabled, default_remind_before, default_remind_unit) VALUES
-- Student Jane (user_id = 3)
(3, 'class_reminder', TRUE, 1, 'hours'),
(3, 'ca_reminder', TRUE, 3, 'days'),
(3, 'ue_reminder', TRUE, 7, 'days'),
(3, 'assignment_reminder', TRUE, 2, 'days'),
(3, 'event_reminder', TRUE, 1, 'days'),
(3, 'attendance_alert', TRUE, 1, 'hours'),
(3, 'low_attendance_warning', TRUE, 1, 'days'),
(3, 'course_announcement', TRUE, 1, 'hours'),
-- Student Bob (user_id = 4)
(4, 'class_reminder', TRUE, 1, 'hours'),
(4, 'ca_reminder', TRUE, 3, 'days'),
(4, 'ue_reminder', TRUE, 7, 'days'),
(4, 'assignment_reminder', TRUE, 2, 'days'),
(4, 'event_reminder', TRUE, 1, 'days'),
(4, 'attendance_alert', FALSE, 1, 'hours'),
(4, 'low_attendance_warning', TRUE, 1, 'days'),
(4, 'course_announcement', TRUE, 1, 'hours'),
-- Lecturer John (user_id = 2)
(2, 'class_reminder', TRUE, 30, 'hours'),
(2, 'event_reminder', TRUE, 1, 'days'),
(2, 'course_announcement', TRUE, 1, 'hours');
