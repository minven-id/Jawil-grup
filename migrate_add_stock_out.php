<?php
require_once 'config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

try {
    // Create stock_out table
    $db->query("
        CREATE TABLE IF NOT EXISTS stock_out (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            quantity INT NOT NULL,
            status ENUM('available', 'in_use', 'maintenance', 'broken', 'lost') NOT NULL,
            notes TEXT NOT NULL,
            photo VARCHAR(255),
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES items(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        )
    ");
    echo "✓ Table 'stock_out' created successfully\n";
} catch (Exception $e) {
    echo "✗ Error creating stock_out table: " . $e->getMessage() . "\n";
}

try {
    // Check if stock_out menu already exists
    $existingMenu = $db->fetchOne("SELECT id FROM menus WHERE menu_name = 'stock_out'");
    
    if (!$existingMenu) {
        // Insert stock_out menu
        $db->query("
            INSERT INTO menus (menu_name, menu_label, menu_icon, menu_url, menu_order) VALUES
            ('stock_out', 'Stok Keluar', 'fa-sign-out-alt', 'stock_out.php', 5)
        ");
        echo "✓ Menu 'Stok Keluar' added successfully\n";
        
        // Get the new menu ID
        $menuId = $db->fetchOne("SELECT id FROM menus WHERE menu_name = 'stock_out'")['id'];
        
        // Grant access to all roles
        $roles = $db->fetchAll("SELECT id FROM roles");
        foreach ($roles as $role) {
            $db->query("
                INSERT INTO role_menu_access (role_id, menu_id, can_view)
                VALUES (?, ?, 1)
            ", [$role['id'], $menuId]);
        }
        echo "✓ Menu access granted to all roles\n";
    } else {
        echo "✓ Menu 'Stok Keluar' already exists\n";
    }
} catch (Exception $e) {
    echo "✗ Error adding menu: " . $e->getMessage() . "\n";
}

echo "\nMigration completed!\n";
