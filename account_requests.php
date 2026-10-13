<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$auth->requirePermission('users.manage');
$db = Database::getInstance();
$user = $auth->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestId = (int)($_POST['request_id'] ?? 0);
    $request = $db->fetchOne("SELECT * FROM account_requests WHERE id = ? AND status = 'pending'", [$requestId]);

    if (!$request) {
        $_SESSION['error'] = 'Permintaan akun tidak ditemukan atau sudah diproses.';
    } elseif ($action === 'reject') {
        $note = trim($_POST['admin_note'] ?? '');
        $db->update('account_requests', [
            'status' => 'rejected',
            'admin_note' => $note !== '' ? $note : null,
            'reviewed_by' => $user['id'],
            'reviewed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$requestId]);
        $auth->logActivity('rejected', 'account_requests', $requestId, $request, ['status' => 'rejected', 'admin_note' => $note]);
        $_SESSION['success'] = 'Permintaan akun ditolak.';
    } elseif ($action === 'approve') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = (int)($_POST['role_id'] ?? 0);
        $duplicate = $db->fetchOne('SELECT id FROM users WHERE employee_id = ? OR username = ? OR LOWER(email) = LOWER(?)', [$request['employee_id'], $username, $request['email']]);

        if ($username === '' || strlen($password) < 6 || $roleId < 1) {
            $_SESSION['error'] = 'Username, role, dan password minimal 6 karakter wajib diisi.';
        } elseif ($duplicate) {
            $_SESSION['error'] = 'Kode pegawai, username, atau email sudah digunakan.';
        } else {
            $connection = $db->getConnection();
            try {
                $connection->beginTransaction();
                $userId = $db->insert('users', [
                    'employee_id' => $request['employee_id'],
                    'username' => $username,
                    'email' => $request['email'],
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'full_name' => $request['full_name'],
                    'role_id' => $roleId,
                    'is_active' => 1
                ]);
                $db->update('account_requests', [
                    'status' => 'approved',
                    'reviewed_by' => $user['id'],
                    'reviewed_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$requestId]);
                $connection->commit();
                $auth->logActivity('approved', 'account_requests', $requestId, $request, ['status' => 'approved', 'user_id' => $userId]);
                $_SESSION['success'] = 'Permintaan disetujui dan akun pengguna berhasil dibuat.';
            } catch (Exception $e) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }
                $_SESSION['error'] = 'Gagal membuat akun: ' . $e->getMessage();
            }
        }
    }

    header('Location: account_requests.php');
    exit;
}

$pageTitle = 'Permintaan Akun';
require_once 'includes/header.php';

$roles = $db->fetchAll('SELECT * FROM roles ORDER BY id');
$requests = $db->fetchAll("SELECT ar.*, reviewer.full_name AS reviewer_name
    FROM account_requests ar
    LEFT JOIN users reviewer ON reviewer.id = ar.reviewed_by
    ORDER BY ar.status = 'pending' DESC, ar.created_at DESC");
?>

<div class="mb-8 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Permintaan Akun</h2>
        <p class="text-gray-600">Tinjau permintaan akses dari pengguna baru.</p>
    </div>
    <a href="users.php" class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"><i class="fas fa-users mr-2"></i>Manajemen Pengguna</a>
</div>

<div class="overflow-hidden rounded-xl bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1000px]">
            <thead class="bg-gray-50"><tr>
                <th class="px-5 py-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Pemohon</th>
                <th class="px-5 py-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Kontak</th>
                <th class="px-5 py-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Keterangan</th>
                <th class="px-5 py-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                <th class="px-5 py-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Aksi</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
            <?php foreach ($requests as $request): ?>
                <tr class="align-top hover:bg-gray-50">
                    <td class="px-5 py-4 text-sm"><div class="font-semibold text-gray-900"><?php echo htmlspecialchars($request['full_name']); ?></div><div class="text-gray-500">Kode: <?php echo htmlspecialchars($request['employee_id']); ?></div><div class="text-gray-500"><?php echo htmlspecialchars($request['department'] ?: '-'); ?></div></td>
                    <td class="px-5 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($request['email']); ?><div class="mt-1 text-xs text-gray-400"><?php echo htmlspecialchars($request['created_at']); ?></div></td>
                    <td class="max-w-xs px-5 py-4 text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($request['reason'] ?: '-')); ?></td>
                    <td class="px-5 py-4 text-sm">
                        <?php $statusClass = $request['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($request['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>
                        <span class="rounded-full px-2 py-1 text-xs font-medium <?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($request['status'])); ?></span>
                    </td>
                    <td class="px-5 py-4 text-sm">
                    <?php if ($request['status'] === 'pending'): ?>
                        <details class="min-w-[240px]"><summary class="cursor-pointer font-semibold text-purple-700">Proses permintaan</summary>
                            <form method="POST" class="mt-3 space-y-2 rounded-lg bg-gray-50 p-3">
                                <input type="hidden" name="action" value="approve"><input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                <input type="text" name="username" required placeholder="Username" value="<?php echo htmlspecialchars(strtolower($request['employee_id'])); ?>" class="w-full rounded border px-3 py-2 text-sm">
                                <input type="password" name="password" required minlength="6" placeholder="Password awal (min. 6)" class="w-full rounded border px-3 py-2 text-sm">
                                <select name="role_id" required class="w-full rounded border px-3 py-2 text-sm"><?php foreach ($roles as $role): ?><option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option><?php endforeach; ?></select>
                                <button class="w-full rounded bg-green-600 px-3 py-2 text-xs font-semibold text-white hover:bg-green-700" type="submit"><i class="fas fa-check mr-1"></i>Setujui & Buat Akun</button>
                            </form>
                            <form method="POST" class="mt-2 space-y-2"><input type="hidden" name="action" value="reject"><input type="hidden" name="request_id" value="<?php echo $request['id']; ?>"><input type="text" name="admin_note" placeholder="Alasan penolakan (opsional)" class="w-full rounded border px-3 py-2 text-sm"><button class="w-full rounded bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700" type="submit"><i class="fas fa-times mr-1"></i>Tolak</button></form>
                        </details>
                    <?php else: ?>
                        <span class="text-xs text-gray-500"><?php echo htmlspecialchars($request['reviewer_name'] ?: '-'); ?><br><?php echo htmlspecialchars($request['reviewed_at'] ?: ''); ?></span>
                    <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$requests): ?><tr><td colspan="5" class="px-5 py-12 text-center text-gray-500">Belum ada permintaan akun.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
