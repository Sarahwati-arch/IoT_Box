-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 30, 2025 at 07:56 AM
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
-- Database: `iotboxtest`
--

-- --------------------------------------------------------

--
-- Table structure for table `fire_sensor`
--

CREATE TABLE `fire_sensor` (
  `id` int(11) NOT NULL,
  `fire_status` varchar(50) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `machine_runtime`
--

CREATE TABLE `machine_runtime` (
  `id` int(11) NOT NULL,
  `machine_on` datetime DEFAULT NULL,
  `machine_off` datetime DEFAULT NULL,
  `runtime` time DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `motion_sensor`
--

CREATE TABLE `motion_sensor` (
  `id` int(11) NOT NULL,
  `motion_status` varchar(50) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `water_sensor`
--

CREATE TABLE `water_sensor` (
  `id` int(11) NOT NULL,
  `water_status` varchar(50) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `fire_sensor`
--
ALTER TABLE `fire_sensor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `machine_runtime`
--
ALTER TABLE `machine_runtime`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `motion_sensor`
--
ALTER TABLE `motion_sensor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `water_sensor`
--
ALTER TABLE `water_sensor`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `fire_sensor`
--
ALTER TABLE `fire_sensor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `machine_runtime`
--
ALTER TABLE `machine_runtime`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `motion_sensor`
--
ALTER TABLE `motion_sensor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `water_sensor`
--
ALTER TABLE `water_sensor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
