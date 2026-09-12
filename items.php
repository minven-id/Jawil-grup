<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$auth->requireLogin();
$user = $auth->getCurrentUser();

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status' && $auth->hasPermission('items.manage')) {
        $itemId = intval($_POST['item_id']);
        $newStatus = $_POST['status'];

        $validStatuses = ['available', 'in_use', 'maintenance', 'broken', 'lost'];
        if (!in_array($newStatus, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
            exit;
        }

        try {
            $oldItem = $db->fetchOne("SELECT * FROM items WHERE id = ?", [$itemId]);
            if (!$oldItem) {
                echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan']);
                exit;
            }

            $db->update('items', ['status' => $newStatus], 'id = ?', [$itemId]);
            $auth->logActivity('updated_status', 'items', $itemId, $oldItem, ['status' => $newStatus]);

            echo json_encode(['success' => true, 'message' => 'Status berhasil diubah']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'add' && $auth->hasPermission('items.manage')) {
        $itemCode = trim($_POST['item_code']);
        $itemName = trim($_POST['item_name']);
        $brand = trim($_POST['brand']) ?: null;
        $description = trim($_POST['description']);
        $categoryId = $_POST['category_id'] ?: null;
        $locationId = $_POST['location_id'] ?: null;
        $quantity = intval($_POST['quantity']);
        $unit = trim($_POST['unit']);
        $status = $_POST['status'];
        $purchaseDate = $_POST['purchase_date'] ?: null;
        $purchasePrice = $_POST['purchase_price'] ?: null;
        $supplier = trim($_POST['supplier']);

        $existing = $db->fetchOne("SELECT id FROM items WHERE item_code = ?", [$itemCode]);
        if ($existing) {
            $_SESSION['error'] = "Kode barang <strong>'$itemCode'</strong> sudah digunakan. Silakan gunakan kode yang lain.";
            header('Location: items.php');
            exit;
        }

        $imagePath = null;
        $fotoAtas = null;
        $fotoBawah = null;
        $fotoKanan = null;
        $fotoKiri = null;
        $photoBon = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed) && $_FILES['image']['size'] <= MAX_FILE_SIZE) {
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0777, true);
                }
                $newFilename = uniqid() . '.' . $ext;
                $imagePath = UPLOAD_DIR . $newFilename;
                move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
            }
        }

        $photoFields = ['foto_atas', 'foto_bawah', 'foto_kanan', 'foto_kiri'];
        foreach ($photoFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES[$field]['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (in_array($ext, $allowed) && $_FILES[$field]['size'] <= MAX_FILE_SIZE) {
                    if (!is_dir(UPLOAD_DIR)) {
                        mkdir(UPLOAD_DIR, 0777, true);
                    }
                    $newFilename = uniqid() . '_' . $field . '.' . $ext;
                    $photoPath = UPLOAD_DIR . $newFilename;
                    move_uploaded_file($_FILES[$field]['tmp_name'], $photoPath);

                    if ($field === 'foto_atas') $fotoAtas = $photoPath;
                    if ($field === 'foto_bawah') $fotoBawah = $photoPath;
                    if ($field === 'foto_kanan') $fotoKanan = $photoPath;
                    if ($field === 'foto_kiri') $fotoKiri = $photoPath;
                }
            }
        }

        if (isset($_FILES['photo_bon']) && $_FILES['photo_bon']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            $filename = $_FILES['photo_bon']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed) && $_FILES['photo_bon']['size'] <= MAX_FILE_SIZE) {
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0777, true);
                }
                $newFilename = uniqid() . '_bon.' . $ext;
                $photoBon = UPLOAD_DIR . $newFilename;
                move_uploaded_file($_FILES['photo_bon']['tmp_name'], $photoBon);
            }
        }

        $data = [
            'item_code' => $itemCode,
            'item_name' => $itemName,
            'brand' => $brand,
            'description' => $description,
            'category_id' => $categoryId,
            'location_id' => $locationId,
            'quantity' => $quantity,
            'unit' => $unit,
            'status' => $status,
            'purchase_date' => $purchaseDate,
            'purchase_price' => $purchasePrice,
            'supplier' => $supplier,
            'image_path' => $imagePath,
            'foto_atas' => $fotoAtas,
            'foto_bawah' => $fotoBawah,
            'foto_kanan' => $fotoKanan,
            'foto_kiri' => $fotoKiri,
            'photo_bon' => $photoBon,
            'created_by' => $user['id']
        ];

        try {
            $itemId = $db->insert('items', $data);
            $auth->logActivity('created', 'items', $itemId, null, $data);
            $_SESSION['success'] = 'Barang berhasil ditambahkan';
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, '23000') !== false || strpos($msg, '1062') !== false) {
                $_SESSION['error'] = "Kode barang <strong>'$itemCode'</strong> sudah digunakan. Silakan gunakan kode yang lain.";
            } else {
                $_SESSION['error'] = 'Gagal menambahkan barang: ' . $msg;
            }
        }

        header('Location: items.php');
        exit;
    }

    if ($action === 'edit' && $auth->hasPermission('items.manage')) {
        $itemId = intval($_POST['item_id']);
        $oldItem = $db->fetchOne("SELECT * FROM items WHERE id = ?", [$itemId]);

        $newItemCode = trim($_POST['item_code']);
        $existing = $db->fetchOne("SELECT id FROM items WHERE item_code = ? AND id != ?", [$newItemCode, $itemId]);
        if ($existing) {
            $_SESSION['error'] = "Kode barang <strong>'$newItemCode'</strong> sudah digunakan barang lain. Silakan gunakan kode yang lain.";
            header('Location: items.php');
            exit;
        }

        $data = [
            'item_code' => $newItemCode,
            'item_name' => trim($_POST['item_name']),
            'brand' => trim($_POST['brand']) ?: null,
            'description' => trim($_POST['description']),
            'category_id' => $_POST['category_id'] ?: null,
            'location_id' => $_POST['location_id'] ?: null,
            'quantity' => intval($_POST['quantity']),
            'unit' => trim($_POST['unit']),
            'status' => $_POST['status'],
            'purchase_date' => $_POST['purchase_date'] ?: null,
            'purchase_price' => $_POST['purchase_price'] ?: null,
            'supplier' => trim($_POST['supplier'])
        ];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed) && $_FILES['image']['size'] <= MAX_FILE_SIZE) {
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0777, true);
                }
                $newFilename = uniqid() . '.' . $ext;
                $imagePath = UPLOAD_DIR . $newFilename;
                move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
                $data['image_path'] = $imagePath;

                if ($oldItem['image_path'] && file_exists($oldItem['image_path'])) {
                    unlink($oldItem['image_path']);
                }
            }
        }

        $photoFields = ['foto_atas', 'foto_bawah', 'foto_kanan', 'foto_kiri'];
        foreach ($photoFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES[$field]['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (in_array($ext, $allowed) && $_FILES[$field]['size'] <= MAX_FILE_SIZE) {
                    if (!is_dir(UPLOAD_DIR)) {
                        mkdir(UPLOAD_DIR, 0777, true);
                    }
                    $newFilename = uniqid() . '_' . $field . '.' . $ext;
                    $photoPath = UPLOAD_DIR . $newFilename;
                    move_uploaded_file($_FILES[$field]['tmp_name'], $photoPath);
                    $data[$field] = $photoPath;

                    if ($oldItem[$field] && file_exists($oldItem[$field])) {
                        unlink($oldItem[$field]);
                    }
                }
            }
        }

        if (isset($_FILES['photo_bon']) && $_FILES['photo_bon']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            $filename = $_FILES['photo_bon']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed) && $_FILES['photo_bon']['size'] <= MAX_FILE_SIZE) {
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0777, true);
                }
                $newFilename = uniqid() . '_bon.' . $ext;
                $photoBon = UPLOAD_DIR . $newFilename;
                move_uploaded_file($_FILES['photo_bon']['tmp_name'], $photoBon);
                $data['photo_bon'] = $photoBon;

                if ($oldItem['photo_bon'] && file_exists($oldItem['photo_bon'])) {
                    unlink($oldItem['photo_bon']);
                }
            }
        }

        try {
            $db->update('items', $data, 'id = ?', [$itemId]);
            $auth->logActivity('updated', 'items', $itemId, $oldItem, $data);
            $_SESSION['success'] = 'Barang berhasil diperbarui';
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, '23000') !== false || strpos($msg, '1062') !== false) {
                $_SESSION['error'] = "Kode barang <strong>'$newItemCode'</strong> sudah digunakan barang lain.";
            } else {
                $_SESSION['error'] = 'Gagal memperbarui barang: ' . $msg;
            }
        }

        header('Location: items.php');
        exit;
    }

    if ($action === 'delete' && $auth->hasPermission('items.manage')) {
        $itemId = intval($_POST['item_id']);
        $oldItem = $db->fetchOne("SELECT * FROM items WHERE id = ?", [$itemId]);

        try {
            $db->delete('items', 'id = ?', [$itemId]);

            if ($oldItem['image_path'] && file_exists($oldItem['image_path'])) {
                unlink($oldItem['image_path']);
            }

            $photoFields = ['foto_atas', 'foto_bawah', 'foto_kanan', 'foto_kiri'];
            foreach ($photoFields as $field) {
                if ($oldItem[$field] && file_exists($oldItem[$field])) {
                    unlink($oldItem[$field]);
                }
            }

            $auth->logActivity('deleted', 'items', $itemId, $oldItem, null);
            $_SESSION['success'] = 'Barang berhasil dihapus';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menghapus barang: ' . $e->getMessage();
        }

        header('Location: items.php');
        exit;
    }

    if ($action === 'stock_out' && $auth->hasPermission('items.manage')) {
        $itemId = intval($_POST['item_id']);
        $quantity = intval($_POST['quantity']);
        $status = $_POST['status'];
        $notes = trim($_POST['notes']);

        $item = $db->fetchOne("SELECT * FROM items WHERE id = ?", [$itemId]);
        if (!$item) {
            $_SESSION['error'] = 'Barang tidak ditemukan';
            header('Location: finished_goods.php');
            exit;
        }

        if ($quantity < 1) {
            $_SESSION['error'] = 'Jumlah barang keluar minimal 1';
            header('Location: finished_goods.php');
            exit;
        }

        if ($quantity > $item['quantity']) {
            $_SESSION['error'] = 'Jumlah barang keluar melebihi stok yang tersedia';
            header('Location: finished_goods.php');
            exit;
        }

        $validStatuses = ['available', 'in_use', 'maintenance', 'broken', 'lost'];
        if (!in_array($status, $validStatuses)) {
            $_SESSION['error'] = 'Status tidak valid';
            header('Location: finished_goods.php');
            exit;
        }

        if ($status === 'broken' && (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK)) {
            echo json_encode(['success' => false, 'message' => 'Foto wajib diunggah jika status barang adalah Rusak.']);
            exit;
        }

        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed) && $_FILES['photo']['size'] <= MAX_FILE_SIZE) {
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0777, true);
                }
                $newFilename = uniqid() . '_stockout.' . $ext;
                $photoPath = UPLOAD_DIR . $newFilename;
                move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
            }
        }

        if ($status === 'broken' && !$photoPath) {
            echo json_encode(['success' => false, 'message' => 'Foto barang rusak harus berupa JPG, JPEG, PNG, atau GIF dan maksimal 5 MB.']);
            exit;
        }

        $connection = $db->getConnection();

        try {
            $connection->beginTransaction();

            $oldQuantity = $item['quantity'];
            $newQuantity = $oldQuantity - $quantity;

            $db->update('items', ['quantity' => $newQuantity, 'status' => $status], 'id = ?', [$itemId]);

            $stockOutData = [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'status' => $status,
                'notes' => $notes,
                'photo' => $photoPath,
                'created_by' => $user['id']
            ];
            $db->insert('stock_out', $stockOutData);

            $auth->logActivity(
                'stock_out',
                'items',
                $itemId,
                ['quantity' => $oldQuantity, 'status' => $item['status']],
                ['quantity' => $newQuantity, 'status' => $status]
            );

            $connection->commit();
            $_SESSION['success'] = 'Barang keluar berhasil diproses';
            echo json_encode([
                'success' => true,
                'message' => 'Barang keluar berhasil diproses. Status barang telah diperbarui.'
            ]);
            exit;
        } catch (Exception $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            $_SESSION['error'] = 'Gagal memproses barang keluar: ' . $e->getMessage();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        header('Location: finished_goods.php');
        exit;
    }
}

$pageTitle = 'Manajemen Barang';
require_once 'includes/header.php';

$auth->requirePermission('items.view');

$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * ITEMS_PER_PAGE;

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(i.item_code LIKE ? OR i.item_name LIKE ? OR i.description LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($categoryFilter) {
    $where[] = "i.category_id = ?";
    $params[] = $categoryFilter;
}

if ($statusFilter) {
    $where[] = "i.status = ?";
    $params[] = $statusFilter;
}

$whereClause = implode(' AND ', $where);

$items = $db->fetchAll("
    SELECT i.*, c.category_name, l.location_name, l.location_code 
    FROM items i 
    LEFT JOIN categories c ON i.category_id = c.id 
    LEFT JOIN locations l ON i.location_id = l.id 
    WHERE $whereClause
    ORDER BY i.created_at DESC 
    LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset
", $params);

$totalItems = $db->fetchOne("
    SELECT COUNT(*) as count
    FROM items i
    WHERE $whereClause
", $params)['count'];

$totalQuantity = $db->fetchOne("
    SELECT COALESCE(SUM(quantity), 0) as total
    FROM items i
    WHERE $whereClause
", $params)['total'];

$totalValue = $db->fetchOne("
    SELECT COALESCE(SUM(quantity * purchase_price), 0) as total
    FROM items i
    WHERE $whereClause
", $params)['total'];

$totalPages = ceil($totalItems / ITEMS_PER_PAGE);

$categories = $db->fetchAll("SELECT * FROM categories ORDER BY category_name");
$locations = $db->fetchAll("SELECT * FROM locations ORDER BY location_name");

$editItem = null;
if (isset($_GET['edit'])) {
    $editItem = $db->fetchOne("SELECT * FROM items WHERE id = ?", [intval($_GET['edit'])]);
}
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Manajemen Barang</h2>
        <p class="text-gray-600">Kelola inventaris barang dan aset</p>
    </div>
    <?php if ($auth->hasPermission('items.manage')): ?>
        <button onclick="openModal('addModal')" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-2 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition">
            <i class="fas fa-plus mr-2"></i>
            Tambah Barang
        </button>
    <?php endif; ?>
</div>

<!-- Summary Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-sm">Total Jenis Barang</p>
                <p class="text-3xl font-bold"><?php echo number_format($totalItems); ?></p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-box text-2xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm">Total Quantity</p>
                <p class="text-3xl font-bold"><?php echo number_format($totalQuantity); ?></p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-cubes text-2xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-gradient-to-r from-orange-500 to-red-500 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-orange-100 text-sm">Total Nilai Barang</p>
                <p class="text-3xl font-bold">Rp <?php echo number_format($totalValue, 0, ',', '.'); ?></p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-money-bill-wave text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form method="GET" action="" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input type="text" name="search" placeholder="Cari barang..." 
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
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <option value="">Semua Status</option>
                <option value="available" <?php echo $statusFilter == 'available' ? 'selected' : ''; ?>>Tersedia</option>
                <option value="in_use" <?php echo $statusFilter == 'in_use' ? 'selected' : ''; ?>>Sedang Digunakan</option>
                <option value="maintenance" <?php echo $statusFilter == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                <option value="broken" <?php echo $statusFilter == 'broken' ? 'selected' : ''; ?>>Rusak</option>
                <option value="lost" <?php echo $statusFilter == 'lost' ? 'selected' : ''; ?>>Hilang</option>
            </select>
        </div>
        <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
            <i class="fas fa-search mr-2"></i>
            Cari
        </button>
        <?php if ($search || $categoryFilter || $statusFilter): ?>
            <a href="items.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-times mr-2"></i>
                Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Items Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Merk</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Beli Satuan</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto Bon</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($items) > 0): ?>
                    <?php foreach ($items as $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($item['item_code']); ?>
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
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars(substr($item['description'], 0, 50)); ?>...</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($item['brand'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($item['category_name'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="<?php echo $item['quantity'] < 5 ? 'text-red-600 font-bold' : ''; ?>">
                                    <?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $item['purchase_price'] ? 'Rp ' . number_format($item['purchase_price'], 0, ',', '.') : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if ($item['location_name']): ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                        <?php echo htmlspecialchars($item['location_code']); ?>
                                    </span>
                                    <span class="ml-1"><?php echo htmlspecialchars($item['location_name']); ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if ($item['photo_bon']): ?>
                                    <?php $ext = strtolower(pathinfo($item['photo_bon'], PATHINFO_EXTENSION)); ?>
                                    <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                        <a href="<?php echo htmlspecialchars($item['photo_bon']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800" title="Lihat Foto Bon">
                                            <i class="fas fa-image"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo htmlspecialchars($item['photo_bon']); ?>" target="_blank" class="text-red-600 hover:text-red-800" title="Lihat Dokumen">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex space-x-2">
                                    <a href="?view=<?php echo $item['id']; ?>" class="text-blue-600 hover:text-blue-800" title="Lihat">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($auth->hasPermission('items.manage')): ?>
                                        <a href="?edit=<?php echo $item['id']; ?>" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $item['id']; ?>)" class="text-red-600 hover:text-red-800" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-box-open text-4xl mb-4"></i>
                            <p>Tidak ada barang ditemukan</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 border-t flex items-center justify-between">
            <p class="text-sm text-gray-500">
                Menampilkan <?php echo ($offset + 1); ?> - <?php echo min($offset + ITEMS_PER_PAGE, $totalItems); ?> dari <?php echo $totalItems; ?> barang
            </p>
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>&status=<?php echo $statusFilter; ?>" 
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>&status=<?php echo $statusFilter; ?>" 
                        class="px-4 py-2 border rounded-lg <?php echo $i == $page ? 'bg-purple-600 text-white' : 'hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>&status=<?php echo $statusFilter; ?>" 
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<?php if ($auth->hasPermission('items.manage')): ?>
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Tambah Barang Baru</h3>
            <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kode Barang *</label>
                    <input type="text" name="item_code" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Barang *</label>
                    <input type="text" name="item_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Merk</label>
                    <input type="text" name="brand" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="Contoh: Kopi">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                    <select name="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah *</label>
                    <input type="number" name="quantity" required min="0" value="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Satuan</label>
                    <input type="text" name="unit" value="pcs" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lokasi</label>
                    <select name="location_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">Pilih Lokasi</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo $loc['id']; ?>"><?php echo htmlspecialchars($loc['location_code'] . ' - ' . $loc['location_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="available">Tersedia</option>
                        <option value="in_use">Sedang Digunakan</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="broken">Rusak</option>
                        <option value="lost">Hilang</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Pembelian</label>
                    <input type="date" name="purchase_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Harga Pembelian</label>
                    <input type="number" name="purchase_price" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                    <input type="text" name="supplier" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Gambar Utama</label>
                <input type="file" name="image" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                <p class="text-xs text-gray-500 mt-1">Maksimal: 5MB (JPG, PNG, GIF)</p>
            </div>
            <div class="border-t pt-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Foto Fisik Barang</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto Atas</label>
                        <input type="file" name="foto_atas" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto Bawah</label>
                        <input type="file" name="foto_bawah" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto Kanan</label>
                        <input type="file" name="foto_kanan" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto Kiri</label>
                        <input type="file" name="foto_kiri" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
            </div>
            <div class="border-t pt-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Dokumen Pembelian</h4>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Foto Bon / Bukti Pembayaran</label>
                    <input type="file" name="photo_bon" accept="image/*,application/pdf" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">Maksimal: 5MB (JPG, PNG, GIF, PDF)</p>
                </div>
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
<?php if ($editItem && $auth->hasPermission('items.manage')): ?>
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Edit Barang</h3>
            <button onclick="closeModal('editModal'); window.location.href='items.php';" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="item_id" value="<?php echo $editItem['id']; ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kode Barang *</label>
                    <input type="text" name="item_code" required value="<?php echo htmlspecialchars($editItem['item_code']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Barang *</label>
                    <input type="text" name="item_name" required value="<?php echo htmlspecialchars($editItem['item_name']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Merk</label>
                    <input type="text" name="brand" value="<?php echo htmlspecialchars($editItem['brand'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="Contoh: Kopi">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"><?php echo htmlspecialchars($editItem['description']); ?></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                    <select name="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $editItem['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah *</label>
                    <input type="number" name="quantity" required min="0" value="<?php echo $editItem['quantity']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Satuan</label>
                    <input type="text" name="unit" value="<?php echo htmlspecialchars($editItem['unit']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lokasi</label>
                    <select name="location_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">Pilih Lokasi</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo $loc['id']; ?>" <?php echo $editItem['location_id'] == $loc['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc['location_code'] . ' - ' . $loc['location_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="available" <?php echo $editItem['status'] == 'available' ? 'selected' : ''; ?>>Tersedia</option>
                        <option value="in_use" <?php echo $editItem['status'] == 'in_use' ? 'selected' : ''; ?>>Sedang Digunakan</option>
                        <option value="maintenance" <?php echo $editItem['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="broken" <?php echo $editItem['status'] == 'broken' ? 'selected' : ''; ?>>Rusak</option>
                        <option value="lost" <?php echo $editItem['status'] == 'lost' ? 'selected' : ''; ?>>Hilang</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Pembelian</label>
                    <input type="date" name="purchase_date" value="<?php echo $editItem['purchase_date']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Harga Pembelian</label>
                    <input type="number" name="purchase_price" step="0.01" min="0" value="<?php echo $editItem['purchase_price']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                    <input type="text" name="supplier" value="<?php echo htmlspecialchars($editItem['supplier']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Gambar Utama</label>
                <?php if ($editItem['image_path']): ?>
                    <img src="<?php echo htmlspecialchars($editItem['image_path']); ?>" alt="" class="w-32 h-32 object-cover rounded-lg mb-2">
                <?php endif; ?>
                <input type="file" name="image" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                <p class="text-xs text-gray-500 mt-1">Maksimal: 5MB (JPG, PNG, GIF)</p>
            </div>
            <div class="border-t pt-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Foto Fisik Barang</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto Atas</label>
                        <?php if ($editItem['foto_atas'] ?? null): ?>
                            <img src="<?php echo htmlspecialchars($editItem['foto_atas']); ?>" alt="" class="w-20 h-20 object-cover rounded-lg mb-2">
                        <?php endif; ?>
                        <input type="file" name="foto_atas" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto Bawah</label>
                        <?php if ($editItem['foto_bawah'] ?? null): ?>
                            <img src="<?php echo htmlspecialchars($editItem['foto_bawah']); ?>" alt="" class="w-20 h-20 object-cover rounded-lg mb-2">
                        <?php endif; ?>
                        <input type="file" name="foto_bawah" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto Kanan</label>
                        <?php if ($editItem['foto_kanan'] ?? null): ?>
                            <img src="<?php echo htmlspecialchars($editItem['foto_kanan']); ?>" alt="" class="w-20 h-20 object-cover rounded-lg mb-2">
                        <?php endif; ?>
                        <input type="file" name="foto_kanan" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto Kiri</label>
                        <?php if ($editItem['foto_kiri'] ?? null): ?>
                            <img src="<?php echo htmlspecialchars($editItem['foto_kiri']); ?>" alt="" class="w-20 h-20 object-cover rounded-lg mb-2">
                        <?php endif; ?>
                        <input type="file" name="foto_kiri" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
            </div>
            <div class="border-t pt-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Dokumen Pembelian</h4>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Foto Bon / Bukti Pembayaran</label>
                    <?php if ($editItem['photo_bon'] ?? null): ?>
                        <?php $ext = strtolower(pathinfo($editItem['photo_bon'], PATHINFO_EXTENSION)); ?>
                        <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                            <img src="<?php echo htmlspecialchars($editItem['photo_bon']); ?>" alt="" class="w-32 h-32 object-cover rounded-lg mb-2">
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($editItem['photo_bon']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                                <i class="fas fa-file-pdf mr-1"></i>Lihat Dokumen
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <input type="file" name="photo_bon" accept="image/*,application/pdf" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">Maksimal: 5MB (JPG, PNG, GIF, PDF)</p>
                </div>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal('editModal'); window.location.href='items.php';" class="px-6 py-2 border rounded-lg hover:bg-gray-50">Batal</button>
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
                <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus barang ini? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <form method="POST" action="" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="item_id" id="deleteItemId">
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
    
    function confirmDelete(itemId) {
        document.getElementById('deleteItemId').value = itemId;
        openModal('deleteModal');
    }
</script>

<?php require_once 'includes/footer.php'; ?>
