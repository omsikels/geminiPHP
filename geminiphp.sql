-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 12, 2025 at 01:54 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `geminiphp`
--

-- --------------------------------------------------------

--
-- Table structure for table `bankcheck`
--

CREATE TABLE `bankcheck` (
  `id` int(11) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `particulars` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference_number` varchar(255) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bankcheck`
--

INSERT INTO `bankcheck` (`id`, `bank_name`, `date`, `particulars`, `amount`, `reference_number`, `image_path`, `created_at`) VALUES
(1, 'BANK OF THE PHILIPPINE ISLANDS', '2024-05-03', 'CHECKS\nLOCAL\nMAQUILING HARDWARE LUMBER', 59819.88, '885INMG3 N95', 'uploads/1741667250_bank.png', '2025-03-11 04:27:35');

-- --------------------------------------------------------

--
-- Table structure for table `cheques`
--

CREATE TABLE `cheques` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `cheque_number` varchar(50) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `brstn_code` varchar(20) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `creditcard`
--

CREATE TABLE `creditcard` (
  `id` int(11) NOT NULL,
  `bank_terminal` varchar(255) NOT NULL,
  `transaction_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `creditcard`
--

INSERT INTO `creditcard` (`id`, `bank_terminal`, `transaction_type`, `amount`, `image_path`, `created_at`) VALUES
(1, 'Metrobank', 'STRAIGHT SALE', 7860.64, 'uploads/1741664312_metrobank.png', '2025-03-11 03:38:36');

-- --------------------------------------------------------

--
-- Table structure for table `mobilebanking`
--

CREATE TABLE `mobilebanking` (
  `id` int(11) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `particulars` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `confirmation_number` varchar(255) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mobilebanking`
--

INSERT INTO `mobilebanking` (`id`, `bank_name`, `date`, `particulars`, `amount`, `confirmation_number`, `image_path`, `created_at`) VALUES
(1, 'BPI', '2024-04-01', 'maquilinglumberhardwareandconstructionsupply- payment primitivo garcia po 2024-2244', 40897.20, '1711978042617', 'uploads/1741666530_mobile.png', '2025-03-11 04:15:34');

-- --------------------------------------------------------

--
-- Table structure for table `onlinebanking`
--

CREATE TABLE `onlinebanking` (
  `id` int(11) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `particulars` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference_number` varchar(255) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `onlinebanking`
--

INSERT INTO `onlinebanking` (`id`, `bank_name`, `date`, `particulars`, `amount`, `reference_number`, `image_path`, `created_at`) VALUES
(1, '', '2024-09-27', 'MAQUILING HARDWARE LUMBER & CONSTRUCTION SUPPLY INC', 33136.78, 'U2540005-LD24000183', 'uploads/1741666094_onlinebanking.png', '2025-03-11 04:08:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bankcheck`
--
ALTER TABLE `bankcheck`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_number` (`reference_number`);

--
-- Indexes for table `cheques`
--
ALTER TABLE `cheques`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cheque_number` (`cheque_number`);

--
-- Indexes for table `creditcard`
--
ALTER TABLE `creditcard`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mobilebanking`
--
ALTER TABLE `mobilebanking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `confirmation_number` (`confirmation_number`);

--
-- Indexes for table `onlinebanking`
--
ALTER TABLE `onlinebanking`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bankcheck`
--
ALTER TABLE `bankcheck`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cheques`
--
ALTER TABLE `cheques`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `creditcard`
--
ALTER TABLE `creditcard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mobilebanking`
--
ALTER TABLE `mobilebanking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `onlinebanking`
--
ALTER TABLE `onlinebanking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
