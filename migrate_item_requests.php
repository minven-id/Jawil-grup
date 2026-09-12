<?php
require_once 'config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

$steps = [
    "CREATE TABLE IF NOT EXISTS item_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_no VARCHAR(50) UNIQUE NOT NULL,
        request_date DATE NOT NULL,
        requester_name VARCHAR(150) NOT NULL,
        department VARCHAR(100),
        purpose TEXT,
        item_details JSON NOT NULL,
        total_items INT DEFAULT 0,
        total_amount DECIMAL(18,2) DEFAULT 0,
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        admin_note TEXT,
        approved_by INT,
        approved_at DATETIME,
        rejected_by INT,
        rejected_at DATETIME,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($steps as $sql) {
    try {
        $db->query($sql);
        echo "OK: " . substr($sql, 0, 80) . "...<br>";
    } catch (Exception $e) {
        echo "SKIP / ERR: " . $e->getMessage() . "<br>";
    }
}

$menuUpdates = [
    "UPDATE menus SET menu_order = 9 WHERE menu_name = 'logs'",
    "UPDATE menus SET menu_order = 8 WHERE menu_name = 'users'",
    "UPDATE menus SET menu_order = 7 WHERE menu_name = 'roles'",
    "UPDATE menus SET menu_order = 6 WHERE menu_name = 'locations'",
    "UPDATE menus SET menu_order = 5 WHERE menu_name = 'categories'",
    "UPDATE menus SET menu_label = 'Monitoring Barang', menu_order = 4 WHERE menu_name = 'finished_goods'",
    "INSERT IGNORE INTO menus (menu_name, menu_label, menu_icon, menu_url, menu_order, is_active) VALUES ('item_requests', 'Pengajuan Barang', 'fa-file-alt', 'item_requests.php', 3, 1)",
];
foreach ($menuUpdates as $sql) {
    try {
        $db->query($sql);
        echo "OK menu...<br>";
    } catch (Exception $e) {
        echo "SKIP: " . $e->getMessage() . "<br>";
    }
}

$menuRes = $db->fetchOne("SELECT id FROM menus WHERE menu_name = 'item_requests'");
if ($menuRes) {
    $mid = $menuRes['id'];
    foreach ([1,2,3,4] as $rid) {
        try {
            $db->query("INSERT IGNORE INTO role_menu_access (role_id, menu_id, can_view) VALUES ($rid, $mid, 1)");
            echo "OK access role $rid...<br>";
        } catch (Exception $e) {
            echo "SKIP access: " . $e->getMessage() . "<br>";
        }
    }
}

echo "<br><strong>Migrasi Pengajuan Barang selesai!</strong> <a href='item_requests.php'>Buka Pengajuan Barang</a> | <a href='dashboard.php'>Dashboard</a>";
?>
