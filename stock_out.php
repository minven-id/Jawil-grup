<?php
$pageTitle = 'Stok Keluar';
require_once 'includes/header.php';

$auth->requirePermission('items.view', 'items');

$db = Database::getInstance();

$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(i.item_code LIKE ? OR i.item_name LIKE ? OR u.full_name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($statusFilter) {
    $where[] = "so.status = ?";
    $params[] = $statusFilter;
}

if ($dateFrom) {
    $where[] = "DATE(so.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = "DATE(so.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = implode(' AND ', $where);

$stockOuts = $db->fetchAll("
    SELECT so.*, i.item_code, i.item_name, i.unit, u.full_name as created_by_name
    FROM stock_out so
    LEFT JOIN items i ON so.item_id = i.id
    LEFT JOIN users u ON so.created_by = u.id
    WHERE $whereClause
    ORDER BY so.created_at DESC
", $params);

$totalQuantity = 0;
foreach ($stockOuts as $stockOut) {
    $totalQuantity += $stockOut['quantity'];
}
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Stok Keluar</h2>
        <p class="text-gray-600">Riwayat barang yang keluar dari inventaris</p>
    </div>
    <div class="flex gap-2">
        <button onclick="window.print()" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition">
            <i class="fas fa-print mr-2"></i>
            Cetak
        </button>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Transaksi</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format(count($stockOuts)); ?></p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-sign-out-alt text-red-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-orange-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Qty Keluar</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($totalQuantity); ?></p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-box-open text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form method="GET" action="" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input type="text" name="search" placeholder="Cari nama barang, kode, atau pengguna..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div>
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <option value="">Semua Status</option>
                <option value="available" <?php echo $statusFilter == 'available' ? 'selected' : ''; ?>>Tersedia</option>
                <option value="in_use" <?php echo $statusFilter == 'in_use' ? 'selected' : ''; ?>>Digunakan</option>
                <option value="maintenance" <?php echo $statusFilter == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                <option value="broken" <?php echo $statusFilter == 'broken' ? 'selected' : ''; ?>>Rusak</option>
                <option value="lost" <?php echo $statusFilter == 'lost' ? 'selected' : ''; ?>>Hilang</option>
            </select>
        </div>
        <div>
            <input type="date" name="date_from" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" value="<?php echo htmlspecialchars($dateFrom); ?>">
        </div>
        <div>
            <input type="date" name="date_to" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" value="<?php echo htmlspecialchars($dateTo); ?>">
        </div>
        <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
            <i class="fas fa-search mr-2"></i>
            Cari
        </button>
        <?php if ($search || $statusFilter || $dateFrom || $dateTo): ?>
            <a href="stock_out.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-times mr-2"></i>
                Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gradient-to-r from-red-600 to-orange-600">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">No</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Tanggal</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Nama Barang</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Kode Barang</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Qty Keluar</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Catatan</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Foto</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Oleh</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($stockOuts) > 0): ?>
                    <?php $no = 1; ?>
                    <?php foreach ($stockOuts as $stockOut): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $no++; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d/m/Y H:i', strtotime($stockOut['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($stockOut['item_name'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($stockOut['item_code'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600">
                                <?php echo number_format($stockOut['quantity']); ?> <?php echo htmlspecialchars($stockOut['unit'] ?? 'pcs'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $statusColors = [
                                    'available' => 'bg-green-100 text-green-800',
                                    'in_use' => 'bg-blue-100 text-blue-800',
                                    'maintenance' => 'bg-yellow-100 text-yellow-800',
                                    'broken' => 'bg-red-100 text-red-800',
                                    'lost' => 'bg-gray-100 text-gray-800'
                                ];
                                $statusLabels = [
                                    'available' => 'Tersedia',
                                    'in_use' => 'Digunakan',
                                    'maintenance' => 'Maintenance',
                                    'broken' => 'Rusak',
                                    'lost' => 'Hilang'
                                ];
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusColors[$stockOut['status']]; ?>">
                                    <?php echo $statusLabels[$stockOut['status']]; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                <?php echo htmlspecialchars($stockOut['notes']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if ($stockOut['photo']): ?>
                                    <a href="<?php echo htmlspecialchars($stockOut['photo']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800" title="Lihat Foto">
                                        <i class="fas fa-image"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($stockOut['created_by_name'] ?? '-'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-box-open text-4xl mb-4 text-gray-300"></i>
                            <p>Tidak ada data stok keluar ditemukan</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
