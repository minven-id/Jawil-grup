<?php
require_once 'config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

try {
    // Add photo_bon column to item_requests table
    $db->query("ALTER TABLE item_requests ADD COLUMN photo_bon VARCHAR(255) NULL AFTER rejected_at");
    
    echo "SUCCESS: Column photo_bon has been added to item_requests table.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
