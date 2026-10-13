<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$auth->requireLogin();
$auth->requirePermission('locations.view', 'locations');
$user = $auth->getCurrentUser();

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && $auth->hasPermission('locations.manage')) {
        $locationName = trim($_POST['location_name']);
        $locationCode = trim($_POST['location_code']);
        $description = trim($_POST['description']);
        $address = trim($_POST['address']);

        $existing = $db->fetchOne("SELECT id FROM locations WHERE location_code = ?", [$locationCode]);
        if ($existing) {
            $_SESSION['error'] = "Kode lokasi <strong>'$locationCode'</strong> sudah digunakan. Silakan gunakan kode yang lain.";
            header('Location: locations.php');
            exit;
        }

        $data = [
            'location_name' => $locationName,
            'location_code' => $locationCode,
            'description' => $description,
            'address' => $address
        ];

        try {
            $locationId = $db->insert('locations', $data);
            $auth->logActivity('created', 'locations', $locationId, null, $data);
            $_SESSION['success'] = 'Lokasi berhasil ditambahkan';
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, '23000') !== false || strpos($msg, '1062') !== false) {
                $_SESSION['error'] = "Kode lokasi <strong>'$locationCode'</strong> sudah digunakan.";
            } else {
                $_SESSION['error'] = 'Gagal menambahkan lokasi: ' . $msg;
            }
        }

        header('Location: locations.php');
        exit;
    }

    if ($action === 'edit' && $auth->hasPermission('locations.manage')) {
        $locationId = intval($_POST['location_id']);
        $oldLocation = $db->fetchOne("SELECT * FROM locations WHERE id = ?", [$locationId]);

        $newLocationCode = trim($_POST['location_code']);
        $existing = $db->fetchOne("SELECT id FROM locations WHERE location_code = ? AND id != ?", [$newLocationCode, $locationId]);
        if ($existing) {
            $_SESSION['error'] = "Kode lokasi <strong>'$newLocationCode'</strong> sudah digunakan lokasi lain. Silakan gunakan kode yang lain.";
            header('Location: locations.php');
            exit;
        }

        $data = [
            'location_name' => trim($_POST['location_name']),
            'location_code' => $newLocationCode,
            'description' => trim($_POST['description']),
            'address' => trim($_POST['address'])
        ];

        try {
            $db->update('locations', $data, 'id = ?', [$locationId]);
            $auth->logActivity('updated', 'locations', $locationId, $oldLocation, $data);
            $_SESSION['success'] = 'Lokasi berhasil diperbarui';
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, '23000') !== false || strpos($msg, '1062') !== false) {
                $_SESSION['error'] = "Kode lokasi <strong>'$newLocationCode'</strong> sudah digunakan lokasi lain.";
            } else {
                $_SESSION['error'] = 'Gagal memperbarui lokasi: ' . $msg;
            }
        }

        header('Location: locations.php');
        exit;
    }

    if ($action === 'delete' && $auth->hasPermission('locations.manage')) {
        $locationId = intval($_POST['location_id']);
        $oldLocation = $db->fetchOne("SELECT * FROM locations WHERE id = ?", [$locationId]);

        $itemCount = $db->fetchOne("SELECT COUNT(*) as count FROM items WHERE location_id = ?", [$locationId])['count'];

        if ($itemCount > 0) {
            $_SESSION['error'] = 'Tidak dapat menghapus lokasi yang masih memiliki barang';
        } else {
            try {
                $db->delete('locations', 'id = ?', [$locationId]);
                $auth->logActivity('deleted', 'locations', $locationId, $oldLocation, null);
                $_SESSION['success'] = 'Lokasi berhasil dihapus';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Gagal menghapus lokasi: ' . $e->getMessage();
            }
        }

        header('Location: locations.php');
        exit;
    }
}

$pageTitle = 'Manajemen Lokasi';
require_once 'includes/header.php';

$auth->requirePermission('locations.view', 'locations');

$search = $_GET['search'] ?? '';

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(location_name LIKE ? OR location_code LIKE ? OR description LIKE ? OR address LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

$whereClause = implode(' AND ', $where);

$locations = $db->fetchAll("
    SELECT l.*, COUNT(i.id) as item_count 
    FROM locations l 
    LEFT JOIN items i ON l.id = i.location_id 
    WHERE $whereClause
    GROUP BY l.id 
    ORDER BY l.location_name ASC
", $params);

$editLocation = null;
if (isset($_GET['edit'])) {
    $editLocation = $db->fetchOne("SELECT * FROM locations WHERE id = ?", [intval($_GET['edit'])]);
}
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Manajemen Lokasi</h2>
        <p class="text-gray-600">Kelola lokasi penyimpanan barang</p>
    </div>
    <?php if ($auth->hasPermission('locations.manage')): ?>
        <button onclick="openModal('addModal')" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-2 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition">
            <i class="fas fa-plus mr-2"></i>
            Tambah Lokasi
        </button>
    <?php endif; ?>
</div>

<!-- Search -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form method="GET" action="" class="flex gap-4">
        <div class="flex-1">
            <input type="text" name="search" placeholder="Cari lokasi..." 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
            <i class="fas fa-search mr-2"></i>
            Cari
        </button>
        <?php if ($search): ?>
            <a href="locations.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-times mr-2"></i>
                Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Locations Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (count($locations) > 0): ?>
        <?php foreach ($locations as $location): ?>
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-600 to-teal-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-white text-xl"></i>
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($auth->hasPermission('locations.manage')): ?>
                            <a href="?edit=<?php echo $location['id']; ?>" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="confirmDelete(<?php echo $location['id']; ?>)" class="text-red-600 hover:text-red-800" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                        <?php echo htmlspecialchars($location['location_code']); ?>
                    </span>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($location['location_name']); ?></h3>
                <p class="text-gray-500 text-sm mb-4"><?php echo htmlspecialchars(substr($location['description'], 0, 100)); ?>...</p>
                <?php if ($location['address']): ?>
                    <p class="text-gray-500 text-sm mb-4">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        <?php echo htmlspecialchars(substr($location['address'], 0, 50)); ?>...
                    </p>
                <?php endif; ?>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">
                        <i class="fas fa-box mr-1"></i>
                        <?php echo $location['item_count']; ?> barang
                    </span>
                    <span class="text-gray-400">
                        <i class="fas fa-calendar mr-1"></i>
                        <?php echo date('d M Y', strtotime($location['created_at'])); ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full bg-white rounded-xl shadow-sm p-12 text-center">
            <i class="fas fa-map-marker-alt text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">Tidak ada lokasi ditemukan</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<?php if ($auth->hasPermission('locations.manage')): ?>
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Tambah Lokasi Baru</h3>
            <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lokasi *</label>
                <input type="text" name="location_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kode Lokasi *</label>
                <input type="text" name="location_code" required maxlength="20" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="Contoh: GDU">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Alamat</label>
                <textarea name="address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal('addModal')" class="px-6 py-2 border rounded-lg hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Edit Modal -->
<?php if ($editLocation && $auth->hasPermission('locations.manage')): ?>
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Edit Lokasi</h3>
            <button onclick="closeModal('editModal'); window.location.href='locations.php';" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6 space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="location_id" value="<?php echo $editLocation['id']; ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lokasi *</label>
                <input type="text" name="location_name" required value="<?php echo htmlspecialchars($editLocation['location_name']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kode Lokasi *</label>
                <input type="text" name="location_code" required maxlength="20" value="<?php echo htmlspecialchars($editLocation['location_code']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="Contoh: GDU">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"><?php echo htmlspecialchars($editLocation['description']); ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Alamat</label>
                <textarea name="address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"><?php echo htmlspecialchars($editLocation['address']); ?></textarea>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal('editModal'); window.location.href='locations.php';" class="px-6 py-2 border rounded-lg hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Update</button>
            </div>
        </form>
    </div>
</div>
<script>
    document.getElementById('editModal').classList.remove('hidden');
</script>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="p-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Konfirmasi Hapus</h3>
                <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus lokasi ini? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <form method="POST" action="" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="location_id" id="deleteLocationId">
                <div class="flex justify-center space-x-3">
                    <button type="button" onclick="closeModal('deleteModal')" class="px-6 py-2 border rounded-lg hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
    
    function confirmDelete(locationId) {
        document.getElementById('deleteLocationId').value = locationId;
        openModal('deleteModal');
    }
</script>

<?php require_once 'includes/footer.php'; ?>
