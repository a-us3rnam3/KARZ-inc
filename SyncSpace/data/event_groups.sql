-- phpMyAdmin SQL Dump
-- version 5.2.3-1.el9
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 22, 2026 at 03:37 AM
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
-- Table structure for table `event_groups`
--

CREATE TABLE `event_groups` (
  `event_group_id` int NOT NULL,
  `event_id` int NOT NULL,
  `group_id` int NOT NULL,
  `anonymous` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `event_groups`
--

INSERT INTO `event_groups` (`event_group_id`, `event_id`, `group_id`, `anonymous`) VALUES
(1, 2, 3, 0),
(2, 14, 5, 0),
(3, 15, 5, 0),
(15, 19, 9, 1),
(18, 18, 9, 1),
(20, 17, 9, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `event_groups`
--
ALTER TABLE `event_groups`
  ADD PRIMARY KEY (`event_group_id`),
  ADD UNIQUE KEY `unique_event_group` (`event_id`,`group_id`),
  ADD KEY `group_id` (`group_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `event_groups`
--
ALTER TABLE `event_groups`
  MODIFY `event_group_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `event_groups`
--
ALTER TABLE `event_groups`
  ADD CONSTRAINT `event_groups_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_groups_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `user_groups` (`group_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
