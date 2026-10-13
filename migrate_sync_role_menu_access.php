<?php
require_once 'config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

try {
    // 1. Tambahkan menu 'documents' jika belum ada
    $existingMenu = $db->fetchOne("SELECT id FROM menus WHERE menu_name = 'documents'");
    if (!$existingMenu) {
        $db->insert('menus', [
            'menu_name' => 'documents',
            'menu_label' => 'Dokumen',
            'menu_icon' => 'fa-file-alt',
            'menu_url' => 'documents.php',
            'menu_order' => 13,
            'is_active' => 1
        ]);
        echo "Menu 'documents' berhasil ditambahkan.<br>";
    } else {
        echo "Menu 'documents' sudah ada.<br>";
    }

    // 2. Ambil semua menu yang aktif
    $menus = $db->fetchAll("SELECT * FROM menus WHERE is_active = 1 ORDER BY menu_order");

    // 3. Definisikan role dan menu yang harus diakses berdasarkan permissions
    $roleMenuConfig = [
        'Super Admin' => 'all', // Semua menu
        'Admin' => ['dashboard', 'items', 'categories', 'locations', 'users', 'logs', 'item_requests', 'finished_goods', 'stock_out', 'finance', 'password_requests', 'documents'],
        'Manager' => ['dashboard', 'items', 'categories', 'locations', 'item_requests', 'finished_goods', 'stock_out', 'finance'],
        'Staff' => ['dashboard', 'items', 'locations', 'item_requests', 'finished_goods', 'stock_out', 'documents'],
        'senior bar' => ['dashboard', 'items', 'locations', 'item_requests', 'finished_goods', 'stock_out', 'documents'] // Akses seperti Staff
    ];

    // 4. Bersihkan role_menu_access yang ada untuk semua role
    $db->query("DELETE FROM role_menu_access");
    echo "Role menu access yang lama telah dibersihkan.<br>";

    // 5. Rebuild role_menu_access berdasarkan konfigurasi
    $roles = $db->fetchAll('SELECT id, role_name FROM roles');

    foreach ($roles as $role) {
        $roleName = $role['role_name'];
        $roleId = $role['id'];

        $allowedMenus = $roleMenuConfig[$roleName] ?? [];

        foreach ($menus as $menu) {
            $menuName = $menu['menu_name'];
            $menuId = $menu['id'];

            // Tentukan apakah role ini boleh mengakses menu ini
            $canView = false;

            if ($allowedMenus === 'all') {
                $canView = true;
            } elseif (in_array($menuName, $allowedMenus, true)) {
                $canView = true;
            }

            if ($canView) {
                // Cek apakah sudah ada
                $existing = $db->fetchOne(
                    "SELECT id FROM role_menu_access WHERE role_id = ? AND menu_id = ?",
                    [$roleId, $menuId]
                );

                if (!$existing) {
                    $db->insert('role_menu_access', [
                        'role_id' => $roleId,
                        'menu_id' => $menuId,
                        'can_view' => 1
                    ]);
                }
            }
        }

        echo "Role menu access untuk '$roleName' telah diperbarui.<br>";
    }

    echo "<br><strong>Sinkronisasi role dan menu akses berhasil!</strong> <a href='dashboard.php'>Kembali ke Dashboard</a>";

} catch (Exception $e) {
    echo 'Sinkronisasi gagal: ' . htmlspecialchars($e->getMessage());
}
