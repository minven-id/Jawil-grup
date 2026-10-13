<?php
$pageTitle = 'Manajemen Role';
require_once 'config.php';
require_once 'includes/auth.php';

$auth->requireLogin();
$auth->requirePermission('roles.manage');

$db = Database::getInstance();

// Available permissions
$availablePermissions = [
    'users.manage' => 'Kelola Pengguna',
    'items.manage' => 'Kelola Barang',
    'items.view' => 'Lihat Barang',
    'items.edit' => 'Edit Barang',
    'categories.manage' => 'Kelola Kategori',
    'categories.view' => 'Lihat Kategori',
    'categories.edit' => 'Edit Kategori',
    'locations.manage' => 'Kelola Lokasi',
    'locations.view' => 'Lihat Lokasi',
    'roles.manage' => 'Kelola Role',
    'logs.view' => 'Lihat Log Aktivitas',
    'settings.manage' => 'Pengaturan Sistem',
    'finance.view' => 'Lihat Finance',
    'finance.manage' => 'Kelola Finance',
    'documents.view' => 'Lihat Dokumen',
    'documents.manage' => 'Kelola Dokumen'
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $roleName = trim($_POST['role_name']);
        $description = trim($_POST['description']);

        // Get selected permissions
        $selectedPermissions = $_POST['permissions'] ?? [];
        $permissionsJson = json_encode($selectedPermissions);

        // Get selected menus
        $selectedMenus = $_POST['menus'] ?? [];

        // Validate that at least one menu is selected
        if (empty($selectedMenus)) {
            $_SESSION['error'] = 'Role harus memiliki minimal satu akses menu';
            header('Location: roles.php');
            exit;
        }

        $data = [
            'role_name' => $roleName,
            'description' => $description,
            'permissions' => $permissionsJson
        ];

        try {
            $roleId = $db->insert('roles', $data);

            // Insert menu access with error handling
            try {
                foreach ($selectedMenus as $menuId) {
                    $db->insert('role_menu_access', [
                        'role_id' => $roleId,
                        'menu_id' => intval($menuId),
                        'can_view' => 1
                    ]);
                }
            } catch (Exception $e) {
                // Table might not exist yet, continue silently
            }

            $auth->logActivity('created', 'roles', $roleId, null, $data);
            $_SESSION['success'] = 'Role berhasil ditambahkan';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menambahkan role: ' . $e->getMessage();
        }

        header('Location: roles.php');
        exit;
    }
    
    if ($action === 'edit') {
        $roleId = intval($_POST['role_id']);
        $oldRole = $db->fetchOne("SELECT * FROM roles WHERE id = ?", [$roleId]);

        $roleName = trim($_POST['role_name']);
        $description = trim($_POST['description']);

        // Get selected permissions
        $selectedPermissions = $_POST['permissions'] ?? [];
        $permissionsJson = json_encode($selectedPermissions);

        // Get selected menus
        $selectedMenus = $_POST['menus'] ?? [];

        // Validate that at least one menu is selected
        if (empty($selectedMenus)) {
            $_SESSION['error'] = 'Role harus memiliki minimal satu akses menu';
            header('Location: roles.php?edit=' . $roleId);
            exit;
        }

        $data = [
            'role_name' => $roleName,
            'description' => $description,
            'permissions' => $permissionsJson
        ];

        try {
            $db->update('roles', $data, 'id = ?', [$roleId]);

            // Delete old menu access with error handling
            try {
                $db->delete('role_menu_access', 'role_id = ?', [$roleId]);
            } catch (Exception $e) {
                // Table might not exist yet, continue
            }

            // Insert new menu access with error handling
            try {
                foreach ($selectedMenus as $menuId) {
                    $db->insert('role_menu_access', [
                        'role_id' => $roleId,
                        'menu_id' => intval($menuId),
                        'can_view' => 1
                    ]);
                }
            } catch (Exception $e) {
                // Table might not exist yet, continue silently
            }

            $auth->logActivity('updated', 'roles', $roleId, $oldRole, $data);
            $_SESSION['success'] = 'Role berhasil diperbarui';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal memperbarui role: ' . $e->getMessage();
        }

        header('Location: roles.php');
        exit;
    }
    
    if ($action === 'delete') {
        $roleId = intval($_POST['role_id']);
        $oldRole = $db->fetchOne("SELECT * FROM roles WHERE id = ?", [$roleId]);
        
        // Check if role has users
        $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role_id = ?", [$roleId])['count'];
        
        if ($userCount > 0) {
            $_SESSION['error'] = 'Tidak dapat menghapus role yang masih memiliki pengguna';
        } else {
            try {
                $db->delete('roles', 'id = ?', [$roleId]);
                $auth->logActivity('deleted', 'roles', $roleId, $oldRole, null);
                $_SESSION['success'] = 'Role berhasil dihapus';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Gagal menghapus role: ' . $e->getMessage();
            }
        }
        
        header('Location: roles.php');
        exit;
    }
}

// Include header after all redirects
require_once 'includes/header.php';

// Get all roles
$roles = $db->fetchAll("SELECT r.*, COUNT(u.id) as user_count FROM roles r LEFT JOIN users u ON r.id = u.role_id GROUP BY r.id ORDER BY r.id");

// Get all menus with fallback
try {
    $menus = $db->fetchAll("SELECT * FROM menus WHERE is_active = 1 ORDER BY menu_order");
} catch (Exception $e) {
    // Fallback to default menus if table doesn't exist
    $menus = [
        ['id' => 1, 'menu_name' => 'dashboard', 'menu_label' => 'Dashboard', 'menu_icon' => 'fa-home', 'menu_url' => 'dashboard.php'],
        ['id' => 2, 'menu_name' => 'items', 'menu_label' => 'Barang', 'menu_icon' => 'fa-box', 'menu_url' => 'items.php'],
        ['id' => 3, 'menu_name' => 'categories', 'menu_label' => 'Kategori', 'menu_icon' => 'fa-tags', 'menu_url' => 'categories.php'],
        ['id' => 4, 'menu_name' => 'locations', 'menu_label' => 'Lokasi', 'menu_icon' => 'fa-map-marker-alt', 'menu_url' => 'locations.php'],
        ['id' => 5, 'menu_name' => 'roles', 'menu_label' => 'Role', 'menu_icon' => 'fa-user-shield', 'menu_url' => 'roles.php'],
        ['id' => 6, 'menu_name' => 'users', 'menu_label' => 'Pengguna', 'menu_icon' => 'fa-users', 'menu_url' => 'users.php'],
        ['id' => 7, 'menu_name' => 'logs', 'menu_label' => 'Aktivitas', 'menu_icon' => 'fa-history', 'menu_url' => 'logs.php']
    ];
}

// Get role for edit
$editRole = null;
if (isset($_GET['edit'])) {
    $editRole = $db->fetchOne("SELECT * FROM roles WHERE id = ?", [intval($_GET['edit'])]);
    if ($editRole) {
        $editRole['permissions_array'] = json_decode($editRole['permissions'], true) ?? [];
        // Get menu access for this role with fallback
        try {
            $menuAccess = $db->fetchAll("SELECT menu_id FROM role_menu_access WHERE role_id = ? AND can_view = 1", [$editRole['id']]);
            $editRole['menu_access'] = array_column($menuAccess, 'menu_id');
        } catch (Exception $e) {
            $editRole['menu_access'] = [];
        }
    }
}
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Manajemen Role</h2>
        <p class="text-gray-600">Kelola role dan hak akses pengguna</p>
    </div>
    <button onclick="openModal('addModal')" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-2 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition">
        <i class="fas fa-plus mr-2"></i>
        Tambah Role
    </button>
</div>

<!-- Roles Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permissions</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah User</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($roles) > 0): ?>
                    <?php foreach ($roles as $role): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg flex items-center justify-center text-white font-bold mr-3">
                                        <?php echo strtoupper(substr($role['role_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($role['role_name']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($role['description']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $perms = json_decode($role['permissions'], true) ?? [];
                                $permLabels = [];
                                foreach ($perms as $perm) {
                                    if (isset($availablePermissions[$perm])) {
                                        $permLabels[] = $availablePermissions[$perm];
                                    }
                                }
                                ?>
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach (array_slice($permLabels, 0, 3) as $label): ?>
                                        <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs">
                                            <?php echo htmlspecialchars($label); ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if (count($permLabels) > 3): ?>
                                        <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">
                                            +<?php echo count($permLabels) - 3; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $role['user_count']; ?> user
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex space-x-2">
                                    <a href="?edit=<?php echo $role['id']; ?>" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($role['user_count'] == 0): ?>
                                        <button onclick="confirmDelete(<?php echo $role['id']; ?>)" class="text-red-600 hover:text-red-800" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-user-shield text-4xl mb-4"></i>
                            <p>Tidak ada role ditemukan</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Tambah Role Baru</h3>
            <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Role *</label>
                    <input type="text" name="role_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                    <input type="text" name="description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Permissions</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <?php foreach ($availablePermissions as $permKey => $permLabel): ?>
                        <label class="flex items-center space-x-2 p-2 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="<?php echo $permKey; ?>" class="rounded text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700"><?php echo $permLabel; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Akses Menu</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <?php foreach ($menus as $menu): ?>
                        <label class="flex items-center space-x-2 p-2 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="menus[]" value="<?php echo $menu['id']; ?>" class="rounded text-indigo-600 focus:ring-indigo-500">
                            <i class="fas <?php echo $menu['menu_icon']; ?> text-gray-500"></i>
                            <span class="text-sm text-gray-700"><?php echo $menu['menu_label']; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal('addModal')" class="px-6 py-2 border rounded-lg hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<?php if ($editRole): ?>
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Edit Role</h3>
            <button onclick="closeModal('editModal'); window.location.href='roles.php';" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6 space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="role_id" value="<?php echo $editRole['id']; ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Role *</label>
                    <input type="text" name="role_name" required value="<?php echo htmlspecialchars($editRole['role_name']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                    <input type="text" name="description" value="<?php echo htmlspecialchars($editRole['description']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Permissions</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <?php foreach ($availablePermissions as $permKey => $permLabel): ?>
                        <label class="flex items-center space-x-2 p-2 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="<?php echo $permKey; ?>"
                                <?php echo in_array($permKey, $editRole['permissions_array'] ?? []) ? 'checked' : ''; ?>
                                class="rounded text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700"><?php echo $permLabel; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Akses Menu</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <?php foreach ($menus as $menu): ?>
                        <label class="flex items-center space-x-2 p-2 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="menus[]" value="<?php echo $menu['id']; ?>"
                                <?php echo in_array($menu['id'], $editRole['menu_access'] ?? []) ? 'checked' : ''; ?>
                                class="rounded text-indigo-600 focus:ring-indigo-500">
                            <i class="fas <?php echo $menu['menu_icon']; ?> text-gray-500"></i>
                            <span class="text-sm text-gray-700"><?php echo $menu['menu_label']; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal('editModal'); window.location.href='roles.php';" class="px-6 py-2 border rounded-lg hover:bg-gray-50">Batal</button>
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
                <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus role ini? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <form method="POST" action="" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="role_id" id="deleteRoleId">
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
    
    function confirmDelete(roleId) {
        document.getElementById('deleteRoleId').value = roleId;
        openModal('deleteModal');
    }
</script>

<?php require_once 'includes/footer.php'; ?>
