<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$auth->requireLogin();
$auth->requirePermission('items.view', 'item_requests');
$user = $auth->getCurrentUser();
$db = Database::getInstance();

$isAdmin = in_array((int)$user['role_id'], [1, 2], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $requestDate = $_POST['request_date'] ?: date('Y-m-d');
        $requesterName = trim($_POST['requester_name']);
        $employeeId = trim($user['employee_id'] ?? '');
        $department = trim($_POST['department']);
        $purpose = trim($_POST['purpose']);

        $itemNames = $_POST['item_name'] ?? [];
        $itemQtys = $_POST['item_qty'] ?? [];
        $itemUnits = $_POST['item_unit'] ?? [];
        $itemPrices = $_POST['item_price'] ?? [];
        $itemNotes = $_POST['item_note'] ?? [];

        $photoBon = null;
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

        $itemDetails = [];
        $totalItems = 0;
        $totalAmount = 0;
        foreach ($itemNames as $i => $nm) {
            $nm = trim($nm);
            if ($nm === '') continue;
            $q = (int)$itemQtys[$i];
            $u = trim($itemUnits[$i]);
            $p = (float)$itemPrices[$i];
            $n = trim($itemNotes[$i]);
            $subtotal = $q * $p;
            $itemDetails[] = [
                'name' => $nm, 'qty' => $q, 'unit' => $u ?: 'pcs',
                'price' => $p, 'note' => $n, 'subtotal' => $subtotal
            ];
            $totalItems += $q;
            $totalAmount += $subtotal;
        }

        if (!$requesterName || !$employeeId || empty($itemDetails)) {
            $_SESSION['error'] = !$employeeId
                ? 'Kode pegawai belum terdaftar pada akun Anda.'
                : 'Nama pemohon dan minimal 1 item barang wajib diisi.';
            header('Location: item_requests.php');
            exit;
        }

        $prefix = 'PBJ/' . date('Y/m') . '/';
        $last = $db->fetchOne("SELECT request_no FROM item_requests WHERE request_no LIKE ? ORDER BY id DESC LIMIT 1", [$prefix . '%']);
        $seq = 1;
        if ($last && preg_match('#/(\d+)$#', $last['request_no'], $m)) {
            $seq = (int)$m[1] + 1;
        }
        $requestNo = $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

        $data = [
            'request_no' => $requestNo,
            'request_date' => $requestDate,
            'requester_name' => $requesterName,
            'employee_id' => $employeeId,
            'department' => $department,
            'purpose' => $purpose,
            'item_details' => json_encode($itemDetails, JSON_UNESCAPED_UNICODE),
            'total_items' => $totalItems,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'photo_bon' => $photoBon,
            'created_by' => (int)$user['id'],
        ];

        try {
            $rid = $db->insert('item_requests', $data);
            $auth->logActivity('created', 'item_requests', $rid, null, $data);
            $_SESSION['success'] = "Pengajuan $requestNo berhasil dikirim. Menunggu persetujuan Admin.";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menyimpan pengajuan: ' . $e->getMessage();
        }
        header('Location: item_requests.php');
        exit;
    }

    if ($action === 'approve' && $isAdmin) {
        $rid = (int)$_POST['request_id'];
        $note = trim($_POST['admin_note'] ?? '');
        $old = $db->fetchOne("SELECT * FROM item_requests WHERE id = ?", [$rid]);
        if (!$old) {
            $_SESSION['error'] = 'Pengajuan tidak ditemukan.';
        } elseif ($old['status'] !== 'pending') {
            $_SESSION['error'] = "Pengajuan {$old['request_no']} sudah diproses (status saat ini: " . strtoupper($old['status']) . ") dan tidak bisa di-approve lagi.";
        } else {
            $db->update('item_requests', [
                'status' => 'approved',
                'admin_note' => $note,
                'approved_by' => (int)$user['id'],
                'approved_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$rid]);
            $auth->logActivity('approved', 'item_requests', $rid, $old, ['status' => 'approved', 'note' => $note]);
            $_SESSION['success'] = "Pengajuan {$old['request_no']} telah DISETUJUI.";
        }
        header('Location: item_requests.php');
        exit;
    }

    if ($action === 'reject' && $isAdmin) {
        $rid = (int)$_POST['request_id'];
        $note = trim($_POST['admin_note'] ?? '');
        if (!$note) {
            $_SESSION['error'] = 'Alasan penolakan wajib diisi.';
            header('Location: item_requests.php');
            exit;
        }
        $old = $db->fetchOne("SELECT * FROM item_requests WHERE id = ?", [$rid]);
        if (!$old) {
            $_SESSION['error'] = 'Pengajuan tidak ditemukan.';
        } elseif ($old['status'] !== 'pending') {
            $_SESSION['error'] = "Pengajuan {$old['request_no']} sudah diproses (status saat ini: " . strtoupper($old['status']) . ") dan tidak bisa ditolak lagi.";
        } else {
            $db->update('item_requests', [
                'status' => 'rejected',
                'admin_note' => $note,
                'rejected_by' => (int)$user['id'],
                'rejected_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$rid]);
            $auth->logActivity('rejected', 'item_requests', $rid, $old, ['status' => 'rejected', 'note' => $note]);
            $_SESSION['success'] = "Pengajuan {$old['request_no']} telah DITOLAK.";
        }
        header('Location: item_requests.php');
        exit;
    }

    if ($action === 'processing' && $isAdmin) {
        $rid = (int)$_POST['request_id'];
        $old = $db->fetchOne("SELECT * FROM item_requests WHERE id = ?", [$rid]);
        if (!$old) {
            $_SESSION['error'] = 'Pengajuan tidak ditemukan.';
        } elseif ($old['status'] !== 'approved') {
            $_SESSION['error'] = "Pengajuan {$old['request_no']} harus dalam status 'Disetujui' terlebih dahulu.";
        } else {
            $db->update('item_requests', [
                'status' => 'processing'
            ], 'id = ?', [$rid]);
            $auth->logActivity('processing', 'item_requests', $rid, $old, ['status' => 'processing']);
            $_SESSION['success'] = "Pengajuan {$old['request_no']} telah dipindahkan ke status PROCESSING.";
        }
        header('Location: item_requests.php');
        exit;
    }

    if ($action === 'barang_tiba' && $isAdmin) {
        $rid = (int)$_POST['request_id'];
        $old = $db->fetchOne("SELECT * FROM item_requests WHERE id = ?", [$rid]);
        if (!$old) {
            $_SESSION['error'] = 'Pengajuan tidak ditemukan.';
        } elseif ($old['status'] !== 'processing') {
            $_SESSION['error'] = "Pengajuan {$old['request_no']} harus dalam status 'Processing' terlebih dahulu.";
        } else {
            $db->update('item_requests', [
                'status' => 'barang_tiba'
            ], 'id = ?', [$rid]);
            $auth->logActivity('barang_tiba', 'item_requests', $rid, $old, ['status' => 'barang_tiba']);
            $_SESSION['success'] = "Pengajuan {$old['request_no']} telah ditandai sebagai BARANG TIBA.";
        }
        header('Location: item_requests.php');
        exit;
    }
}

$pageTitle = 'Pengajuan Barang';

// Check menu access for item_requests
if (!$auth->hasMenuAccess('item_requests')) {
    $_SESSION['error'] = 'Anda tidak memiliki akses ke menu Pengajuan Barang.';
    header('Location: dashboard.php');
    exit;
}

require_once 'includes/header.php';

$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where = ['1=1'];
$params = [];

if (!$isAdmin) {
    $where[] = 'created_by = ?';
    $params[] = (int)$user['id'];
}

if ($statusFilter) {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}

if ($search) {
    $where[] = '(request_no LIKE ? OR requester_name LIKE ? OR employee_id LIKE ?)';
    $sp = "%$search%";
    $params[] = $sp;
    $params[] = $sp;
    $params[] = $sp;
}

$whereClause = implode(' AND ', $where);
$requests = $db->fetchAll("
    SELECT r.*, 
           ua.full_name AS approved_name,
           ur.full_name AS rejected_name,
           uc.full_name AS creator_name
    FROM item_requests r
    LEFT JOIN users ua ON r.approved_by = ua.id
    LEFT JOIN users ur ON r.rejected_by = ur.id
    LEFT JOIN users uc ON r.created_by = uc.id
    WHERE $whereClause
    ORDER BY r.created_at DESC
    LIMIT 200
", $params);

$stats = [
    'total' => 0, 'pending' => 0, 'approved' => 0, 'processing' => 0, 'barang_tiba' => 0, 'rejected' => 0
];
foreach ($requests as $r) {
    $stats['total']++;
    $stats[$r['status']]++;
}

function statusBadge($s) {
    if ($s === 'approved') return '<span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-300">✓ DISETUJUI</span>';
    if ($s === 'processing') return '<span class="px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700 border border-orange-300">⚙️ PROCESSING</span>';
    if ($s === 'barang_tiba') return '<span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 border border-blue-300">📦 BARANG TIBA</span>';
    if ($s === 'rejected') return '<span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-300">✗ DITOLAK</span>';
    return '<span class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700 border border-yellow-300 animate-pulse">⏳ MENUNGGU</span>';
}
?>
<script>
function togglePengajuan() {
    const form = document.getElementById('formPengajuan');
    const button = document.getElementById('toggleFormPengajuan');
    const isHidden = form.classList.toggle('hidden');

    button.setAttribute('aria-expanded', String(!isHidden));
    button.innerHTML = isHidden
        ? '<i class="fas fa-plus-circle mr-2"></i>Buat Pengajuan Baru'
        : '<i class="fas fa-chevron-up mr-2"></i>Tutup Form Pengajuan';

    if (!isHidden) {
        form.scrollIntoView({behavior: 'smooth'});
    }
}

function addItemRow() {
    const t = document.getElementById('itemsTable');
    const i = t.querySelectorAll('tbody tr').length + 1;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="border px-2 py-2 text-gray-500 text-center text-sm">${i}</td>
        <td class="border px-2 py-1"><input type="text" name="item_name[]" class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-purple-400 outline-none" placeholder="Nama barang..."></td>
        <td class="border px-2 py-1 w-20"><input type="number" name="item_qty[]" min="1" value="1" class="w-full px-2 py-1 border border-gray-300 rounded text-right"></td>
        <td class="border px-2 py-1 w-24"><input type="text" name="item_unit[]" value="pcs" class="w-full px-2 py-1 border border-gray-300 rounded"></td>
        <td class="border px-2 py-1 w-32"><input type="number" name="item_price[]" min="0" step="100" value="0" class="w-full px-2 py-1 border border-gray-300 rounded text-right price-input"></td>
        <td class="border px-2 py-1 w-36"><input type="text" name="item_note[]" class="w-full px-2 py-1 border border-gray-300 rounded" placeholder="Spesifikasi"></td>
        <td class="border px-2 py-1 w-20 text-center">
            <button type="button" onclick="this.closest('tr').remove(); recalcIdx();" class="text-red-600 hover:text-red-800" title="Hapus baris">
                <i class="fas fa-trash"></i>
            </button>
        </td>`;
    t.querySelector('tbody').appendChild(tr);
    tr.querySelectorAll('.price-input').forEach(e => e.addEventListener('input', calcTotal));
    recalcIdx();
}
function recalcIdx() {
    document.querySelectorAll('#itemsTable tbody tr').forEach((r,i) => r.cells[0].textContent = i+1);
    calcTotal();
}
function calcTotal() {
    const totalQtyEl = document.getElementById('totalQty');
    const totalAmtEl = document.getElementById('totalAmt');
    if (!totalQtyEl || !totalAmtEl) return;
    let qty=0, amt=0;
    document.querySelectorAll('#itemsTable tbody tr').forEach(r => {
        const q = parseInt(r.querySelector('[name="item_qty[]"]').value || 0);
        const p = parseFloat(r.querySelector('[name="item_price[]"]').value || 0);
        qty += q;
        amt += q*p;
    });
    totalQtyEl.textContent = qty.toLocaleString('id-ID');
    totalAmtEl.textContent = 'Rp ' + amt.toLocaleString('id-ID');
}
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.price-input').forEach(e => e.addEventListener('input', calcTotal));
    document.querySelectorAll('[name="item_qty[]"]').forEach(e => e.addEventListener('input', calcTotal));
    calcTotal();
});
function openApprove(id, no) {
    document.getElementById('modReqId').value = id;
    document.getElementById('modReqNo').textContent = no;
    document.getElementById('modAction').value = 'approve';
    document.getElementById('modTitle').textContent = 'Setujui Pengajuan';
    document.getElementById('modLabel').textContent = 'Catatan (opsional):';
    document.getElementById('modNote').required = false;
    document.getElementById('modApproveModal').classList.remove('hidden');
}
function openReject(id, no) {
    document.getElementById('modReqId').value = id;
    document.getElementById('modReqNo').textContent = no;
    document.getElementById('modAction').value = 'reject';
    document.getElementById('modTitle').textContent = 'Tolak Pengajuan';
    document.getElementById('modLabel').textContent = 'Alasan penolakan (WAJIB):';
    document.getElementById('modNote').required = true;
    document.getElementById('modApproveModal').classList.remove('hidden');
}
</script>

<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Pengajuan Barang</h2>
        <p class="text-gray-600">Buat pengajuan, lacak status, dan approval oleh Administrator</p>
    </div>
    <div class="flex gap-2">
        <button type="button" id="toggleFormPengajuan" onclick="togglePengajuan()" aria-expanded="false"
                class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-2 rounded-lg shadow hover:shadow-lg transition">
            <i class="fas fa-plus-circle mr-2"></i>Buat Pengajuan Baru
        </button>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl shadow p-5 border-l-4 border-blue-500">
        <p class="text-gray-500 text-xs">Total Pengajuan</p>
        <p class="text-2xl font-bold text-gray-800"><?= $stats['total'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-5 border-l-4 border-yellow-500">
        <p class="text-gray-500 text-xs">Menunggu Approval</p>
        <p class="text-2xl font-bold text-yellow-600"><?= $stats['pending'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-5 border-l-4 border-green-500">
        <p class="text-gray-500 text-xs">Disetujui</p>
        <p class="text-2xl font-bold text-green-600"><?= $stats['approved'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-5 border-l-4 border-red-500">
        <p class="text-gray-500 text-xs">Ditolak</p>
        <p class="text-2xl font-bold text-red-600"><?= $stats['rejected'] ?></p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-8 hidden" id="formPengajuan">
    <h3 class="text-lg font-bold text-gray-800 mb-5 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-edit text-indigo-600"></i> Form Pengajuan Barang
    </h3>
    <form method="POST" action="item_requests.php" enctype="multipart/form-data" class="space-y-5">
        <input type="hidden" name="action" value="create">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Pengajuan</label>
                <input type="date" name="request_date" value="<?= date('Y-m-d') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Pemohon <span class="text-red-500">*</span></label>
                <input type="text" name="requester_name" required value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Kode Pegawai</label>
                <input type="text" value="<?= htmlspecialchars($user['employee_id'] ?? '-') ?>" readonly
                       class="w-full px-4 py-2 border border-gray-300 bg-gray-100 rounded-lg text-gray-700 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Departemen / Bagian</label>
                <input type="text" name="department" placeholder="cth: Bar, Kitchen, Gudang"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Keperluan / Tujuan</label>
            <textarea name="purpose" rows="2" placeholder="Jelaskan tujuan pengajuan ini..."
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
        </div>

        <div class="border rounded-xl overflow-hidden">
            <div class="bg-indigo-50 px-4 py-3 flex items-center justify-between">
                <span class="font-bold text-indigo-800"><i class="fas fa-list-ul mr-2"></i>Daftar Barang yang Diajukan</span>
                <button type="button" onclick="addItemRow()" class="bg-indigo-600 text-white px-3 py-1.5 rounded-md text-sm hover:bg-indigo-700">
                    <i class="fas fa-plus mr-1"></i>Tambah Baris
                </button>
            </div>
            <div class="overflow-x-auto">
            <table class="w-full text-sm" id="itemsTable">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="border px-2 py-2 w-12">#</th>
                        <th class="border px-2 py-2 text-left">Nama Barang <span class="text-red-500">*</span></th>
                        <th class="border px-2 py-2 w-20">Qty</th>
                        <th class="border px-2 py-2 w-24">Satuan</th>
                        <th class="border px-2 py-2 w-32 text-right">Est. Harga/Unit</th>
                        <th class="border px-2 py-2 w-36">Spesifikasi / Catatan</th>
                        <th class="border px-2 py-2 w-20">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i=1; $i<=3; $i++): ?>
                    <tr>
                        <td class="border px-2 py-2 text-gray-500 text-center text-sm"><?= $i ?></td>
                        <td class="border px-2 py-1"><input type="text" name="item_name[]" class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-400 outline-none" placeholder="<?= $i===1 ? 'Contoh: Mesin Kopi' : 'Nama barang...' ?>"></td>
                        <td class="border px-2 py-1 w-20"><input type="number" name="item_qty[]" min="1" value="1" class="w-full px-2 py-1 border border-gray-300 rounded text-right"></td>
                        <td class="border px-2 py-1 w-24"><input type="text" name="item_unit[]" value="pcs" class="w-full px-2 py-1 border border-gray-300 rounded"></td>
                        <td class="border px-2 py-1 w-32"><input type="number" name="item_price[]" min="0" step="100" value="0" class="w-full px-2 py-1 border border-gray-300 rounded text-right price-input"></td>
                        <td class="border px-2 py-1 w-36"><input type="text" name="item_note[]" class="w-full px-2 py-1 border border-gray-300 rounded" placeholder="<?= $i===1 ? 'merk Kopi, 1 group' : 'Spesifikasi' ?>"></td>
                        <td class="border px-2 py-1 w-20 text-center">
                            <button type="button" onclick="this.closest('tr').remove(); recalcIdx();" class="text-red-500 hover:text-red-700" title="Hapus baris">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
                <tfoot class="bg-gray-50 font-bold">
                    <tr>
                        <td colspan="5" class="border px-4 py-2 text-right text-gray-700">TOTAL:</td>
                        <td class="border px-4 py-2 text-right whitespace-nowrap">
                            <div class="text-xs text-gray-500 font-normal">Jumlah Item:</div>
                            <div id="totalQty" class="text-indigo-700">0</div>
                        </td>
                        <td class="border px-4 py-2 text-right whitespace-nowrap">
                            <div class="text-xs text-gray-500 font-normal">Est. Nilai:</div>
                            <div id="totalAmt" class="text-purple-700">Rp 0</div>
                        </td>
                    </tr>
                </tfoot>
            </table>
            </div>
        </div>

        <div class="border rounded-xl overflow-hidden mt-4">
            <div class="bg-indigo-50 px-4 py-3">
                <span class="font-bold text-indigo-800"><i class="fas fa-file-invoice mr-2"></i>Dokumen Pendukung</span>
            </div>
            <div class="p-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Foto Bon / Bukti Pembayaran (Opsional)</label>
                <input type="file" name="photo_bon" accept="image/*,application/pdf" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                <p class="text-xs text-gray-500 mt-1">Maksimal: 5MB (JPG, PNG, GIF, PDF)</p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-2">
            <button type="reset" class="bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg hover:bg-gray-300 transition font-medium">
                <i class="fas fa-undo mr-2"></i>Reset
            </button>
            <button type="submit" class="bg-gradient-to-r from-green-600 to-emerald-600 text-white px-8 py-2.5 rounded-lg hover:shadow-lg transition font-bold shadow">
                <i class="fas fa-paper-plane mr-2"></i>Kirim Pengajuan
            </button>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-4 border-b flex flex-wrap gap-3 items-center justify-between bg-gray-50">
        <h3 class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-history"></i>Daftar Pengajuan</h3>
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="search" placeholder="Cari No. / Pemohon..." value="<?= htmlspecialchars($search) ?>"
                   class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
            <select name="status" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                <option value="">Semua Status</option>
                <option value="pending" <?= $statusFilter==='pending'?'selected':'' ?>>⏳ Menunggu</option>
                <option value="approved" <?= $statusFilter==='approved'?'selected':'' ?>>✓ Disetujui</option>
                <option value="processing" <?= $statusFilter==='processing'?'selected':'' ?>>⚙️ Processing</option>
                <option value="barang_tiba" <?= $statusFilter==='barang_tiba'?'selected':'' ?>>📦 Barang Tiba</option>
                <option value="rejected" <?= $statusFilter==='rejected'?'selected':'' ?>>✗ Ditolak</option>
            </select>
            <button class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm hover:bg-indigo-700">
                <i class="fas fa-search mr-1"></i>Cari
            </button>
            <?php if ($search || $statusFilter): ?>
                <a href="item_requests.php" class="px-3 py-1.5 bg-gray-200 rounded-md text-sm hover:bg-gray-300">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
            <tr>
                <th class="px-4 py-3 text-left w-20">No. Dok</th>
                <th class="px-4 py-3 text-left">Tanggal / Pemohon</th>
                <th class="px-4 py-3 text-left">Kode Pegawai</th>
                <th class="px-4 py-3 text-left">Keperluan</th>
                <th class="px-4 py-3 text-right">Item / Qty</th>
                <?php if ($isAdmin): ?><th class="px-4 py-3 text-right">Est. Nilai</th><?php endif; ?>
                <th class="px-4 py-3 text-center">Foto Bon</th>
                <th class="px-4 py-3 text-center">Status</th>
                <?php if ($isAdmin): ?><th class="px-4 py-3 text-center">Approval</th><?php endif; ?>
                <th class="px-4 py-3 text-center">Dokumen</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
        <?php if (empty($requests)): ?>
            <tr><td colspan="<?= $isAdmin ? 10 : 8 ?>" class="px-6 py-12 text-center text-gray-500"><i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i><p>Belum ada data pengajuan.</p></td></tr>
        <?php else: ?>
            <?php foreach ($requests as $r):
                $items = json_decode($r['item_details'], true) ?: [];
            ?>
            <tr class="hover:bg-indigo-50/30">
                <td class="px-4 py-3">
                    <div class="font-mono font-bold text-indigo-700 text-xs"><?= htmlspecialchars($r['request_no']) ?></div>
                    <div class="text-xs text-gray-500">Pembuat: <?= htmlspecialchars($r['creator_name'] ?? '-') ?></div>
                </td>
                <td class="px-4 py-3">
                    <div class="font-semibold text-gray-800"><?= htmlspecialchars($r['requester_name']) ?></div>
                    <div class="text-xs text-gray-500">
                        <i class="far fa-calendar mr-1"></i><?= (new DateTime($r['request_date']))->format('d M Y') ?>
                        <?php if ($r['department']): ?> • <i class="fas fa-building mr-1"></i><?= htmlspecialchars($r['department']) ?><?php endif; ?>
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap font-medium text-indigo-700"><?= htmlspecialchars($r['employee_id'] ?? '-') ?></td>
                <td class="px-4 py-3 max-w-xs"><p class="line-clamp-2 text-gray-600"><?= htmlspecialchars($r['purpose'] ?: '-') ?></p></td>
                <td class="px-4 py-3 text-right">
                    <span class="font-bold text-gray-800"><?= count($items) ?> jenis</span>
                    <div class="text-xs text-gray-500">/ <?= (int)$r['total_items'] ?> unit</div>
                </td>
                <?php if ($isAdmin): ?><td class="px-4 py-3 text-right font-semibold text-purple-700 whitespace-nowrap">Rp <?= number_format($r['total_amount'], 0, ',', '.') ?></td><?php endif; ?>
                <td class="px-4 py-3 text-center">
                    <?php if ($r['photo_bon']): ?>
                        <?php $ext = strtolower(pathinfo($r['photo_bon'], PATHINFO_EXTENSION)); ?>
                        <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                            <a href="<?= htmlspecialchars($r['photo_bon']) ?>" target="_blank" class="text-blue-600 hover:text-blue-800" title="Lihat Foto Bon">
                                <i class="fas fa-image"></i>
                            </a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($r['photo_bon']) ?>" target="_blank" class="text-red-600 hover:text-red-800" title="Lihat Dokumen">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-center"><?= statusBadge($r['status']) ?>
                    <?php if ($r['status']==='approved'): ?><div class="text-xs text-green-700 mt-1">oleh <?= htmlspecialchars($r['approved_name'] ?? '-') ?></div><?php endif; ?>
                    <?php if ($r['status']==='rejected'): ?><div class="text-xs text-red-700 mt-1">oleh <?= htmlspecialchars($r['rejected_name'] ?? '-') ?></div><?php endif; ?>
                </td>
                <?php if ($isAdmin): ?>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                    <?php if ($r['status'] === 'pending'): ?>
                        <button onclick="openApprove(<?= $r['id'] ?>, '<?= htmlspecialchars($r['request_no']) ?>')"
                                class="bg-green-600 text-white px-3 py-1 rounded-md text-xs hover:bg-green-700 mr-1 mb-1">
                            <i class="fas fa-check mr-1"></i>Setujui
                        </button>
                        <button onclick="openReject(<?= $r['id'] ?>, '<?= htmlspecialchars($r['request_no']) ?>')"
                                class="bg-red-600 text-white px-3 py-1 rounded-md text-xs hover:bg-red-700 mb-1">
                            <i class="fas fa-times mr-1"></i>Tolak
                        </button>
                    <?php elseif ($r['status'] === 'approved'): ?>
                        <form method="POST" action="item_requests.php" class="inline">
                            <input type="hidden" name="action" value="processing">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <button type="submit" onclick="return confirm('Pindahkan pengajuan ini ke PROCESSING?')"
                                    class="bg-orange-600 text-white px-3 py-1 rounded-md text-xs hover:bg-orange-700 mr-1 mb-1">
                                <i class="fas fa-cog mr-1"></i>Processing
                            </button>
                        </form>
                    <?php elseif ($r['status'] === 'processing'): ?>
                        <form method="POST" action="item_requests.php" class="inline">
                            <input type="hidden" name="action" value="barang_tiba">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <button type="submit" onclick="return confirm('Tandai pengajuan ini sebagai BARANG TIBA?')"
                                    class="bg-blue-600 text-white px-3 py-1 rounded-md text-xs hover:bg-blue-700 mr-1 mb-1">
                                <i class="fas fa-box mr-1"></i>Barang Tiba
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="inline-block px-3 py-1 rounded-md text-xs bg-gray-100 text-gray-500 border border-gray-200 cursor-not-allowed"
                              title="Pengajuan sudah diproses dan tidak bisa diubah lagi">
                            <i class="fas fa-lock mr-1"></i>Terkunci
                        </span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                    <?php if ($r['status'] === 'pending'): ?>
                        <span class="inline-flex items-center bg-gray-100 text-gray-400 border border-gray-200 px-2.5 py-1 rounded-md text-xs cursor-not-allowed mr-1 mb-1"
                              title="Menunggu approval Admin sebelum dapat dicetak">
                            <i class="fas fa-print mr-1"></i>Print
                        </span>
                        <span class="inline-flex items-center bg-gray-100 text-gray-400 border border-gray-200 px-2.5 py-1 rounded-md text-xs cursor-not-allowed mb-1"
                              title="Menunggu approval Admin sebelum di-download PDF">
                            <i class="fas fa-file-pdf mr-1"></i>PDF
                        </span>
                        <div class="text-[10px] text-gray-400">Menunggu Approval</div>
                    <?php else: ?>
                        <a href="item_request_print.php?id=<?= $r['id'] ?>" target="_blank"
                           class="inline-flex items-center bg-blue-50 text-blue-700 border border-blue-300 px-2.5 py-1 rounded-md text-xs hover:bg-blue-100 mr-1 mb-1">
                            <i class="fas fa-print mr-1"></i>Print
                        </a>
                        <a href="item_request_pdf.php?id=<?= $r['id'] ?>"
                           class="inline-flex items-center bg-red-50 text-red-700 border border-red-300 px-2.5 py-1 rounded-md text-xs hover:bg-red-100 mb-1">
                            <i class="fas fa-file-pdf mr-1"></i>PDF
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($r['admin_note']): ?>
            <tr class="bg-gray-50/70">
                <td></td>
                <td colspan="7" class="px-4 pb-3 pt-0">
                    <div class="text-xs bg-white border rounded-md px-3 py-2 shadow-sm">
                        <strong class="text-gray-600"><i class="fas fa-comment-alt mr-1"></i>Catatan Admin:</strong>
                        <span class="text-gray-700 ml-1"><?= htmlspecialchars($r['admin_note']) ?></span>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div id="modApproveModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="border-b px-6 py-4 flex items-center justify-between">
            <h3 id="modTitle" class="font-bold text-gray-800 text-lg">Approval Pengajuan</h3>
            <button onclick="document.getElementById('modApproveModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form method="POST" action="item_requests.php">
            <input type="hidden" name="action" id="modAction" value="approve">
            <input type="hidden" name="request_id" id="modReqId">
            <div class="p-6 space-y-4">
                <p class="text-gray-700">Nomor Pengajuan: <strong id="modReqNo" class="text-indigo-700 font-mono"></strong></p>
                <div>
                    <label id="modLabel" class="block text-sm font-semibold text-gray-700 mb-1">Catatan:</label>
                    <textarea id="modNote" name="admin_note" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Isi catatan atau alasan..."></textarea>
                </div>
            </div>
            <div class="border-t px-6 py-4 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modApproveModal').classList.add('hidden')"
                        class="px-5 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Batal</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                    Konfirmasi
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
