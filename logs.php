<?php
$pageTitle = 'Log Aktivitas';
require_once 'includes/header.php';

$auth->requirePermission('logs.view');

$db = Database::getInstance();

// Get filter parameters
$search = $_GET['search'] ?? '';
$actionFilter = $_GET['action'] ?? '';
$tableFilter = $_GET['table'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query
$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(u.full_name LIKE ? OR al.action LIKE ? OR al.table_name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($actionFilter) {
    $where[] = "al.action = ?";
    $params[] = $actionFilter;
}

if ($tableFilter) {
    $where[] = "al.table_name = ?";
    $params[] = $tableFilter;
}

if ($dateFrom) {
    $where[] = "DATE(al.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = "DATE(al.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = implode(' AND ', $where);

// Get logs with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * ITEMS_PER_PAGE;

$logs = $db->fetchAll("
    SELECT al.*, u.full_name, u.username 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    WHERE $whereClause
    ORDER BY al.created_at DESC 
    LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset
", $params);

// Get total count
$totalLogs = $db->fetchOne("
    SELECT COUNT(*) as count 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    WHERE $whereClause
", $params)['count'];

$totalPages = ceil($totalLogs / ITEMS_PER_PAGE);

// Get unique actions and tables for filters
$actions = $db->fetchAll("SELECT DISTINCT action FROM activity_logs ORDER BY action");
$tables = $db->fetchAll("SELECT DISTINCT table_name FROM activity_logs WHERE table_name IS NOT NULL ORDER BY table_name");
?>

<div class="mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Log Aktivitas</h2>
        <p class="text-gray-600">Riwayat aktivitas pengguna dalam sistem</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form method="GET" action="" class="flex flex-col lg:flex-row gap-4">
        <div class="flex-1">
            <input type="text" name="search" placeholder="Cari aktivitas..." 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div>
            <select name="action" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <option value="">Semua Aksi</option>
                <?php foreach ($actions as $act): ?>
                    <option value="<?php echo htmlspecialchars($act['action']); ?>" <?php echo $actionFilter == $act['action'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($act['action']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <select name="table" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <option value="">Semua Tabel</option>
                <?php foreach ($tables as $tbl): ?>
                    <option value="<?php echo htmlspecialchars($tbl['table_name']); ?>" <?php echo $tableFilter == $tbl['table_name'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tbl['table_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <input type="date" name="date_from" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="Dari tanggal">
        </div>
        <div>
            <input type="date" name="date_to" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="Sampai tanggal">
        </div>
        <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
            <i class="fas fa-search mr-2"></i>
            Filter
        </button>
        <?php if ($search || $actionFilter || $tableFilter || $dateFrom || $dateTo): ?>
            <a href="logs.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-times mr-2"></i>
                Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Logs Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tabel</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($logs) > 0): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d M Y H:i:s', strtotime($log['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-full flex items-center justify-center text-white text-sm font-bold mr-3">
                                        <?php echo strtoupper(substr($log['full_name'] ?? 'S', 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></p>
                                        <p class="text-xs text-gray-500">@<?php echo htmlspecialchars($log['username'] ?? '-'); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $actionColors = [
                                    'created' => 'bg-green-100 text-green-800',
                                    'updated' => 'bg-blue-100 text-blue-800',
                                    'deleted' => 'bg-red-100 text-red-800',
                                    'login' => 'bg-purple-100 text-purple-800',
                                    'logout' => 'bg-gray-100 text-gray-800'
                                ];
                                $color = $actionColors[$log['action']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $color; ?>">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $log['table_name'] ? htmlspecialchars($log['table_name']) : '-'; ?>
                                <?php if ($log['record_id']): ?>
                                    <span class="text-gray-400">#<?php echo $log['record_id']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button onclick="showDetail(<?php echo $log['id']; ?>)" class="text-purple-600 hover:text-purple-800" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-history text-4xl mb-4"></i>
                            <p>Tidak ada aktivitas ditemukan</p>
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
                Menampilkan <?php echo ($offset + 1); ?> - <?php echo min($offset + ITEMS_PER_PAGE, $totalLogs); ?> dari <?php echo $totalLogs; ?> aktivitas
            </p>
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo $actionFilter; ?>&table=<?php echo $tableFilter; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>" 
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo $actionFilter; ?>&table=<?php echo $tableFilter; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>" 
                        class="px-4 py-2 border rounded-lg <?php echo $i == $page ? 'bg-purple-600 text-white' : 'hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo $actionFilter; ?>&table=<?php echo $tableFilter; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>" 
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Detail Modal -->
<div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Detail Aktivitas</h3>
            <button onclick="closeModal('detailModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="detailContent" class="p-6">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<script>
    function showDetail(logId) {
        // In a real application, you would fetch this via AJAX
        // For now, we'll show a simple message
        const logs = <?php echo json_encode($logs); ?>;
        const log = logs.find(l => l.id === logId);
        
        if (log) {
            let content = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Waktu</p>
                            <p class="font-medium">${log.created_at}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Aksi</p>
                            <p class="font-medium">${log.action}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Pengguna</p>
                            <p class="font-medium">${log.full_name || 'System'}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Tabel</p>
                            <p class="font-medium">${log.table_name || '-'}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">IP Address</p>
                            <p class="font-medium">${log.ip_address || '-'}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">User Agent</p>
                            <p class="font-medium text-xs">${log.user_agent || '-'}</p>
                        </div>
                    </div>
            `;
            
            if (log.old_values) {
                content += `
                    <div>
                        <p class="text-sm text-gray-500 mb-2">Nilai Lama</p>
                        <pre class="bg-gray-100 p-4 rounded-lg overflow-x-auto text-xs">${log.old_values}</pre>
                    </div>
                `;
            }
            
            if (log.new_values) {
                content += `
                    <div>
                        <p class="text-sm text-gray-500 mb-2">Nilai Baru</p>
                        <pre class="bg-gray-100 p-4 rounded-lg overflow-x-auto text-xs">${log.new_values}</pre>
                    </div>
                `;
            }
            
            content += '</div>';
            document.getElementById('detailContent').innerHTML = content;
            openModal('detailModal');
        }
    }
    
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
</script>

<?php require_once 'includes/footer.php'; ?>
