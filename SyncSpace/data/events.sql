-- phpMyAdmin SQL Dump
-- version 5.2.3-1.el9
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 22, 2026 at 03:30 AM
-- Server version: 9.1.0-commercial
-- PHP Version: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rotarum_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `created_by` int NOT NULL,
  `owner_user_id` int DEFAULT NULL,
  `owner_group_id` int DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `is_all_day` tinyint(1) NOT NULL DEFAULT '0',
  `priority` varchar(10) NOT NULL DEFAULT 'medium',
  `anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `title`, `description`, `start_time`, `end_time`, `created_by`, `owner_user_id`, `owner_group_id`, `location`, `is_all_day`, `priority`, `anonymous`, `created_at`, `updated_at`) VALUES
(1, 'Test Event', '', '2026-04-19 09:00:00', '2026-04-19 10:00:00', 1, 1, NULL, '', 0, 'medium', 0, '2026-04-18 23:15:30', '2026-04-18 23:15:30'),
(2, 'test event', '', '2026-04-19 00:00:00', '2026-04-19 23:59:59', 2, 2, NULL, '', 1, 'medium', 0, '2026-04-18 23:17:30', '2026-04-18 23:17:30'),
(3, 'test event 2', '', '2026-04-18 09:00:00', '2026-04-18 10:00:00', 2, 2, NULL, '', 0, 'high', 0, '2026-04-18 23:18:10', '2026-04-18 23:18:10'),
(4, 'test event for test2', '', '2026-04-20 00:00:00', '2026-04-20 23:59:59', 3, 3, NULL, '', 1, 'medium', 0, '2026-04-18 23:25:32', '2026-04-18 23:25:32'),
(5, 'testtsts', '', '2026-04-23 09:00:00', '2026-04-23 10:00:00', 4, 4, NULL, '', 0, 'medium', 0, '2026-04-21 13:55:08', '2026-04-21 13:55:08'),
(6, 'anas', '', '2026-04-21 09:00:00', '2026-04-21 10:00:00', 4, 4, NULL, '', 0, 'medium', 0, '2026-04-21 13:55:21', '2026-04-21 13:55:21'),
(7, 'nms', '', '2026-04-25 09:00:00', '2026-04-25 10:00:00', 4, 4, NULL, '', 0, 'high', 0, '2026-04-21 13:55:38', '2026-04-21 13:55:38'),
(8, 'nsm', '', '2026-04-21 09:00:00', '2026-04-21 10:00:00', 5, 5, NULL, '', 0, 'medium', 0, '2026-04-21 14:27:23', '2026-04-21 14:27:23'),
(9, 'msmma', '', '2026-04-22 18:03:00', '2026-04-22 20:04:00', 5, 5, NULL, 'Thode', 0, 'high', 0, '2026-04-21 14:28:11', '2026-04-21 14:28:11'),
(11, 'test rep 2', '', '2026-04-21 10:00:00', '2026-04-21 12:00:00', 2, 2, NULL, '', 0, 'medium', 0, '2026-04-21 15:21:02', '2026-04-21 15:21:02'),
(12, 'nasnnan', '', '2026-04-21 09:00:00', '2026-04-21 11:00:00', 4, 4, NULL, '', 0, 'medium', 0, '2026-04-21 16:58:39', '2026-04-21 16:58:39'),
(14, 'Lets see this time finder', '', '2026-04-22 09:00:00', '2026-04-22 13:00:00', 2, 2, NULL, '', 0, 'medium', 0, '2026-04-21 19:33:22', '2026-04-21 19:33:22'),
(15, 'edge case all day test', '', '2026-04-23 00:00:00', '2026-04-23 23:59:59', 2, 2, NULL, '', 1, 'medium', 0, '2026-04-21 19:34:12', '2026-04-21 19:34:12'),
(17, 'yoga (testing share logic)', 'testing my newly implemented share logc', '2026-04-24 12:00:00', '2026-04-24 13:00:00', 2, 2, NULL, 'Gym', 0, 'low', 0, '2026-04-21 20:16:45', '2026-04-21 20:16:45'),
(18, 'doctor\'s appointment', '', '2026-04-25 09:00:00', '2026-04-25 10:00:00', 3, 3, NULL, '', 0, 'high', 0, '2026-04-21 21:13:35', '2026-04-21 21:13:35'),
(19, 'ANOTHER doctors appt', '', '2026-04-26 09:00:00', '2026-04-26 10:00:00', 3, 3, NULL, '', 0, 'medium', 1, '2026-04-21 21:25:37', '2026-04-21 21:25:37'),
(20, 'busy busy things', '', '2026-04-25 09:00:00', '2026-04-25 17:00:00', 2, 2, NULL, '', 0, 'low', 0, '2026-04-21 23:16:47', '2026-04-21 23:16:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `owner_user_id` (`owner_user_id`),
  ADD KEY `owner_group_id` (`owner_group_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `events_ibfk_3` FOREIGN KEY (`owner_group_id`) REFERENCES `user_groups` (`group_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
