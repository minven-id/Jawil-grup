<?php
require_once 'config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

try {
    $db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) NULL AFTER full_name");
    echo "Kolom foto profil berhasil ditambahkan atau sudah tersedia.<br>";
} catch (Exception $e) {
    echo "Migrasi foto profil dilewati: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<br><strong>Migrasi selesai!</strong> <a href='dashboard.php'>Kembali ke Dashboard</a>";
?>