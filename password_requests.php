<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$auth->requireLogin();
$auth->requirePermission('users.manage', 'password_requests');
$db = Database::getInstance();
$user = $auth->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestId = (int)($_POST['request_id'] ?? 0);
    $request = $db->fetchOne(
        "SELECT pr.*, u.employee_id, u.username, u.email, u.full_name
         FROM password_reset_requests pr
         JOIN users u ON u.id = pr.user_id
         WHERE pr.id = ? AND pr.status = 'pending' AND u.is_active = 1",
        [$requestId]
    );

    if (!$request) {
        $_SESSION['error'] = 'Permintaan reset password tidak ditemukan atau sudah diproses.';
    } elseif ($action === 'reject') {
        $note = trim($_POST['admin_note'] ?? '');
        $db->update('password_reset_requests', [
            'status' => 'rejected',
            'requested_password_hash' => null,
            'admin_note' => $note !== '' ? $note : null,
            'reviewed_by' => $user['id'],
            'reviewed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$requestId]);
        $auth->logActivity('rejected', 'password_reset_requests', $requestId, [
            'user_id' => $request['user_id'],
            'status' => 'pending'
        ], ['status' => 'rejected', 'admin_note' => $note]);
        $_SESSION['success'] = 'Permintaan reset password ditolak.';
    } elseif ($action === 'approve') {
        $connection = $db->getConnection();
        try {
            $connection->beginTransaction();
            $db->update('users', [
                'password' => $request['requested_password_hash']
            ], 'id = ?', [$request['user_id']]);
            $db->update('password_reset_requests', [
                'status' => 'approved',
                'requested_password_hash' => null,
                'reviewed_by' => $user['id'],
                'reviewed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$requestId]);
            $connection->commit();
            $auth->logActivity('approved', 'password_reset_requests', $requestId, [
                'user_id' => $request['user_id'],
                'status' => 'pending'
            ], ['status' => 'approved']);
            $_SESSION['success'] = 'Permintaan disetujui. Password pengguna berhasil diperbarui.';
        } catch (Exception $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            $_SESSION['error'] = 'Gagal menyetujui permintaan reset password: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Aksi permintaan reset password tidak valid.';
    }

    header('Location: password_requests.php');
    exit;
}

$pageTitle = 'Permintaan Reset Password';
require_once 'includes/header.php';

$requests = $db->fetchAll("SELECT pr.*, u.employee_id, u.username, u.email, u.full_name,
        reviewer.full_name AS reviewer_name
    FROM password_reset_requests pr
    JOIN users u ON u.id = pr.user_id
    LEFT JOIN users reviewer ON reviewer.id = pr.reviewed_by
    ORDER BY pr.status = 'pending' DESC, pr.created_at DESC");
?>

<div class="mb-8 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Permintaan Reset Password</h2>
        <p class="text-gray-600">Tinjau permintaan password baru dari pengguna.</p>
    </div>
    <a href="users.php" class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"><i class="fas fa-users mr-2"></i>Manajemen Pengguna</a>
</div>

<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
    <i class="fas fa-shield-alt mr-2"></i>Password baru tidak ditampilkan kepada admin. Setelah disetujui, sistem langsung menerapkan password tersebut secara aman.
</div>

<div class="overflow-hidden rounded-xl bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1000px]">
            <thead class="bg-gray-50"><tr>
                <th class="px-5 py-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Pemohon</th>
                <th class="px-5 py-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Kontak</th>
                <th class="px-5 py-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Diajukan</th>
                <th class="px-5 py-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                <th class="px-5 py-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Aksi</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
            <?php foreach ($requests as $request): ?>
                <tr class="align-top hover:bg-gray-50">
                    <td class="px-5 py-4 text-sm"><div class="font-semibold text-gray-900"><?php echo htmlspecialchars($request['full_name']); ?></div><div class="text-gray-500">Kode: <?php echo htmlspecialchars($request['employee_id']); ?></div><div class="text-gray-500">Username: <?php echo htmlspecialchars($request['username']); ?></div></td>
                    <td class="px-5 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($request['email']); ?></td>
                    <td class="px-5 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($request['created_at']); ?></td>
                    <td class="px-5 py-4 text-sm">
                        <?php $statusClass = $request['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($request['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>
                        <span class="rounded-full px-2 py-1 text-xs font-medium <?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($request['status'])); ?></span>
                        <?php if ($request['status'] !== 'pending'): ?><div class="mt-2 text-xs text-gray-500"><?php echo htmlspecialchars($request['reviewer_name'] ?: '-'); ?><br><?php echo htmlspecialchars($request['reviewed_at'] ?: ''); ?><?php if ($request['admin_note']): ?><br>Catatan: <?php echo htmlspecialchars($request['admin_note']); ?><?php endif; ?></div><?php endif; ?>
                    </td>
                    <td class="px-5 py-4 text-sm">
                    <?php if ($request['status'] === 'pending'): ?>
                        <details class="min-w-[240px]"><summary class="cursor-pointer font-semibold text-purple-700">Proses permintaan</summary>
                            <form method="POST" class="mt-3 rounded-lg bg-gray-50 p-3">
                                <input type="hidden" name="action" value="approve"><input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                <p class="mb-2 text-xs text-gray-600">Setujui untuk menerapkan password baru yang diajukan pengguna.</p>
                                <button class="w-full rounded bg-green-600 px-3 py-2 text-xs font-semibold text-white hover:bg-green-700" type="submit"><i class="fas fa-check mr-1"></i>Setujui & Terapkan</button>
                            </form>
                            <form method="POST" class="mt-2 space-y-2"><input type="hidden" name="action" value="reject"><input type="hidden" name="request_id" value="<?php echo $request['id']; ?>"><input type="text" name="admin_note" maxlength="500" placeholder="Alasan penolakan (opsional)" class="w-full rounded border px-3 py-2 text-sm"><button class="w-full rounded bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700" type="submit"><i class="fas fa-times mr-1"></i>Tolak</button></form>
                        </details>
                    <?php else: ?>
                        <span class="text-xs text-gray-500">Sudah diproses</span>
                    <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$requests): ?><tr><td colspan="5" class="px-5 py-12 text-center text-gray-500">Belum ada permintaan reset password.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
