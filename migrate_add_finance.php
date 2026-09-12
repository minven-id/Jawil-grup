<?php
require_once 'config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

$steps = [
    "CREATE TABLE IF NOT EXISTS finance_settings (
        id TINYINT UNSIGNED PRIMARY KEY,
        available_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
        total_income DECIMAL(18,2) NOT NULL DEFAULT 0,
        total_expense DECIMAL(18,2) NOT NULL DEFAULT 0,
        updated_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
    )",
    "CREATE TABLE IF NOT EXISTS finance_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_name VARCHAR(150) NOT NULL,
        transaction_type ENUM('income', 'expense') NOT NULL,
        method VARCHAR(80) NOT NULL,
        amount DECIMAL(18,2) NOT NULL,
        notes TEXT,
        transaction_date DATE NOT NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )",
    "INSERT IGNORE INTO finance_settings (id) VALUES (1)",
    "UPDATE finance_settings SET available_amount = total_income - total_expense WHERE id = 1",
    "INSERT IGNORE INTO menus (menu_name, menu_label, menu_icon, menu_url, menu_order) VALUES ('finance', 'Finance', 'fa-wallet', 'finance.php', 6)",
    "UPDATE menus SET menu_label = 'Finance', menu_order = 6 WHERE menu_name = 'finance'",
    "UPDATE menus SET menu_order = 7 WHERE menu_name = 'categories'",
    "UPDATE menus SET menu_order = 8 WHERE menu_name = 'locations'",
    "UPDATE menus SET menu_order = 9 WHERE menu_name = 'roles'",
    "UPDATE menus SET menu_order = 10 WHERE menu_name = 'users'",
    "UPDATE menus SET menu_order = 11 WHERE menu_name = 'logs'"
];

foreach ($steps as $sql) {
    try {
        $db->query($sql);
        echo "OK: " . substr($sql, 0, 75) . "...<br>";
    } catch (Exception $e) {
        echo "SKIP: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
}

try {
    $financeMenu = $db->fetchOne("SELECT id FROM menus WHERE menu_name = 'finance'");
    $roles = $db->fetchAll("SELECT id, role_name, permissions FROM roles");

    foreach ($roles as $role) {
        $permissions = json_decode($role['permissions'], true) ?: [];
        $permissions[] = 'finance.view';

        if (in_array($role['role_name'], ['Super Admin', 'Admin'], true)) {
            $permissions[] = 'finance.manage';
        }

        $permissions = array_values(array_unique($permissions));
        $db->update('roles', ['permissions' => json_encode($permissions)], 'id = ?', [$role['id']]);

        if ($financeMenu) {
            $db->query(
                "INSERT IGNORE INTO role_menu_access (role_id, menu_id, can_view) VALUES (?, ?, 1)",
                [$role['id'], $financeMenu['id']]
            );
        }
    }

    echo "OK: Finance permission and menu access configured.<br>";
} catch (Exception $e) {
    echo "SKIP: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<br><strong>Migrasi Finance selesai!</strong> <a href='finance.php'>Buka Finance</a>";
?>
