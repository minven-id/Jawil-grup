<?php
require_once 'config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

try {
    $column = $db->fetchOne(
        "SELECT COLUMN_NAME
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'employee_id'",
        [DB_NAME]
    );

    if (!$column) {
        $db->query("ALTER TABLE users ADD COLUMN employee_id VARCHAR(50) NULL AFTER id");
        echo "Kolom No. Pegawai berhasil ditambahkan.<br>";
    } else {
        echo "Kolom No. Pegawai sudah tersedia.<br>";
    }

    $index = $db->fetchOne(
        "SELECT INDEX_NAME
         FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'employee_id' AND NON_UNIQUE = 0",
        [DB_NAME]
    );

    if (!$index) {
        $db->query("ALTER TABLE users ADD UNIQUE KEY unique_employee_id (employee_id)");
        echo "Index unik No. Pegawai berhasil ditambahkan.<br>";
    } else {
        echo "Index unik No. Pegawai sudah tersedia.<br>";
    }
} catch (Exception $e) {
    echo "Migrasi gagal: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<br><strong>Migrasi No. Pegawai selesai!</strong> <a href='users.php'>Kembali ke Pengguna</a>";
?>
