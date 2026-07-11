-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 11, 2026 at 02:17 PM
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
-- Database: `qr_attendance_v2`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_name` varchar(255) DEFAULT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `user_phone` varchar(50) DEFAULT NULL,
  `device_info` longtext DEFAULT NULL,
  `attendance_status` varchar(30) NOT NULL DEFAULT 'present',
  `time` timestamp NULL DEFAULT current_timestamp(),
  `scan_lat` decimal(10,7) DEFAULT NULL,
  `scan_lng` decimal(10,7) DEFAULT NULL,
  `scan_address` varchar(255) DEFAULT NULL,
  `scan_ip` varchar(80) DEFAULT NULL,
  `browser_info` longtext DEFAULT NULL,
  `distance_from_venue` decimal(10,2) DEFAULT NULL,
  `phone_matched` tinyint(1) NOT NULL DEFAULT 0,
  `verification_method` varchar(30) DEFAULT NULL,
  `check_in_time` time DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `event_id`, `created_at`, `user_name`, `user_email`, `user_phone`, `device_info`, `attendance_status`, `time`, `scan_lat`, `scan_lng`, `scan_address`, `scan_ip`, `browser_info`, `distance_from_venue`, `phone_matched`, `verification_method`, `check_in_time`, `notes`) VALUES
(1, 2, 4, '2026-05-27 06:14:47', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', '{\"browser\":\"Chrome 148\",\"device_type\":\"Desktop\",\"user_agent\":\"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36\",\"screen_resolution\":\"703x1561\",\"platform\":\"Linux armv81\",\"language\":\"en-GB\",\"timezone\":\"Africa/Dar_es_Salaam\",\"cookies_enabled\":true,\"online_status\":true,\"java_enabled\":false,\"do_not_track\":null}', 'present', '2026-05-27 06:14:47', -6.9180476, 37.5605515, '-6.9180476, 37.5605515', '197.186.0.195', '{\"name\":\"Chrome\",\"version\":\"148.0\",\"engine\":\"Blink\",\"platform\":\"Linux armv81\",\"language\":\"en-GB\",\"languages\":[\"en-GB\",\"en-US\",\"en\"],\"cookie_enabled\":true,\"on_line\":true,\"java_enabled\":false,\"do_not_track\":null,\"screen\":{\"width\":703,\"height\":1561,\"avail_width\":703,\"avail_height\":1561,\"color_depth\":24,\"pixel_depth\":24},\"window\":{\"inner_width\":616,\"inner_height\":1244,\"outer_width\":703,\"outer_height\":1561},\"device_memory\":4,\"hardware_concurrency\":8,\"connection\":{\"effective_type\":\"3g\",\"downlink\":1.3,\"rtt\":300}}', 0.01, 1, 'qr_scan', '09:14:47', ''),
(2, 7, 4, '2026-05-27 06:18:59', 'Sabrina', 'sabrina@gmail.com', '079 487 2433', '{\"browser\":\"Chrome 148\",\"device_type\":\"Mobile\",\"user_agent\":\"Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36\",\"screen_resolution\":\"703x1561\",\"platform\":\"Linux armv81\",\"language\":\"en-GB\",\"timezone\":\"Africa/Dar_es_Salaam\",\"cookies_enabled\":true,\"online_status\":true,\"java_enabled\":false,\"do_not_track\":null}', 'present', '2026-05-27 06:18:59', -6.9180582, 37.5605654, '-6.9180582, 37.5605654', '197.186.0.195', '{\"name\":\"Chrome\",\"version\":\"148.0\",\"engine\":\"Blink\",\"platform\":\"Linux armv81\",\"language\":\"en-GB\",\"languages\":[\"en-GB\",\"en-US\",\"en\"],\"cookie_enabled\":true,\"on_line\":true,\"java_enabled\":false,\"do_not_track\":null,\"screen\":{\"width\":703,\"height\":1561,\"avail_width\":703,\"avail_height\":1561,\"color_depth\":24,\"pixel_depth\":24},\"window\":{\"inner_width\":616,\"inner_height\":1244,\"outer_width\":703,\"outer_height\":1561},\"device_memory\":4,\"hardware_concurrency\":8,\"connection\":{\"effective_type\":\"3g\",\"downlink\":1.3,\"rtt\":400}}', 0.01, 1, 'qr_scan', '09:18:59', ''),
(3, 7, 8, '2026-05-27 07:20:13', 'Sabrina', 'sabrina@gmail.com', '079 487 2433', '{\"browser\":\"Chrome 148\",\"device_type\":\"Mobile\",\"user_agent\":\"Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36\",\"screen_resolution\":\"703x1561\",\"platform\":\"Linux armv81\",\"language\":\"en-GB\",\"timezone\":\"Africa/Dar_es_Salaam\",\"cookies_enabled\":true,\"online_status\":true,\"java_enabled\":false,\"do_not_track\":null}', 'present', '2026-05-27 07:20:13', -6.9180758, 37.5605850, '-6.9180758, 37.560585', '197.186.0.195', '{\"name\":\"Chrome\",\"version\":\"148.0\",\"engine\":\"Blink\",\"platform\":\"Linux armv81\",\"language\":\"en-GB\",\"languages\":[\"en-GB\",\"en-US\",\"en\"],\"cookie_enabled\":true,\"on_line\":true,\"java_enabled\":false,\"do_not_track\":null,\"screen\":{\"width\":703,\"height\":1561,\"avail_width\":703,\"avail_height\":1561,\"color_depth\":24,\"pixel_depth\":24},\"window\":{\"inner_width\":616,\"inner_height\":1244,\"outer_width\":703,\"outer_height\":1561},\"device_memory\":4,\"hardware_concurrency\":8,\"connection\":{\"effective_type\":\"3g\",\"downlink\":1.3,\"rtt\":600}}', 0.01, 1, 'qr_scan', '10:20:13', ''),
(4, 2, 8, '2026-05-27 07:29:22', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', '{\"browser\":\"Chrome v148\",\"browser_name\":\"Chrome\",\"os_name\":\"Linux\",\"platform\":\"Linux armv81\",\"device_type\":\"Mobile\",\"screen_resolution\":\"703x1561\",\"screen_dpi\":\"1.168784260749817x\",\"device_memory\":\"4 GB\",\"cpu_cores\":8,\"connection_type\":\"3g\",\"language\":\"en-GB\",\"timezone\":\"Africa/Dar_es_Salaam\",\"cookies_enabled\":\"Yes\",\"online_status\":\"Online\",\"user_agent\":\"Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36\"}', 'present', '2026-05-27 07:29:22', -6.9180473, 37.5605456, '-6.9180473, 37.5605456', '197.186.0.195', '{\"name\":\"Chrome\",\"version\":\"148.0\",\"engine\":\"Blink\",\"platform\":\"Linux armv81\",\"language\":\"en-GB\",\"languages\":[\"en-GB\",\"en-US\",\"en\"],\"cookie_enabled\":true,\"on_line\":true,\"java_enabled\":false,\"do_not_track\":null,\"screen\":{\"width\":703,\"height\":1561,\"avail_width\":703,\"avail_height\":1561,\"color_depth\":24,\"pixel_depth\":24},\"window\":{\"inner_width\":616,\"inner_height\":1244,\"outer_width\":703,\"outer_height\":1561},\"device_memory\":4,\"hardware_concurrency\":8,\"connection\":{\"effective_type\":\"3g\",\"downlink\":1.3,\"rtt\":500}}', 0.01, 1, 'qr_scan', '10:29:22', ''),
(5, 2, 10, '2026-06-03 10:36:31', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', '{\"browser\":\"Chrome v148\",\"browser_name\":\"Chrome\",\"os_name\":\"Linux\",\"platform\":\"Linux armv81\",\"device_type\":\"Mobile\",\"screen_resolution\":\"703x1561\",\"screen_dpi\":\"1.168784260749817x\",\"device_memory\":\"4 GB\",\"cpu_cores\":8,\"connection_type\":\"4g\",\"language\":\"en-GB\",\"timezone\":\"Africa/Dar_es_Salaam\",\"cookies_enabled\":\"Yes\",\"online_status\":\"Online\",\"user_agent\":\"Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36\"}', 'present', '2026-06-03 10:36:31', NULL, NULL, '', '197.186.4.194', '{\"name\":\"Chrome\",\"version\":\"148.0\",\"engine\":\"Blink\",\"platform\":\"Linux armv81\",\"language\":\"en-GB\",\"languages\":[\"en-GB\",\"en-US\",\"en\"],\"cookie_enabled\":true,\"on_line\":true,\"java_enabled\":false,\"do_not_track\":null,\"screen\":{\"width\":703,\"height\":1561,\"avail_width\":703,\"avail_height\":1561,\"color_depth\":24,\"pixel_depth\":24},\"window\":{\"inner_width\":616,\"inner_height\":1244,\"outer_width\":703,\"outer_height\":1561},\"device_memory\":4,\"hardware_concurrency\":8,\"connection\":{\"effective_type\":\"4g\",\"downlink\":1.6,\"rtt\":200}}', NULL, 1, 'qr_scan', '13:36:31', ''),
(6, 2, 12, '2026-06-05 07:33:04', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', '{\"browser\":\"Chrome v148\",\"browser_name\":\"Chrome\",\"os_name\":\"Android\",\"platform\":\"Linux armv81\",\"device_type\":\"Mobile\",\"screen_resolution\":\"703x1561\",\"screen_dpi\":\"1.168784260749817x\",\"device_memory\":\"4 GB\",\"cpu_cores\":8,\"connection_type\":\"3g\",\"language\":\"en-GB\",\"timezone\":\"Africa/Dar_es_Salaam\",\"cookies_enabled\":\"Yes\",\"online_status\":\"Online\",\"user_agent\":\"Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36\"}', 'present', '2026-06-05 07:33:04', NULL, NULL, '', '197.186.4.194', '{\"name\":\"Chrome\",\"version\":\"148.0\",\"engine\":\"Blink\",\"platform\":\"Linux armv81\",\"language\":\"en-GB\",\"languages\":[\"en-GB\",\"en-US\",\"en\"],\"cookie_enabled\":true,\"on_line\":true,\"java_enabled\":false,\"do_not_track\":null,\"screen\":{\"width\":703,\"height\":1561,\"avail_width\":703,\"avail_height\":1561,\"color_depth\":24,\"pixel_depth\":24},\"window\":{\"inner_width\":616,\"inner_height\":1244,\"outer_width\":703,\"outer_height\":1561},\"device_memory\":4,\"hardware_concurrency\":8,\"connection\":{\"effective_type\":\"3g\",\"downlink\":1.45,\"rtt\":350}}', NULL, 1, 'qr_scan', '10:33:04', ''),
(7, 2, 13, '2026-06-05 08:37:35', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', '{\"browser\":\"Chrome v148\",\"browser_name\":\"Chrome\",\"os_name\":\"Android\",\"platform\":\"Linux armv81\",\"device_type\":\"Mobile\",\"screen_resolution\":\"703x1561\",\"screen_dpi\":\"1.168784260749817x\",\"device_memory\":\"4 GB\",\"cpu_cores\":8,\"connection_type\":\"3g\",\"language\":\"en-GB\",\"timezone\":\"Africa/Dar_es_Salaam\",\"cookies_enabled\":\"Yes\",\"online_status\":\"Online\",\"user_agent\":\"Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36\"}', 'present', '2026-06-05 08:37:35', -6.9245573, 37.5672729, '-6.9245573, 37.5672729', '196.249.111.166', '{\"name\":\"Chrome\",\"version\":\"148.0\",\"engine\":\"Blink\",\"platform\":\"Linux armv81\",\"language\":\"en-GB\",\"languages\":[\"en-GB\",\"en-US\",\"en\"],\"cookie_enabled\":true,\"on_line\":true,\"java_enabled\":false,\"do_not_track\":null,\"screen\":{\"width\":703,\"height\":1561,\"avail_width\":703,\"avail_height\":1561,\"color_depth\":24,\"pixel_depth\":24},\"window\":{\"inner_width\":616,\"inner_height\":1244,\"outer_width\":703,\"outer_height\":1561},\"device_memory\":4,\"hardware_concurrency\":8,\"connection\":{\"effective_type\":\"3g\",\"downlink\":1.45,\"rtt\":750}}', 0.04, 1, 'qr_scan', '11:37:35', ''),
(8, 2, 14, '2026-07-11 09:47:44', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', '{\"browser\":\"Chrome v150\",\"browser_name\":\"Chrome\",\"os_name\":\"Android\",\"platform\":\"Linux armv81\",\"device_type\":\"Mobile\",\"screen_resolution\":\"848x1883\",\"screen_dpi\":\"0.9692357778549194x\",\"device_memory\":\"4 GB\",\"cpu_cores\":8,\"connection_type\":\"3g\",\"language\":\"en-GB\",\"timezone\":\"Africa/Dar_es_Salaam\",\"cookies_enabled\":\"Yes\",\"online_status\":\"Online\",\"user_agent\":\"Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36\"}', 'present', '2026-07-11 09:47:44', -6.9181585, 37.5606097, '-6.9181585, 37.5606097', '197.186.16.72', '{\"name\":\"Chrome\",\"version\":\"150.0\",\"engine\":\"Blink\",\"platform\":\"Linux armv81\",\"language\":\"en-GB\",\"languages\":[\"en-GB\",\"en-US\",\"en\"],\"cookie_enabled\":true,\"on_line\":true,\"java_enabled\":false,\"do_not_track\":null,\"screen\":{\"width\":848,\"height\":1883,\"avail_width\":848,\"avail_height\":1883,\"color_depth\":24,\"pixel_depth\":24},\"window\":{\"inner_width\":707,\"inner_height\":1541,\"outer_width\":848,\"outer_height\":1883},\"device_memory\":4,\"hardware_concurrency\":8,\"connection\":{\"effective_type\":\"3g\",\"downlink\":0.4,\"rtt\":400}}', 0.01, 1, 'qr_scan', '12:47:44', ''),
(9, 2, 15, '2026-07-11 10:54:44', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', '{\"browser\":\"Chrome v150\",\"browser_name\":\"Chrome\",\"os_name\":\"Android\",\"platform\":\"Linux armv81\",\"device_type\":\"Mobile\",\"screen_resolution\":\"848x1883\",\"screen_dpi\":\"0.9692357778549194x\",\"device_memory\":\"4 GB\",\"cpu_cores\":8,\"connection_type\":\"3g\",\"language\":\"en-GB\",\"timezone\":\"Africa/Dar_es_Salaam\",\"cookies_enabled\":\"Yes\",\"online_status\":\"Online\",\"user_agent\":\"Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36\"}', 'present', '2026-07-11 10:54:44', -6.9181600, 37.5606085, '-6.91816, 37.5606085', '197.186.16.72', '{\"name\":\"Chrome\",\"version\":\"150.0\",\"engine\":\"Blink\",\"platform\":\"Linux armv81\",\"language\":\"en-GB\",\"languages\":[\"en-GB\",\"en-US\",\"en\"],\"cookie_enabled\":true,\"on_line\":true,\"java_enabled\":false,\"do_not_track\":null,\"screen\":{\"width\":848,\"height\":1883,\"avail_width\":848,\"avail_height\":1883,\"color_depth\":24,\"pixel_depth\":24},\"window\":{\"inner_width\":707,\"inner_height\":1541,\"outer_width\":848,\"outer_height\":1883},\"device_memory\":4,\"hardware_concurrency\":8,\"connection\":{\"effective_type\":\"3g\",\"downlink\":0.35,\"rtt\":400}}', 0.01, 1, 'qr_scan', '13:54:44', ''),
(10, 7, 15, '2026-07-11 10:56:16', 'Sabrina', 'sabrina@gmail.com', '079 487 2433', '{\"browser\":\"Chrome v150\",\"browser_name\":\"Chrome\",\"os_name\":\"Android\",\"platform\":\"Linux armv81\",\"device_type\":\"Mobile\",\"screen_resolution\":\"848x1883\",\"screen_dpi\":\"0.9692357778549194x\",\"device_memory\":\"4 GB\",\"cpu_cores\":8,\"connection_type\":\"3g\",\"language\":\"en-GB\",\"timezone\":\"Africa/Dar_es_Salaam\",\"cookies_enabled\":\"Yes\",\"online_status\":\"Online\",\"user_agent\":\"Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36\"}', 'present', '2026-07-11 10:56:16', -6.9181622, 37.5605996, '-6.9181622, 37.5605996', '197.186.16.72', '{\"name\":\"Chrome\",\"version\":\"150.0\",\"engine\":\"Blink\",\"platform\":\"Linux armv81\",\"language\":\"en-GB\",\"languages\":[\"en-GB\",\"en-US\",\"en\"],\"cookie_enabled\":true,\"on_line\":true,\"java_enabled\":false,\"do_not_track\":null,\"screen\":{\"width\":848,\"height\":1883,\"avail_width\":848,\"avail_height\":1883,\"color_depth\":24,\"pixel_depth\":24},\"window\":{\"inner_width\":707,\"inner_height\":1541,\"outer_width\":848,\"outer_height\":1883},\"device_memory\":4,\"hardware_concurrency\":8,\"connection\":{\"effective_type\":\"3g\",\"downlink\":0.35,\"rtt\":550}}', 0.01, 1, 'qr_scan', '13:56:16', ''),
(11, 12, 15, '2026-07-11 10:59:08', 'Asante', 'symon@gmail.com', '07845845848', '{\"browser\":\"Chrome v150\",\"browser_name\":\"Chrome\",\"os_name\":\"Android\",\"platform\":\"Linux armv81\",\"device_type\":\"Mobile\",\"screen_resolution\":\"848x1883\",\"screen_dpi\":\"0.9692357778549194x\",\"device_memory\":\"4 GB\",\"cpu_cores\":8,\"connection_type\":\"3g\",\"language\":\"en-GB\",\"timezone\":\"Africa/Dar_es_Salaam\",\"cookies_enabled\":\"Yes\",\"online_status\":\"Online\",\"user_agent\":\"Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36\"}', 'present', '2026-07-11 10:59:08', -6.9181640, 37.5606070, '-6.918164, 37.560607', '197.186.16.72', '{\"name\":\"Chrome\",\"version\":\"150.0\",\"engine\":\"Blink\",\"platform\":\"Linux armv81\",\"language\":\"en-GB\",\"languages\":[\"en-GB\",\"en-US\",\"en\"],\"cookie_enabled\":true,\"on_line\":true,\"java_enabled\":false,\"do_not_track\":null,\"screen\":{\"width\":848,\"height\":1883,\"avail_width\":848,\"avail_height\":1883,\"color_depth\":24,\"pixel_depth\":24},\"window\":{\"inner_width\":707,\"inner_height\":1541,\"outer_width\":848,\"outer_height\":1883},\"device_memory\":4,\"hardware_concurrency\":8,\"connection\":{\"effective_type\":\"3g\",\"downlink\":0.35,\"rtt\":450}}', 0.01, 1, 'qr_scan', '13:59:08', '');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `attendance_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`id`, `attendance_id`, `user_id`, `event_id`, `action`, `details`, `created_at`) VALUES
(1, 1, 2, 4, 'check_in', 'Status: present | Distance: 0.01km | Phone matched: Yes | IP: 197.186.0.195', '2026-05-27 06:14:47'),
(2, 2, 7, 4, 'check_in', 'Status: present | Distance: 0.01km | Phone matched: Yes | IP: 197.186.0.195', '2026-05-27 06:18:59'),
(3, 3, 7, 8, 'check_in', 'Status: present | Distance: 0.01km | Phone matched: Yes | IP: 197.186.0.195', '2026-05-27 07:20:13'),
(4, 4, 2, 8, 'check_in', 'Status: present | Distance: 0.01km | Phone matched: Yes | IP: 197.186.0.195', '2026-05-27 07:29:22'),
(5, 5, 2, 10, 'check_in', 'Status: present | Distance: N/A | Phone matched: Yes | IP: 197.186.4.194', '2026-06-03 10:36:31'),
(6, 6, 2, 12, 'check_in', 'Status: present | Distance: N/A | Phone matched: Yes | IP: 197.186.4.194', '2026-06-05 07:33:04'),
(7, 7, 2, 13, 'check_in', 'Status: present | Distance: 0.04km | Phone matched: Yes | IP: 196.249.111.166', '2026-06-05 08:37:35'),
(8, 8, 2, 14, 'check_in', 'Status: present | Distance: 0.01km | Phone matched: Yes | IP: 197.186.16.72', '2026-07-11 09:47:44'),
(9, 9, 2, 15, 'check_in', 'Status: present | Distance: 0.01km | Phone matched: Yes | IP: 197.186.16.72', '2026-07-11 10:54:44'),
(10, 10, 7, 15, 'check_in', 'Status: present | Distance: 0.01km | Phone matched: Yes | IP: 197.186.16.72', '2026-07-11 10:56:16'),
(11, 11, 12, 15, 'check_in', 'Status: present | Distance: 0.01km | Phone matched: Yes | IP: 197.186.16.72', '2026-07-11 10:59:08');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` int(11) DEFAULT NULL,
  `check_in_time` datetime NOT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `attendance_type` enum('check_in','check_out','both') DEFAULT 'check_in',
  `qr_code_used` varchar(255) DEFAULT NULL,
  `device_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`device_info`)),
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(11,8) DEFAULT NULL,
  `verification_method` enum('qr_code','manual','nfc','biometric') DEFAULT 'qr_code',
  `is_verified` tinyint(1) DEFAULT 1,
  `verified_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_events`
--

CREATE TABLE `deleted_events` (
  `id` int(11) NOT NULL,
  `original_event_id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `deleted_by` int(11) NOT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` text DEFAULT NULL,
  `attendance_data_preserved` tinyint(1) DEFAULT 1,
  `event_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deleted_events`
--

INSERT INTO `deleted_events` (`id`, `original_event_id`, `event_name`, `deleted_by`, `deleted_at`, `reason`, `attendance_data_preserved`, `event_data`) VALUES
(1, 4, 'Home Practical', 4, '2026-05-27 06:38:50', 'Event deleted by organizer', 1, '{\"id\":\"4\",\"title\":null,\"description\":\"Development Test\",\"short_description\":null,\"category_id\":null,\"organization_id\":\"1\",\"venue_id\":\"6\",\"start_datetime\":null,\"end_datetime\":null,\"registration_start\":null,\"registration_end\":null,\"attendance_start\":null,\"attendance_end\":null,\"max_attendees\":null,\"is_public\":\"1\",\"requires_approval\":\"0\",\"is_recurring\":\"0\",\"recurring_pattern\":null,\"event_image\":null,\"banner_image\":null,\"status\":\"draft\",\"access_code\":\"\",\"qr_code_data\":null,\"settings\":null,\"created_by\":\"4\",\"created_at\":\"2026-05-27 09:01:40\",\"updated_at\":\"2026-05-27 09:08:30\",\"name\":\"Home Practical\",\"date\":\"2026-05-27\",\"time\":\"09:03:00\",\"image\":\"logo.png\",\"venue_name\":\"Home\",\"venue_location\":\"Changarawe, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga\",\"location_lat\":-6.9180997,\"location_lng\":37.5606289,\"target_audience\":\"all\",\"type\":\"online\",\"registration_mode\":\"self\",\"invited_emails\":\"\",\"end_time\":\"09:20:00\",\"deleted\":\"0\",\"deleted_at\":null,\"max_distance_km\":\"0.20\"}'),
(2, 9, 'Home Practical', 4, '2026-06-03 17:32:55', 'Event deleted by organizer', 1, '{\"id\":\"9\",\"title\":null,\"description\":\"Try To Scan QR test for project\",\"short_description\":null,\"category_id\":null,\"organization_id\":\"1\",\"venue_id\":\"6\",\"start_datetime\":null,\"end_datetime\":null,\"registration_start\":null,\"registration_end\":null,\"attendance_start\":\"17:15:00\",\"attendance_end\":\"17:45:00\",\"max_attendees\":null,\"is_public\":\"1\",\"requires_approval\":\"0\",\"is_recurring\":\"0\",\"recurring_pattern\":null,\"event_image\":null,\"banner_image\":null,\"status\":\"draft\",\"access_code\":null,\"qr_code_data\":null,\"settings\":null,\"created_by\":\"4\",\"created_at\":\"2026-05-27 17:07:50\",\"updated_at\":\"2026-05-27 17:07:50\",\"name\":\"Home Practical\",\"date\":\"2026-05-27\",\"time\":\"17:14:00\",\"image\":\"uploads\\/event_4_c04350f22bc87bb4.png\",\"venue_name\":\"Home\",\"venue_location\":\"Changarawe, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga\",\"location_lat\":-6.9180997,\"location_lng\":37.5606289,\"target_audience\":\"all\",\"type\":\"online\",\"registration_mode\":\"0\",\"invited_emails\":\"\",\"end_time\":\"17:45:00\",\"deleted\":\"0\",\"deleted_at\":null,\"max_distance_km\":\"0.12\"}'),
(3, 8, 'Home Practical', 4, '2026-06-03 17:33:06', 'Event deleted by organizer', 1, '{\"id\":\"8\",\"title\":null,\"description\":\"Testing System Development\",\"short_description\":null,\"category_id\":null,\"organization_id\":\"1\",\"venue_id\":\"6\",\"start_datetime\":null,\"end_datetime\":null,\"registration_start\":null,\"registration_end\":null,\"attendance_start\":\"10:19:00\",\"attendance_end\":\"10:48:00\",\"max_attendees\":null,\"is_public\":\"1\",\"requires_approval\":\"0\",\"is_recurring\":\"0\",\"recurring_pattern\":null,\"event_image\":null,\"banner_image\":null,\"status\":\"draft\",\"access_code\":null,\"qr_code_data\":null,\"settings\":null,\"created_by\":\"4\",\"created_at\":\"2026-05-27 10:17:56\",\"updated_at\":\"2026-05-27 10:17:56\",\"name\":\"Home Practical\",\"date\":\"2026-05-27\",\"time\":\"10:18:00\",\"image\":\"uploads\\/event_4_6aab69b9c31ccf17.png\",\"venue_name\":\"Home\",\"venue_location\":\"Changarawe, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga\",\"location_lat\":-6.9180997,\"location_lng\":37.5606289,\"target_audience\":\"all\",\"type\":\"online\",\"registration_mode\":\"0\",\"invited_emails\":\"\",\"end_time\":\"10:48:00\",\"deleted\":\"0\",\"deleted_at\":null,\"max_distance_km\":\"0.15\"}'),
(4, 7, 'Home Practical', 4, '2026-06-03 17:33:17', 'Event deleted by organizer', 1, '{\"id\":\"7\",\"title\":null,\"description\":\"Testing System Development\",\"short_description\":null,\"category_id\":null,\"organization_id\":\"1\",\"venue_id\":\"6\",\"start_datetime\":null,\"end_datetime\":null,\"registration_start\":null,\"registration_end\":null,\"attendance_start\":\"09:47:00\",\"attendance_end\":\"10:00:00\",\"max_attendees\":null,\"is_public\":\"1\",\"requires_approval\":\"0\",\"is_recurring\":\"0\",\"recurring_pattern\":null,\"event_image\":null,\"banner_image\":null,\"status\":\"draft\",\"access_code\":null,\"qr_code_data\":null,\"settings\":null,\"created_by\":\"4\",\"created_at\":\"2026-05-27 09:47:00\",\"updated_at\":\"2026-05-27 09:47:00\",\"name\":\"Home Practical\",\"date\":\"2026-05-27\",\"time\":\"09:47:00\",\"image\":\"uploads\\/event_4_1108568d9602a4eb.png\",\"venue_name\":\"Home\",\"venue_location\":\"Changarawe, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga\",\"location_lat\":-6.9180997,\"location_lng\":37.5606289,\"target_audience\":\"all\",\"type\":\"online\",\"registration_mode\":\"0\",\"invited_emails\":\"\",\"end_time\":\"10:00:00\",\"deleted\":\"0\",\"deleted_at\":null,\"max_distance_km\":\"0.10\"}'),
(5, 3, 'Financial Account Meeting', 4, '2026-06-03 17:33:30', 'Event deleted by organizer', 1, '{\"id\":\"3\",\"title\":null,\"description\":\"Financial Institution Discussion\",\"short_description\":null,\"category_id\":null,\"organization_id\":\"1\",\"venue_id\":\"5\",\"start_datetime\":null,\"end_datetime\":null,\"registration_start\":null,\"registration_end\":null,\"attendance_start\":null,\"attendance_end\":null,\"max_attendees\":null,\"is_public\":\"1\",\"requires_approval\":\"0\",\"is_recurring\":\"0\",\"recurring_pattern\":null,\"event_image\":null,\"banner_image\":null,\"status\":\"draft\",\"access_code\":\"987654321\",\"qr_code_data\":null,\"settings\":null,\"created_by\":\"4\",\"created_at\":\"2026-05-26 06:57:21\",\"updated_at\":\"2026-05-27 09:08:30\",\"name\":\"Financial Account Meeting\",\"date\":\"2026-05-26\",\"time\":\"06:57:00\",\"image\":\"uploads\\/event_4_1a8b1503c12e33c9.jpg\",\"venue_name\":\"Fanoni hall\",\"venue_location\":\"New Assembly Hall (NAH), Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga\",\"location_lat\":-6.9247263,\"location_lng\":37.5679556,\"target_audience\":\"all\",\"type\":\"online\",\"registration_mode\":\"self\",\"invited_emails\":\"\",\"end_time\":\"07:05:00\",\"deleted\":\"0\",\"deleted_at\":null,\"max_distance_km\":\"0.50\"}'),
(6, 10, 'Changarawe Development Testing', 4, '2026-06-05 07:11:38', 'Event deleted by organizer', 1, '{\"id\":\"10\",\"title\":null,\"description\":\"Testing the system and improvement\",\"short_description\":null,\"category_id\":null,\"organization_id\":\"1\",\"venue_id\":\"6\",\"start_datetime\":null,\"end_datetime\":null,\"registration_start\":null,\"registration_end\":null,\"attendance_start\":\"13:27:00\",\"attendance_end\":\"13:57:00\",\"max_attendees\":null,\"is_public\":\"1\",\"requires_approval\":\"0\",\"is_recurring\":\"0\",\"recurring_pattern\":null,\"event_image\":null,\"banner_image\":null,\"status\":\"draft\",\"access_code\":null,\"qr_code_data\":null,\"settings\":null,\"created_by\":\"4\",\"created_at\":\"2026-06-03 13:22:42\",\"updated_at\":\"2026-06-03 13:22:42\",\"name\":\"Changarawe Development Testing\",\"date\":\"2026-06-03\",\"time\":\"13:25:00\",\"image\":\"uploads\\/event_4_96ed91fc14532f10.jpg\",\"venue_name\":\"Home\",\"venue_location\":\"Changarawe, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga\",\"location_lat\":-6.9180997,\"location_lng\":37.5606289,\"target_audience\":\"all\",\"type\":\"online\",\"registration_mode\":\"0\",\"invited_emails\":\"\",\"end_time\":\"13:58:00\",\"deleted\":\"0\",\"deleted_at\":null,\"max_distance_km\":null}'),
(7, 11, 'Home Practical', 4, '2026-06-22 04:30:17', 'Event deleted by organizer', 1, '{\"id\":\"11\",\"title\":null,\"description\":\"Development issue\",\"short_description\":null,\"category_id\":null,\"organization_id\":\"1\",\"venue_id\":\"6\",\"start_datetime\":null,\"end_datetime\":null,\"registration_start\":null,\"registration_end\":null,\"attendance_start\":\"20:51:00\",\"attendance_end\":\"21:21:00\",\"max_attendees\":null,\"is_public\":\"1\",\"requires_approval\":\"0\",\"is_recurring\":\"0\",\"recurring_pattern\":null,\"event_image\":null,\"banner_image\":null,\"status\":\"draft\",\"access_code\":null,\"qr_code_data\":null,\"settings\":null,\"created_by\":\"4\",\"created_at\":\"2026-06-03 21:01:10\",\"updated_at\":\"2026-06-08 15:27:33\",\"name\":\"Home Practical\",\"date\":\"2026-06-03\",\"time\":\"21:01:00\",\"image\":\"uploads\\/event_4_ff91ebfcc1e16940.jpg\",\"venue_name\":\"Home\",\"venue_location\":\"Changarawe, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga\",\"location_lat\":-6.9180997,\"location_lng\":37.5606289,\"target_audience\":\"all\",\"type\":\"online\",\"registration_mode\":\"0\",\"invited_emails\":\"\",\"end_time\":\"21:50:00\",\"deleted\":\"0\",\"deleted_at\":null,\"max_distance_km\":\"0.08\"}');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `venue_id` int(11) DEFAULT NULL,
  `start_datetime` datetime DEFAULT NULL,
  `end_datetime` datetime DEFAULT NULL,
  `registration_start` datetime DEFAULT NULL,
  `registration_end` datetime DEFAULT NULL,
  `attendance_start` time DEFAULT NULL,
  `attendance_end` time DEFAULT NULL,
  `max_attendees` int(11) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `requires_approval` tinyint(1) DEFAULT 0,
  `is_recurring` tinyint(1) DEFAULT 0,
  `recurring_pattern` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recurring_pattern`)),
  `event_image` varchar(255) DEFAULT NULL,
  `banner_image` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','cancelled','completed') DEFAULT 'draft',
  `access_code` varchar(20) DEFAULT NULL,
  `qr_code_data` text DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `name` varchar(255) NOT NULL DEFAULT 'Untitled Event',
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `image` varchar(255) DEFAULT 'logo.png',
  `venue_name` varchar(255) DEFAULT NULL,
  `venue_location` varchar(255) DEFAULT NULL,
  `location_lat` decimal(10,7) DEFAULT NULL,
  `location_lng` decimal(10,7) DEFAULT NULL,
  `target_audience` varchar(255) DEFAULT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'online',
  `registration_mode` varchar(20) NOT NULL DEFAULT 'self',
  `invited_emails` text DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `max_distance_km` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `short_description`, `category_id`, `organization_id`, `venue_id`, `start_datetime`, `end_datetime`, `registration_start`, `registration_end`, `attendance_start`, `attendance_end`, `max_attendees`, `is_public`, `requires_approval`, `is_recurring`, `recurring_pattern`, `event_image`, `banner_image`, `status`, `access_code`, `qr_code_data`, `settings`, `created_by`, `created_at`, `updated_at`, `name`, `date`, `time`, `image`, `venue_name`, `venue_location`, `location_lat`, `location_lng`, `target_audience`, `type`, `registration_mode`, `invited_emails`, `end_time`, `deleted`, `deleted_at`, `max_distance_km`) VALUES
(1, NULL, 'financial Description', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, NULL, NULL, NULL, 'draft', '086136', NULL, NULL, 1, '2026-05-22 07:10:18', '2026-05-27 06:08:30', 'Financial Account Meeting', '2026-05-22', '10:30:00', 'uploads/event_1_7e84277f294f00dc.jpg', 'Samora hall', '-6.9250300, 37.5671400', -6.9250300, 37.5671400, 'final year', 'online', 'code', 'meshackkaaya50@gmail.com\nakilykaaya@gmail.com', '11:20:00', 1, '2026-05-24 21:16:06', 0.71),
(2, NULL, 'meeting', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, NULL, NULL, NULL, 'draft', '170347', NULL, NULL, 1, '2026-05-22 07:40:52', '2026-05-27 06:08:30', 'Stakeholder meeting', '2026-05-08', '10:43:00', 'uploads/event_1_1b65fcb38e032c2f.jpg', 'Fanoni hall', 'Mzumbe, Morogoro', NULL, NULL, 'final year student', 'online', 'self', '', '14:38:00', 1, '2026-05-24 21:16:06', 0.20),
(3, NULL, 'Financial Institution Discussion', NULL, NULL, 1, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, NULL, NULL, NULL, 'draft', '987654321', NULL, NULL, 4, '2026-05-26 03:57:21', '2026-06-03 17:33:30', 'Financial Account Meeting', '2026-05-26', '06:57:00', 'uploads/event_4_1a8b1503c12e33c9.jpg', 'Fanoni hall', 'New Assembly Hall (NAH), Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga', -6.9247263, 37.5679556, 'all', 'online', 'self', '', '07:05:00', 1, '2026-06-03 17:33:30', 0.50),
(4, NULL, 'Development Test', NULL, NULL, 1, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, NULL, NULL, NULL, 'draft', '', NULL, NULL, 4, '2026-05-27 06:01:40', '2026-05-27 06:38:50', 'Home Practical', '2026-05-27', '09:03:00', 'logo.png', 'Home', 'Changarawe, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga', -6.9180997, 37.5606289, 'all', 'online', 'self', '', '09:20:00', 1, '2026-05-27 06:38:50', 0.20),
(7, NULL, 'Testing System Development', NULL, NULL, 1, 6, NULL, NULL, NULL, NULL, '09:47:00', '10:00:00', NULL, 1, 0, 0, NULL, NULL, NULL, 'draft', NULL, NULL, NULL, 4, '2026-05-27 06:47:00', '2026-06-03 17:33:17', 'Home Practical', '2026-05-27', '09:47:00', 'uploads/event_4_1108568d9602a4eb.png', 'Home', 'Changarawe, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga', -6.9180997, 37.5606289, 'all', 'online', '0', '', '10:00:00', 1, '2026-06-03 17:33:17', 0.10),
(8, NULL, 'Testing System Development', NULL, NULL, 1, 6, NULL, NULL, NULL, NULL, '10:19:00', '10:48:00', NULL, 1, 0, 0, NULL, NULL, NULL, 'draft', NULL, NULL, NULL, 4, '2026-05-27 07:17:56', '2026-06-03 17:33:06', 'Home Practical', '2026-05-27', '10:18:00', 'uploads/event_4_6aab69b9c31ccf17.png', 'Home', 'Changarawe, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga', -6.9180997, 37.5606289, 'all', 'online', '0', '', '10:48:00', 1, '2026-06-03 17:33:06', 0.15),
(9, NULL, 'Try To Scan QR test for project', NULL, NULL, 1, 6, NULL, NULL, NULL, NULL, '17:15:00', '17:45:00', NULL, 1, 0, 0, NULL, NULL, NULL, 'draft', NULL, NULL, NULL, 4, '2026-05-27 14:07:50', '2026-06-03 17:32:55', 'Home Practical', '2026-05-27', '17:14:00', 'uploads/event_4_c04350f22bc87bb4.png', 'Home', 'Changarawe, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga', -6.9180997, 37.5606289, 'all', 'online', '0', '', '17:45:00', 1, '2026-06-03 17:32:55', 0.12),
(10, NULL, 'Testing the system and improvement', NULL, NULL, 1, 6, NULL, NULL, NULL, NULL, '13:27:00', '13:57:00', NULL, 1, 0, 0, NULL, NULL, NULL, 'draft', NULL, NULL, NULL, 4, '2026-06-03 10:22:42', '2026-06-05 07:11:38', 'Changarawe Development Testing', '2026-06-03', '13:25:00', 'uploads/event_4_96ed91fc14532f10.jpg', 'Home', 'Changarawe, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga', -6.9180997, 37.5606289, 'all', 'online', '0', '', '13:58:00', 1, '2026-06-05 07:11:38', NULL),
(11, NULL, 'Development issue', NULL, NULL, 1, 6, NULL, NULL, NULL, NULL, '20:51:00', '21:21:00', NULL, 1, 0, 0, NULL, NULL, NULL, 'draft', NULL, NULL, NULL, 4, '2026-06-03 18:01:10', '2026-06-22 04:30:17', 'Home Practical', '2026-06-03', '21:01:00', 'uploads/event_4_ff91ebfcc1e16940.jpg', 'Home', 'Changarawe, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga', -6.9180997, 37.5606289, 'all', 'online', '0', '', '21:50:00', 1, '2026-06-22 04:30:17', 0.08),
(12, NULL, 'Check Lcation Error Event', NULL, NULL, 1, 7, NULL, NULL, NULL, NULL, '10:20:00', '10:50:00', NULL, 1, 0, 0, NULL, NULL, NULL, 'draft', NULL, NULL, NULL, 4, '2026-06-05 07:30:14', '2026-06-08 12:27:33', 'Try Error Checker', '2026-06-05', '10:30:00', 'uploads/event_4_57705e6976a69671.jpg', 'Selasie', 'Mzumbe Health Centre, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga', -6.9247404, 37.5669336, 'all', 'online', '0', '', '10:39:00', 0, NULL, 0.31),
(13, NULL, 'event testing', NULL, NULL, 1, 7, NULL, NULL, NULL, NULL, '11:25:00', '11:55:00', NULL, 1, 0, 0, NULL, NULL, NULL, 'draft', NULL, NULL, NULL, 4, '2026-06-05 08:35:26', '2026-06-08 12:27:33', 'Financial Account Meeting', '2026-06-05', '11:35:00', 'uploads/event_4_cd8fbcbad5c3044f.jpg', 'Selasie', 'Mzumbe Health Centre, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga', -6.9247404, 37.5669336, 'all', 'online', '0', '', '11:40:00', 0, NULL, 0.17),
(14, NULL, 'All about business', NULL, NULL, 1, 6, NULL, NULL, NULL, NULL, '12:33:00', '13:03:00', NULL, 1, 0, 0, NULL, NULL, NULL, 'draft', NULL, NULL, NULL, 4, '2026-07-11 09:36:48', '2026-07-11 09:36:48', 'Summit in New Orleans', '2026-07-11', '12:43:00', 'uploads/event_4_95f1401eafc5602b.jpg', 'Home', 'Changarawe, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga', -6.9180997, 37.5606289, 'all', 'online', '0', '', '12:59:00', 0, NULL, NULL),
(15, NULL, 'Risk management and Taking', NULL, NULL, 1, 6, NULL, NULL, NULL, NULL, '13:53:00', '14:33:00', NULL, 1, 0, 0, NULL, NULL, NULL, 'draft', NULL, NULL, NULL, 4, '2026-07-11 10:52:45', '2026-07-11 10:52:45', 'Risk Management', '2026-07-11', '14:23:00', 'logo.png', 'Home', 'Changarawe, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania, Sanga Sanga', -6.9180997, 37.5606289, 'all', 'online', '0', '', '14:45:00', 0, NULL, 0.05);

-- --------------------------------------------------------

--
-- Stand-in structure for view `event_attendance_summary`
-- (See below for the actual view)
--
CREATE TABLE `event_attendance_summary` (
`event_id` int(11)
,`title` varchar(200)
,`start_datetime` datetime
,`registered_count` bigint(21)
,`attended_count` bigint(21)
,`completed_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `event_categories`
--

CREATE TABLE `event_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#007bff',
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_categories`
--

INSERT INTO `event_categories` (`id`, `name`, `description`, `color`, `icon`, `created_at`) VALUES
(1, 'Conference', 'Professional conferences and seminars', '#007bff', 'fa-briefcase', '2026-05-18 17:52:36'),
(2, 'Workshop', 'Hands-on training sessions', '#28a745', 'fa-tools', '2026-05-18 17:52:36'),
(3, 'Social', 'Social gatherings and networking', '#ffc107', 'fa-users', '2026-05-18 17:52:36'),
(4, 'Sports', 'Sports and fitness events', '#dc3545', 'fa-running', '2026-05-18 17:52:36'),
(5, 'Cultural', 'Cultural events and performances', '#6f42c1', 'fa-theater-masks', '2026-05-18 17:52:36'),
(6, 'Academic', 'Academic events and lectures', '#17a2b8', 'fa-graduation-cap', '2026-05-18 17:52:36');

-- --------------------------------------------------------

--
-- Table structure for table `event_sessions`
--

CREATE TABLE `event_sessions` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `speaker` varchar(100) DEFAULT NULL,
  `max_attendees` int(11) DEFAULT NULL,
  `session_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_teams`
--

CREATE TABLE `event_teams` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('organizer','coordinator','volunteer','security') DEFAULT 'volunteer',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('event_reminder','attendance_confirmation','registration_status','system','announcement') DEFAULT 'system',
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `email_sent` tinyint(1) DEFAULT 0,
  `push_sent` tinyint(1) DEFAULT 0,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `brand_color` varchar(7) DEFAULT '#2563ff',
  `background_color` varchar(7) DEFAULT '#ffffff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`id`, `name`, `description`, `logo`, `website`, `contact_email`, `is_active`, `created_by`, `created_at`, `updated_at`, `brand_color`, `background_color`) VALUES
(1, 'MZUMBE UNIVERSITY', 'mzumbe university event attendance management', 'logo.png', '', 'succedkanael@gmail.com', 1, NULL, '2026-05-23 20:39:28', '2026-06-20 10:34:44', '#2563ff', '#ffffff'),
(2, 'UDOM UNIVERSITY', 'Security management', NULL, NULL, NULL, 0, NULL, '2026-05-24 21:37:18', '2026-06-05 06:44:34', '#2563ff', '#ffffff');

-- --------------------------------------------------------

--
-- Table structure for table `participants`
--

CREATE TABLE `participants` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `participant_name` varchar(255) DEFAULT NULL,
  `participant_email` varchar(255) DEFAULT NULL,
  `participant_phone` varchar(50) DEFAULT NULL,
  `invite_status` varchar(30) NOT NULL DEFAULT 'registered',
  `invited_at` timestamp NULL DEFAULT current_timestamp(),
  `access_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `participants`
--

INSERT INTO `participants` (`id`, `user_id`, `event_id`, `created_at`, `participant_name`, `participant_email`, `participant_phone`, `invite_status`, `invited_at`, `access_code`) VALUES
(1, 1, 1, '2026-05-22 07:10:18', 'Kaaya Meshack', 'akilykaaya@gmail.com', '0794872433', 'registered', '2026-05-22 07:10:18', '796977'),
(2, 1, 2, '2026-05-22 07:40:52', 'Kaaya Meshack', 'akilykaaya@gmail.com', '0794872433', 'registered', '2026-05-22 07:40:52', ''),
(3, 2, 1, '2026-05-22 07:53:54', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', 'registered', '2026-05-22 07:53:54', '738384'),
(4, 4, 3, '2026-05-26 03:57:21', 'Akily2026', 'akilyhawkwolf@gmail.com', '+255773702533', 'registered', '2026-05-26 03:57:21', ''),
(5, 2, 3, '2026-05-26 05:53:33', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', 'registered', '2026-05-26 05:53:33', ''),
(6, 4, 4, '2026-05-27 06:01:40', 'Akily2026', 'akilyhawkwolf@gmail.com', '+255773702533', 'registered', '2026-05-27 06:01:40', ''),
(7, 2, 4, '2026-05-27 06:14:16', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', 'registered', '2026-05-27 06:14:16', ''),
(8, 7, 4, '2026-05-27 06:18:42', 'Sabrina', 'sabrina@gmail.com', '079 487 2433', 'registered', '2026-05-27 06:18:42', ''),
(9, 4, 7, '2026-05-27 06:47:00', 'Akily2026', 'akilyhawkwolf@gmail.com', '+255773702533', 'registered', '2026-05-27 06:47:00', ''),
(10, 4, 8, '2026-05-27 07:17:56', 'Akily2026', 'akilyhawkwolf@gmail.com', '+255773702533', 'registered', '2026-05-27 07:17:56', ''),
(11, 7, 8, '2026-05-27 07:19:49', 'Sabrina', 'sabrina@gmail.com', '079 487 2433', 'registered', '2026-05-27 07:19:49', ''),
(12, 2, 8, '2026-05-27 07:29:08', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', 'registered', '2026-05-27 07:29:08', ''),
(13, 4, 9, '2026-05-27 14:07:50', 'Akily2026', 'akilyhawkwolf@gmail.com', '+255773702533', 'registered', '2026-05-27 14:07:50', ''),
(14, 2, 9, '2026-05-27 14:11:10', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', 'registered', '2026-05-27 14:11:10', ''),
(15, 4, 10, '2026-06-03 10:22:42', 'Akily2026', 'akilyhawkwolf@gmail.com', '+255773702533', 'registered', '2026-06-03 10:22:42', ''),
(16, 2, 10, '2026-06-03 10:35:10', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', 'registered', '2026-06-03 10:35:10', ''),
(17, 4, 11, '2026-06-03 18:01:10', 'Akily2026', 'akilyhawkwolf@gmail.com', '+255773702533', 'registered', '2026-06-03 18:01:10', ''),
(18, 4, 12, '2026-06-05 07:30:14', 'Akily2026', 'akilyhawkwolf@gmail.com', '+255773702533', 'registered', '2026-06-05 07:30:14', ''),
(19, 2, 12, '2026-06-05 07:32:50', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', 'registered', '2026-06-05 07:32:50', ''),
(20, 4, 13, '2026-06-05 08:35:26', 'Akily2026', 'akilyhawkwolf@gmail.com', '+255773702533', 'registered', '2026-06-05 08:35:26', ''),
(21, 2, 13, '2026-06-05 08:36:36', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', 'registered', '2026-06-05 08:36:36', ''),
(22, 4, 14, '2026-07-11 09:36:48', 'Akily2026', 'akilyhawkwolf@gmail.com', '+255773702533', 'registered', '2026-07-11 09:36:48', ''),
(23, 2, 14, '2026-07-11 09:47:01', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', 'registered', '2026-07-11 09:47:01', ''),
(24, 4, 15, '2026-07-11 10:52:45', 'Akily2026', 'akilyhawkwolf@gmail.com', '+255773702533', 'registered', '2026-07-11 10:52:45', ''),
(25, 2, 15, '2026-07-11 10:53:50', 'Meshack Kaaya', 'meshackkaaya50@gmail.com', '+255794872433', 'registered', '2026-07-11 10:53:50', ''),
(26, 7, 15, '2026-07-11 10:56:07', 'Sabrina', 'sabrina@gmail.com', '079 487 2433', 'registered', '2026-07-11 10:56:07', ''),
(27, 12, 15, '2026-07-11 10:58:54', 'Asante', 'symon@gmail.com', '07845845848', 'registered', '2026-07-11 10:58:54', '');

-- --------------------------------------------------------

--
-- Table structure for table `qr_codes`
--

CREATE TABLE `qr_codes` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `session_id` int(11) DEFAULT NULL,
  `code_data` varchar(500) NOT NULL,
  `code_type` enum('event','session','check_in','check_out') DEFAULT 'event',
  `is_active` tinyint(1) DEFAULT 1,
  `expires_at` datetime DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `generated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_type` enum('individual','group') DEFAULT 'individual',
  `group_size` int(11) DEFAULT 1,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `report_type` enum('attendance_summary','registration_stats','demographics','session_breakdown') DEFAULT 'attendance_summary',
  `title` varchar(200) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `generated_by` int(11) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type` enum('string','number','boolean','json') DEFAULT 'string',
  `is_public` tinyint(1) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `description`, `type`, `is_public`, `updated_by`, `updated_at`) VALUES
(1, 'site_name', 'QR Attendance System', 'Name of the application', 'string', 1, NULL, '2026-05-18 17:52:36'),
(2, 'default_timezone', 'UTC', 'Default timezone for the system', 'string', 0, NULL, '2026-05-18 17:52:36'),
(3, 'max_file_size', '5242880', 'Maximum file upload size in bytes', 'number', 0, NULL, '2026-05-18 17:52:36'),
(4, 'allow_registration', 'true', 'Allow public user registration', 'boolean', 1, NULL, '2026-05-18 17:52:36'),
(5, 'email_notifications', 'true', 'Enable email notifications', 'boolean', 0, NULL, '2026-05-18 17:52:36'),
(6, 'qr_expiry_hours', '24', 'QR code expiry time in hours', 'number', 0, NULL, '2026-05-18 17:52:36');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('brand_color', '#39141c', '2026-05-27 19:33:34'),
('faq_content', 'What is QR Attendance System?\r\nA modern attendance tracking system using QR codes for quick and accurate event check-ins.\r\n\r\nHow do I create an event?\r\nEvent Organizers use Create Event to enter details, select a venue, and set attendance windows.\r\n\r\nCan I download attendance reports?\r\nYes. Event Organizers and Organization Admins can generate reports with attendance statistics and details.', '2026-05-25 03:26:36'),
('google_maps_api_key', '', '2026-05-25 06:14:59'),
('help_intro', 'A QR based attendance system for events, venues, organizers, and attendees.', '2026-05-25 03:23:09'),
('logo_path', 'logo.png', '2026-05-25 03:23:09'),
('system_name', 'QR Attendance Event', '2026-05-25 03:23:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT '',
  `last_name` varchar(50) DEFAULT '',
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT '',
  `role` enum('super_admin','organization_admin','event_organizer','attendee') NOT NULL DEFAULT 'attendee',
  `profile_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `name` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `organization_id` int(11) DEFAULT NULL,
  `reg_no` varchar(100) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `attendee_type` enum('student','staff','guest') DEFAULT NULL,
  `status` enum('active','disabled') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `role`, `profile_image`, `is_active`, `email_verified`, `last_login`, `created_at`, `updated_at`, `name`, `password`, `organization_id`, `reg_no`, `department`, `attendee_type`, `status`) VALUES
(1, 'Kaaya', 'Meshack', 'akilykaaya@gmail.com', '0794872433', '$2y$10$OosSIlrKzwKoa5PcQf.asO0JQqpyQ2osB5d0gjFaG9.KsL9kvLtXi', 'super_admin', 'uploads/profile_1_1779225316.jpg', 1, 0, '2026-07-11 11:43:45', '2026-05-19 10:56:31', '2026-07-11 08:43:45', 'Kaaya Meshack', '$2y$10$OosSIlrKzwKoa5PcQf.asO0JQqpyQ2osB5d0gjFaG9.KsL9kvLtXi', NULL, NULL, NULL, NULL, 'active'),
(2, '', '', 'meshackkaaya50@gmail.com', '+255794872433', '', 'attendee', 'uploads/profile_2_c6f3abc88f9f0970.jpg', 1, 0, '2026-07-11 13:53:38', '2026-05-22 07:52:15', '2026-07-11 10:53:38', 'Meshack Kaaya', '$2y$10$tf.h0YVibH6UMiIRSiCzpuYLJ8AOQYdD1tB4vl/7aW9HexeyvMk/S', 1, '14233057/T.23', 'CSS', 'student', 'active'),
(3, '', '', 'succedkanael@gmail.com', '0794872433', '$2y$10$AGfruNtZ5dVkeoHwABfgxOzt9kk//Rp7ta8dutM5yTtu0NtWD9ZyG', 'organization_admin', NULL, 1, 0, '2026-07-11 11:55:07', '2026-05-23 21:23:24', '2026-07-11 08:55:07', 'succed Akily', '$2y$10$AGfruNtZ5dVkeoHwABfgxOzt9kk//Rp7ta8dutM5yTtu0NtWD9ZyG', 1, NULL, NULL, NULL, 'active'),
(4, '', '', 'akilyhawkwolf@gmail.com', '+255773702533', '$2y$10$OitOWajNiYNfxvL9WQyuWO.4wKX1hneeybFgRPTAB/iVtif1mnE8q', 'event_organizer', NULL, 1, 0, '2026-07-11 12:31:59', '2026-05-23 21:25:27', '2026-07-11 09:31:59', 'Akily2026', '$2y$10$OitOWajNiYNfxvL9WQyuWO.4wKX1hneeybFgRPTAB/iVtif1mnE8q', 1, NULL, 'FST', NULL, 'active'),
(7, '', '', 'sabrina@gmail.com', '079 487 2433', '$2y$10$PgRHyTo7y5WscaOALCnAVeaDI2jOrk4qzF/IiiCS7dyPLM8BIIb0i', 'attendee', NULL, 1, 0, '2026-07-11 13:55:49', '2026-05-27 06:18:18', '2026-07-11 10:55:49', 'Sabrina', '$2y$10$PgRHyTo7y5WscaOALCnAVeaDI2jOrk4qzF/IiiCS7dyPLM8BIIb0i', 1, '17231303/T.23', 'FSS', 'student', 'active'),
(8, '', '', 'fuadommy@gmail.com', '0714247229', '$2y$10$.2nRGBbuW5SFIffU3iqofesqLOXKqqD07s6RK2tWTFW9iVNFKk2LK', 'attendee', NULL, 1, 0, '2026-06-03 15:16:21', '2026-06-03 12:16:00', '2026-06-03 12:16:21', 'fuad', '$2y$10$.2nRGBbuW5SFIffU3iqofesqLOXKqqD07s6RK2tWTFW9iVNFKk2LK', 1, '14233050/t.24', 'css', 'student', 'active'),
(9, '', '', 'brownlizer@gmail.com', '0615257868', '$2y$10$GMzLijauFVUeo9PgsiJdnu7qNqllo.KGVPM.474yUvHucbwImrWe6', 'attendee', NULL, 1, 0, '2026-06-03 15:18:24', '2026-06-03 12:18:00', '2026-06-03 12:18:24', 'Robert Brown', '$2y$10$GMzLijauFVUeo9PgsiJdnu7qNqllo.KGVPM.474yUvHucbwImrWe6', 1, '14233038/t.24', 'Css', 'student', 'active'),
(10, '', '', 'nicoedgar25@gmail.com', '0755665356', '$2y$10$jFBEkaCNiyWnAjy3adH5zuXrzZcxnWc3xwpcc0FTXXVM0nbIyLpqW', 'attendee', NULL, 1, 0, '2026-06-03 21:19:15', '2026-06-03 18:00:12', '2026-06-03 18:19:15', 'Edgar Nico', '$2y$10$jFBEkaCNiyWnAjy3adH5zuXrzZcxnWc3xwpcc0FTXXVM0nbIyLpqW', 1, '14233046/T.24', 'Css', 'student', 'active'),
(11, '', '', 'edgar@gmail.com', '0793106104', '$2y$10$Jr4AV5kpEEWVJyBMbBLhUesvx8pnMgSM4A.72iVKaLsA3r.0gK0YG', 'attendee', NULL, 1, 0, '2026-07-03 19:56:15', '2026-07-03 16:55:50', '2026-07-03 16:56:15', 'edgar nico', '$2y$10$Jr4AV5kpEEWVJyBMbBLhUesvx8pnMgSM4A.72iVKaLsA3r.0gK0YG', 1, '14233045/T.24', 'css', 'student', 'active'),
(12, '', '', 'symon@gmail.com', '07845845848', '$2y$10$u1ODulES/mIvE.ZHUL3o6u6kyFUhSl.4admXNkGz0BceRTafFxwUS', 'attendee', NULL, 1, 0, '2026-07-11 13:58:34', '2026-07-11 10:57:59', '2026-07-11 10:58:34', 'Asante', '$2y$10$u1ODulES/mIvE.ZHUL3o6u6kyFUhSl.4admXNkGz0BceRTafFxwUS', 1, '17231303/T.23', 'FSS', 'student', 'active');

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_activity_summary`
-- (See below for the actual view)
--
CREATE TABLE `user_activity_summary` (
`user_id` int(11)
,`full_name` varchar(101)
,`email` varchar(100)
,`events_registered` bigint(21)
,`events_attended` bigint(21)
,`last_attendance` datetime
);

-- --------------------------------------------------------

--
-- Table structure for table `user_organizations`
--

CREATE TABLE `user_organizations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `role` enum('owner','admin','member') DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `venues`
--

CREATE TABLE `venues` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `organization_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `venues`
--

INSERT INTO `venues` (`id`, `name`, `address`, `city`, `state`, `country`, `postal_code`, `capacity`, `latitude`, `longitude`, `description`, `image`, `created_by`, `created_at`, `updated_at`, `organization_id`) VALUES
(4, 'Samora hall', 'New Assembly Hall (NAH), Iringa Road, Sanga Sanga, Morogoro Region, Tanzania', 'Sanga Sanga', NULL, NULL, NULL, 500, -6.92540610, 37.56726090, '', NULL, 3, '2026-05-26 03:36:13', '2026-05-26 03:36:13', 1),
(5, 'Fanoni hall', 'New Assembly Hall (NAH), Iringa Road, Sanga Sanga, Morogoro Region, Tanzania', 'Sanga Sanga', NULL, NULL, NULL, 300, -6.92472630, 37.56795560, 'hall', NULL, 3, '2026-05-26 03:44:24', '2026-05-26 03:44:24', 1),
(6, 'Home', 'Changarawe, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania', 'Sanga Sanga', NULL, NULL, NULL, 5, -6.91809970, 37.56062890, 'Home testing', NULL, 3, '2026-05-27 05:50:51', '2026-05-27 05:50:51', 1),
(7, 'Selasie', 'Mzumbe Health Centre, Iringa Road, Sanga Sanga, Morogoro Region, Tanzania', 'Sanga Sanga', NULL, NULL, NULL, 10, -6.92474040, 37.56693360, 'Selasie Offices', NULL, 3, '2026-06-05 07:26:19', '2026-06-05 07:26:19', 1);

-- --------------------------------------------------------

--
-- Structure for view `event_attendance_summary`
--
DROP TABLE IF EXISTS `event_attendance_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `event_attendance_summary`  AS SELECT `e`.`id` AS `event_id`, `e`.`title` AS `title`, `e`.`start_datetime` AS `start_datetime`, count(distinct `r`.`user_id`) AS `registered_count`, count(distinct `ar`.`user_id`) AS `attended_count`, count(distinct case when `ar`.`check_out_time` is not null then `ar`.`user_id` end) AS `completed_count` FROM ((`events` `e` left join `registrations` `r` on(`e`.`id` = `r`.`event_id` and `r`.`status` = 'approved')) left join `attendance_records` `ar` on(`e`.`id` = `ar`.`event_id`)) WHERE `e`.`status` = 'published' GROUP BY `e`.`id`, `e`.`title`, `e`.`start_datetime` ;

-- --------------------------------------------------------

--
-- Structure for view `user_activity_summary`
--
DROP TABLE IF EXISTS `user_activity_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_activity_summary`  AS SELECT `u`.`id` AS `user_id`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `full_name`, `u`.`email` AS `email`, count(distinct `r`.`event_id`) AS `events_registered`, count(distinct `ar`.`event_id`) AS `events_attended`, max(`ar`.`check_in_time`) AS `last_attendance` FROM ((`users` `u` left join `registrations` `r` on(`u`.`id` = `r`.`user_id` and `r`.`status` = 'approved')) left join `attendance_records` `ar` on(`u`.`id` = `ar`.`user_id`)) GROUP BY `u`.`id`, `u`.`first_name`, `u`.`last_name`, `u`.`email` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_event_id` (`event_id`);

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendance_id` (`attendance_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_event_id` (`event_id`);

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_event_user` (`event_id`,`user_id`),
  ADD KEY `idx_check_in_time` (`check_in_time`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_verification_method` (`verification_method`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_table_name` (`table_name`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `deleted_events`
--
ALTER TABLE `deleted_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_original_event_id` (`original_event_id`),
  ADD KEY `idx_deleted_by` (`deleted_by`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `access_code` (`access_code`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `venue_id` (`venue_id`),
  ADD KEY `idx_start_datetime` (`start_datetime`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_organization` (`organization_id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_access_code` (`access_code`);

--
-- Indexes for table `event_categories`
--
ALTER TABLE `event_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `event_sessions`
--
ALTER TABLE `event_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_start_datetime` (`start_datetime`);

--
-- Indexes for table `event_teams`
--
ALTER TABLE `event_teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_event_user_role` (`event_id`,`user_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `participants`
--
ALTER TABLE `participants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_event_id` (`event_id`);

--
-- Indexes for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code_data` (`code_data`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `generated_by` (`generated_by`),
  ADD KEY `idx_code_data` (`code_data`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_event_user` (`event_id`,`user_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_generated_by` (`generated_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_key` (`key`),
  ADD KEY `idx_is_public` (`is_public`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_organization_id` (`organization_id`);

--
-- Indexes for table `user_organizations`
--
ALTER TABLE `user_organizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_org` (`user_id`,`organization_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_org_id` (`organization_id`);

--
-- Indexes for table `venues`
--
ALTER TABLE `venues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_capacity` (`capacity`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deleted_events`
--
ALTER TABLE `deleted_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `event_categories`
--
ALTER TABLE `event_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `event_sessions`
--
ALTER TABLE `event_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_teams`
--
ALTER TABLE `event_teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `participants`
--
ALTER TABLE `participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `user_organizations`
--
ALTER TABLE `user_organizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `venues`
--
ALTER TABLE `venues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD CONSTRAINT `attendance_records_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_records_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_records_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `event_sessions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_records_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `event_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_ibfk_3` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_sessions`
--
ALTER TABLE `event_sessions`
  ADD CONSTRAINT `event_sessions_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_teams`
--
ALTER TABLE `event_teams`
  ADD CONSTRAINT `event_teams_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_teams_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_teams_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `organizations`
--
ALTER TABLE `organizations`
  ADD CONSTRAINT `organizations_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD CONSTRAINT `qr_codes_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `qr_codes_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `event_sessions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `qr_codes_ibfk_3` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_organizations`
--
ALTER TABLE `user_organizations`
  ADD CONSTRAINT `user_organizations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_organizations_ibfk_2` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `venues`
--
ALTER TABLE `venues`
  ADD CONSTRAINT `venues_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
