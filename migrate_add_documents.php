<?php
require_once 'config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

try {
    $db->query("CREATE TABLE IF NOT EXISTS documents (
        id INT(11) NOT NULL AUTO_INCREMENT,
        title VARCHAR(200) NOT NULL,
        description TEXT DEFAULT NULL,
        original_filename VARCHAR(255) NOT NULL,
        stored_filename VARCHAR(255) NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        file_size INT UNSIGNED NOT NULL,
        uploaded_by INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY stored_filename (stored_filename),
        KEY uploaded_by (uploaded_by),
        CONSTRAINT documents_ibfk_1 FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $roles = $db->fetchAll('SELECT id, role_name, permissions FROM roles');
    foreach ($roles as $role) {
        $permissions = json_decode($role['permissions'] ?? '[]', true);
        $permissions = is_array($permissions) ? $permissions : [];

        if (in_array($role['role_name'], ['Super Admin', 'Admin'], true)) {
            $newPermissions = ['documents.view', 'documents.manage'];
        } elseif ($role['role_name'] === 'Staff') {
            $newPermissions = ['documents.view'];
        } else {
            continue;
        }

        $updated = false;
        foreach ($newPermissions as $permission) {
            if (!in_array($permission, $permissions, true)) {
                $permissions[] = $permission;
                $updated = true;
            }
        }

        if ($updated) {
            $db->update('roles', ['permissions' => json_encode($permissions)], 'id = ?', [$role['id']]);
        }
    }

    $documentDirectory = __DIR__ . '/uploads/documents';
    if (!is_dir($documentDirectory)) {
        mkdir($documentDirectory, 0750, true);
    }

    echo "Migrasi dokumen berhasil. <a href='dashboard.php'>Kembali ke Dashboard</a>";
} catch (Exception $e) {
    echo 'Migrasi dokumen gagal: ' . htmlspecialchars($e->getMessage());
}
