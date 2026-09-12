<?php
require_once 'config.php';
require_once 'includes/auth.php';

$auth->requireLogin();
$user = $auth->getCurrentUser();
$db = Database::getInstance();

$managerRoles = ['Super Admin', 'Admin'];
$canManage = $auth->hasPermission('finance.manage') || in_array($user['role_name'], $managerRoles, true);
$canView = $auth->hasPermission('finance.view') || $auth->hasMenuAccess('finance') || $canManage;

if (!$canView) {
    $_SESSION['error'] = 'Anda tidak memiliki akses ke menu Finance.';
    header('Location: dashboard.php');
    exit;
}

$settings = $db->fetchOne("SELECT * FROM finance_settings WHERE id = 1");
if (!$settings) {
    $db->insert('finance_settings', ['id' => 1]);
    $settings = $db->fetchOne("SELECT * FROM finance_settings WHERE id = 1");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canManage) {
        $_SESSION['error'] = 'Anda tidak memiliki izin untuk mengubah data Finance.';
        header('Location: finance.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_summary') {
        $totalIncome = (float) ($_POST['total_income'] ?? 0);
        $totalExpense = (float) ($_POST['total_expense'] ?? 0);
        $availableAmount = round($totalIncome - $totalExpense, 2);

        if ($totalIncome < 0 || $totalExpense < 0) {
            $_SESSION['error'] = 'Nilai Finance tidak boleh negatif.';
        } else {
            try {
                $oldSettings = $settings;
                $db->update('finance_settings', [
                    'available_amount' => $availableAmount,
                    'total_income' => $totalIncome,
                    'total_expense' => $totalExpense,
                    'updated_by' => $user['id']
                ], 'id = 1');
                $auth->logActivity('updated', 'finance_settings', 1, $oldSettings, [
                    'available_amount' => $availableAmount,
                    'total_income' => $totalIncome,
                    'total_expense' => $totalExpense
                ]);
                $_SESSION['success'] = 'Ringkasan Finance berhasil disimpan.';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Gagal menyimpan ringkasan Finance: ' . $e->getMessage();
            }
        }

        header('Location: finance.php');
        exit;
    }

    if ($action === 'add_transaction') {
        $transactionName = trim($_POST['transaction_name'] ?? '');
        $transactionType = $_POST['transaction_type'] ?? '';
        $method = trim($_POST['method'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');

        if ($transactionName === '' || $method === '' || $amount <= 0) {
            $_SESSION['error'] = 'Nama transaksi, metode, dan nominal wajib diisi dengan benar.';
        } elseif (!in_array($transactionType, ['income', 'expense'], true)) {
            $_SESSION['error'] = 'Jenis transaksi tidak valid.';
        } else {
            $connection = $db->getConnection();

            try {
                $connection->beginTransaction();

                $transactionId = $db->insert('finance_transactions', [
                    'transaction_name' => $transactionName,
                    'transaction_type' => $transactionType,
                    'method' => $method,
                    'amount' => $amount,
                    'notes' => $notes ?: null,
                    'transaction_date' => $transactionDate,
                    'created_by' => $user['id']
                ]);

                $totalColumn = $transactionType === 'income' ? 'total_income' : 'total_expense';
                $db->query(
                    "UPDATE finance_settings SET {$totalColumn} = {$totalColumn} + ?, updated_by = ? WHERE id = 1",
                    [$amount, $user['id']]
                );
                $db->query(
                    "UPDATE finance_settings SET available_amount = total_income - total_expense, updated_by = ? WHERE id = 1",
                    [$user['id']]
                );

                $auth->logActivity('created', 'finance_transactions', $transactionId, null, [
                    'transaction_name' => $transactionName,
                    'transaction_type' => $transactionType,
                    'method' => $method,
                    'amount' => $amount,
                    'transaction_date' => $transactionDate
                ]);

                $connection->commit();
                $_SESSION['success'] = ($transactionType === 'income' ? 'Uang masuk' : 'Uang keluar') . ' berhasil ditambahkan.';
            } catch (Exception $e) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }
                $_SESSION['error'] = 'Gagal menyimpan transaksi Finance: ' . $e->getMessage();
            }
        }

        header('Location: finance.php');
        exit;
    }
}

$settings = $db->fetchOne("SELECT * FROM finance_settings WHERE id = 1");
$availableAmount = round((float) $settings['total_income'] - (float) $settings['total_expense'], 2);

$filterMonth = $_GET['month'] ?? '';
$filterYear = $_GET['year'] ?? '';
$validMonth = ctype_digit((string) $filterMonth) && (int) $filterMonth >= 1 && (int) $filterMonth <= 12;
$validYear = ctype_digit((string) $filterYear) && (int) $filterYear >= 2000 && (int) $filterYear <= 2100;

$transactionWhere = ['1=1'];
$transactionParams = [];
if ($validMonth) {
    $transactionWhere[] = 'MONTH(ft.transaction_date) = ?';
    $transactionParams[] = (int) $filterMonth;
} else {
    $filterMonth = '';
}
if ($validYear) {
    $transactionWhere[] = 'YEAR(ft.transaction_date) = ?';
    $transactionParams[] = (int) $filterYear;
} else {
    $filterYear = '';
}

$transactions = $db->fetchAll(
    "SELECT ft.*, u.full_name AS created_by_name
     FROM finance_transactions ft
     LEFT JOIN users u ON ft.created_by = u.id
     WHERE " . implode(' AND ', $transactionWhere) . "
     ORDER BY ft.transaction_date DESC, ft.id DESC
     LIMIT 100",
    $transactionParams
);

$pageTitle = 'Finance';
require_once 'includes/header.php';
?>

<style>
    .finance-income { color: #15803d !important; }
    .finance-expense { color: #b91c1c !important; }
    .finance-income-bg { background-color: #f0fdf4 !important; }
    .finance-expense-bg { background-color: #fef2f2 !important; }
</style>

<div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Finance</h2>
        <p class="text-gray-600">Kelola saldo dan transaksi keuangan</p>
    </div>
    <?php if ($canManage): ?>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="openTransactionModal('income')" class="rounded-lg bg-green-600 px-5 py-2 text-white transition hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i>Tambah Uang Masuk
            </button>
            <button type="button" onclick="openTransactionModal('expense')" class="rounded-lg bg-red-600 px-5 py-2 text-white transition hover:bg-red-700">
                <i class="fas fa-minus mr-2"></i>Tambah Uang Keluar
            </button>
        </div>
    <?php endif; ?>
</div>

<div class="mb-8 grid grid-cols-1 gap-5 md:grid-cols-3">
    <div class="rounded-xl border-l-4 border-blue-600 bg-white p-5 shadow-sm">
        <p class="mb-2 text-sm text-gray-500">Uang Tersedia</p>
        <p class="text-2xl font-bold text-blue-700">Rp <?php echo number_format($availableAmount, 0, ',', '.'); ?></p>
        <p class="mt-1 text-xs text-gray-500">Total masuk dikurangi total keluar</p>
    </div>
    <div class="rounded-xl border-l-4 border-green-600 bg-white p-5 shadow-sm">
        <p class="mb-2 text-sm text-gray-500">Total Uang Masuk</p>
        <p class="text-2xl font-bold finance-income">Rp <?php echo number_format((float) $settings['total_income'], 0, ',', '.'); ?></p>
    </div>
    <div class="rounded-xl border-l-4 border-red-600 bg-white p-5 shadow-sm">
        <p class="mb-2 text-sm text-gray-500">Total Uang Keluar</p>
        <p class="text-2xl font-bold finance-expense">Rp <?php echo number_format((float) $settings['total_expense'], 0, ',', '.'); ?></p>
    </div>
</div>

<div class="mb-8 rounded-xl bg-white p-6 shadow-sm">
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Ringkasan Finance</h3>
            <p class="text-sm text-gray-500">Total uang masuk dan keluar dapat diisi manual. Uang tersedia dihitung otomatis.</p>
        </div>
        <i class="fas fa-wallet text-2xl text-gray-400"></i>
    </div>

    <form method="POST" action="finance.php" class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <input type="hidden" name="action" value="update_summary">
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Total Uang Masuk</label>
            <input type="number" name="total_income" min="0" step="0.01" value="<?php echo htmlspecialchars($settings['total_income']); ?>" <?php echo !$canManage ? 'disabled' : ''; ?> class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-500">
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Total Uang Keluar</label>
            <input type="number" name="total_expense" min="0" step="0.01" value="<?php echo htmlspecialchars($settings['total_expense']); ?>" <?php echo !$canManage ? 'disabled' : ''; ?> class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-500">
        </div>
        <?php if ($canManage): ?>
            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="rounded-lg bg-gray-900 px-6 py-2 text-white transition hover:bg-gray-700">
                    <i class="fas fa-save mr-2"></i>Simpan Ringkasan
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<div class="mb-6 rounded-xl bg-white p-6 shadow-sm">
    <form method="GET" action="finance.php" class="flex flex-col gap-4 md:flex-row md:items-end">
        <div class="flex-1">
            <label class="mb-2 block text-sm font-medium text-gray-700">Bulan</label>
            <select name="month" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-500">
                <option value="">Semua Bulan</option>
                <?php
                $months = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];
                foreach ($months as $monthNumber => $monthName):
                ?>
                    <option value="<?php echo $monthNumber; ?>" <?php echo (string) $filterMonth === (string) $monthNumber ? 'selected' : ''; ?>><?php echo $monthName; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex-1">
            <label class="mb-2 block text-sm font-medium text-gray-700">Tahun</label>
            <select name="year" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-500">
                <option value="">Semua Tahun</option>
                <?php for ($year = (int) date('Y') + 1; $year >= (int) date('Y') - 5; $year--): ?>
                    <option value="<?php echo $year; ?>" <?php echo (string) $filterYear === (string) $year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-gray-900 px-6 py-2 text-white transition hover:bg-gray-700">
            <i class="fas fa-filter mr-2"></i>Filter
        </button>
        <?php if ($filterMonth !== '' || $filterYear !== ''): ?>
            <a href="finance.php" class="rounded-lg bg-gray-200 px-6 py-2 text-center text-gray-700 transition hover:bg-gray-300">Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="overflow-hidden rounded-xl bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b p-6 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Riwayat Transaksi</h3>
            <p class="text-sm text-gray-500">Daftar uang masuk dan uang keluar sesuai filter</p>
        </div>
        <span class="text-sm text-gray-500"><?php echo count($transactions); ?> transaksi</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nama Transaksi</th>
                    <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Jenis</th>
                    <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Metode</th>
                    <th class="px-6 py-4 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Rupiah</th>
                    <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Catatan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if ($transactions): ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <?php $isIncome = $transaction['transaction_type'] === 'income'; ?>
                        <?php $typeClass = $isIncome ? 'finance-income' : 'finance-expense'; ?>
                        <?php $rowClass = $isIncome ? 'finance-income-bg' : 'finance-expense-bg'; ?>
                        <tr class="<?php echo $rowClass; ?> hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <p class="font-medium <?php echo $typeClass; ?>"><?php echo htmlspecialchars($transaction['transaction_name']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold <?php echo $isIncome ? 'bg-green-100 finance-income' : 'bg-red-100 finance-expense'; ?>">
                                    <?php echo $isIncome ? 'Uang Masuk' : 'Uang Keluar'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($transaction['method']); ?></td>
                            <td class="px-6 py-4 text-right font-semibold <?php echo $typeClass; ?>">Rp <?php echo number_format((float) $transaction['amount'], 0, ',', '.'); ?></td>
                            <td class="max-w-sm px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($transaction['notes'] ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-receipt mb-3 text-4xl text-gray-300"></i>
                            <p>Belum ada transaksi Finance.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($canManage): ?>
<div id="transactionModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b p-6">
            <h3 id="transactionModalTitle" class="text-xl font-bold text-gray-800">Tambah Transaksi</h3>
            <button type="button" onclick="closeModal('transactionModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="finance.php" class="space-y-4 p-6">
            <input type="hidden" name="action" value="add_transaction">
            <input type="hidden" name="transaction_type" id="transactionType" value="income">
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Nama Transaksi *</label>
                <input type="text" name="transaction_name" required maxlength="150" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-500" placeholder="Contoh: Pembayaran pelanggan">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Metode *</label>
                <input type="text" name="method" required maxlength="80" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-500" placeholder="Cash, Transfer, QRIS, atau lainnya">
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Rupiah *</label>
                    <input type="number" name="amount" required min="0.01" step="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-500" placeholder="0">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Tanggal *</label>
                    <input type="date" name="transaction_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Catatan</label>
                <textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-500" placeholder="Tambahkan catatan transaksi"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal('transactionModal')" class="rounded-lg border px-5 py-2 text-gray-700 hover:bg-gray-50">Batal</button>
                <button id="transactionSubmitButton" type="submit" class="rounded-lg bg-green-600 px-5 py-2 text-white hover:bg-green-700">Simpan Uang Masuk</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openTransactionModal(type) {
        const isIncome = type === 'income';
        document.getElementById('transactionType').value = type;
        document.getElementById('transactionModalTitle').textContent = isIncome ? 'Tambah Uang Masuk' : 'Tambah Uang Keluar';

        const submitButton = document.getElementById('transactionSubmitButton');
        submitButton.textContent = isIncome ? 'Simpan Uang Masuk' : 'Simpan Uang Keluar';
        submitButton.className = isIncome
            ? 'rounded-lg bg-green-600 px-5 py-2 text-white hover:bg-green-700'
            : 'rounded-lg bg-red-600 px-5 py-2 text-white hover:bg-red-700';

        openModal('transactionModal');
    }
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
