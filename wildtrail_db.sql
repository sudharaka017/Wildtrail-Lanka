-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 23, 2026 at 07:43 PM
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
-- Database: `wildtrail_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `visitor_id` int(11) NOT NULL,
  `park_id` int(11) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `guide_id` int(11) DEFAULT NULL,
  `entry_date` date NOT NULL,
  `exit_date` date NOT NULL,
  `visitors_count` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `visitor_id`, `park_id`, `vehicle_id`, `guide_id`, `entry_date`, `exit_date`, `visitors_count`, `total_amount`, `status`, `payment_status`, `created_at`) VALUES
(1, 2, 2, NULL, 4, '2026-06-22', '2026-06-22', 2, 6000.00, 'confirmed', 'unpaid', '2026-06-21 17:18:09'),
(3, 2, 3, NULL, NULL, '2026-07-02', '2026-07-03', 10, 25000.00, 'pending', 'unpaid', '2026-06-21 17:32:23'),
(4, 2, 1, NULL, NULL, '2026-07-12', '2026-07-12', 7, 24500.00, 'pending', 'unpaid', '2026-06-21 18:57:47');

-- --------------------------------------------------------

--
-- Table structure for table `parks`
--

CREATE TABLE `parks` (
  `id` int(11) NOT NULL,
  `park_name` varchar(100) NOT NULL,
  `province` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `entry_fee` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parks`
--

INSERT INTO `parks` (`id`, `park_name`, `province`, `description`, `image`, `entry_fee`, `status`, `created_at`) VALUES
(1, 'Yala National Park', 'Southern Province', 'Famous for leopards, elephants, and diverse wildlife', 'yala.jpg', 3500.00, 'active', '2026-06-21 17:15:09'),
(2, 'Wilpattu National Park', 'North Western Province', 'Largest national park, known for lakes and leopards', 'wilpattu.jpg', 3000.00, 'active', '2026-06-21 17:15:09'),
(3, 'Udawalawe National Park', 'Sabaragamuwa Province', 'Best place to see elephants in Sri Lanka', 'udawalawe.jpg', 2500.00, 'active', '2026-06-21 17:15:09'),
(4, 'Minneriya National Park', 'North Central Province', 'Famous for the Great Elephant Gathering', 'minneriya.jpg', 2000.00, 'active', '2026-06-21 17:15:09'),
(5, 'Kumana National Park', 'Eastern Province', 'Famous for bird watching and leopards', NULL, 2800.00, 'active', '2026-06-21 17:58:51');

-- --------------------------------------------------------

--
-- Table structure for table `sightings`
--

CREATE TABLE `sightings` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `animal_name` varchar(50) NOT NULL,
  `count` int(11) DEFAULT 1,
  `sighting_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('visitor','driver','guide','admin') DEFAULT 'visitor',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `role`, `status`, `created_at`) VALUES
(1, 'System Admin', 'admin@wildtrail.com', '$2y$10$Y1ZvsCa/L7DqZ0k.3sN9DeMLyQBMXqqXSmeeR8n.zsiKq7RdC0Nf2', '0771234567', 'admin', 'active', '2026-06-21 17:02:09'),
(2, 'Sudharaka', 'sd@gmail.com', '$2y$10$svBttC9RTdu.gs03.PUM7u0zZigv5bvFFZvk6lLcU3icG58.t5iBi', '0773508063', 'visitor', 'active', '2026-06-21 13:55:13'),
(3, 'Kamal Perera', 'kamal@driver.com', '$2y$10$Y1ZvsCa/L7DqZ0k.3sN9DeMLyQBMXqqXSmeeR8n.zsiKq7RdC0Nf2', '0771111111', 'driver', 'active', '2026-06-21 18:17:26'),
(4, 'Nimal Silva', 'nimal@guide.com', '$2y$10$Y1ZvsCa/L7DqZ0k.3sN9DeMLyQBMXqqXSmeeR8n.zsiKq7RdC0Nf2', '0772222222', 'guide', 'active', '2026-06-21 18:17:26');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `vehicle_name` varchar(100) NOT NULL,
  `vehicle_type` enum('Jeep','Van','Bus') NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `capacity` int(11) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `status` enum('available','booked','maintenance') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visitor_id` (`visitor_id`),
  ADD KEY `park_id` (`park_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `guide_id` (`guide_id`);

--
-- Indexes for table `parks`
--
ALTER TABLE `parks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sightings`
--
ALTER TABLE `sightings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate_number` (`plate_number`),
  ADD KEY `driver_id` (`driver_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `parks`
--
ALTER TABLE `parks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sightings`
--
ALTER TABLE `sightings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`visitor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`park_id`) REFERENCES `parks` (`id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  ADD CONSTRAINT `bookings_ibfk_4` FOREIGN KEY (`guide_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sightings`
--
ALTER TABLE `sightings`
  ADD CONSTRAINT `sightings_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
