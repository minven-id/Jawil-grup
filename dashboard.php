<?php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

$db = Database::getInstance();

// Get statistics
$totalItems = $db->fetchOne("SELECT COUNT(*) as count FROM items")['count'];
$totalCategories = $db->fetchOne("SELECT COUNT(*) as count FROM categories")['count'];
$totalLocations = $db->fetchOne("SELECT COUNT(*) as count FROM locations")['count'];
$totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];

// Get items by status
$itemsByStatus = $db->fetchAll("SELECT status, COUNT(*) as count FROM items GROUP BY status");
$statusData = [];
foreach ($itemsByStatus as $row) {
    $statusData[$row['status']] = $row['count'];
}

// Get recent activities
$recentActivities = $db->fetchAll("
    SELECT al.*, u.full_name 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");

// Get low stock items (quantity < 5)
$lowStockItems = $db->fetchAll("
    SELECT i.*, c.category_name 
    FROM items i 
    LEFT JOIN categories c ON i.category_id = c.id 
    WHERE i.quantity < 5 
    ORDER BY i.quantity ASC 
    LIMIT 5
");

// Get items by location
$itemsByLocation = $db->fetchAll("
    SELECT l.location_name, l.location_code, COUNT(i.id) as item_count 
    FROM locations l 
    LEFT JOIN items i ON l.id = i.location_id 
    GROUP BY l.id 
    ORDER BY item_count DESC 
    LIMIT 5
");

// Get recent items
$recentItems = $db->fetchAll("
    SELECT i.*, c.category_name 
    FROM items i 
    LEFT JOIN categories c ON i.category_id = c.id 
    ORDER BY i.created_at DESC 
    LIMIT 5
");
?>

<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
    <p class="text-gray-600">Selamat datang kembali, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Barang</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($totalItems); ?></p>
            </div>
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-box text-gray-800 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Kategori</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($totalCategories); ?></p>
            </div>
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-tags text-gray-800 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-gray-600">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Lokasi</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($totalLocations); ?></p>
            </div>
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-map-marker-alt text-gray-800 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-gray-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Stok Rendah</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo count($lowStockItems); ?></p>
            </div>
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-gray-800 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Status Overview -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Status Barang</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Tersedia</span>
                <div class="flex items-center space-x-2">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div class="bg-gray-800 h-2 rounded-full" style="width: <?php echo $totalItems > 0 ? (($statusData['available'] ?? 0) / $totalItems * 100) : 0; ?>%"></div>
                    </div>
                    <span class="text-sm font-medium"><?php echo $statusData['available'] ?? 0; ?></span>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Sedang Digunakan</span>
                <div class="flex items-center space-x-2">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div class="bg-gray-700 h-2 rounded-full" style="width: <?php echo $totalItems > 0 ? (($statusData['in_use'] ?? 0) / $totalItems * 100) : 0; ?>%"></div>
                    </div>
                    <span class="text-sm font-medium"><?php echo $statusData['in_use'] ?? 0; ?></span>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Maintenance</span>
                <div class="flex items-center space-x-2">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div class="bg-gray-500 h-2 rounded-full" style="width: <?php echo $totalItems > 0 ? (($statusData['maintenance'] ?? 0) / $totalItems * 100) : 0; ?>%"></div>
                    </div>
                    <span class="text-sm font-medium"><?php echo $statusData['maintenance'] ?? 0; ?></span>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Rusak</span>
                <div class="flex items-center space-x-2">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div class="bg-gray-600 h-2 rounded-full" style="width: <?php echo $totalItems > 0 ? (($statusData['broken'] ?? 0) / $totalItems * 100) : 0; ?>%"></div>
                    </div>
                    <span class="text-sm font-medium"><?php echo $statusData['broken'] ?? 0; ?></span>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Hilang</span>
                <div class="flex items-center space-x-2">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div class="bg-gray-500 h-2 rounded-full" style="width: <?php echo $totalItems > 0 ? (($statusData['lost'] ?? 0) / $totalItems * 100) : 0; ?>%"></div>
                    </div>
                    <span class="text-sm font-medium"><?php echo $statusData['lost'] ?? 0; ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Stok Rendah</h3>
        <?php if (count($lowStockItems) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($lowStockItems as $item): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-100 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($item['item_name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($item['category_name'] ?? '-'); ?></p>
                        </div>
                        <span class="px-3 py-1 bg-gray-200 text-gray-800 rounded-full text-sm font-medium">
                            <?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Tidak ada barang dengan stok rendah</p>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Items and Activities -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Barang Terbaru</h3>
            <?php if ($auth->hasPermission('items.view')): ?>
                <a href="items.php" class="text-gray-800 hover:text-gray-600 text-sm font-semibold">Lihat Semua</a>
            <?php endif; ?>
        </div>
        <?php if (count($recentItems) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($recentItems as $item): ?>
                    <div class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-box text-gray-800"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($item['item_name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($item['item_code']); ?></p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 bg-gray-200 text-gray-800 rounded text-xs">
                                <?php echo htmlspecialchars($item['status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Belum ada barang</p>
        <?php endif; ?>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Barang per Lokasi</h3>
            <?php if ($auth->hasPermission('locations.view')): ?>
                <a href="locations.php" class="text-gray-800 hover:text-gray-600 text-sm font-semibold">Lihat Semua</a>
            <?php endif; ?>
        </div>
        <?php if (count($itemsByLocation) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($itemsByLocation as $loc): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($loc['location_name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($loc['location_code']); ?></p>
                        </div>
                        <span class="px-3 py-1 bg-gray-200 text-gray-800 rounded-full text-sm font-medium">
                            <?php echo $loc['item_count']; ?> barang
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Belum ada lokasi</p>
        <?php endif; ?>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Aktivitas Terbaru</h3>
            <?php if ($auth->hasPermission('logs.view')): ?>
                <a href="logs.php" class="text-gray-800 hover:text-gray-600 text-sm font-semibold">Lihat Semua</a>
            <?php endif; ?>
        </div>
        <?php if (count($recentActivities) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($recentActivities as $activity): ?>
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-history text-gray-800 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-800">
                                <strong><?php echo htmlspecialchars($activity['full_name'] ?? 'System'); ?></strong>
                                <?php echo htmlspecialchars($activity['action']); ?>
                                <?php if ($activity['table_name']): ?>
                                    <?php echo htmlspecialchars($activity['table_name']); ?>
                                <?php endif; ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php echo date('d M Y H:i', strtotime($activity['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Belum ada aktivitas</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
