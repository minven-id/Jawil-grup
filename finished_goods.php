<?php
$pageTitle = 'Monitoring Barang';
require_once 'includes/header.php';

$auth->requirePermission('items.view');

$db = Database::getInstance();

$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$locationFilter = $_GET['location'] ?? '';

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(i.item_code LIKE ? OR i.item_name LIKE ? OR i.brand LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($categoryFilter) {
    $where[] = "i.category_id = ?";
    $params[] = $categoryFilter;
}

if ($locationFilter) {
    $where[] = "i.location_id = ?";
    $params[] = $locationFilter;
}

$whereClause = implode(' AND ', $where);

$items = $db->fetchAll("
    SELECT i.*, c.category_name, l.location_name, l.location_code
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.id
    LEFT JOIN locations l ON i.location_id = l.id
    WHERE $whereClause
    ORDER BY i.created_at DESC
", $params);

$totalQuantity = 0;
$grandTotal = 0;

foreach ($items as $item) {
    $totalQuantity += $item['quantity'];
    $itemTotal = $item['quantity'] * ($item['purchase_price'] ?? 0);
    $grandTotal += $itemTotal;
}

$categories = $db->fetchAll("SELECT * FROM categories ORDER BY category_name");
$locations = $db->fetchAll("SELECT * FROM locations ORDER BY location_name");
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Monitoring Barang</h2>
        <p class="text-gray-600">Pantau stok, harga beli, dan nilai inventaris barang</p>
    </div>
    <div class="flex gap-2">
        <?php if ($auth->hasPermission('items.manage')): ?>
            <button onclick="openModal('stockOutModal')" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-sign-out-alt mr-2"></i>
                Barang Keluar
            </button>
        <?php endif; ?>
        <button onclick="window.print()" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition">
            <i class="fas fa-print mr-2"></i>
            Cetak
        </button>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-indigo-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Jenis Barang</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format(count($items)); ?></p>
            </div>
            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                <i class="fas fa-boxes text-indigo-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Qty Semua Barang</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($totalQuantity); ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-layer-group text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Harga Pembelian</p>
                <p class="text-2xl font-bold text-gray-800">Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-coins text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form method="GET" action="" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input type="text" name="search" placeholder="Cari nama barang, merk, atau kode..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div>
            <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <select name="location" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <option value="">Semua Lokasi</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?php echo $loc['id']; ?>" <?php echo $locationFilter == $loc['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($loc['location_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
            <i class="fas fa-search mr-2"></i>
            Cari
        </button>
        <?php if ($search || $categoryFilter || $locationFilter): ?>
            <a href="finished_goods.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-times mr-2"></i>
                Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gradient-to-r from-indigo-600 to-purple-600">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">No</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Nama Barang</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Merk</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Lokasi</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Kategori</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-right text-xs font-medium text-white uppercase tracking-wider">Qty Barang</th>
                    <th class="px-6 py-4 text-right text-xs font-medium text-white uppercase tracking-wider">Harga Beli Satuan</th>
                    <th class="px-6 py-4 text-right text-xs font-medium text-white uppercase tracking-wider">Total (Qty x Harga)</th>
                    <?php if ($auth->hasPermission('items.manage')): ?>
                    <th class="px-6 py-4 text-center text-xs font-medium text-white uppercase tracking-wider">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($items) > 0): ?>
                    <?php $no = 1; ?>
                    <?php foreach ($items as $item): ?>
                        <?php $itemTotal = $item['quantity'] * ($item['purchase_price'] ?? 0); ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $no++; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php if ($item['image_path']): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="" class="w-10 h-10 rounded-lg object-cover mr-3">
                                    <?php else: ?>
                                        <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-box text-gray-400"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['item_name']); ?></p>
                                        <p class="text-xs text-gray-500">Kode: <?php echo htmlspecialchars($item['item_code']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if ($item['brand']): ?>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                        <?php echo htmlspecialchars($item['brand']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if ($item['location_name']): ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium mr-1">
                                        <?php echo htmlspecialchars($item['location_code']); ?>
                                    </span>
                                    <span><?php echo htmlspecialchars($item['location_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($item['category_name'] ?? '-'); ?>
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
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusColors[$item['status']]; ?>">
                                    <?php echo $statusLabels[$item['status']]; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">
                                <span class="<?php echo $item['quantity'] < 5 ? 'text-red-600' : 'text-gray-900'; ?>">
                                    <?php echo number_format($item['quantity']); ?> <?php echo htmlspecialchars($item['unit']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                <?php echo $item['purchase_price'] ? 'Rp ' . number_format($item['purchase_price'], 0, ',', '.') : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">
                                Rp <?php echo number_format($itemTotal, 0, ',', '.'); ?>
                            </td>
                            <?php if ($auth->hasPermission('items.manage')): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <select onchange="updateStatus(<?php echo $item['id']; ?>, this.value)" class="px-3 py-1 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500">
                                    <option value="available" <?php echo $item['status'] == 'available' ? 'selected' : ''; ?>>Tersedia</option>
                                    <option value="in_use" <?php echo $item['status'] == 'in_use' ? 'selected' : ''; ?>>Digunakan</option>
                                    <option value="maintenance" <?php echo $item['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="broken" <?php echo $item['status'] == 'broken' ? 'selected' : ''; ?>>Rusak</option>
                                    <option value="lost" <?php echo $item['status'] == 'lost' ? 'selected' : ''; ?>>Hilang</option>
                                </select>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo $auth->hasPermission('items.manage') ? 10 : 9; ?>" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-box-open text-4xl mb-4 text-gray-300"></i>
                            <p>Tidak ada barang ditemukan</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <?php if (count($items) > 0): ?>
                <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                    <tr>
                        <td colspan="<?php echo $auth->hasPermission('items.manage') ? 7 : 6; ?>" class="px-6 py-4 text-sm font-bold text-gray-800 text-right uppercase tracking-wider">
                            <i class="fas fa-calculator mr-2"></i>TOTAL SEMUA BARANG:
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-lg font-bold text-green-600">
                                <?php echo number_format($totalQuantity); ?> unit
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-500">
                            -
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-lg font-bold text-purple-600">
                                Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?>
                            </span>
                        </td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
function updateStatus(itemId, newStatus) {
    if (!confirm('Apakah Anda yakin ingin mengubah status barang ini?')) {
        location.reload();
        return;
    }

    fetch('items.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=update_status&item_id=' + itemId + '&status=' + newStatus
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Gagal mengubah status: ' + data.message);
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat mengubah status');
        location.reload();
    });
}

function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Handle stock out form submission after the modal markup is available.
document.addEventListener('DOMContentLoaded', function() {
    const stockOutForm = document.getElementById('stockOutForm');
    if (!stockOutForm) return;

    const statusInput = stockOutForm.querySelector('[name="status"]');
    const photoInput = document.getElementById('photo');
    const photoLabel = document.getElementById('photoLabel');
    const photoHelp = document.getElementById('photoHelp');

    function syncPhotoRequirement() {
        const isBroken = statusInput && statusInput.value === 'broken';
        if (photoInput) photoInput.required = isBroken;
        if (photoLabel) photoLabel.innerHTML = isBroken ? 'Foto Barang Rusak *' : 'Foto (Opsional)';
        if (photoHelp) photoHelp.textContent = isBroken
            ? 'Foto wajib diunggah sebagai bukti kondisi barang rusak.'
            : 'Upload foto bukti barang keluar (tidak wajib)';
    }

    if (statusInput) {
        statusInput.addEventListener('change', syncPhotoRequirement);
        syncPhotoRequirement();
    }

    stockOutForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';

        fetch('items.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal('stockOutModal');
                showSuccessPopup(data.message || 'Barang keluar berhasil diproses!');
                setTimeout(() => {
                    window.location.href = 'stock_out.php';
                }, 1500);
            } else {
                alert('Gagal: ' + data.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-sign-out-alt mr-2"></i> Proses Barang Keluar';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memproses barang keluar');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-sign-out-alt mr-2"></i> Proses Barang Keluar';
        });
    });
});

function showSuccessPopup(message) {
    const popup = document.createElement('div');
    popup.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 flex items-center gap-3 transform transition-all duration-300 translate-x-full';
    popup.innerHTML = `
        <i class="fas fa-check-circle text-2xl"></i>
        <span class="font-medium">${message}</span>
    `;
    document.body.appendChild(popup);

    setTimeout(() => {
        popup.classList.remove('translate-x-full');
    }, 100);

    setTimeout(() => {
        popup.classList.add('translate-x-full');
        setTimeout(() => {
            document.body.removeChild(popup);
        }, 300);
    }, 3000);
}
</script>

<!-- Stock Out Modal -->
<?php if ($auth->hasPermission('items.manage')): ?>
<div id="stockOutModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Barang Keluar</h3>
            <button onclick="closeModal('stockOutModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="stockOutForm" method="POST" action="items.php" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="action" value="stock_out">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Barang *</label>
                <select name="item_id" id="item_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">Pilih Barang</option>
                    <?php foreach ($items as $item): ?>
                        <option value="<?php echo $item['id']; ?>" data-qty="<?php echo $item['quantity']; ?>">
                            <?php echo htmlspecialchars($item['item_name']); ?> (Kode: <?php echo htmlspecialchars($item['item_code']); ?>) - Stok: <?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Keluar *</label>
                    <input type="number" name="quantity" id="quantity" required min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="available">Tersedia</option>
                        <option value="in_use">Digunakan</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="broken">Rusak</option>
                        <option value="lost">Hilang</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Catatan *</label>
                <textarea name="notes" required rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="Masukkan alasan barang keluar..."></textarea>
            </div>
            <div>
                <label id="photoLabel" class="block text-sm font-medium text-gray-700 mb-2">Foto (Opsional)</label>
                <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/gif" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                <p id="photoHelp" class="text-xs text-gray-500 mt-1">Upload foto bukti barang keluar (tidak wajib)</p>
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="closeModal('stockOutModal')" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Batal
                </button>
                <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Proses Barang Keluar
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
