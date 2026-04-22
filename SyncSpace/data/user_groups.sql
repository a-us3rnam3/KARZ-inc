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
-- Table structure for table `user_groups`
--

CREATE TABLE `user_groups` (
  `group_id` int NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `description` text,
  `created_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `user_groups`
--

INSERT INTO `user_groups` (`group_id`, `group_name`, `description`, `created_by`, `created_at`) VALUES
(1, 'TestGroup1', '', 1, '2026-04-18 23:19:57'),
(2, 'TestGroup2', 'test2test2', 1, '2026-04-18 23:20:08'),
(3, 'TestGroup1', 'testing testing', 3, '2026-04-18 23:26:12'),
(5, 'empty group', 'this is a test empty group', 2, '2026-04-19 01:14:24'),
(6, 'test group maz', 'nsmm', 4, '2026-04-21 13:57:28'),
(7, 'test group maz 2', '', 5, '2026-04-21 14:25:14'),
(9, 'Gym TIIIIIMEEEEE!!!!', 'none needed', 2, '2026-04-21 20:17:22'),
(10, 'empty group test', '', 3, '2026-04-21 21:52:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user_groups`
--
ALTER TABLE `user_groups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user_groups`
--
ALTER TABLE `user_groups`
  MODIFY `group_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_groups`
--
ALTER TABLE `user_groups`
  ADD CONSTRAINT `user_groups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
