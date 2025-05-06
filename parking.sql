-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2025 at 09:35 AM
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
-- Database: `parking`
--

-- --------------------------------------------------------

--
-- Table structure for table `parking_cards`
--

CREATE TABLE `parking_cards` (
  `id` int(11) NOT NULL,
  `card_id` varchar(24) DEFAULT NULL,
  `license_plate` varchar(20) DEFAULT NULL,
  `entry_time` datetime DEFAULT NULL,
  `slot_number` int(11) DEFAULT NULL,
  `is_paid` tinyint(1) DEFAULT 0,
  `is_qrscan` bit(1) NOT NULL DEFAULT b'0',
  `expire_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_cards`
--

INSERT INTO `parking_cards` (`id`, `card_id`, `license_plate`, `entry_time`, `slot_number`, `is_paid`, `is_qrscan`, `expire_at`) VALUES
(157, 'F8CIOJHNRMZVL229K06B2U92', 'ทดสอบ6461', '2025-04-21 22:50:14', 4, 0, b'1', '2025-04-24 17:50:14'),
(158, 'QORLFC5LID4TVVWL4SYGJH1U', 'ทดสอบ6239', '2025-04-21 22:54:21', 9, 0, b'1', '2025-04-24 17:54:21'),
(159, 'NTESMWDUK2AB1ONVD6FGP0JH', 'ทดสอบ3520', '2025-04-21 23:00:12', 7, 0, b'1', '2025-04-24 18:00:12'),
(160, 'ID3F5OMKAHRQCKZPMRXVCRHA', 'ทดสอบ5392', '2025-04-21 23:06:01', 6, 0, b'0', '2025-04-24 18:06:01');

--
-- Triggers `parking_cards`
--
DELIMITER $$
CREATE TRIGGER `after_delete_parking_card` AFTER DELETE ON `parking_cards` FOR EACH ROW BEGIN
    UPDATE parking_slots
    SET is_occupied = 0
    WHERE slot_number = OLD.slot_number;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `parking_slots`
--

CREATE TABLE `parking_slots` (
  `slot_number` int(11) NOT NULL,
  `is_occupied` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_slots`
--

INSERT INTO `parking_slots` (`slot_number`, `is_occupied`) VALUES
(1, 0),
(2, 0),
(3, 0),
(4, 0),
(5, 0),
(6, 1),
(7, 1),
(8, 0),
(9, 1),
(10, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `parking_cards`
--
ALTER TABLE `parking_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `card_id` (`card_id`);

--
-- Indexes for table `parking_slots`
--
ALTER TABLE `parking_slots`
  ADD PRIMARY KEY (`slot_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `parking_cards`
--
ALTER TABLE `parking_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
