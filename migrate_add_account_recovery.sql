-- Migrasi fitur Lupa Password dan Butuh Akun
-- Database: jawil_grup
-- Jalankan melalui phpMyAdmin setelah memilih database jawil_grup.

USE `jawil_grup`;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token_hash` (`token_hash`),
  KEY `password_resets_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `password_reset_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `requested_password_hash` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` varchar(500) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `password_reset_requests_user_id` (`user_id`),
  KEY `password_reset_requests_status` (`status`),
  KEY `password_reset_requests_reviewer` (`reviewed_by`),
  CONSTRAINT `password_reset_requests_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `password_reset_requests_reviewer_fk` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `account_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` varchar(500) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `account_requests_status` (`status`),
  KEY `account_requests_employee_id` (`employee_id`),
  KEY `account_requests_reviewer` (`reviewed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `menus` (`menu_name`, `menu_label`, `menu_icon`, `menu_url`, `menu_order`, `is_active`)
SELECT 'password_requests', 'Reset Password', 'fa-key', 'password_requests.php', 12, 1
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `menu_name` = 'password_requests');

INSERT INTO `role_menu_access` (`role_id`, `menu_id`, `can_view`)
SELECT r.id, m.id, 1
FROM `roles` r
CROSS JOIN `menus` m
WHERE r.role_name IN ('Super Admin', 'Admin')
  AND m.menu_name = 'password_requests'
  AND NOT EXISTS (
      SELECT 1 FROM `role_menu_access` rma
      WHERE rma.role_id = r.id AND rma.menu_id = m.id
  );

SELECT 'Migrasi berhasil. Tabel password_resets, password_reset_requests, dan account_requests sudah siap.' AS hasil;
