-- phpMyAdmin SQL Dump
-- version 5.2.3-1.el9
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 22, 2026 at 03:36 AM
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
-- Table structure for table `recurring_event`
--

CREATE TABLE `recurring_event` (
  `recurring_id` int NOT NULL,
  `event_id` int NOT NULL,
  `repeat_type` varchar(20) NOT NULL,
  `interval_value` int NOT NULL DEFAULT '1',
  `days_of_week` varchar(20) DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `occurrence_count` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `recurring_event`
--

INSERT INTO `recurring_event` (`recurring_id`, `event_id`, `repeat_type`, `interval_value`, `days_of_week`, `end_date`, `occurrence_count`) VALUES
(2, 11, 'weekly', 1, NULL, '2026-05-12 00:00:00', NULL),
(3, 17, 'weekly', 1, NULL, '2026-07-07 00:00:00', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `recurring_event`
--
ALTER TABLE `recurring_event`
  ADD PRIMARY KEY (`recurring_id`),
  ADD KEY `event_id` (`event_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `recurring_event`
--
ALTER TABLE `recurring_event`
  MODIFY `recurring_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `recurring_event`
--
ALTER TABLE `recurring_event`
  ADD CONSTRAINT `recurring_event_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
