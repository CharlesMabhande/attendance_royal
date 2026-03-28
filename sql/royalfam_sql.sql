-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 28, 2026 at 04:59 PM
-- Server version: 10.6.20-MariaDB-cll-lve
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `royalfam_sql`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin'),
(2, 'head', 'head1'),
(4, 'deputy', 'deputy1'),
(8, 'ict', 'ict1'),
(17, 'grade1', 'grade1'),
(18, 'grade2', 'grade2'),
(19, 'grade3', 'grade3'),
(20, 'grade4', 'grade4'),
(21, 'grade5', 'grade5'),
(22, 'grade6', 'grade6'),
(23, 'grade7', 'grade7'),
(24, 'ecda', 'ecda'),
(25, 'ecdb', 'ecdb');

-- --------------------------------------------------------

--
-- Table structure for table `user_mark`
--

CREATE TABLE `user_mark` (
  `id` int(11) NOT NULL,
  `u_name` text NOT NULL,
  `u_rollno` int(2) NOT NULL,
  `u_class` text NOT NULL,
  `term` int(1) NOT NULL DEFAULT 1,
  `year` year(4) NOT NULL DEFAULT 2026,
  `u_mathematics_1` int(3) NOT NULL,
  `u_mathematics_2` int(3) NOT NULL,
  `u_english_1` int(3) NOT NULL,
  `u_english_2` int(3) NOT NULL,
  `u_shona_1` int(3) NOT NULL,
  `u_shona_2` int(3) NOT NULL,
  `u_social_science_1` int(3) NOT NULL,
  `u_social_science_2` int(3) NOT NULL,
  `u_physical_education_arts_1` int(3) NOT NULL,
  `u_physical_education_arts_2` int(3) NOT NULL,
  `u_science_technology_1` int(3) NOT NULL,
  `u_science_technology_2` int(3) NOT NULL,
  `u_total` int(3) NOT NULL,
  `u_position` int(3) NOT NULL,
  `u_image` varchar(255) DEFAULT 'default.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `user_mark`
--

INSERT INTO `user_mark` (`id`, `u_name`, `u_rollno`, `u_class`, `term`, `year`, `u_mathematics_1`, `u_mathematics_2`, `u_english_1`, `u_english_2`, `u_shona_1`, `u_shona_2`, `u_social_science_1`, `u_social_science_2`, `u_physical_education_arts_1`, `u_physical_education_arts_2`, `u_science_technology_1`, `u_science_technology_2`, `u_total`, `u_position`, `u_image`) VALUES
(29, 'Chikwanda Shylynne', 19, '5', 1, '2026', 39, 19, 54, 27, 48, 33, 45, 38, 42, 37, 51, 34, 467, 1, 'default.jpg'),
(30, 'Chioko Fadziso', 20, '5', 1, '2026', 48, 30, 44, 23, 33, 17, 36, 30, 35, 35, 47, 30, 408, 3, 'default.jpg'),
(31, 'Chivere Makanaka', 21, '5', 1, '2026', 38, 28, 50, 25, 32, 29, 41, 24, 41, 22, 41, 28, 399, 4, 'default.jpg'),
(32, 'Mushayahwaro Kin', 22, '5', 1, '2026', 45, 25, 38, 23, 42, 13, 42, 29, 48, 32, 47, 33, 417, 2, 'default.jpg'),
(33, 'Mutema Tiara R', 23, '5', 1, '2026', 26, 5, 45, 9, 24, 7, 33, 22, 35, 31, 44, 25, 306, 8, 'default.jpg'),
(34, 'Mandizvidza Wisdom', 24, '5', 1, '2026', 39, 31, 32, 16, 44, 23, 38, 24, 36, 26, 41, 17, 367, 6, 'default.jpg'),
(35, 'Mutunhire Asiel', 25, '5', 1, '2026', 35, 15, 47, 18, 33, 27, 41, 22, 42, 26, 42, 21, 369, 5, 'default.jpg'),
(36, 'Tambanda Desire', 26, '5', 1, '2026', 33, 17, 42, 16, 30, 23, 33, 20, 38, 21, 38, 19, 330, 7, 'default.jpg'),
(37, 'Chiramba Tadiswa  K', 53, '2', 1, '2026', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'default.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `user_massage`
--

CREATE TABLE `user_massage` (
  `id` int(11) NOT NULL,
  `u_name` text NOT NULL,
  `u_email` text NOT NULL,
  `u_contact` text NOT NULL,
  `u_massage` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_mark`
--
ALTER TABLE `user_mark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_massage`
--
ALTER TABLE `user_massage`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `user_mark`
--
ALTER TABLE `user_mark`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `user_massage`
--
ALTER TABLE `user_massage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
