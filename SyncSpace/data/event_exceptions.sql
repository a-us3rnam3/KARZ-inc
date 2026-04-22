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
-- Table structure for table `event_exceptions`
--

CREATE TABLE `event_exceptions` (
  `exception_id` int NOT NULL,
  `recurring_id` int NOT NULL,
  `original_date` datetime NOT NULL,
  `is_cancelled` tinyint(1) NOT NULL DEFAULT '0',
  `new_start_time` datetime DEFAULT NULL,
  `new_end_time` datetime DEFAULT NULL,
  `new_title` varchar(100) DEFAULT NULL,
  `new_description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `event_exceptions`
--
ALTER TABLE `event_exceptions`
  ADD PRIMARY KEY (`exception_id`),
  ADD KEY `recurring_id` (`recurring_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `event_exceptions`
--
ALTER TABLE `event_exceptions`
  MODIFY `exception_id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `event_exceptions`
--
ALTER TABLE `event_exceptions`
  ADD CONSTRAINT `event_exceptions_ibfk_1` FOREIGN KEY (`recurring_id`) REFERENCES `recurring_event` (`recurring_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
