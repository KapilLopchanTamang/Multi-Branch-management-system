-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 21, 2025 at 05:23 AM
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
-- Database: `gym_admin`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','branch_admin') NOT NULL DEFAULT 'branch_admin',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'admin@gymnetwork.com', '$2y$10$AzlUwqirGNlnw2rwxMdIy.YugOoPBamKMPIPryPTZTrxqsQpBI1vK', 'super_admin', NULL, '2025-03-12 01:28:12', '2025-03-12 02:14:38'),
(2, 'Downtown Manager', 'downtown@gymnetwork.com', '$2y$10$AzlUwqirGNlnw2rwxMdIy.YugOoPBamKMPIPryPTZTrxqsQpBI1vK', 'branch_admin', '813a36f8f05b65648ca00ea4b563762fcbddcc22a0fea4b25270ba010f4952ee', '2025-03-12 01:28:12', '2025-03-18 04:34:54'),
(3, 'Uptown Manager', 'uptown@gymnetwork.com', '$2y$10$AzlUwqirGNlnw2rwxMdIy.YugOoPBamKMPIPryPTZTrxqsQpBI1vK', 'branch_admin', NULL, '2025-03-12 01:28:12', '2025-03-12 02:14:38'),
(7, 'skapil', 'admin@gmail.com', '$2y$10$AzlUwqirGNlnw2rwxMdIy.YugOoPBamKMPIPryPTZTrxqsQpBI1vK', 'super_admin', NULL, '2025-03-12 01:44:47', '2025-03-12 02:14:38'),
(8, 'bratchowk', 'kapiltamang123@gmail.com', '$2y$10$0vct8/e.zqSa0Ti5MmdcwuR7c0EjHiLzIj3juNbBbphJb7I85z0fS', 'branch_admin', '58379f7151a54ce03faabdead3248be9cca033d9b1164148278388b2f255be85', '2025-03-16 02:33:13', '2025-03-16 02:48:15');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `check_in` datetime NOT NULL,
  `check_out` datetime DEFAULT NULL,
  `branch` varchar(100) NOT NULL,
  `created_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `customer_id`, `check_in`, `check_out`, `branch`, `created_by`, `notes`, `created_at`) VALUES
(1, 4, '2025-03-18 09:55:38', '2025-03-18 09:57:35', 'Downtown Fitness', 2, ' | Check-out notes: done', '2025-03-18 04:10:38'),
(2, 3, '2025-03-18 09:55:50', '2025-03-18 09:55:57', 'Downtown Fitness', 2, ' | Check-out notes: ', '2025-03-18 04:10:50'),
(3, 6, '2025-03-18 09:58:01', '2025-03-18 10:02:56', 'Downtown Fitness', 2, ' | Check-out notes: ', '2025-03-18 04:13:01'),
(4, 4, '2025-03-18 10:06:43', '2025-03-18 10:07:24', 'Downtown Fitness', 2, ' | Check-out notes: ', '2025-03-18 04:21:43'),
(5, 4, '2025-03-18 10:11:26', '2025-03-18 10:20:26', 'Downtown Fitness', 2, ' | Check-out notes: ', '2025-03-18 04:26:26'),
(6, 3, '2025-03-18 10:22:35', '2025-03-18 10:22:42', 'Downtown Fitness', 2, ' | Check-out notes: ', '2025-03-18 04:37:35');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_settings`
--

CREATE TABLE `attendance_settings` (
  `id` int(11) NOT NULL,
  `branch` varchar(100) NOT NULL,
  `allow_self_checkin` tinyint(1) DEFAULT 0,
  `max_entries_per_day` int(11) DEFAULT 1,
  `require_checkout` tinyint(1) DEFAULT 0,
  `auto_checkout_after` int(11) DEFAULT 180,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_settings`
--

INSERT INTO `attendance_settings` (`id`, `branch`, `allow_self_checkin`, `max_entries_per_day`, `require_checkout`, `auto_checkout_after`, `created_at`, `updated_at`) VALUES
(1, 'Downtown Fitness', 1, 1, 0, 180, '2025-03-18 04:06:55', '2025-03-18 04:37:25'),
(2, 'Uptown Gym', 0, 1, 0, 180, '2025-03-18 04:06:55', '2025-03-18 04:06:55'),
(3, 'Westside Health Club', 0, 1, 0, 180, '2025-03-18 04:06:55', '2025-03-18 04:06:55'),
(4, 'East End Fitness Center', 0, 1, 0, 180, '2025-03-18 04:06:55', '2025-03-18 04:06:55');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `location`, `admin_id`, `created_at`, `updated_at`) VALUES
(1, 'Downtown Fitness', '123 Main St, Downtown', 2, '2025-03-12 01:28:12', '2025-03-12 01:28:12'),
(2, 'Uptown Gym', '456 High St, Uptown', 3, '2025-03-12 01:28:12', '2025-03-12 01:28:12'),
(3, 'Westside Health Club', '789 West Blvd, Westside', 8, '2025-03-12 01:28:12', '2025-03-16 02:33:13'),
(4, 'East End Fitness Center', '321 East Ave, East End', NULL, '2025-03-12 01:28:12', '2025-03-12 01:28:12');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `branch` varchar(100) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `class_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `instructor` varchar(100) NOT NULL,
  `max_capacity` int(11) NOT NULL,
  `current_capacity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `branch`, `class_name`, `class_date`, `start_time`, `end_time`, `instructor`, `max_capacity`, `current_capacity`, `created_at`) VALUES
(1, 'Downtown Fitness', 'Yoga Basics', '2023-06-15', '09:00:00', '10:00:00', 'John Smith', 20, 8, '2025-03-18 03:17:18'),
(2, 'Downtown Fitness', 'HIIT Workout', '2023-06-16', '18:00:00', '19:00:00', 'Sarah Johnson', 15, 12, '2025-03-18 03:17:18'),
(3, 'Downtown Fitness', 'Spin Class', '2023-06-17', '10:00:00', '11:00:00', 'Mike Brown', 12, 5, '2025-03-18 03:17:18'),
(4, 'Uptown Gym', 'Pilates', '2023-06-15', '17:00:00', '18:00:00', 'Lisa Davis', 15, 7, '2025-03-18 03:17:18'),
(5, 'Uptown Gym', 'Zumba', '2023-06-16', '19:00:00', '20:00:00', 'Carlos Rodriguez', 25, 18, '2025-03-18 03:17:18');

-- --------------------------------------------------------

--
-- Table structure for table `class_bookings`
--

CREATE TABLE `class_bookings` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `booking_date` datetime NOT NULL DEFAULT current_timestamp(),
  `attendance_status` enum('booked','attended','cancelled','no_show') NOT NULL DEFAULT 'booked',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `branch` varchar(50) NOT NULL,
  `subscription_type` enum('monthly','six_months','yearly') DEFAULT 'monthly',
  `fitness_goal` varchar(50) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `health_conditions` text DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `first_name`, `last_name`, `address`, `email`, `phone`, `date_of_birth`, `password`, `branch`, `subscription_type`, `fitness_goal`, `emergency_contact_name`, `emergency_contact_phone`, `health_conditions`, `weight`, `status`, `profile_image`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'kapil', 'Tamang', NULL, 'kapiltamang123@gmail.com', '9816308527', NULL, '$2y$10$P/BqfGNX6ZzJFUQFUNt07.GoJbHyvkC6XIsV30uqS.x3UD547Asea', 'downtown', 'monthly', 'weight_loss', NULL, NULL, NULL, NULL, 'active', NULL, NULL, '2025-03-12 01:41:05', '2025-03-18 03:17:58'),
(2, 'abijeet', 'raut', NULL, 'abijeet@gmail.com', '98765678657', NULL, '$2y$10$P/BqfGNX6ZzJFUQFUNt07.GoJbHyvkC6XIsV30uqS.x3UD547Asea', 'westside', 'monthly', 'muscle_gain', NULL, NULL, NULL, NULL, 'active', NULL, NULL, '2025-03-12 03:08:39', '2025-03-18 03:17:58'),
(3, 'prabin', 'subedi', NULL, 'prabin123@gmail.com', '98172637494', NULL, '$2y$10$P/BqfGNX6ZzJFUQFUNt07.GoJbHyvkC6XIsV30uqS.x3UD547Asea', 'Downtown Fitness', 'monthly', 'weight_loss', NULL, NULL, NULL, NULL, 'active', NULL, NULL, '2025-03-16 02:00:06', '2025-03-18 03:17:58'),
(4, 'kapil', 'tamang', 'khorsane', 'tamang@gmail.com', '982736472', NULL, '$2y$10$P/BqfGNX6ZzJFUQFUNt07.GoJbHyvkC6XIsV30uqS.x3UD547Asea', 'Downtown Fitness', 'six_months', 'weight_loss', NULL, NULL, NULL, 89.00, 'active', NULL, NULL, '2025-03-18 03:17:10', '2025-03-18 03:55:26'),
(5, 'kapil', 'tamang', NULL, 'kapil123@gmail.com', '9816308527', NULL, '$2y$10$7ZbnTNUgjYZ8Wr3z5Vk.XuK5iv/3D85b8d1dlG4k3kuP6RR8Ub3yy', 'East End Fitness Center', 'monthly', 'general_fitness', NULL, NULL, NULL, NULL, 'active', NULL, NULL, '2025-03-18 03:22:42', '2025-03-18 03:22:42'),
(6, 'rakesh', 'niraula', 'bahuna', 'rakesh@gmail.com', '9876545678', NULL, '$2y$10$tSOc5WGfECpfFW2POofuZOS15SG0pk.7uXP7bkY1FcgLZLBSz6G2m', 'Downtown Fitness', 'yearly', 'muscle_gain', 'Prasanga Pokharel', '9765470926', 'Normal', 90.00, 'active', 'uploads/profile_images/profile_6_1742523269.png', '0e13efffbddfc3987195fc23e0cb95723d9bca4c2d5311f1eddb668856bc8ccd', '2025-03-18 03:56:38', '2025-03-21 02:14:29');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `branch` varchar(100) NOT NULL,
  `purchase_date` date DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL,
  `next_maintenance` date DEFAULT NULL,
  `status` enum('operational','maintenance','out_of_order') NOT NULL DEFAULT 'operational',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fitness_assessments`
--

CREATE TABLE `fitness_assessments` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `assessment_date` date NOT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `body_fat_percentage` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(5,2) DEFAULT NULL,
  `chest` decimal(5,2) DEFAULT NULL,
  `waist` decimal(5,2) DEFAULT NULL,
  `hips` decimal(5,2) DEFAULT NULL,
  `arms` decimal(5,2) DEFAULT NULL,
  `thighs` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `assessed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `memberships`
--

CREATE TABLE `memberships` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `membership_type` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `memberships`
--

INSERT INTO `memberships` (`id`, `customer_id`, `membership_type`, `status`, `start_date`, `end_date`, `created_at`) VALUES
(1, 1, 'Premium', 'Active', '2025-03-18', '2026-03-18', '2025-03-18 03:17:18'),
(2, 2, 'Premium', 'Active', '2025-03-18', '2026-03-18', '2025-03-18 03:17:18'),
(3, 3, 'Premium', 'Active', '2025-03-18', '2026-03-18', '2025-03-18 03:17:18'),
(4, 4, 'six_months', 'Active', '2025-03-18', '2025-09-18', '2025-03-18 03:17:18'),
(5, 5, 'Premium', 'Active', '2025-03-18', '2026-03-18', '2025-03-18 03:22:48'),
(6, 6, 'yearly', 'Active', '2025-03-18', '2026-03-18', '2025-03-18 03:56:38');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_type` enum('admin','customer') NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_type`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 'customer', 6, 'Welcome to Gym Network!', 'Thank you for joining our fitness community. We\'re excited to help you achieve your fitness goals!', 'welcome', 1, '2025-03-18 21:04:24'),
(2, 'customer', 6, 'New Yoga Class Added', 'We\'ve added a new Yoga class on Tuesdays at 6:00 PM. Book your spot now!', 'class', 1, '2025-03-19 21:04:24'),
(3, 'customer', 6, 'Membership Renewal Reminder', 'Your membership will expire in 30 days. Renew now to avoid interruption in your fitness journey.', 'membership', 1, '2025-03-20 21:04:24');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(100) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(1, 'admin@gmail.com', '84c61c1d3b48c815274a7dd5e342be6ecaa4b25f8e8cd578ccfbf43159d5dc28', '2025-03-12 03:46:13', '2025-03-12 01:46:13');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `membership_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_training`
--

CREATE TABLE `personal_training` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('scheduled','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `branch` varchar(100) NOT NULL,
  `bio` text DEFAULT NULL,
  `status` enum('active','inactive','on_leave') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_check_in_date` (`check_in`);

--
-- Indexes for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branch` (`branch`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_class_date` (`class_date`);

--
-- Indexes for table `class_bookings`
--
ALTER TABLE `class_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_booking` (`class_id`,`customer_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_branch` (`branch`),
  ADD KEY `idx_subscription_type` (`subscription_type`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_branch` (`branch`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `fitness_assessments`
--
ALTER TABLE `fitness_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `assessed_by` (`assessed_by`);

--
-- Indexes for table `memberships`
--
ALTER TABLE `memberships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_end_date` (`end_date`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_type`,`user_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `membership_id` (`membership_id`);

--
-- Indexes for table `personal_training`
--
ALTER TABLE `personal_training`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `class_bookings`
--
ALTER TABLE `class_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fitness_assessments`
--
ALTER TABLE `fitness_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `memberships`
--
ALTER TABLE `memberships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_training`
--
ALTER TABLE `personal_training`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainers`
--
ALTER TABLE `trainers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`);

--
-- Constraints for table `branches`
--
ALTER TABLE `branches`
  ADD CONSTRAINT `branches_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`);

--
-- Constraints for table `class_bookings`
--
ALTER TABLE `class_bookings`
  ADD CONSTRAINT `class_bookings_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_bookings_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fitness_assessments`
--
ALTER TABLE `fitness_assessments`
  ADD CONSTRAINT `fitness_assessments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fitness_assessments_ibfk_2` FOREIGN KEY (`assessed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `personal_training`
--
ALTER TABLE `personal_training`
  ADD CONSTRAINT `personal_training_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `personal_training_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
