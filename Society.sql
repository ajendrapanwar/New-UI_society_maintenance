-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 03, 2026 at 07:44 AM
-- Server version: 8.0.45-0ubuntu0.24.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Society`
--

-- --------------------------------------------------------

--
-- Table structure for table `allotments`
--

CREATE TABLE `allotments` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `flat_id` int UNSIGNED NOT NULL,
  `move_in_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `allotments`
--

INSERT INTO `allotments` (`id`, `user_id`, `flat_id`, `move_in_date`, `created_at`) VALUES
(1, 34, 1, '2023-01-01', '2023-02-22 12:53:46'),
(35, 3, 5, '2026-02-10', '2026-01-19 11:30:09'),
(36, 4, 9, '2018-06-06', '2026-01-19 11:31:19');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('pending','processing','completed') NOT NULL DEFAULT 'pending',
  `resolve_note` text,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `user_id`, `subject`, `message`, `image`, `status`, `resolve_note`, `resolved_at`, `created_at`) VALUES
(2, 3, 'Water Problem', 'Water Problem', NULL, 'completed', 'Done', '2026-02-26 16:35:04', '2026-02-26 09:25:44'),
(3, 3, 'Test', 'Test', NULL, 'pending', NULL, NULL, '2026-02-26 11:06:56');

-- --------------------------------------------------------

--
-- Table structure for table `electricity_bills`
--

CREATE TABLE `electricity_bills` (
  `id` int NOT NULL,
  `month` tinyint NOT NULL,
  `year` smallint NOT NULL,
  `reading` decimal(10,2) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `bill_file` varchar(255) NOT NULL,
  `bill_receipt` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_amount` decimal(10,2) DEFAULT '0.00',
  `status` enum('pending','partial','paid') DEFAULT 'pending',
  `last_paid_on` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `electricity_bills`
--

INSERT INTO `electricity_bills` (`id`, `month`, `year`, `reading`, `amount`, `bill_file`, `bill_receipt`, `created_at`, `paid_amount`, `status`, `last_paid_on`) VALUES
(6, 1, 2026, 10.00, 100.00, 'electricity_2026_1_1769671685.jpg', NULL, '2026-01-29 07:28:05', 100.00, 'paid', '2026-02-02 12:59:57'),
(7, 2, 2026, 100.00, 10000.00, 'electricity_bill_2026_2_1771314561.jpg', 'electricity_receipt_2026_2_1771314561.jpg', '2026-02-17 07:49:21', 0.00, 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `electricity_payments`
--

CREATE TABLE `electricity_payments` (
  `id` int NOT NULL,
  `electricity_bill_id` int DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT NULL,
  `payment_mode` enum('cash','online') DEFAULT NULL,
  `paid_on` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `electricity_payments`
--

INSERT INTO `electricity_payments` (`id`, `electricity_bill_id`, `paid_amount`, `payment_mode`, `paid_on`) VALUES
(9, 6, 100.00, 'cash', '2026-02-02 12:59:57');

-- --------------------------------------------------------

--
-- Table structure for table `flats`
--

CREATE TABLE `flats` (
  `id` int UNSIGNED NOT NULL,
  `flat_number` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `floor` int NOT NULL,
  `block_number` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `flat_type` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `flats`
--

INSERT INTO `flats` (`id`, `flat_number`, `floor`, `block_number`, `flat_type`, `created_at`) VALUES
(1, '101', 1, 'A', '1 BHK Flat', '2023-02-20 11:37:45'),
(5, '201', 1, 'B', '2 BHK Flat', '2023-03-06 12:49:24'),
(9, '301', 1, 'C', '3 BHK Flat', '2023-03-06 12:50:17'),
(34, '101', 2, 'A', '1 BHK Flat', '2026-02-12 09:28:45');

-- --------------------------------------------------------

--
-- Table structure for table `garbage_collectors`
--

CREATE TABLE `garbage_collectors` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `mobile` varchar(10) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `shift` enum('day','evening') NOT NULL,
  `joining_date` date NOT NULL,
  `address` text NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `garbage_collectors`
--

INSERT INTO `garbage_collectors` (`id`, `name`, `mobile`, `dob`, `gender`, `shift`, `joining_date`, `address`, `salary`, `created_at`) VALUES
(1, 'Rajat', '7897897888', '2000-01-01', 'Male', 'day', '2026-01-28', 'Test', 10000.00, '2026-01-28 14:33:12');

-- --------------------------------------------------------

--
-- Table structure for table `garbage_salary`
--

CREATE TABLE `garbage_salary` (
  `id` int NOT NULL,
  `collector_id` int NOT NULL,
  `salary_month` int NOT NULL COMMENT '1-12',
  `salary_year` int NOT NULL COMMENT 'e.g. 2026',
  `salary_amount` decimal(10,2) NOT NULL,
  `status` enum('unpaid','paid') DEFAULT 'unpaid',
  `paid_on` datetime DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `garbage_salary`
--

INSERT INTO `garbage_salary` (`id`, `collector_id`, `salary_month`, `salary_year`, `salary_amount`, `status`, `paid_on`, `generated_at`) VALUES
(1, 1, 2, 2026, 10000.00, 'unpaid', NULL, '2026-01-30 05:54:01');

-- --------------------------------------------------------

--
-- Table structure for table `guard_salary`
--

CREATE TABLE `guard_salary` (
  `id` int NOT NULL,
  `guard_id` int NOT NULL,
  `salary_month` int NOT NULL COMMENT '1-12',
  `salary_year` int NOT NULL COMMENT 'e.g. 2026',
  `salary_amount` decimal(10,2) NOT NULL,
  `status` enum('unpaid','paid') DEFAULT 'unpaid',
  `paid_on` datetime DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `guard_salary`
--

INSERT INTO `guard_salary` (`id`, `guard_id`, `salary_month`, `salary_year`, `salary_amount`, `status`, `paid_on`, `generated_at`) VALUES
(1, 1, 2, 2026, 10000.00, 'paid', '2026-01-30 11:32:29', '2026-01-30 05:54:01'),
(2, 2, 2, 2026, 10000.00, 'paid', '2026-02-27 10:40:36', '2026-01-30 05:54:01'),
(3, 3, 2, 2026, 10000.00, 'unpaid', NULL, '2026-01-30 05:54:01');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_bills`
--

CREATE TABLE `maintenance_bills` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `flat_id` int NOT NULL,
  `bill_month` int NOT NULL,
  `bill_year` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `fine_amount` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','overdue') DEFAULT 'pending',
  `due_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `maintenance_bills`
--

INSERT INTO `maintenance_bills` (`id`, `user_id`, `flat_id`, `bill_month`, `bill_year`, `amount`, `fine_amount`, `total_amount`, `status`, `due_date`, `created_at`) VALUES
(47, 34, 1, 3, 2026, 1000.00, 0.00, 1000.00, 'paid', '2026-03-07', '2026-02-09 07:49:26'),
(48, 3, 5, 3, 2026, 2000.00, 0.00, 2000.00, 'paid', '2026-03-07', '2026-02-09 07:49:26'),
(49, 4, 9, 3, 2026, 3000.00, 0.00, 3000.00, 'pending', '2026-03-07', '2026-02-09 07:49:26');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_payments`
--

CREATE TABLE `maintenance_payments` (
  `id` int NOT NULL,
  `maintenance_bill_id` int NOT NULL,
  `payment_mode` enum('cash','online') DEFAULT NULL,
  `payment_method` enum('upi','credit_card','debit_card','netbanking') DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `proof` varchar(255) DEFAULT NULL,
  `paid_on` datetime DEFAULT NULL,
  `note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `maintenance_payments`
--

INSERT INTO `maintenance_payments` (`id`, `maintenance_bill_id`, `payment_mode`, `payment_method`, `razorpay_payment_id`, `proof`, `paid_on`, `note`, `created_at`) VALUES
(35, 47, 'cash', NULL, NULL, NULL, '2026-02-18 12:06:36', NULL, '2026-02-18 06:36:36'),
(36, 48, 'online', 'upi', 'pay_SHxRIiwxZcLscJ', NULL, '2026-02-19 15:09:05', NULL, '2026-02-19 09:39:05');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_rates`
--

CREATE TABLE `maintenance_rates` (
  `id` int UNSIGNED NOT NULL,
  `flat_type` varchar(50) NOT NULL,
  `rate` decimal(10,2) NOT NULL COMMENT 'Monthly maintenance rate',
  `overdue_fine` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `maintenance_rates`
--

INSERT INTO `maintenance_rates` (`id`, `flat_type`, `rate`, `overdue_fine`, `created_at`, `updated_at`) VALUES
(1, '1 BHK Flat', 1000.00, 500.00, '2026-01-19 15:13:45', '2026-01-20 06:21:57'),
(2, '2 BHK Flat', 2000.00, 1000.00, '2026-01-19 17:00:37', '2026-01-20 06:22:06'),
(3, '3 BHK Flat', 3000.00, 1500.00, '2026-01-19 17:00:46', '2026-02-25 06:45:35');

-- --------------------------------------------------------

--
-- Table structure for table `miscellaneous_works`
--

CREATE TABLE `miscellaneous_works` (
  `id` int NOT NULL,
  `work_title` varchar(255) NOT NULL,
  `worker_name` varchar(255) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `description` text,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'paid',
  `paid_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `miscellaneous_works`
--

INSERT INTO `miscellaneous_works` (`id`, `work_title`, `worker_name`, `contact_number`, `amount`, `description`, `month`, `year`, `status`, `paid_on`) VALUES
(4, 'Plumber', 'Rajuuu', '7878787878', 200.00, 'Test', 1, 2026, 'paid', '2026-01-30 15:00:44'),
(6, 'Test', 'Test', '7777788888', 1000.00, 'Test', 2, 2026, 'paid', '2026-02-26 18:08:41');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `title`, `category`, `message`, `start_date`, `end_date`, `created_at`) VALUES
(9, 'Test', 'General Info', 'Test', '2026-02-27 17:10:00', '2026-03-01 17:10:00', '2026-02-26 11:40:55'),
(10, 'Test', 'Maintenance', 'Test', '2026-02-26 17:18:00', NULL, '2026-02-26 11:48:40'),
(11, 'Holi event', 'General Info', 'Holi', '2026-03-01 06:00:00', '2026-03-05 00:00:00', '2026-03-02 07:15:03');

-- --------------------------------------------------------

--
-- Table structure for table `resident_parking`
--

CREATE TABLE `resident_parking` (
  `id` int UNSIGNED NOT NULL,
  `flat_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `vehicle_count` tinyint NOT NULL DEFAULT '1',
  `vehicle1` varchar(30) NOT NULL,
  `vehicle2` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `resident_parking`
--

INSERT INTO `resident_parking` (`id`, `flat_id`, `user_id`, `name`, `mobile`, `vehicle_count`, `vehicle1`, `vehicle2`, `created_at`) VALUES
(1, 1, 34, 'User3', '7894561239', 1, 'CH01CU1234', '', '2026-02-02 12:43:15');

-- --------------------------------------------------------

--
-- Table structure for table `security_guards`
--

CREATE TABLE `security_guards` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `mobile` varchar(10) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `shift` enum('day','night','rotational') NOT NULL,
  `salary` decimal(10,2) NOT NULL DEFAULT '0.00',
  `joining_date` date NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `security_guards`
--

INSERT INTO `security_guards` (`id`, `name`, `mobile`, `dob`, `gender`, `shift`, `salary`, `joining_date`, `address`, `created_at`) VALUES
(1, 'Raja', '7897897897', '2000-01-01', 'Male', 'day', 10000.00, '2026-01-27', 'Test', '2026-01-27 11:39:21'),
(2, 'Atul', '7897897898', '2000-01-01', 'Male', 'night', 10000.00, '2026-01-27', 'Test', '2026-01-27 12:35:00'),
(3, 'Vija', '7897897899', '2000-01-01', 'Male', 'rotational', 10000.00, '2026-01-27', 'Test', '2026-01-27 12:36:54');

-- --------------------------------------------------------

--
-- Table structure for table `sweepers`
--

CREATE TABLE `sweepers` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `mobile` varchar(10) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `joining_date` date NOT NULL,
  `address` text NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sweepers`
--

INSERT INTO `sweepers` (`id`, `name`, `mobile`, `dob`, `gender`, `joining_date`, `address`, `salary`, `created_at`) VALUES
(1, 'Anuj', '7897897897', '2000-01-01', 'Male', '2026-01-28', 'Test', 10000.00, '2026-01-28 09:53:53');

-- --------------------------------------------------------

--
-- Table structure for table `sweeper_salary`
--

CREATE TABLE `sweeper_salary` (
  `id` int NOT NULL,
  `sweeper_id` int NOT NULL,
  `salary_month` int NOT NULL COMMENT '1-12',
  `salary_year` int NOT NULL COMMENT 'e.g. 2026',
  `salary_amount` decimal(10,2) NOT NULL,
  `status` enum('unpaid','paid') DEFAULT 'unpaid',
  `paid_on` datetime DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sweeper_salary`
--

INSERT INTO `sweeper_salary` (`id`, `sweeper_id`, `salary_month`, `salary_year`, `salary_amount`, `status`, `paid_on`, `generated_at`) VALUES
(1, 1, 2, 2026, 10000.00, 'unpaid', NULL, '2026-01-30 05:54:01');

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `id` int NOT NULL,
  `flat_no` varchar(50) NOT NULL,
  `tenant_name` varchar(150) NOT NULL,
  `mobile_no` varchar(15) NOT NULL,
  `vehicle_no` varchar(50) DEFAULT NULL,
  `move_in` date NOT NULL,
  `move_out` date DEFAULT NULL,
  `agreement_file` varchar(255) DEFAULT NULL,
  `police_files` text,
  `status` enum('active','vacated') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`id`, `flat_no`, `tenant_name`, `mobile_no`, `vehicle_no`, `move_in`, `move_out`, `agreement_file`, `police_files`, `status`, `created_at`) VALUES
(7, '301', 'Test', '8888877777', 'CH01CU3333', '2026-03-02', NULL, '1772455249_agreement.pdf', '1772455249_police_0.pdf', 'active', '2026-03-02 12:40:49');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `mobile` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `role` enum('admin','cashier','user') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'user',
  `password` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `mobile`, `dob`, `gender`, `role`, `password`, `created_at`) VALUES
(1, 'Admin', 'admin@test.com', '7894561232', '2000-10-10', 'Male', 'admin', '$2y$10$tqfTlmJi1e1MRVodAAGqc.yBumE6t0YbvzJqjIYPh4GaPz7wZSTXu', '2026-02-24 11:32:10'),
(3, 'User1', 'User1@test.com', '7894561234', '2000-10-10', 'Male', 'user', '$2y$10$jG3oHf/u7D6nqgBrwF0YXOxr2g3GXhkkTeQwfesMHKUhSUojxpMPO', '2026-02-24 11:41:54'),
(4, 'User2', 'User2@test.com', '7894561236', '2000-10-10', 'Male', 'user', '$2y$10$MqtbNwr.4kjOeRRWWwAreuYt3eul9zFuXj2aF6B.WqfAn..nWhbh.', '2026-02-12 04:37:02'),
(34, 'User3', 'jaspreetkarayat@gmail.com', '7894561239', '2000-10-10', 'Male', 'user', '$2y$10$InUkJ7bOi1QtynaUX6kmie4BzQBaMETRCdrfcCJbftHHTXkEhlv9a', '2026-02-24 11:36:55'),
(37, 'Cashier', 'cashier@test.com', '7897897899', '2000-01-01', 'Male', 'cashier', '$2y$10$wE1kNsf96oBP3z75jmtoMOvWns5L7N3W5eiMM.jPfssii1CZ/8ca6', '2026-02-24 10:47:23');

-- --------------------------------------------------------

--
-- Table structure for table `visitor_entries`
--

CREATE TABLE `visitor_entries` (
  `id` int NOT NULL,
  `visitor_name` varchar(100) NOT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `vehicle_no` varchar(50) DEFAULT NULL,
  `flat_id` int NOT NULL,
  `visit_type` enum('Guest','Delivery Boy','Electrician','Plumber','Other') NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `in_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `out_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `visitor_entries`
--

INSERT INTO `visitor_entries` (`id`, `visitor_name`, `mobile`, `vehicle_no`, `flat_id`, `visit_type`, `purpose`, `in_time`, `out_time`) VALUES
(6, 'Test1', '7897897897', 'CH01CU1235', 5, 'Delivery Boy', '', '2026-02-05 13:19:08', '2026-02-05 15:03:55'),
(7, 'Test2', '7897897845', 'CH01CU2222', 1, 'Guest', '', '2026-02-09 11:13:40', '2026-02-09 11:19:34'),
(8, 'Test3', '7897897877', 'CH01CU3333', 1, 'Delivery Boy', '', '2026-02-09 11:20:03', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `allotments`
--
ALTER TABLE `allotments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `flat_id` (`flat_id`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `electricity_bills`
--
ALTER TABLE `electricity_bills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_month_year` (`month`,`year`);

--
-- Indexes for table `electricity_payments`
--
ALTER TABLE `electricity_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `flats`
--
ALTER TABLE `flats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `garbage_collectors`
--
ALTER TABLE `garbage_collectors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile` (`mobile`);

--
-- Indexes for table `garbage_salary`
--
ALTER TABLE `garbage_salary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `collector_id` (`collector_id`);

--
-- Indexes for table `guard_salary`
--
ALTER TABLE `guard_salary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `guard_id` (`guard_id`);

--
-- Indexes for table `maintenance_bills`
--
ALTER TABLE `maintenance_bills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance_payments`
--
ALTER TABLE `maintenance_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance_rates`
--
ALTER TABLE `maintenance_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_flat_type` (`flat_type`);

--
-- Indexes for table `miscellaneous_works`
--
ALTER TABLE `miscellaneous_works`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resident_parking`
--
ALTER TABLE `resident_parking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_flat_parking` (`flat_id`),
  ADD UNIQUE KEY `flat_id` (`flat_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `security_guards`
--
ALTER TABLE `security_guards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile` (`mobile`);

--
-- Indexes for table `sweepers`
--
ALTER TABLE `sweepers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile` (`mobile`);

--
-- Indexes for table `sweeper_salary`
--
ALTER TABLE `sweeper_salary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sweeper_id` (`sweeper_id`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `visitor_entries`
--
ALTER TABLE `visitor_entries`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `allotments`
--
ALTER TABLE `allotments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `electricity_bills`
--
ALTER TABLE `electricity_bills`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `electricity_payments`
--
ALTER TABLE `electricity_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `flats`
--
ALTER TABLE `flats`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `garbage_collectors`
--
ALTER TABLE `garbage_collectors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `garbage_salary`
--
ALTER TABLE `garbage_salary`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `guard_salary`
--
ALTER TABLE `guard_salary`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `maintenance_bills`
--
ALTER TABLE `maintenance_bills`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `maintenance_payments`
--
ALTER TABLE `maintenance_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `maintenance_rates`
--
ALTER TABLE `maintenance_rates`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `miscellaneous_works`
--
ALTER TABLE `miscellaneous_works`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `resident_parking`
--
ALTER TABLE `resident_parking`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `security_guards`
--
ALTER TABLE `security_guards`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sweepers`
--
ALTER TABLE `sweepers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sweeper_salary`
--
ALTER TABLE `sweeper_salary`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `visitor_entries`
--
ALTER TABLE `visitor_entries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `allotments`
--
ALTER TABLE `allotments`
  ADD CONSTRAINT `allotments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `allotments_ibfk_2` FOREIGN KEY (`flat_id`) REFERENCES `flats` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resident_parking`
--
ALTER TABLE `resident_parking`
  ADD CONSTRAINT `resident_parking_ibfk_1` FOREIGN KEY (`flat_id`) REFERENCES `flats` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resident_parking_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
