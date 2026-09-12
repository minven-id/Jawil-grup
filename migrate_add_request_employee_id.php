<?php
require_once 'config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

try {
    $column = $db->fetchOne(
        "SELECT COLUMN_NAME
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'item_requests' AND COLUMN_NAME = 'employee_id'",
        [DB_NAME]
    );

    if (!$column) {
        $db->query("ALTER TABLE item_requests ADD COLUMN employee_id VARCHAR(50) NULL AFTER requester_name");
        echo "Kolom kode pegawai pada pengajuan berhasil ditambahkan.<br>";
    } else {
        echo "Kolom kode pegawai pada pengajuan sudah tersedia.<br>";
    }
} catch (Exception $e) {
    echo "Migrasi gagal: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<br><strong>Migrasi kode pegawai pengajuan selesai!</strong> <a href='item_requests.php'>Kembali ke Pengajuan Barang</a>";
?>
