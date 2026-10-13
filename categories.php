<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$auth->requireLogin();
$auth->requirePermission('categories.view', 'categories');
$user = $auth->getCurrentUser();

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && $auth->hasPermission('categories.manage')) {
        $categoryName = trim($_POST['category_name']);
        $description = trim($_POST['description']);

        $data = [
            'category_name' => $categoryName,
            'description' => $description
        ];

        try {
            $categoryId = $db->insert('categories', $data);
            $auth->logActivity('created', 'categories', $categoryId, null, $data);
            $_SESSION['success'] = 'Kategori berhasil ditambahkan';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menambahkan kategori: ' . $e->getMessage();
        }

        header('Location: categories.php');
        exit;
    }

    if ($action === 'edit' && $auth->hasPermission('categories.manage')) {
        $categoryId = intval($_POST['category_id']);
        $oldCategory = $db->fetchOne("SELECT * FROM categories WHERE id = ?", [$categoryId]);

        $data = [
            'category_name' => trim($_POST['category_name']),
            'description' => trim($_POST['description'])
        ];

        try {
            $db->update('categories', $data, 'id = ?', [$categoryId]);
            $auth->logActivity('updated', 'categories', $categoryId, $oldCategory, $data);
            $_SESSION['success'] = 'Kategori berhasil diperbarui';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal memperbarui kategori: ' . $e->getMessage();
        }

        header('Location: categories.php');
        exit;
    }

    if ($action === 'delete' && $auth->hasPermission('categories.manage')) {
        $categoryId = intval($_POST['category_id']);
        $oldCategory = $db->fetchOne("SELECT * FROM categories WHERE id = ?", [$categoryId]);

        $itemCount = $db->fetchOne("SELECT COUNT(*) as count FROM items WHERE category_id = ?", [$categoryId])['count'];

        if ($itemCount > 0) {
            $_SESSION['error'] = 'Tidak dapat menghapus kategori yang masih memiliki barang';
        } else {
            try {
                $db->delete('categories', 'id = ?', [$categoryId]);
                $auth->logActivity('deleted', 'categories', $categoryId, $oldCategory, null);
                $_SESSION['success'] = 'Kategori berhasil dihapus';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Gagal menghapus kategori: ' . $e->getMessage();
            }
        }

        header('Location: categories.php');
        exit;
    }
}

$pageTitle = 'Manajemen Kategori';
require_once 'includes/header.php';

$auth->requirePermission('categories.view', 'categories');

$search = $_GET['search'] ?? '';

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(category_name LIKE ? OR description LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam]);
}

$whereClause = implode(' AND ', $where);

$categories = $db->fetchAll("
    SELECT c.*, COUNT(i.id) as item_count 
    FROM categories c 
    LEFT JOIN items i ON c.id = i.category_id 
    WHERE $whereClause
    GROUP BY c.id 
    ORDER BY c.category_name ASC
", $params);

$editCategory = null;
if (isset($_GET['edit'])) {
    $editCategory = $db->fetchOne("SELECT * FROM categories WHERE id = ?", [intval($_GET['edit'])]);
}
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Manajemen Kategori</h2>
        <p class="text-gray-600">Kelola kategori barang</p>
    </div>
    <?php if ($auth->hasPermission('categories.manage')): ?>
        <button onclick="openModal('addModal')" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-2 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition">
            <i class="fas fa-plus mr-2"></i>
            Tambah Kategori
        </button>
    <?php endif; ?>
</div>

<!-- Search -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form method="GET" action="" class="flex gap-4">
        <div class="flex-1">
            <input type="text" name="search" placeholder="Cari kategori..." 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
            <i class="fas fa-search mr-2"></i>
            Cari
        </button>
        <?php if ($search): ?>
            <a href="categories.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-times mr-2"></i>
                Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Categories Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (count($categories) > 0): ?>
        <?php foreach ($categories as $category): ?>
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-tags text-white text-xl"></i>
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($auth->hasPermission('categories.manage')): ?>
                            <a href="?edit=<?php echo $category['id']; ?>" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="confirmDelete(<?php echo $category['id']; ?>)" class="text-red-600 hover:text-red-800" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($category['category_name']); ?></h3>
                <p class="text-gray-500 text-sm mb-4"><?php echo htmlspecialchars(substr($category['description'], 0, 100)); ?>...</p>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">
                        <i class="fas fa-box mr-1"></i>
                        <?php echo $category['item_count']; ?> barang
                    </span>
                    <span class="text-gray-400">
                        <i class="fas fa-calendar mr-1"></i>
                        <?php echo date('d M Y', strtotime($category['created_at'])); ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full bg-white rounded-xl shadow-sm p-12 text-center">
            <i class="fas fa-tags text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">Tidak ada kategori ditemukan</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<?php if ($auth->hasPermission('categories.manage')): ?>
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Tambah Kategori Baru</h3>
            <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kategori *</label>
                <input type="text" name="category_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
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
<?php if ($editCategory && $auth->hasPermission('categories.manage')): ?>
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Edit Kategori</h3>
            <button onclick="closeModal('editModal'); window.location.href='categories.php';" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6 space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="category_id" value="<?php echo $editCategory['id']; ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kategori *</label>
                <input type="text" name="category_name" required value="<?php echo htmlspecialchars($editCategory['category_name']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"><?php echo htmlspecialchars($editCategory['description']); ?></textarea>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal('editModal'); window.location.href='categories.php';" class="px-6 py-2 border rounded-lg hover:bg-gray-50">Batal</button>
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
                <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus kategori ini? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <form method="POST" action="" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="category_id" id="deleteCategoryId">
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
    
    function confirmDelete(categoryId) {
        document.getElementById('deleteCategoryId').value = categoryId;
        openModal('deleteModal');
    }
</script>

<?php require_once 'includes/footer.php'; ?>
