<?php
require_once 'config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

$migrations = [
    "ALTER TABLE items ADD COLUMN IF NOT EXISTS brand VARCHAR(100) AFTER item_name",
    "INSERT IGNORE INTO menus (menu_name, menu_label, menu_icon, menu_url, menu_order) VALUES ('finished_goods', 'Monitoring Barang', 'fa-clipboard-list', 'finished_goods.php', 4)",
    "UPDATE menus SET menu_label = 'Monitoring Barang', menu_order = 4 WHERE menu_name = 'finished_goods'",
    "UPDATE menus SET menu_order = 5 WHERE menu_name = 'categories'",
    "UPDATE menus SET menu_order = 6 WHERE menu_name = 'locations'",
    "UPDATE menus SET menu_order = 7 WHERE menu_name = 'roles'",
    "UPDATE menus SET menu_order = 8 WHERE menu_name = 'users'",
    "UPDATE menus SET menu_order = 9 WHERE menu_name = 'logs'"
];

foreach ($migrations as $sql) {
    try {
        $db->query($sql);
        echo "OK: " . substr($sql, 0, 60) . "...<br>";
    } catch (Exception $e) {
        echo "SKIP: " . $e->getMessage() . "<br>";
    }
}

$menuResult = $db->fetchOne("SELECT id FROM menus WHERE menu_name = 'finished_goods'");
if ($menuResult) {
    $menuId = $menuResult['id'];
    foreach ([1, 2, 3, 4] as $roleId) {
        try {
            $db->query("INSERT IGNORE INTO role_menu_access (role_id, menu_id, can_view) VALUES (?, ?, 1)", [$roleId, $menuId]);
        } catch (Exception $e) {
            echo "SKIP access: " . $e->getMessage() . "<br>";
        }
    }
}

echo "<br><strong>Migrasi selesai!</strong> <a href='dashboard.php'>Kembali ke Dashboard</a>";
?>
