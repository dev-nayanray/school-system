CREATE DATABASE IF NOT EXISTS school_system;
USE school_system;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  role ENUM('admin', 'teacher', 'student', 'user'),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_name VARCHAR(100)
);

CREATE TABLE subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject_name VARCHAR(100),
  class_id INT,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

CREATE TABLE student_profiles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  class_id INT,
  roll_number VARCHAR(20),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

CREATE TABLE teacher_profiles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  subject_id INT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE marks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT,
  subject_id INT,
  marks_obtained INT,
  exam_type VARCHAR(50),
  FOREIGN KEY (student_id) REFERENCES student_profiles(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200),
  content TEXT,
  posted_by INT,
  role_target ENUM('all', 'admin', 'teacher', 'student', 'user'),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL
);
