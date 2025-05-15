-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2025 at 09:42 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `padho`
--

-- --------------------------------------------------------

--
-- Table structure for table `ausers`
--

CREATE TABLE `ausers` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ausers`
--

INSERT INTO `ausers` (`id`, `username`, `password`) VALUES
(1, 'Nikhil', '$2y$10$4NgdL1VCJ8JEwjo2lfuYMuBoIZ718ISYa8Af8pEyijydskfIP6xtm'),
(2, 'sonal', '$2y$10$o7/GrhPP/JUubr3EYHMvkuDeysa4pIEZWVE6.fEWZYukmk/ZZb85W'),
(4, 'admin', '$2y$10$0a9tNYe/6wDQ9ea0hOrbcudV90HXbDzG/I6E.h8QpZ3y5GVvel61W'),
(5, 'deepali', '$2y$10$m3Jl7XKuVuPGTWpNmMwygenzwyB5J7Qtf89rS4HDZqIhep.KAAGWG');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `author` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `video_link` varchar(255) DEFAULT NULL,
  `resource_link` varchar(255) DEFAULT NULL,
  `upload_resource` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `rating` float NOT NULL DEFAULT 0,
  `rating_count` int(11) DEFAULT 0,
  `price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `subject` varchar(255) DEFAULT NULL,
  `PrimaryID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `title`, `author`, `description`, `image`, `video_link`, `resource_link`, `upload_resource`, `category`, `rating`, `rating_count`, `price`, `created_at`, `subject`, `PrimaryID`) VALUES
(1, 'Flutter for intermediate', 'Rakesh Singh', 'Flutter is an open-source UI software development kit created by Google.', 'flutter.png', 'https://www.youtube.com/embed/esnBf6V4C34?si=PFgW7yJmmtRcPZEr', 'https://www.geeksforgeeks.org/what-is-flutter/', '', 'Mobile Development', 0, 0, 450.00, '2025-04-26 18:35:34', 'Flutter', 40),
(1, 'HTML Crash Course', 'Rakesh Singh', 'HTML (HyperText Markup Language) is the most basic building block of the Web.', 'htmlBasics.png', NULL, 'https://developer.mozilla.org/en-US/docs/Web/HTML', 'Beginners_Guide_to_HTML.pdf', 'Web Development', 3.6842, 19, 400.00, '2025-04-26 18:39:17', 'HTML', 41),
(1, 'CSS - Display property', 'Nikhil Purwar', 'Display proerty - flex, block, position,... etc..', 'css_display.jpg', 'https://www.youtube.com/embed/YjWktudqGN4?si=jGR_2PregayVJbJK\"title=\"YouTubevideoplayer', 'https://www.w3schools.com/cssref/pr_class_display.php', '', 'Web Development', 0, 0, 100.00, '2025-04-27 11:41:28', 'CSS', 45),
(1, 'CPP Fundamentals', 'Nikhil Purwar', 'CPP basic language beginner friendly', 'cpp fundamentals.png', 'https://www.youtube.com/embed/MNeX4EGtR5Y?si=us9TCSqfo26EIl8_', 'https://www.geeksforgeeks.org/c-plus-plus/', 'cpp_tutorial.pdf', 'Programming', 0, 0, 200.00, '2025-04-27 14:18:18', 'CPP', 48),
(1, 'Python Basics', 'Deepali', '​Python is a versatile, high-level programming language known for its readability and simplicity.', 'Python+programming.png', 'https://www.youtube.com/embed/v9bOWjwdTlg?si=n7gRxLF78ZcPnMdd', 'https://www.geeksforgeeks.org/python-basics/', 'python.pdf', 'Programming', 0, 0, 300.00, '2025-04-28 09:55:22', 'Python', 49);

-- --------------------------------------------------------

--
-- Table structure for table `newcourses`
--

CREATE TABLE `newcourses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `resource_link` text DEFAULT NULL,
  `video_link` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `progress`
--

CREATE TABLE `progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `progress_percent` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `education` varchar(255) DEFAULT NULL,
  `skills` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `name` varchar(255) DEFAULT NULL,
  `profile_pic` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `address`, `education`, `skills`, `password`, `created_at`, `name`, `profile_pic`) VALUES
(1, 'student1', 'student1@example.com', '9876543210', NULL, NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-04-09 18:00:42', NULL, NULL),
(2, 'webdev', 'webdev@example.com', '9876543211', NULL, NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-04-09 18:00:42', NULL, NULL),
(3, 'datascience', 'data@example.com', '9876543212', NULL, NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-04-09 18:00:42', NULL, NULL),
(8, 'nikhil', 'nikhil@gmail.com', '9120734999', NULL, NULL, NULL, '$2y$10$8e.3ZY/JsQTrPRelERkHce3z56X5tt9j/3T5P0Sy7BXPh7qVYqVQS', '2025-04-09 19:21:13', NULL, NULL),
(9, 'Sonal', 'sonal200kumari@gmail.com', '8607857636', 'Law Gate', 'MCA', 'C++', '$2y$10$MjXySDBpoet5O33ysDyNauZprTYo0OOI3dNMoYmatdjr.DB9qtlMu', '2025-04-09 19:28:37', 'Sonal Kumari', 0x75706c6f6164732f363830393238306463343232335f53637265656e73686f7420323032352d30342d3233203138343035362e706e67),
(11, 'admin', 'admin@gmail.com', '1234567890', NULL, NULL, NULL, '$2y$10$EvjTYsdYacYwH.JYcpvFluT4GHjX4bttYPcfjr56oPoXZux4ue7Vm', '2025-04-09 20:40:43', NULL, NULL),
(13, '', '', NULL, NULL, NULL, NULL, '', '2025-04-10 10:25:47', NULL, NULL),
(14, 'asdf', 'newuser@example.com', '9876543213', 'Sample Address', '', 'Java, Python', '$2y$10$mq6KFgNn5Cp3aURH1PmDxuSFhiO2KtbsE4sbxqrXCn5zv.pWhUUIe', '2025-04-23 14:59:20', 'asdf', 0x75706c6f6164732f363830636535616264303336365f53637265656e73686f7420323032352d30342d3233203138343035362e706e67),
(25, 'nikhil1', 'nikhil1@gmail.com', '5436543654', NULL, NULL, NULL, '$2y$10$8eLd0hxS/Z.7Mm3bm2SQROpRSe0O9642N4n4Ug4KgVf8ApCopRp.C', '2025-04-24 10:43:48', NULL, NULL),
(29, 'deepali', 'deepali@gmail.com', '6546765645', 'jalandhar', 'MCA', 'Python', '$2y$10$QdX96HGJffKkZQfrJH79x.T3n3fS8SdtGe8ntlPAYZI.Jefn5N8qe', '2025-04-24 10:48:19', 'Deepali', 0x75706c6f6164732f363830613165346338396563305f53637265656e73686f7420323032342d30382d3238203135353133342e706e67);

-- --------------------------------------------------------

--
-- Table structure for table `user_courses`
--

CREATE TABLE `user_courses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `progress` int(11) DEFAULT 0,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_courses`
--

INSERT INTO `user_courses` (`id`, `user_id`, `course_id`, `progress`, `enrolled_at`, `last_accessed`) VALUES
(44, 9, 40, 10, '2025-04-26 17:24:29', '2025-04-27 16:23:29'),
(49, 9, 48, 10, '2025-04-27 08:48:46', '2025-04-27 16:09:00'),
(52, 8, 49, 10, '2025-04-28 04:28:42', '2025-04-28 09:59:18'),
(57, 9, 41, 0, '2025-05-03 16:33:46', '2025-05-03 22:03:46');

-- --------------------------------------------------------

--
-- Table structure for table `user_interests`
--

CREATE TABLE `user_interests` (
  `user_id` int(11) NOT NULL,
  `interest` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_interests`
--

INSERT INTO `user_interests` (`user_id`, `interest`) VALUES
(1, 'Web Development'),
(1, 'Mobile Development'),
(2, 'Web Development'),
(3, 'Data Science');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ausers`
--
ALTER TABLE `ausers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`PrimaryID`);

--
-- Indexes for table `newcourses`
--
ALTER TABLE `newcourses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `progress`
--
ALTER TABLE `progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_courses`
--
ALTER TABLE `user_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`user_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `user_interests`
--
ALTER TABLE `user_interests`
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ausers`
--
ALTER TABLE `ausers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `PrimaryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `newcourses`
--
ALTER TABLE `newcourses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `progress`
--
ALTER TABLE `progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `user_courses`
--
ALTER TABLE `user_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `newcourses`
--
ALTER TABLE `newcourses`
  ADD CONSTRAINT `newcourses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `progress`
--
ALTER TABLE `progress`
  ADD CONSTRAINT `progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_courses`
--
ALTER TABLE `user_courses`
  ADD CONSTRAINT `user_courses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`PrimaryID`) ON DELETE CASCADE;

--
-- Constraints for table `user_interests`
--
ALTER TABLE `user_interests`
  ADD CONSTRAINT `user_interests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
