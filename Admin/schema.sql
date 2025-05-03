CREATE DATABASE neo_pop_dashboard;

USE neo_pop_dashboard;

-- Users table for storing login information
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

-- Courses table for storing course info
CREATE TABLE courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  course_name VARCHAR(255),
  topic VARCHAR(255),
  resource_link TEXT,
  video_link TEXT,
  image VARCHAR(255),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Progress table to track learner's progress
CREATE TABLE progress (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  topic VARCHAR(255),
  progress_percent INT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
