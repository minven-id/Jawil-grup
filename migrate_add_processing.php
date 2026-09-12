<?php
require_once 'config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

try {
    // Add 'processing' to the status ENUM in item_requests table
    $db->query("
        ALTER TABLE item_requests 
        MODIFY COLUMN status ENUM('pending','approved','processing','barang_tiba','rejected') 
        DEFAULT 'pending'
    ");
    
    echo "SUCCESS: Status 'processing' has been added to item_requests table.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
