<?php
require_once 'config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

try {
    // Add photo_bon column to items table
    $db->query("ALTER TABLE items ADD COLUMN photo_bon VARCHAR(255) NULL AFTER foto_kiri");
    
    echo "SUCCESS: Column photo_bon has been added to items table.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
