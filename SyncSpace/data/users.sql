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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `created_at`) VALUES
(1, 'placeholder', 'placeholder@syncspace.local', 'no-auth-yet', '2026-04-18 23:13:14'),
(2, 'test', 'test@test.com', '$2y$10$ZCfsUpIP5FkyG40V5JJzIuy8c2JOpmIa58qcj6qBt.glV5deaeEfe', '2026-04-18 23:17:15'),
(3, 'test2', 'test2@test2.com', '$2y$10$1d3JbGyrMuk76F/a2SJQkeV9wPJsY/ZQD4.0KsXGl1elwzaf41Mmi', '2026-04-18 23:25:01'),
(4, 'ankli1', 'mazen.anklis@gmail.com', '$2y$10$PKAw6prkc4fUP.JsrO6il.l7DVveSCWhjgSgLx9ziMCgEThCOFFyK', '2026-04-20 16:16:14'),
(5, 'poop1', 'poop1@test.com', '$2y$10$FFgqQFgUbmHesszdRrvitOPgP/FeklH13JuPq2UVvt1kbWRk1lo1m', '2026-04-21 14:23:52'),
(6, 'erfantest', 'erfan@gmail.com', '$2y$10$S53AuICYLxbX9vfWyChiTe5O7.K7srFq/P1YCZB8AGREbENg3Tw2.', '2026-04-21 18:15:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
