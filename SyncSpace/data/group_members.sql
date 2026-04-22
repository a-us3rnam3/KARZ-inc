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
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `membership_id` int NOT NULL,
  `group_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'member',
  `joined_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`membership_id`, `group_id`, `user_id`, `role`, `joined_at`) VALUES
(1, 1, 1, 'owner', '2026-04-18 23:19:57'),
(2, 2, 1, 'owner', '2026-04-18 23:20:08'),
(3, 3, 3, 'owner', '2026-04-18 23:26:12'),
(7, 5, 2, 'owner', '2026-04-19 01:14:24'),
(8, 6, 4, 'owner', '2026-04-21 13:57:28'),
(9, 6, 2, 'member', '2026-04-21 13:57:28'),
(10, 6, 1, 'member', '2026-04-21 13:57:28'),
(11, 7, 5, 'owner', '2026-04-21 14:25:14'),
(12, 7, 1, 'member', '2026-04-21 14:25:14'),
(13, 7, 2, 'member', '2026-04-21 14:25:14'),
(14, 7, 4, 'member', '2026-04-21 14:25:14'),
(17, 9, 2, 'owner', '2026-04-21 20:17:22'),
(18, 9, 3, 'member', '2026-04-21 20:17:22'),
(19, 10, 3, 'owner', '2026-04-21 21:52:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`membership_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `membership_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `user_groups` (`group_id`),
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
