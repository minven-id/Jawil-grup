<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$auth->requireLogin();
$user = $auth->getCurrentUser();

$db = Database::getInstance();

function uploadProfilePhoto($fieldName = 'profile_photo') {
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) {
        throw new Exception('Foto profil harus berupa gambar maksimal 2 MB.');
    }

    $imageInfo = getimagesize($file['tmp_name']);
    $allowedTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp'
    ];

    if (!$imageInfo || !isset($allowedTypes[$imageInfo[2]])) {
        throw new Exception('Format foto profil harus JPG, PNG, atau WEBP.');
    }

    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new Exception('Folder upload foto profil tidak dapat dibuat.');
    }

    $fileName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$imageInfo[2]];
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $fileName)) {
        throw new Exception('Foto profil gagal disimpan.');
    }

    return $fileName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && $auth->hasPermission('users.manage')) {
        $employeeId = trim($_POST['employee_id'] ?? '');
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $fullName = trim($_POST['full_name']);
        $roleId = intval($_POST['role_id']);

        // Validate employee_id first
        if ($employeeId === '') {
            $_SESSION['error'] = 'No. Pegawai wajib diisi';
            header('Location: users.php');
            exit;
        }

        try {
            $profilePhoto = uploadProfilePhoto();
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: users.php');
            exit;
        }

        $existing = $db->fetchOne("SELECT id FROM users WHERE employee_id = ? OR username = ? OR email = ?", [$employeeId, $username, $email]);

        if ($existing) {
            $_SESSION['error'] = 'No. Pegawai, username, atau email sudah digunakan';
            header('Location: users.php');
            exit;
        }

        $data = [
            'employee_id' => $employeeId,
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $fullName,
            'profile_photo' => $profilePhoto,
            'role_id' => $roleId,
            'is_active' => 1
        ];

        try {
            $userId = $db->insert('users', $data);
            $auth->logActivity('created', 'users', $userId, null, $data);
            $_SESSION['success'] = 'Pengguna berhasil ditambahkan';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menambahkan pengguna: ' . $e->getMessage();
        }

        header('Location: users.php');
        exit;
    }

    if ($action === 'edit' && $auth->hasPermission('users.manage')) {
        $userId = intval($_POST['user_id']);
        $oldUser = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

        $newEmployeeId = trim($_POST['employee_id'] ?? '');
        $newUsername = trim($_POST['username']);
        $newEmail = trim($_POST['email']);
        
        // Validate employee_id first
        if ($newEmployeeId === '') {
            $_SESSION['error'] = 'No. Pegawai wajib diisi.';
            header('Location: users.php?edit=' . $userId);
            exit;
        }
        
        // Check for existing records
        $existing = $db->fetchOne("SELECT id FROM users WHERE (employee_id = ? OR username = ? OR email = ?) AND id != ?", [$newEmployeeId, $newUsername, $newEmail, $userId]);
        if ($existing) {
            $_SESSION['error'] = "No. Pegawai, username, atau email sudah digunakan akun lain.";
            header('Location: users.php');
            exit;
        }

        try {
            $profilePhoto = uploadProfilePhoto();
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: users.php?edit=' . $userId);
            exit;
        }

        $data = [
            'employee_id' => $newEmployeeId,
            'username' => $newUsername,
            'email' => $newEmail,
            'full_name' => trim($_POST['full_name']),
            'role_id' => intval($_POST['role_id']),
            'is_active' => intval($_POST['is_active'])
        ];

        if ($profilePhoto) {
            $data['profile_photo'] = $profilePhoto;
        }

        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        try {
            $db->update('users', $data, 'id = ?', [$userId]);
            if ($userId === (int)$user['id'] && isset($data['profile_photo'])) {
                $_SESSION['profile_photo'] = $data['profile_photo'];
            }
            $auth->logActivity('updated', 'users', $userId, $oldUser, $data);
            $_SESSION['success'] = 'Pengguna berhasil diperbarui';
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, '23000') !== false || strpos($msg, '1062') !== false) {
                $_SESSION['error'] = "No. Pegawai, username, atau email sudah digunakan akun lain.";
            } else {
                $_SESSION['error'] = 'Gagal memperbarui pengguna: ' . $msg;
            }
        }

        header('Location: users.php');
        exit;
    }

    if ($action === 'delete' && $auth->hasPermission('users.manage')) {
        $userId = intval($_POST['user_id']);

        if ($userId == $user['id']) {
            $_SESSION['error'] = 'Tidak dapat menghapus akun sendiri';
        } else {
            $oldUser = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

            try {
                $db->delete('users', 'id = ?', [$userId]);
                $auth->logActivity('deleted', 'users', $userId, $oldUser, null);
                $_SESSION['success'] = 'Pengguna berhasil dihapus';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Gagal menghapus pengguna: ' . $e->getMessage();
            }
        }

        header('Location: users.php');
        exit;
    }
}

$pageTitle = 'Manajemen Pengguna';
require_once 'includes/header.php';

$auth->requirePermission('users.manage');

$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(u.employee_id LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($roleFilter) {
    $where[] = "u.role_id = ?";
    $params[] = $roleFilter;
}

$whereClause = implode(' AND ', $where);

$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * ITEMS_PER_PAGE;

$users = $db->fetchAll("
    SELECT u.*, r.role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE $whereClause
    ORDER BY u.created_at DESC 
    LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset
", $params);

$totalUsers = $db->fetchOne("
    SELECT COUNT(*) as count 
    FROM users u 
    WHERE $whereClause
", $params)['count'];

$totalPages = ceil($totalUsers / ITEMS_PER_PAGE);

$roles = $db->fetchAll("SELECT * FROM roles ORDER BY role_name");

$editUser = null;
if (isset($_GET['edit'])) {
    $editUser = $db->fetchOne("SELECT * FROM users WHERE id = ?", [intval($_GET['edit'])]);
}
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Manajemen Pengguna</h2>
        <p class="text-gray-600">Kelola pengguna dan hak akses</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="account_requests.php" class="rounded-lg border border-purple-200 bg-purple-50 px-5 py-2 font-semibold text-purple-700 transition hover:bg-purple-100">
            <i class="fas fa-user-clock mr-2"></i>Permintaan Akun
        </a>
        <a href="password_requests.php" class="rounded-lg border border-purple-200 bg-purple-50 px-5 py-2 font-semibold text-purple-700 transition hover:bg-purple-100">
            <i class="fas fa-key mr-2"></i>Reset Password
        </a>
        <button onclick="openModal('addModal')" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-2 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition">
            <i class="fas fa-plus mr-2"></i>
            Tambah Pengguna
        </button>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form method="GET" action="" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input type="text" name="search" placeholder="Cari pengguna..." 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div>
            <select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <option value="">Semua Role</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['id']; ?>" <?php echo $roleFilter == $role['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($role['role_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
            <i class="fas fa-search mr-2"></i>
            Cari
        </button>
        <?php if ($search || $roleFilter): ?>
            <a href="users.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-times mr-2"></i>
                Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Users Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Pegawai / Kode Pegawai</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php if (!empty($u['profile_photo'])): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($u['profile_photo']); ?>" alt="Foto profil" class="w-10 h-10 rounded-full object-cover border border-gray-200 mr-3">
                                    <?php else: ?>
                                        <div class="w-10 h-10 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold mr-3">
                                            <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($u['full_name']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700">
                                <?php echo htmlspecialchars($u['employee_id'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($u['username']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($u['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-medium">
                                    <?php echo htmlspecialchars($u['role_name']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($u['is_active']): ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Aktif</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex space-x-2">
                                    <a href="?edit=<?php echo $u['id']; ?>" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($u['id'] != $user['id']): ?>
                                        <button onclick="confirmDelete(<?php echo $u['id']; ?>)" class="text-red-600 hover:text-red-800" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-users text-4xl mb-4"></i>
                            <p>Tidak ada pengguna ditemukan</p>
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
                Menampilkan <?php echo ($offset + 1); ?> - <?php echo min($offset + ITEMS_PER_PAGE, $totalUsers); ?> dari <?php echo $totalUsers; ?> pengguna
            </p>
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $roleFilter; ?>" 
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $roleFilter; ?>" 
                        class="px-4 py-2 border rounded-lg <?php echo $i == $page ? 'bg-purple-600 text-white' : 'hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $roleFilter; ?>" 
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Tambah Pengguna Baru</h3>
            <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                <input type="text" name="full_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">No. Pegawai / No. ID *</label>
                <input type="text" name="employee_id" required maxlength="50" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="Contoh: PGW-001">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                <input type="text" name="username" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                <input type="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                <input type="password" name="password" required minlength="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Foto Profil</label>
                <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                <p class="text-xs text-gray-500 mt-1">JPG, PNG, atau WEBP maksimal 2 MB.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                <select name="role_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal('addModal')" class="px-6 py-2 border rounded-lg hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<?php if ($editUser): ?>
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Edit Pengguna</h3>
            <button onclick="closeModal('editModal'); window.location.href='users.php';" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                <input type="text" name="full_name" required value="<?php echo htmlspecialchars($editUser['full_name']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">No. Pegawai / No. ID *</label>
                <input type="text" name="employee_id" required maxlength="50" value="<?php echo htmlspecialchars($editUser['employee_id'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                <input type="text" name="username" required value="<?php echo htmlspecialchars($editUser['username']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                <input type="email" name="email" required value="<?php echo htmlspecialchars($editUser['email']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password (biarkan kosong jika tidak ingin mengubah)</label>
                <input type="password" name="password" minlength="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Foto Profil</label>
                <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengganti foto. Maksimal 2 MB.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                <select name="role_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>" <?php echo $editUser['role_id'] == $role['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($role['role_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="is_active" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="1" <?php echo $editUser['is_active'] ? 'selected' : ''; ?>>Aktif</option>
                    <option value="0" <?php echo !$editUser['is_active'] ? 'selected' : ''; ?>>Nonaktif</option>
                </select>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal('editModal'); window.location.href='users.php';" class="px-6 py-2 border rounded-lg hover:bg-gray-50">Batal</button>
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
                <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus pengguna ini? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <form method="POST" action="" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="deleteUserId">
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
    
    function confirmDelete(userId) {
        document.getElementById('deleteUserId').value = userId;
        openModal('deleteModal');
    }
</script>

<?php require_once 'includes/footer.php'; ?>
