<?php
require_once 'config.php';
require_once 'includes/auth.php';

$auth->requireLogin();
$user = $auth->getCurrentUser();
$db = Database::getInstance();
$canViewDocuments = $auth->canViewDocuments();
$canManageDocuments = $auth->canManageDocuments();

if (!isset($_SESSION['documents_csrf_token'])) {
    $_SESSION['documents_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_document') {
    if (!$canManageDocuments) {
        $_SESSION['error'] = 'Anda tidak memiliki izin untuk mengunggah dokumen.';
        header('Location: dashboard.php');
        exit;
    }

    if (!hash_equals($_SESSION['documents_csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Permintaan tidak valid. Silakan coba lagi.';
        header('Location: dashboard.php');
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $file = $_FILES['document_file'] ?? null;
    $uploadError = null;

    if ($title === '' || strlen($title) > 200) {
        $uploadError = 'Judul wajib diisi dan maksimal 200 karakter.';
    } elseif (strlen($description) > 5000) {
        $uploadError = 'Deskripsi maksimal 5.000 karakter.';
    } elseif (!$file || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $uploadError = 'Pilih file yang akan diunggah.';
    } elseif (!isset($file['size']) || $file['size'] > DOCUMENT_MAX_FILE_SIZE) {
        $uploadError = 'Ukuran file maksimal 10 MB.';
    }

    $extension = '';
    $mimeType = '';
    if ($uploadError === null) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, DOCUMENT_ALLOWED_EXTENSIONS, true)) {
            $uploadError = 'Format file tidak didukung.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
            if ($finfo) {
                finfo_close($finfo);
            }

            $allowedMimeTypes = [
                'pdf' => ['application/pdf'],
                'doc' => ['application/msword', 'application/octet-stream'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
                'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
                'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
                'ppt' => ['application/vnd.ms-powerpoint', 'application/octet-stream'],
                'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
                'txt' => ['text/plain'],
                'csv' => ['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/octet-stream'],
                'zip' => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png' => ['image/png']
            ];

            if (!in_array($mimeType, $allowedMimeTypes[$extension] ?? [], true)) {
                $uploadError = 'Isi file tidak sesuai dengan format yang dipilih.';
            }
        }
    }

    if ($uploadError !== null) {
        $_SESSION['error'] = $uploadError;
        header('Location: dashboard.php');
        exit;
    }

    $documentDirectory = __DIR__ . '/uploads/documents/';
    if (!is_dir($documentDirectory) && !mkdir($documentDirectory, 0750, true)) {
        $_SESSION['error'] = 'Folder penyimpanan dokumen tidak dapat dibuat.';
        header('Location: dashboard.php');
        exit;
    }

    $storedFilename = bin2hex(random_bytes(16)) . '.' . $extension;
    $storedPath = $documentDirectory . $storedFilename;

    if (!move_uploaded_file($file['tmp_name'], $storedPath)) {
        $_SESSION['error'] = 'File gagal disimpan. Pastikan folder upload dapat ditulis.';
        header('Location: dashboard.php');
        exit;
    }

    try {
        $documentId = $db->insert('documents', [
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'original_filename' => basename($file['name']),
            'stored_filename' => $storedFilename,
            'mime_type' => $mimeType,
            'file_size' => (int) $file['size'],
            'uploaded_by' => $user['id']
        ]);

        $auth->logActivity('uploaded', 'documents', $documentId, null, [
            'title' => $title,
            'original_filename' => basename($file['name']),
            'file_size' => (int) $file['size']
        ]);
        $_SESSION['success'] = 'Dokumen berhasil diunggah.';
    } catch (Exception $e) {
        if (is_file($storedPath)) {
            unlink($storedPath);
        }
        $_SESSION['error'] = 'Dokumen gagal disimpan ke database.';
    }

    header('Location: dashboard.php');
    exit;
}

$documents = [];
$documentsTableAvailable = true;
if ($canViewDocuments) {
    try {
        $documents = $db->fetchAll("SELECT d.*, u.full_name AS uploaded_by_name
            FROM documents d
            LEFT JOIN users u ON u.id = d.uploaded_by
            ORDER BY d.created_at DESC");
    } catch (Exception $e) {
        $documentsTableAvailable = false;
    }
}

$pageTitle = 'Dashboard';
require_once 'includes/header.php';

// Get statistics
$totalItems = $db->fetchOne("SELECT COUNT(*) as count FROM items")['count'];
$totalCategories = $db->fetchOne("SELECT COUNT(*) as count FROM categories")['count'];
$totalLocations = $db->fetchOne("SELECT COUNT(*) as count FROM locations")['count'];
$totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];

// Get items by status
$itemsByStatus = $db->fetchAll("SELECT status, COUNT(*) as count FROM items GROUP BY status");
$statusData = [];
foreach ($itemsByStatus as $row) {
    $statusData[$row['status']] = $row['count'];
}

// Get recent activities
$recentActivities = $db->fetchAll("
    SELECT al.*, u.full_name 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");

// Get low stock items (quantity < 5)
$lowStockItems = $db->fetchAll("
    SELECT i.*, c.category_name 
    FROM items i 
    LEFT JOIN categories c ON i.category_id = c.id 
    WHERE i.quantity < 5 
    ORDER BY i.quantity ASC 
    LIMIT 5
");

// Get items by location
$itemsByLocation = $db->fetchAll("
    SELECT l.location_name, l.location_code, COUNT(i.id) as item_count 
    FROM locations l 
    LEFT JOIN items i ON l.id = i.location_id 
    GROUP BY l.id 
    ORDER BY item_count DESC 
    LIMIT 5
");

// Get recent items
$recentItems = $db->fetchAll("
    SELECT i.*, c.category_name 
    FROM items i 
    LEFT JOIN categories c ON i.category_id = c.id 
    ORDER BY i.created_at DESC 
    LIMIT 5
");
?>

<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
    <p class="text-gray-600">Selamat datang kembali, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
</div>

<?php if ($canViewDocuments): ?>
<section class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div>
            <h3 class="text-xl font-bold text-gray-800"><i class="fas fa-folder-open text-gray-700 mr-2"></i>Dokumen Informasi</h3>
            <p class="text-gray-500 text-sm">Baca deskripsi dan unduh dokumen yang dibagikan oleh admin.</p>
        </div>
        <span class="inline-flex items-center gap-2 self-start px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 text-sm">
            <i class="fas fa-file-alt"></i><?php echo count($documents); ?> dokumen
        </span>
    </div>

    <?php if (!$documentsTableAvailable): ?>
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl px-4 py-3">
            <i class="fas fa-info-circle mr-2"></i>
            Fitur dokumen belum aktif. Jalankan migrasi <code>migrate_add_documents.php</code> terlebih dahulu.
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 <?php echo $canManageDocuments ? 'xl:grid-cols-3' : ''; ?> gap-6">
            <?php if ($canManageDocuments): ?>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-start gap-3 mb-5">
                        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-cloud-upload-alt text-gray-700"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Upload Dokumen</h4>
                            <p class="text-sm text-gray-500">Bagikan file untuk dibaca dan diunduh staff.</p>
                        </div>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="upload_document">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['documents_csrf_token']); ?>">
                        <div>
                            <label for="document-title" class="block text-sm font-medium text-gray-700 mb-1">Judul <span class="text-red-600">*</span></label>
                            <input id="document-title" type="text" name="title" maxlength="200" required class="w-full px-3 py-2 border rounded-lg" placeholder="Contoh: SOP Operasional">
                        </div>
                        <div>
                            <label for="document-description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                            <textarea id="document-description" name="description" rows="4" maxlength="5000" class="w-full px-3 py-2 border rounded-lg resize-y" placeholder="Jelaskan isi atau kegunaan file"></textarea>
                        </div>
                        <div>
                            <label for="document-file" class="block text-sm font-medium text-gray-700 mb-1">File <span class="text-red-600">*</span></label>
                            <input id="document-file" type="file" name="document_file" required class="w-full px-3 py-2 border rounded-lg bg-white" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.jpg,.jpeg,.png">
                            <p class="text-xs text-gray-500 mt-1">PDF, Word, Excel, PowerPoint, TXT, CSV, ZIP, JPG, JPEG, PNG. Maks. 10 MB.</p>
                        </div>
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-gray-800 text-white hover:bg-gray-700 transition">
                            <i class="fas fa-upload"></i> Upload File
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm p-6 <?php echo $canManageDocuments ? 'xl:col-span-2' : ''; ?>">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-semibold text-gray-800">Daftar Dokumen</h4>
                    <span class="text-xs text-gray-500">Terbaru di atas</span>
                </div>
                <?php if (count($documents) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500">
                                    <th class="px-3 py-3 font-semibold">Judul</th>
                                    <th class="px-3 py-3 font-semibold">Deskripsi</th>
                                    <?php if ($canManageDocuments): ?>
                                        <th class="px-3 py-3 font-semibold">File</th>
                                        <th class="px-3 py-3 font-semibold">Tanggal</th>
                                    <?php endif; ?>
                                    <th class="px-3 py-3 font-semibold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($documents as $document): ?>
                                    <tr class="align-top hover:bg-gray-50">
                                        <td class="px-3 py-4 min-w-[170px]">
                                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($document['title']); ?></p>
                                        </td>
                                        <td class="px-3 py-4 min-w-[220px] max-w-md">
                                            <p class="text-sm text-gray-600 whitespace-pre-line"><?php echo htmlspecialchars($document['description'] ?: 'Tidak ada deskripsi.'); ?></p>
                                        </td>
                                        <?php if ($canManageDocuments): ?>
                                            <td class="px-3 py-4 min-w-[180px]">
                                                <p class="text-sm text-gray-700 break-all"><?php echo htmlspecialchars($document['original_filename']); ?></p>
                                                <p class="text-xs text-gray-500 mt-1"><?php echo number_format(((int) $document['file_size']) / 1048576, 2); ?> MB</p>
                                            </td>
                                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('d M Y H:i', strtotime($document['created_at'])); ?>
                                            </td>
                                        <?php endif; ?>
                                        <td class="px-3 py-4 text-right whitespace-nowrap">
                                            <a href="document_download.php?id=<?php echo (int) $document['id']; ?>" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-800 text-white text-sm hover:bg-gray-700 transition" title="Download dokumen">
                                                <i class="fas fa-download"></i><span>Download</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-10 text-gray-500">
                        <i class="fas fa-folder-open text-3xl text-gray-300 mb-3"></i>
                        <p>Belum ada dokumen yang diunggah.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Barang</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($totalItems); ?></p>
            </div>
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-box text-gray-800 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Kategori</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($totalCategories); ?></p>
            </div>
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-tags text-gray-800 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-gray-600">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Lokasi</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($totalLocations); ?></p>
            </div>
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-map-marker-alt text-gray-800 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-gray-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Stok Rendah</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo count($lowStockItems); ?></p>
            </div>
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-gray-800 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Status Overview -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Status Barang</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Tersedia</span>
                <div class="flex items-center space-x-2">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div class="bg-gray-800 h-2 rounded-full" style="width: <?php echo $totalItems > 0 ? (($statusData['available'] ?? 0) / $totalItems * 100) : 0; ?>%"></div>
                    </div>
                    <span class="text-sm font-medium"><?php echo $statusData['available'] ?? 0; ?></span>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Sedang Digunakan</span>
                <div class="flex items-center space-x-2">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div class="bg-gray-700 h-2 rounded-full" style="width: <?php echo $totalItems > 0 ? (($statusData['in_use'] ?? 0) / $totalItems * 100) : 0; ?>%"></div>
                    </div>
                    <span class="text-sm font-medium"><?php echo $statusData['in_use'] ?? 0; ?></span>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Maintenance</span>
                <div class="flex items-center space-x-2">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div class="bg-gray-500 h-2 rounded-full" style="width: <?php echo $totalItems > 0 ? (($statusData['maintenance'] ?? 0) / $totalItems * 100) : 0; ?>%"></div>
                    </div>
                    <span class="text-sm font-medium"><?php echo $statusData['maintenance'] ?? 0; ?></span>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Rusak</span>
                <div class="flex items-center space-x-2">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div class="bg-gray-600 h-2 rounded-full" style="width: <?php echo $totalItems > 0 ? (($statusData['broken'] ?? 0) / $totalItems * 100) : 0; ?>%"></div>
                    </div>
                    <span class="text-sm font-medium"><?php echo $statusData['broken'] ?? 0; ?></span>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Hilang</span>
                <div class="flex items-center space-x-2">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div class="bg-gray-500 h-2 rounded-full" style="width: <?php echo $totalItems > 0 ? (($statusData['lost'] ?? 0) / $totalItems * 100) : 0; ?>%"></div>
                    </div>
                    <span class="text-sm font-medium"><?php echo $statusData['lost'] ?? 0; ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Stok Rendah</h3>
        <?php if (count($lowStockItems) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($lowStockItems as $item): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-100 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($item['item_name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($item['category_name'] ?? '-'); ?></p>
                        </div>
                        <span class="px-3 py-1 bg-gray-200 text-gray-800 rounded-full text-sm font-medium">
                            <?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Tidak ada barang dengan stok rendah</p>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Items and Activities -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Barang Terbaru</h3>
            <?php if ($auth->hasPermission('items.view')): ?>
                <a href="items.php" class="text-gray-800 hover:text-gray-600 text-sm font-semibold">Lihat Semua</a>
            <?php endif; ?>
        </div>
        <?php if (count($recentItems) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($recentItems as $item): ?>
                    <div class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-box text-gray-800"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($item['item_name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($item['item_code']); ?></p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 bg-gray-200 text-gray-800 rounded text-xs">
                                <?php echo htmlspecialchars($item['status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Belum ada barang</p>
        <?php endif; ?>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Barang per Lokasi</h3>
            <?php if ($auth->hasPermission('locations.view')): ?>
                <a href="locations.php" class="text-gray-800 hover:text-gray-600 text-sm font-semibold">Lihat Semua</a>
            <?php endif; ?>
        </div>
        <?php if (count($itemsByLocation) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($itemsByLocation as $loc): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($loc['location_name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($loc['location_code']); ?></p>
                        </div>
                        <span class="px-3 py-1 bg-gray-200 text-gray-800 rounded-full text-sm font-medium">
                            <?php echo $loc['item_count']; ?> barang
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Belum ada lokasi</p>
        <?php endif; ?>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Aktivitas Terbaru</h3>
            <?php if ($auth->hasPermission('logs.view')): ?>
                <a href="logs.php" class="text-gray-800 hover:text-gray-600 text-sm font-semibold">Lihat Semua</a>
            <?php endif; ?>
        </div>
        <?php if (count($recentActivities) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($recentActivities as $activity): ?>
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-history text-gray-800 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-800">
                                <strong><?php echo htmlspecialchars($activity['full_name'] ?? 'System'); ?></strong>
                                <?php echo htmlspecialchars($activity['action']); ?>
                                <?php if ($activity['table_name']): ?>
                                    <?php echo htmlspecialchars($activity['table_name']); ?>
                                <?php endif; ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php echo date('d M Y H:i', strtotime($activity['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Belum ada aktivitas</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
