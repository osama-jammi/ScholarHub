-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 05, 2025 at 01:04 PM
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
-- Database: `protofolio`
--

-- --------------------------------------------------------

--
-- Table structure for table `annonces`
--
CREATE database IF NOT EXISTS `protofolio` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `annonces` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `genre` varchar(20) DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT current_timestamp(),
  `cours` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `annonces`
--

INSERT INTO `annonces` (`id`, `title`, `description`, `genre`, `time`, `cours`) VALUES
(1, 'Seance de domaine', 'je absence ...', 'absence', '2025-04-01 10:31:00', 'Web-tech'),
(2, 'Examen finale', 'a partie de cette somaine', 'examen', '2025-04-01 08:31:00', 'SE'),
(3, 'Cours Web-tech', 'Ds domaine', 'urgent', '2025-03-30 10:31:00', 'Python');

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

CREATE TABLE `comment` (
  `id` int(11) NOT NULL,
  `id_annonce` int(11) NOT NULL,
  `descreption` text NOT NULL,
  `likee` int(11) NOT NULL,
  `deslike` int(11) NOT NULL,
  `id_user` int(11) NOT NULL DEFAULT 0,
  `time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment`
--

INSERT INTO `comment` (`id`, `id_annonce`, `descreption`, `likee`, `deslike`, `id_user`, `time`) VALUES
(1, 1, 'Bonne recu', 6, 7, 1, '2025-04-03 23:32:40'),
(2, 2, 'issam is the best', 2, 2, 1, '2025-04-03 23:32:40'),
(3, 1, 'issam the best', 1, 1, 2, '2025-04-03 23:32:40'),
(10, 1, 'issam isa a nombre one', 0, 0, 1, '2025-04-02 23:32:40'),
(11, 1, 'best prof', 2, 0, 0, '2025-04-03 23:32:40'),
(12, 1, 'issam isa a nombre one', 1, 1, 0, '2025-04-03 23:32:40'),
(14, 1, 'issam isa a nombre one iiimkml.k\'mk\'k,mlmmk;lmlm', 0, 0, 0, '2025-04-03 23:32:40'),
(15, 1, 'issam is the best in the world', 0, 0, 0, '2025-04-03 23:32:40'),
(16, 3, 'issam isa a nombre one', 1, 1, 0, '2025-04-03 23:32:40'),
(17, 1, 'issam isa a nombre one', 0, 0, 0, '2025-04-03 23:32:40'),
(18, 1, 'best prof', 0, 0, 0, '2025-04-03 23:32:40'),
(19, 1, 'issam isa a nombre one', 0, 0, 0, '2025-04-03 23:42:31'),
(20, 1, 'issam isa a nombre one', 0, 0, 0, '2025-04-03 23:47:11');

-- --------------------------------------------------------

--
-- Table structure for table `support`
--

CREATE TABLE `support` (
  `id` int(11) NOT NULL,
  `id_annonce` int(11) NOT NULL,
  `file` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `phone` int(12) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `phone`, `password`, `avatar`) VALUES
(0, 'Admin', 672905541, '123456', NULL),
(1, 'issam Mouhala', 672905541, '123456789', NULL),
(2, 'rayan Mouhala', 672905541, '1453\r\n69', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `annonces`
--
ALTER TABLE `annonces`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_annonce` (`id_annonce`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `support`
--
ALTER TABLE `support`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_annonce` (`id_annonce`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `annonces`
--
ALTER TABLE `annonces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `id_annonce` FOREIGN KEY (`id_annonce`) REFERENCES `annonces` (`id`),
  ADD CONSTRAINT `id_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
