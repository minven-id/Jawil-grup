<?php
require_once 'config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

try {
    $db->query("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_token_hash (token_hash),
        KEY password_resets_user_id (user_id),
        CONSTRAINT password_resets_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "Tabel password_resets siap.<br>";

    $db->query("CREATE TABLE IF NOT EXISTS password_reset_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        requested_password_hash VARCHAR(255) NULL,
        status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
        admin_note VARCHAR(500) NULL,
        reviewed_by INT NULL,
        reviewed_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY password_reset_requests_user_id (user_id),
        KEY password_reset_requests_status (status),
        KEY password_reset_requests_reviewer (reviewed_by),
        CONSTRAINT password_reset_requests_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT password_reset_requests_reviewer_fk FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "Tabel password_reset_requests siap.<br>";

    $db->query("CREATE TABLE IF NOT EXISTS account_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id VARCHAR(50) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        department VARCHAR(100) NULL,
        reason VARCHAR(500) NULL,
        status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
        admin_note VARCHAR(500) NULL,
        reviewed_by INT NULL,
        reviewed_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY account_requests_status (status),
        KEY account_requests_employee_id (employee_id),
        CONSTRAINT account_requests_reviewer_fk FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "Tabel account_requests siap.<br>";

    $db->query("INSERT INTO menus (menu_name, menu_label, menu_icon, menu_url, menu_order, is_active)
        SELECT 'password_requests', 'Reset Password', 'fa-key', 'password_requests.php', 12, 1
        WHERE NOT EXISTS (SELECT 1 FROM menus WHERE menu_name = 'password_requests')");
    $db->query("INSERT INTO role_menu_access (role_id, menu_id, can_view)
        SELECT r.id, m.id, 1
        FROM roles r
        CROSS JOIN menus m
        WHERE r.role_name IN ('Super Admin', 'Admin')
          AND m.menu_name = 'password_requests'
          AND NOT EXISTS (
              SELECT 1 FROM role_menu_access rma
              WHERE rma.role_id = r.id AND rma.menu_id = m.id
          )");
    echo "Menu Reset Password siap.<br>";
} catch (Exception $e) {
    echo "Migrasi gagal: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<br><strong>Migrasi fitur akun selesai!</strong> <a href='login.php'>Ke Login</a>";
?>
