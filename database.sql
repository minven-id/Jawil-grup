-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 12, 2026 at 05:29 PM
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
-- Database: `jawil_grup`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 02:42:24'),
(2, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 02:42:46'),
(3, 1, 'updated', 'roles', 1, '{\"id\":1,\"role_name\":\"Super Admin\",\"description\":\"Full access to all features including roles management\",\"permissions\":\"[\\\"users.manage\\\",\\\"items.manage\\\",\\\"categories.manage\\\",\\\"locations.manage\\\",\\\"roles.manage\\\",\\\"logs.view\\\",\\\"settings.manage\\\"]\",\"created_at\":\"2026-09-11 09:42:12\"}', '{\"role_name\":\"Super Admin\",\"description\":\"Full access to all features including roles management\",\"permissions\":\"[\\\"users.manage\\\",\\\"items.manage\\\",\\\"items.view\\\",\\\"items.edit\\\",\\\"categories.manage\\\",\\\"categories.view\\\",\\\"categories.edit\\\",\\\"locations.manage\\\",\\\"locations.view\\\",\\\"roles.manage\\\",\\\"logs.view\\\",\\\"settings.manage\\\"]\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 02:45:23'),
(4, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 02:45:34'),
(5, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 02:45:38'),
(6, 1, 'created', 'items', 1, NULL, '{\"item_code\":\"CJ\",\"item_name\":\"Mesin Kopi\",\"brand\":\"brand\",\"description\":\"-\",\"category_id\":\"5\",\"location_id\":\"5\",\"quantity\":2,\"unit\":\"pcs\",\"status\":\"available\",\"purchase_date\":\"2026-09-11\",\"purchase_price\":\"2000000\",\"supplier\":\"\",\"image_path\":null,\"created_by\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 03:01:56'),
(7, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 03:29:17'),
(8, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 03:29:23'),
(9, 1, 'updated', 'roles', 1, '{\"id\":1,\"role_name\":\"Super Admin\",\"description\":\"Full access to all features including roles management\",\"permissions\":\"[\\\"users.manage\\\",\\\"items.manage\\\",\\\"items.view\\\",\\\"items.edit\\\",\\\"categories.manage\\\",\\\"categories.view\\\",\\\"categories.edit\\\",\\\"locations.manage\\\",\\\"locations.view\\\",\\\"roles.manage\\\",\\\"logs.view\\\",\\\"settings.manage\\\"]\",\"created_at\":\"2026-09-11 09:42:12\"}', '{\"role_name\":\"Super Admin\",\"description\":\"Full access to all features including roles management\",\"permissions\":\"[\\\"users.manage\\\",\\\"items.manage\\\",\\\"items.view\\\",\\\"items.edit\\\",\\\"categories.manage\\\",\\\"categories.view\\\",\\\"categories.edit\\\",\\\"locations.manage\\\",\\\"locations.view\\\",\\\"roles.manage\\\",\\\"logs.view\\\",\\\"settings.manage\\\"]\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 03:31:20'),
(10, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 05:38:56'),
(11, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 05:39:07'),
(12, 1, 'created', 'item_requests', 1, NULL, '{\"request_no\":\"PBJ\\/2026\\/09\\/0001\",\"request_date\":\"2026-09-11\",\"requester_name\":\"Administrator\",\"department\":\"\",\"purpose\":\"\",\"item_details\":\"[{\\\"name\\\":\\\"PC\\\",\\\"qty\\\":1,\\\"unit\\\":\\\"pcs\\\",\\\"price\\\":1700000,\\\"note\\\":\\\"\\\",\\\"subtotal\\\":1700000}]\",\"total_items\":1,\"total_amount\":1700000,\"status\":\"pending\",\"created_by\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 06:42:56'),
(13, 1, 'approved', 'item_requests', 1, '{\"id\":1,\"request_no\":\"PBJ\\/2026\\/09\\/0001\",\"request_date\":\"2026-09-11\",\"requester_name\":\"Administrator\",\"department\":\"\",\"purpose\":\"\",\"item_details\":\"[{\\\"name\\\":\\\"PC\\\",\\\"qty\\\":1,\\\"unit\\\":\\\"pcs\\\",\\\"price\\\":1700000,\\\"note\\\":\\\"\\\",\\\"subtotal\\\":1700000}]\",\"total_items\":1,\"total_amount\":\"1700000.00\",\"status\":\"pending\",\"admin_note\":null,\"approved_by\":null,\"approved_at\":null,\"rejected_by\":null,\"rejected_at\":null,\"created_by\":1,\"created_at\":\"2026-09-11 13:42:56\",\"updated_at\":\"2026-09-11 13:42:56\"}', '{\"status\":\"approved\",\"note\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 06:43:03'),
(14, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 07:00:24'),
(15, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 07:35:34'),
(16, 1, 'created', 'users', 2, NULL, '{\"username\":\"sano\",\"email\":\"akagamisans2@gmail.com\",\"password\":\"$2y$10$x5W9Snk.hT4v86GcGIYWROSBuGYGGU2JBBK13dnwWHIrhTZ.UszaC\",\"full_name\":\"sano\",\"profile_photo\":\"bd5c63586b187181654276cef37d6485.jpg\",\"role_id\":1,\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 07:38:55'),
(17, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 07:38:59'),
(18, 2, 'login', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 07:39:04'),
(19, 2, 'logout', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 07:49:40'),
(20, 2, 'login', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 07:49:46'),
(21, 2, 'updated', 'roles', 1, '{\"id\":1,\"role_name\":\"Super Admin\",\"description\":\"Full access to all features including roles management\",\"permissions\":\"[\\\"users.manage\\\",\\\"items.manage\\\",\\\"items.view\\\",\\\"items.edit\\\",\\\"categories.manage\\\",\\\"categories.view\\\",\\\"categories.edit\\\",\\\"locations.manage\\\",\\\"locations.view\\\",\\\"roles.manage\\\",\\\"logs.view\\\",\\\"settings.manage\\\"]\",\"created_at\":\"2026-09-11 09:42:12\"}', '{\"role_name\":\"Super Admin\",\"description\":\"Full access to all features including roles management\",\"permissions\":\"[\\\"users.manage\\\",\\\"items.manage\\\",\\\"items.view\\\",\\\"items.edit\\\",\\\"categories.manage\\\",\\\"categories.view\\\",\\\"categories.edit\\\",\\\"locations.manage\\\",\\\"locations.view\\\",\\\"roles.manage\\\",\\\"logs.view\\\",\\\"settings.manage\\\"]\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 07:51:57'),
(22, 2, 'updated', 'roles', 1, '{\"id\":1,\"role_name\":\"Super Admin\",\"description\":\"Full access to all features including roles management\",\"permissions\":\"[\\\"users.manage\\\",\\\"items.manage\\\",\\\"items.view\\\",\\\"items.edit\\\",\\\"categories.manage\\\",\\\"categories.view\\\",\\\"categories.edit\\\",\\\"locations.manage\\\",\\\"locations.view\\\",\\\"roles.manage\\\",\\\"logs.view\\\",\\\"settings.manage\\\"]\",\"created_at\":\"2026-09-11 09:42:12\"}', '{\"role_name\":\"Super Admin\",\"description\":\"Full access to all features including roles management\",\"permissions\":\"[\\\"users.manage\\\",\\\"items.manage\\\",\\\"items.view\\\",\\\"items.edit\\\",\\\"categories.manage\\\",\\\"categories.view\\\",\\\"categories.edit\\\",\\\"locations.manage\\\",\\\"locations.view\\\",\\\"roles.manage\\\",\\\"logs.view\\\",\\\"settings.manage\\\"]\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 07:52:47'),
(23, 2, 'updated', 'roles', 1, '{\"id\":1,\"role_name\":\"Super Admin\",\"description\":\"Full access to all features including roles management\",\"permissions\":\"[\\\"users.manage\\\",\\\"items.manage\\\",\\\"items.view\\\",\\\"items.edit\\\",\\\"categories.manage\\\",\\\"categories.view\\\",\\\"categories.edit\\\",\\\"locations.manage\\\",\\\"locations.view\\\",\\\"roles.manage\\\",\\\"logs.view\\\",\\\"settings.manage\\\"]\",\"created_at\":\"2026-09-11 09:42:12\"}', '{\"role_name\":\"Super Admin\",\"description\":\"Full access to all features including roles management\",\"permissions\":\"[\\\"users.manage\\\",\\\"items.manage\\\",\\\"items.view\\\",\\\"items.edit\\\",\\\"categories.manage\\\",\\\"categories.view\\\",\\\"categories.edit\\\",\\\"locations.manage\\\",\\\"locations.view\\\",\\\"roles.manage\\\",\\\"logs.view\\\",\\\"settings.manage\\\"]\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 07:53:22'),
(24, 2, 'updated', 'roles', 1, '{\"id\":1,\"role_name\":\"Super Admin\",\"description\":\"Full access to all features including roles management\",\"permissions\":\"[\\\"users.manage\\\",\\\"items.manage\\\",\\\"items.view\\\",\\\"items.edit\\\",\\\"categories.manage\\\",\\\"categories.view\\\",\\\"categories.edit\\\",\\\"locations.manage\\\",\\\"locations.view\\\",\\\"roles.manage\\\",\\\"logs.view\\\",\\\"settings.manage\\\"]\",\"created_at\":\"2026-09-11 09:42:12\"}', '{\"role_name\":\"Super Admin\",\"description\":\"Full access to all features including roles management\",\"permissions\":\"[\\\"users.manage\\\",\\\"items.manage\\\",\\\"items.view\\\",\\\"items.edit\\\",\\\"categories.manage\\\",\\\"categories.view\\\",\\\"categories.edit\\\",\\\"locations.manage\\\",\\\"locations.view\\\",\\\"roles.manage\\\",\\\"logs.view\\\",\\\"settings.manage\\\"]\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 07:55:50'),
(25, 2, 'created', 'items', 3, NULL, '{\"item_code\":\"CJ-01\",\"item_name\":\"mesin creamer\",\"brand\":\"brand\",\"description\":\"-\",\"category_id\":\"5\",\"location_id\":\"4\",\"quantity\":1,\"unit\":\"pcs\",\"status\":\"in_use\",\"purchase_date\":\"2026-09-11\",\"purchase_price\":\"4000000\",\"supplier\":\"-\",\"image_path\":null,\"created_by\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 07:57:17'),
(26, 2, 'logout', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 07:57:41'),
(27, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 13:05:52'),
(28, 1, 'created', 'item_requests', 2, NULL, '{\"request_no\":\"PBJ\\/2026\\/09\\/0002\",\"request_date\":\"2026-09-11\",\"requester_name\":\"Administrator\",\"department\":\"\",\"purpose\":\"\",\"item_details\":\"[{\\\"name\\\":\\\"kopi\\\",\\\"qty\\\":1,\\\"unit\\\":\\\"pcs\\\",\\\"price\\\":500000,\\\"note\\\":\\\"kol\\\",\\\"subtotal\\\":500000}]\",\"total_items\":1,\"total_amount\":500000,\"status\":\"pending\",\"created_by\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 13:11:25'),
(29, 1, 'approved', 'item_requests', 2, '{\"id\":2,\"request_no\":\"PBJ\\/2026\\/09\\/0002\",\"request_date\":\"2026-09-11\",\"requester_name\":\"Administrator\",\"department\":\"\",\"purpose\":\"\",\"item_details\":\"[{\\\"name\\\":\\\"kopi\\\",\\\"qty\\\":1,\\\"unit\\\":\\\"pcs\\\",\\\"price\\\":500000,\\\"note\\\":\\\"kol\\\",\\\"subtotal\\\":500000}]\",\"total_items\":1,\"total_amount\":\"500000.00\",\"status\":\"pending\",\"admin_note\":null,\"approved_by\":null,\"approved_at\":null,\"rejected_by\":null,\"rejected_at\":null,\"created_by\":1,\"created_at\":\"2026-09-11 20:11:25\",\"updated_at\":\"2026-09-11 20:11:25\"}', '{\"status\":\"approved\",\"note\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 13:11:39'),
(30, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 16:04:22'),
(31, 2, 'login', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 16:04:32'),
(32, 2, 'login', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 18:00:45'),
(33, 2, 'updated_status', 'items', 3, '{\"id\":3,\"item_code\":\"CJ-01\",\"item_name\":\"mesin creamer\",\"brand\":\"brand\",\"description\":\"-\",\"category_id\":5,\"location_id\":4,\"quantity\":1,\"unit\":\"pcs\",\"status\":\"in_use\",\"purchase_date\":\"2026-09-11\",\"purchase_price\":\"4000000.00\",\"supplier\":\"-\",\"image_path\":null,\"foto_atas\":null,\"foto_bawah\":null,\"foto_kanan\":null,\"foto_kiri\":null,\"created_by\":2,\"created_at\":\"2026-09-11 14:57:17\",\"updated_at\":\"2026-09-11 14:57:17\"}', '{\"status\":\"available\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 18:11:06'),
(34, 2, 'barang_tiba', 'item_requests', 2, '{\"id\":2,\"request_no\":\"PBJ\\/2026\\/09\\/0002\",\"request_date\":\"2026-09-11\",\"requester_name\":\"Administrator\",\"department\":\"\",\"purpose\":\"\",\"item_details\":\"[{\\\"name\\\":\\\"kopi\\\",\\\"qty\\\":1,\\\"unit\\\":\\\"pcs\\\",\\\"price\\\":500000,\\\"note\\\":\\\"kol\\\",\\\"subtotal\\\":500000}]\",\"total_items\":1,\"total_amount\":\"500000.00\",\"status\":\"approved\",\"admin_note\":\"\",\"approved_by\":1,\"approved_at\":\"2026-09-11 20:11:39\",\"rejected_by\":null,\"rejected_at\":null,\"created_by\":1,\"created_at\":\"2026-09-11 20:11:25\",\"updated_at\":\"2026-09-11 20:11:39\"}', '{\"status\":\"barang_tiba\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 18:13:52'),
(35, 2, 'processing', 'item_requests', 1, '{\"id\":1,\"request_no\":\"PBJ\\/2026\\/09\\/0001\",\"request_date\":\"2026-09-11\",\"requester_name\":\"Administrator\",\"department\":\"\",\"purpose\":\"\",\"item_details\":\"[{\\\"name\\\":\\\"PC\\\",\\\"qty\\\":1,\\\"unit\\\":\\\"pcs\\\",\\\"price\\\":1700000,\\\"note\\\":\\\"\\\",\\\"subtotal\\\":1700000}]\",\"total_items\":1,\"total_amount\":\"1700000.00\",\"status\":\"approved\",\"admin_note\":\"\",\"approved_by\":1,\"approved_at\":\"2026-09-11 13:43:03\",\"rejected_by\":null,\"rejected_at\":null,\"created_by\":1,\"created_at\":\"2026-09-11 13:42:56\",\"updated_at\":\"2026-09-11 13:43:03\"}', '{\"status\":\"processing\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 18:15:45'),
(36, 2, 'barang_tiba', 'item_requests', 1, '{\"id\":1,\"request_no\":\"PBJ\\/2026\\/09\\/0001\",\"request_date\":\"2026-09-11\",\"requester_name\":\"Administrator\",\"department\":\"\",\"purpose\":\"\",\"item_details\":\"[{\\\"name\\\":\\\"PC\\\",\\\"qty\\\":1,\\\"unit\\\":\\\"pcs\\\",\\\"price\\\":1700000,\\\"note\\\":\\\"\\\",\\\"subtotal\\\":1700000}]\",\"total_items\":1,\"total_amount\":\"1700000.00\",\"status\":\"processing\",\"admin_note\":\"\",\"approved_by\":1,\"approved_at\":\"2026-09-11 13:43:03\",\"rejected_by\":null,\"rejected_at\":null,\"created_by\":1,\"created_at\":\"2026-09-11 13:42:56\",\"updated_at\":\"2026-09-12 01:15:45\"}', '{\"status\":\"barang_tiba\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 18:16:42'),
(37, 2, 'stock_out', 'items', 1, '{\"quantity\":2}', '{\"quantity\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 18:26:47'),
(38, 2, 'created', 'items', 4, NULL, '{\"item_code\":\"CJ-02\",\"item_name\":\"Mesin es\",\"brand\":\"brand\",\"description\":\"-\",\"category_id\":\"5\",\"location_id\":\"3\",\"quantity\":2,\"unit\":\"pcs\",\"status\":\"maintenance\",\"purchase_date\":\"2026-09-12\",\"purchase_price\":\"2000000\",\"supplier\":\"-\",\"image_path\":null,\"foto_atas\":null,\"foto_bawah\":null,\"foto_kanan\":null,\"foto_kiri\":null,\"photo_bon\":null,\"created_by\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 18:29:03'),
(39, 2, 'updated_status', 'items', 4, '{\"id\":4,\"item_code\":\"CJ-02\",\"item_name\":\"Mesin es\",\"brand\":\"brand\",\"description\":\"-\",\"category_id\":5,\"location_id\":3,\"quantity\":2,\"unit\":\"pcs\",\"status\":\"maintenance\",\"purchase_date\":\"2026-09-12\",\"purchase_price\":\"2000000.00\",\"supplier\":\"-\",\"image_path\":null,\"foto_atas\":null,\"foto_bawah\":null,\"foto_kanan\":null,\"foto_kiri\":null,\"photo_bon\":null,\"created_by\":2,\"created_at\":\"2026-09-12 01:29:03\",\"updated_at\":\"2026-09-12 01:29:03\"}', '{\"status\":\"lost\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 18:40:20'),
(40, 2, 'updated_status', 'items', 4, '{\"id\":4,\"item_code\":\"CJ-02\",\"item_name\":\"Mesin es\",\"brand\":\"brand\",\"description\":\"-\",\"category_id\":5,\"location_id\":3,\"quantity\":2,\"unit\":\"pcs\",\"status\":\"lost\",\"purchase_date\":\"2026-09-12\",\"purchase_price\":\"2000000.00\",\"supplier\":\"-\",\"image_path\":null,\"foto_atas\":null,\"foto_bawah\":null,\"foto_kanan\":null,\"foto_kiri\":null,\"photo_bon\":null,\"created_by\":2,\"created_at\":\"2026-09-12 01:29:03\",\"updated_at\":\"2026-09-12 01:40:20\"}', '{\"status\":\"available\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 18:40:38'),
(41, 2, 'stock_out', 'items', 4, '{\"quantity\":2,\"status\":\"available\"}', '{\"quantity\":1,\"status\":\"broken\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 18:45:25'),
(42, 2, 'updated', 'finance_settings', 1, '{\"id\":1,\"available_amount\":\"0.00\",\"used_amount\":\"0.00\",\"total_income\":\"0.00\",\"total_expense\":\"0.00\",\"updated_by\":null,\"updated_at\":\"2026-09-12 01:52:25\"}', '{\"available_amount\":2000000,\"used_amount\":1000000,\"total_income\":2000000,\"total_expense\":500000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 18:53:57'),
(43, 2, 'created', 'finance_transactions', 1, NULL, '{\"transaction_name\":\"investor\",\"transaction_type\":\"income\",\"method\":\"saldo\",\"amount\":1000000,\"transaction_date\":\"2026-09-12\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 18:54:29'),
(44, 2, 'created', 'finance_transactions', 2, NULL, '{\"transaction_name\":\"bayar\",\"transaction_type\":\"expense\",\"method\":\"cash\",\"amount\":500000,\"transaction_date\":\"2026-09-12\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 18:54:58'),
(45, 2, 'login', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 12:01:01'),
(46, 2, 'created', 'item_requests', 3, NULL, '{\"request_no\":\"PBJ\\/2026\\/09\\/0003\",\"request_date\":\"2026-09-12\",\"requester_name\":\"sano\",\"department\":\"\",\"purpose\":\"\",\"item_details\":\"[{\\\"name\\\":\\\"kopi mesin\\\",\\\"qty\\\":1,\\\"unit\\\":\\\"pcs\\\",\\\"price\\\":2000000,\\\"note\\\":\\\"pesanan\\\",\\\"subtotal\\\":2000000}]\",\"total_items\":1,\"total_amount\":2000000,\"status\":\"pending\",\"photo_bon\":null,\"created_by\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 12:04:18'),
(47, 2, 'approved', 'item_requests', 3, '{\"id\":3,\"request_no\":\"PBJ\\/2026\\/09\\/0003\",\"request_date\":\"2026-09-12\",\"requester_name\":\"sano\",\"department\":\"\",\"purpose\":\"\",\"item_details\":\"[{\\\"name\\\":\\\"kopi mesin\\\",\\\"qty\\\":1,\\\"unit\\\":\\\"pcs\\\",\\\"price\\\":2000000,\\\"note\\\":\\\"pesanan\\\",\\\"subtotal\\\":2000000}]\",\"total_items\":1,\"total_amount\":\"2000000.00\",\"status\":\"pending\",\"admin_note\":null,\"approved_by\":null,\"approved_at\":null,\"rejected_by\":null,\"rejected_at\":null,\"photo_bon\":null,\"created_by\":2,\"created_at\":\"2026-09-12 19:04:18\",\"updated_at\":\"2026-09-12 19:04:18\"}', '{\"status\":\"approved\",\"note\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 12:11:19'),
(48, 2, 'processing', 'item_requests', 3, '{\"id\":3,\"request_no\":\"PBJ\\/2026\\/09\\/0003\",\"request_date\":\"2026-09-12\",\"requester_name\":\"sano\",\"department\":\"\",\"purpose\":\"\",\"item_details\":\"[{\\\"name\\\":\\\"kopi mesin\\\",\\\"qty\\\":1,\\\"unit\\\":\\\"pcs\\\",\\\"price\\\":2000000,\\\"note\\\":\\\"pesanan\\\",\\\"subtotal\\\":2000000}]\",\"total_items\":1,\"total_amount\":\"2000000.00\",\"status\":\"approved\",\"admin_note\":\"\",\"approved_by\":2,\"approved_at\":\"2026-09-12 19:11:19\",\"rejected_by\":null,\"rejected_at\":null,\"photo_bon\":null,\"created_by\":2,\"created_at\":\"2026-09-12 19:04:18\",\"updated_at\":\"2026-09-12 19:11:19\"}', '{\"status\":\"processing\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 12:11:29'),
(49, 2, 'barang_tiba', 'item_requests', 3, '{\"id\":3,\"request_no\":\"PBJ\\/2026\\/09\\/0003\",\"request_date\":\"2026-09-12\",\"requester_name\":\"sano\",\"department\":\"\",\"purpose\":\"\",\"item_details\":\"[{\\\"name\\\":\\\"kopi mesin\\\",\\\"qty\\\":1,\\\"unit\\\":\\\"pcs\\\",\\\"price\\\":2000000,\\\"note\\\":\\\"pesanan\\\",\\\"subtotal\\\":2000000}]\",\"total_items\":1,\"total_amount\":\"2000000.00\",\"status\":\"processing\",\"admin_note\":\"\",\"approved_by\":2,\"approved_at\":\"2026-09-12 19:11:19\",\"rejected_by\":null,\"rejected_at\":null,\"photo_bon\":null,\"created_by\":2,\"created_at\":\"2026-09-12 19:04:18\",\"updated_at\":\"2026-09-12 19:11:29\"}', '{\"status\":\"barang_tiba\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 12:11:34'),
(50, 2, 'stock_out', 'items', 4, '{\"quantity\":1,\"status\":\"broken\"}', '{\"quantity\":0,\"status\":\"broken\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 12:12:28'),
(51, 2, 'updated', 'finance_settings', 1, '{\"id\":1,\"available_amount\":\"2000000.00\",\"used_amount\":\"1000000.00\",\"total_income\":\"3000000.00\",\"total_expense\":\"1000000.00\",\"updated_by\":2,\"updated_at\":\"2026-09-12 01:54:58\"}', '{\"available_amount\":3000000,\"used_amount\":1000000,\"total_income\":3000000,\"total_expense\":1000000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 12:13:01'),
(52, 2, 'created', 'finance_transactions', 3, NULL, '{\"transaction_name\":\"investor\",\"transaction_type\":\"income\",\"method\":\"cash\",\"amount\":1000000,\"transaction_date\":\"2026-09-12\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 12:15:55'),
(53, 2, 'login', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 14:40:28'),
(54, 2, 'updated', 'users', 2, '{\"id\":2,\"employee_id\":null,\"username\":\"sano\",\"email\":\"akagamisans2@gmail.com\",\"password\":\"$2y$10$x5W9Snk.hT4v86GcGIYWROSBuGYGGU2JBBK13dnwWHIrhTZ.UszaC\",\"full_name\":\"sano\",\"profile_photo\":\"bd5c63586b187181654276cef37d6485.jpg\",\"role_id\":1,\"is_active\":1,\"created_at\":\"2026-09-11 14:38:55\",\"updated_at\":\"2026-09-11 14:38:55\"}', '{\"employee_id\":\"171297\",\"username\":\"sano\",\"email\":\"akagamisans2@gmail.com\",\"full_name\":\"sano\",\"role_id\":1,\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 14:42:06'),
(55, 2, 'updated', 'users', 2, '{\"id\":2,\"employee_id\":\"171297\",\"username\":\"sano\",\"email\":\"akagamisans2@gmail.com\",\"password\":\"$2y$10$x5W9Snk.hT4v86GcGIYWROSBuGYGGU2JBBK13dnwWHIrhTZ.UszaC\",\"full_name\":\"sano\",\"profile_photo\":\"bd5c63586b187181654276cef37d6485.jpg\",\"role_id\":1,\"is_active\":1,\"created_at\":\"2026-09-11 14:38:55\",\"updated_at\":\"2026-09-12 21:42:06\"}', '{\"employee_id\":\"171297\",\"username\":\"sano\",\"email\":\"akagamisans2@gmail.com\",\"full_name\":\"sano\",\"role_id\":1,\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 14:42:11'),
(56, 2, 'updated', 'users', 1, '{\"id\":1,\"employee_id\":null,\"username\":\"admin\",\"email\":\"admin@jawilgrup.com\",\"password\":\"$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi\",\"full_name\":\"Administrator\",\"profile_photo\":null,\"role_id\":1,\"is_active\":1,\"created_at\":\"2026-09-11 09:42:12\",\"updated_at\":\"2026-09-11 09:42:12\"}', '{\"employee_id\":\"100926\",\"username\":\"admin\",\"email\":\"admin@jawilgrup.com\",\"full_name\":\"Administrator\",\"role_id\":1,\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 14:59:26'),
(57, 2, 'logout', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 15:01:27'),
(58, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 15:02:03'),
(59, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 15:02:13'),
(60, 2, 'login', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 15:02:24'),
(61, 2, 'created', 'item_requests', 4, NULL, '{\"request_no\":\"PBJ\\/2026\\/09\\/0004\",\"request_date\":\"2026-09-12\",\"requester_name\":\"sano\",\"employee_id\":\"171297\",\"department\":\"\",\"purpose\":\"\",\"item_details\":\"[{\\\"name\\\":\\\"mesin kopi\\\",\\\"qty\\\":1,\\\"unit\\\":\\\"pcs\\\",\\\"price\\\":3000000,\\\"note\\\":\\\"\\\",\\\"subtotal\\\":3000000}]\",\"total_items\":1,\"total_amount\":3000000,\"status\":\"pending\",\"photo_bon\":null,\"created_by\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 15:21:08'),
(62, 2, 'approved', 'item_requests', 4, '{\"id\":4,\"request_no\":\"PBJ\\/2026\\/09\\/0004\",\"request_date\":\"2026-09-12\",\"requester_name\":\"sano\",\"employee_id\":\"171297\",\"department\":\"\",\"purpose\":\"\",\"item_details\":\"[{\\\"name\\\":\\\"mesin kopi\\\",\\\"qty\\\":1,\\\"unit\\\":\\\"pcs\\\",\\\"price\\\":3000000,\\\"note\\\":\\\"\\\",\\\"subtotal\\\":3000000}]\",\"total_items\":1,\"total_amount\":\"3000000.00\",\"status\":\"pending\",\"admin_note\":null,\"approved_by\":null,\"approved_at\":null,\"rejected_by\":null,\"rejected_at\":null,\"photo_bon\":null,\"created_by\":2,\"created_at\":\"2026-09-12 22:21:08\",\"updated_at\":\"2026-09-12 22:21:08\"}', '{\"status\":\"approved\",\"note\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 15:21:12'),
(63, 2, 'processing', 'item_requests', 4, '{\"id\":4,\"request_no\":\"PBJ\\/2026\\/09\\/0004\",\"request_date\":\"2026-09-12\",\"requester_name\":\"sano\",\"employee_id\":\"171297\",\"department\":\"\",\"purpose\":\"\",\"item_details\":\"[{\\\"name\\\":\\\"mesin kopi\\\",\\\"qty\\\":1,\\\"unit\\\":\\\"pcs\\\",\\\"price\\\":3000000,\\\"note\\\":\\\"\\\",\\\"subtotal\\\":3000000}]\",\"total_items\":1,\"total_amount\":\"3000000.00\",\"status\":\"approved\",\"admin_note\":\"\",\"approved_by\":2,\"approved_at\":\"2026-09-12 22:21:12\",\"rejected_by\":null,\"rejected_at\":null,\"photo_bon\":null,\"created_by\":2,\"created_at\":\"2026-09-12 22:21:08\",\"updated_at\":\"2026-09-12 22:21:12\"}', '{\"status\":\"processing\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 15:21:15'),
(64, 2, 'barang_tiba', 'item_requests', 4, '{\"id\":4,\"request_no\":\"PBJ\\/2026\\/09\\/0004\",\"request_date\":\"2026-09-12\",\"requester_name\":\"sano\",\"employee_id\":\"171297\",\"department\":\"\",\"purpose\":\"\",\"item_details\":\"[{\\\"name\\\":\\\"mesin kopi\\\",\\\"qty\\\":1,\\\"unit\\\":\\\"pcs\\\",\\\"price\\\":3000000,\\\"note\\\":\\\"\\\",\\\"subtotal\\\":3000000}]\",\"total_items\":1,\"total_amount\":\"3000000.00\",\"status\":\"processing\",\"admin_note\":\"\",\"approved_by\":2,\"approved_at\":\"2026-09-12 22:21:12\",\"rejected_by\":null,\"rejected_at\":null,\"photo_bon\":null,\"created_by\":2,\"created_at\":\"2026-09-12 22:21:08\",\"updated_at\":\"2026-09-12 22:21:15\"}', '{\"status\":\"barang_tiba\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 15:21:17');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Elektronik', 'Barang elektronik dan peralatan IT', '2026-09-11 02:42:12', '2026-09-11 02:42:12'),
(2, 'Furniture', 'Meja, kursi, dan perabot kantor', '2026-09-11 02:42:12', '2026-09-11 02:42:12'),
(3, 'Alat Tulis', 'Peralatan tulis dan kantor', '2026-09-11 02:42:12', '2026-09-11 02:42:12'),
(4, 'Peralatan', 'Peralatan kerja dan mesin', '2026-09-11 02:42:12', '2026-09-11 02:42:12'),
(5, 'Lainnya', 'Kategori lainnya', '2026-09-11 02:42:12', '2026-09-11 02:42:12');

-- --------------------------------------------------------

--
-- Table structure for table `finance_settings`
--

CREATE TABLE `finance_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `available_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `used_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_income` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_expense` decimal(18,2) NOT NULL DEFAULT 0.00,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finance_settings`
--

INSERT INTO `finance_settings` (`id`, `available_amount`, `used_amount`, `total_income`, `total_expense`, `updated_by`, `updated_at`) VALUES
(1, 3000000.00, 1000000.00, 4000000.00, 1000000.00, 2, '2026-09-12 12:15:55');

-- --------------------------------------------------------

--
-- Table structure for table `finance_transactions`
--

CREATE TABLE `finance_transactions` (
  `id` int(11) NOT NULL,
  `transaction_name` varchar(150) NOT NULL,
  `transaction_type` enum('income','expense') NOT NULL,
  `method` varchar(80) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finance_transactions`
--

INSERT INTO `finance_transactions` (`id`, `transaction_name`, `transaction_type`, `method`, `amount`, `notes`, `transaction_date`, `created_by`, `created_at`) VALUES
(1, 'investor', 'income', 'saldo', 1000000.00, 'masuk', '2026-09-12', 2, '2026-09-11 18:54:29'),
(2, 'bayar', 'expense', 'cash', 500000.00, 'bayar', '2026-09-12', 2, '2026-09-11 18:54:58'),
(3, 'investor', 'income', 'cash', 1000000.00, NULL, '2026-09-12', 2, '2026-09-12 12:15:55');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit` varchar(20) DEFAULT 'pcs',
  `status` enum('available','in_use','maintenance','broken','lost') DEFAULT 'available',
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `foto_atas` varchar(255) DEFAULT NULL,
  `foto_bawah` varchar(255) DEFAULT NULL,
  `foto_kanan` varchar(255) DEFAULT NULL,
  `foto_kiri` varchar(255) DEFAULT NULL,
  `photo_bon` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `item_code`, `item_name`, `brand`, `description`, `category_id`, `location_id`, `quantity`, `unit`, `status`, `purchase_date`, `purchase_price`, `supplier`, `image_path`, `foto_atas`, `foto_bawah`, `foto_kanan`, `foto_kiri`, `photo_bon`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CJ', 'Mesin Kopi', 'brand', '-', 5, 5, 1, 'pcs', 'broken', '2026-09-11', 2000000.00, '', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-09-11 03:01:56', '2026-09-11 18:26:47'),
(3, 'CJ-01', 'mesin creamer', 'brand', '-', 5, 4, 1, 'pcs', 'available', '2026-09-11', 4000000.00, '-', NULL, NULL, NULL, NULL, NULL, NULL, 2, '2026-09-11 07:57:17', '2026-09-11 18:11:06'),
(4, 'CJ-02', 'Mesin es', 'brand', '-', 5, 3, 0, 'pcs', 'broken', '2026-09-12', 2000000.00, '-', NULL, NULL, NULL, NULL, NULL, NULL, 2, '2026-09-11 18:29:03', '2026-09-12 12:12:28');

-- --------------------------------------------------------

--
-- Table structure for table `item_requests`
--

CREATE TABLE `item_requests` (
  `id` int(11) NOT NULL,
  `request_no` varchar(50) NOT NULL,
  `request_date` date NOT NULL,
  `requester_name` varchar(150) NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `item_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`item_details`)),
  `total_items` int(11) DEFAULT 0,
  `total_amount` decimal(18,2) DEFAULT 0.00,
  `status` enum('pending','approved','processing','barang_tiba','rejected') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `photo_bon` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_requests`
--

INSERT INTO `item_requests` (`id`, `request_no`, `request_date`, `requester_name`, `employee_id`, `department`, `purpose`, `item_details`, `total_items`, `total_amount`, `status`, `admin_note`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `photo_bon`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PBJ/2026/09/0001', '2026-09-11', 'Administrator', NULL, '', '', '[{\"name\":\"PC\",\"qty\":1,\"unit\":\"pcs\",\"price\":1700000,\"note\":\"\",\"subtotal\":1700000}]', 1, 1700000.00, 'barang_tiba', '', 1, '2026-09-11 13:43:03', NULL, NULL, NULL, 1, '2026-09-11 06:42:56', '2026-09-11 18:16:42'),
(2, 'PBJ/2026/09/0002', '2026-09-11', 'Administrator', NULL, '', '', '[{\"name\":\"kopi\",\"qty\":1,\"unit\":\"pcs\",\"price\":500000,\"note\":\"kol\",\"subtotal\":500000}]', 1, 500000.00, 'barang_tiba', '', 1, '2026-09-11 20:11:39', NULL, NULL, NULL, 1, '2026-09-11 13:11:25', '2026-09-11 18:13:52'),
(3, 'PBJ/2026/09/0003', '2026-09-12', 'sano', NULL, '', '', '[{\"name\":\"kopi mesin\",\"qty\":1,\"unit\":\"pcs\",\"price\":2000000,\"note\":\"pesanan\",\"subtotal\":2000000}]', 1, 2000000.00, 'barang_tiba', '', 2, '2026-09-12 19:11:19', NULL, NULL, NULL, 2, '2026-09-12 12:04:18', '2026-09-12 12:11:34'),
(4, 'PBJ/2026/09/0004', '2026-09-12', 'sano', '171297', '', '', '[{\"name\":\"mesin kopi\",\"qty\":1,\"unit\":\"pcs\",\"price\":3000000,\"note\":\"\",\"subtotal\":3000000}]', 1, 3000000.00, 'barang_tiba', '', 2, '2026-09-12 22:21:12', NULL, NULL, NULL, 2, '2026-09-12 15:21:08', '2026-09-12 15:21:17');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `location_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `location_name`, `location_code`, `description`, `address`, `created_at`, `updated_at`) VALUES
(1, 'Gudang Utama', 'GDU', 'Gudang penyimpanan utama', 'Jl. Utama No. 1', '2026-09-11 02:42:12', '2026-09-11 02:42:12'),
(2, 'Gudang Cadangan', 'GDC', 'Gudang penyimpanan cadangan', 'Jl. Cadangan No. 2', '2026-09-11 02:42:12', '2026-09-11 02:42:12'),
(3, 'Ruang Admin', 'RAD', 'Ruang administrasi', 'Lt. 1 Ruang 101', '2026-09-11 02:42:12', '2026-09-11 02:42:12'),
(4, 'Ruang Produksi', 'RPR', 'Ruang produksi dan workshop', 'Lt. 2 Ruang 201', '2026-09-11 02:42:12', '2026-09-11 02:42:12'),
(5, 'Showroom', 'SHW', 'Area pameran produk', 'Lt. 1 Ruang Depan', '2026-09-11 02:42:12', '2026-09-11 02:42:12'),
(6, 'Kantor Cabang', 'KCB', 'Kantor cabang pusat', 'Jl. Cabang No. 5', '2026-09-11 02:42:12', '2026-09-11 02:42:12');

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `menu_name` varchar(50) NOT NULL,
  `menu_label` varchar(100) NOT NULL,
  `menu_icon` varchar(50) NOT NULL,
  `menu_url` varchar(100) NOT NULL,
  `menu_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `menu_name`, `menu_label`, `menu_icon`, `menu_url`, `menu_order`, `is_active`, `created_at`) VALUES
(1, 'dashboard', 'Dashboard', 'fa-home', 'dashboard.php', 1, 1, '2026-09-11 02:42:12'),
(2, 'items', 'Barang', 'fa-box', 'items.php', 2, 1, '2026-09-11 02:42:12'),
(3, 'categories', 'Kategori', 'fa-tags', 'categories.php', 7, 1, '2026-09-11 02:42:12'),
(4, 'locations', 'Lokasi', 'fa-map-marker-alt', 'locations.php', 8, 1, '2026-09-11 02:42:12'),
(5, 'roles', 'Role', 'fa-user-shield', 'roles.php', 9, 1, '2026-09-11 02:42:12'),
(6, 'users', 'Pengguna', 'fa-users', 'users.php', 10, 1, '2026-09-11 02:42:12'),
(7, 'logs', 'Aktivitas', 'fa-history', 'logs.php', 11, 1, '2026-09-11 02:42:12'),
(17, 'item_requests', 'Pengajuan Barang', 'fa-file-alt', 'item_requests.php', 3, 1, '2026-09-11 03:17:53'),
(18, 'finished_goods', 'Monitoring Barang', 'fa-clipboard-list', 'finished_goods.php', 4, 1, '2026-09-11 07:55:26'),
(19, 'stock_out', 'Stok Keluar', 'fa-sign-out-alt', 'stock_out.php', 5, 1, '2026-09-11 18:25:56'),
(20, 'finance', 'Finance', 'fa-wallet', 'finance.php', 6, 1, '2026-09-11 18:52:25');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `description`, `permissions`, `created_at`) VALUES
(1, 'Super Admin', 'Full access to all features including roles management', '[\"users.manage\",\"items.manage\",\"items.view\",\"items.edit\",\"categories.manage\",\"categories.view\",\"categories.edit\",\"locations.manage\",\"locations.view\",\"roles.manage\",\"logs.view\",\"settings.manage\",\"finance.view\",\"finance.manage\"]', '2026-09-11 02:42:12'),
(2, 'Admin', 'Full access to manage items, categories, locations and users', '[\"users.manage\",\"items.manage\",\"categories.manage\",\"locations.manage\",\"logs.view\",\"finance.view\",\"finance.manage\"]', '2026-09-11 02:42:12'),
(3, 'Manager', 'Can view and edit items and categories', '[\"items.view\",\"items.edit\",\"categories.view\",\"categories.edit\",\"locations.view\",\"finance.view\"]', '2026-09-11 02:42:12'),
(4, 'Staff', 'Can view items and locations only', '[\"items.view\",\"locations.view\",\"finance.view\"]', '2026-09-11 02:42:12');

-- --------------------------------------------------------

--
-- Table structure for table `role_menu_access`
--

CREATE TABLE `role_menu_access` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_menu_access`
--

INSERT INTO `role_menu_access` (`id`, `role_id`, `menu_id`, `can_view`, `created_at`) VALUES
(8, 2, 3, 1, '2026-09-11 02:42:12'),
(9, 2, 1, 1, '2026-09-11 02:42:12'),
(10, 2, 2, 1, '2026-09-11 02:42:12'),
(11, 2, 4, 1, '2026-09-11 02:42:12'),
(12, 2, 7, 1, '2026-09-11 02:42:12'),
(13, 2, 6, 1, '2026-09-11 02:42:12'),
(15, 3, 3, 1, '2026-09-11 02:42:12'),
(16, 3, 1, 1, '2026-09-11 02:42:12'),
(17, 3, 2, 1, '2026-09-11 02:42:12'),
(18, 3, 4, 1, '2026-09-11 02:42:12'),
(22, 4, 1, 1, '2026-09-11 02:42:12'),
(23, 4, 2, 1, '2026-09-11 02:42:12'),
(24, 4, 4, 1, '2026-09-11 02:42:12'),
(37, 2, 17, 1, '2026-09-11 03:17:53'),
(38, 3, 17, 1, '2026-09-11 03:17:53'),
(39, 4, 17, 1, '2026-09-11 03:17:53'),
(75, 2, 18, 1, '2026-09-11 07:55:26'),
(76, 3, 18, 1, '2026-09-11 07:55:26'),
(77, 4, 18, 1, '2026-09-11 07:55:26'),
(78, 1, 1, 1, '2026-09-11 07:55:50'),
(79, 1, 2, 1, '2026-09-11 07:55:50'),
(80, 1, 17, 1, '2026-09-11 07:55:50'),
(81, 1, 18, 1, '2026-09-11 07:55:50'),
(82, 1, 3, 1, '2026-09-11 07:55:50'),
(83, 1, 4, 1, '2026-09-11 07:55:50'),
(84, 1, 5, 1, '2026-09-11 07:55:50'),
(85, 1, 6, 1, '2026-09-11 07:55:50'),
(86, 1, 7, 1, '2026-09-11 07:55:50'),
(87, 2, 19, 1, '2026-09-11 18:25:56'),
(88, 3, 19, 1, '2026-09-11 18:25:56'),
(89, 4, 19, 1, '2026-09-11 18:25:56'),
(90, 1, 19, 1, '2026-09-11 18:25:56'),
(91, 1, 20, 1, '2026-09-11 18:52:25'),
(92, 2, 20, 1, '2026-09-11 18:52:25'),
(93, 3, 20, 1, '2026-09-11 18:52:25'),
(94, 4, 20, 1, '2026-09-11 18:52:25');

-- --------------------------------------------------------

--
-- Table structure for table `stock_out`
--

CREATE TABLE `stock_out` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('available','in_use','maintenance','broken','lost') NOT NULL,
  `notes` text NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_out`
--

INSERT INTO `stock_out` (`id`, `item_id`, `quantity`, `status`, `notes`, `photo`, `created_by`, `created_at`) VALUES
(1, 1, 1, 'broken', 'rusak', NULL, 2, '2026-09-11 18:26:47'),
(2, 4, 1, 'broken', 'rusak', NULL, 2, '2026-09-11 18:45:25'),
(3, 4, 1, 'broken', 'rusak', NULL, 2, '2026-09-12 12:12:28');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `username`, `email`, `password`, `full_name`, `profile_photo`, `role_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '100926', 'admin', 'admin@jawilgrup.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', NULL, 1, 1, '2026-09-11 02:42:12', '2026-09-12 14:59:26'),
(2, '171297', 'sano', 'akagamisans2@gmail.com', '$2y$10$x5W9Snk.hT4v86GcGIYWROSBuGYGGU2JBBK13dnwWHIrhTZ.UszaC', 'sano', 'bd5c63586b187181654276cef37d6485.jpg', 1, 1, '2026-09-11 07:38:55', '2026-09-12 14:42:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `finance_settings`
--
ALTER TABLE `finance_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `finance_transactions`
--
ALTER TABLE `finance_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `item_requests`
--
ALTER TABLE `item_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_no` (`request_no`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `location_code` (`location_code`);

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `menu_name` (`menu_name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_menu_access`
--
ALTER TABLE `role_menu_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_menu` (`role_id`,`menu_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indexes for table `stock_out`
--
ALTER TABLE `stock_out`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `unique_employee_id` (`employee_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `finance_transactions`
--
ALTER TABLE `finance_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `item_requests`
--
ALTER TABLE `item_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `role_menu_access`
--
ALTER TABLE `role_menu_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `stock_out`
--
ALTER TABLE `stock_out`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `finance_settings`
--
ALTER TABLE `finance_settings`
  ADD CONSTRAINT `finance_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `finance_transactions`
--
ALTER TABLE `finance_transactions`
  ADD CONSTRAINT `finance_transactions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `items_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  ADD CONSTRAINT `items_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `role_menu_access`
--
ALTER TABLE `role_menu_access`
  ADD CONSTRAINT `role_menu_access_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_menu_access_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_out`
--
ALTER TABLE `stock_out`
  ADD CONSTRAINT `stock_out_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `stock_out_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
