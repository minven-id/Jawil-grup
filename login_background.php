<?php
$pageTitle = 'Background Login';
require_once 'config.php';
require_once 'includes/auth.php';

$auth->requireLogin();

if (!$auth->hasPermission('settings.manage') && !$auth->hasPermission('users.manage')) {
    $_SESSION['error'] = 'Anda tidak memiliki izin untuk mengubah background login.';
    header('Location: dashboard.php');
    exit;
}

$backgroundDir = __DIR__ . '/includes';
$allowedImageTypes = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG => 'png',
    IMAGETYPE_WEBP => 'webp',
    IMAGETYPE_GIF => 'gif'
];
$backgroundFiles = [];

foreach ($allowedImageTypes as $extension) {
    $file = $backgroundDir . '/login-background.' . $extension;
    if (is_file($file)) {
        $backgroundFiles[] = $file;
    }
}

$currentBackgroundFile = $backgroundFiles[0] ?? null;
$csrfToken = $_SESSION['login_background_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['login_background_csrf'] = $csrfToken;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrfToken, $postedToken)) {
        $_SESSION['error'] = 'Sesi formulir sudah kedaluwarsa. Silakan coba lagi.';
    } elseif (empty($_FILES['background_image']) || $_FILES['background_image']['error'] === UPLOAD_ERR_NO_FILE) {
        $_SESSION['error'] = 'Silakan pilih file gambar terlebih dahulu.';
    } else {
        $file = $_FILES['background_image'];
        $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($uploadError !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Gambar gagal diunggah. Silakan coba lagi.';
        } elseif (($file['size'] ?? 0) > MAX_FILE_SIZE) {
            $_SESSION['error'] = 'Ukuran gambar maksimal ' . number_format(MAX_FILE_SIZE / 1048576, 0) . ' MB.';
        } else {
            $imageInfo = @getimagesize($file['tmp_name']);
            $extension = $imageInfo && isset($allowedImageTypes[$imageInfo[2]])
                ? $allowedImageTypes[$imageInfo[2]]
                : null;

            if (!$imageInfo || !$extension) {
                $_SESSION['error'] = 'Format gambar harus JPG, PNG, WEBP, atau GIF.';
            } else {
                $temporaryFile = $backgroundDir . '/.login-background-' . bin2hex(random_bytes(12)) . '.' . $extension;
                $targetFile = $backgroundDir . '/login-background.' . $extension;

                if (!move_uploaded_file($file['tmp_name'], $temporaryFile)) {
                    $_SESSION['error'] = 'Gambar gagal disimpan. Pastikan folder includes dapat ditulis.';
                } else {
                    foreach ($allowedImageTypes as $oldExtension) {
                        $oldFile = $backgroundDir . '/login-background.' . $oldExtension;
                        if (is_file($oldFile)) {
                            @unlink($oldFile);
                        }
                    }

                    if (!@rename($temporaryFile, $targetFile)) {
                        @unlink($temporaryFile);
                        $_SESSION['error'] = 'Background gagal diperbarui. Pastikan folder includes dapat ditulis.';
                    } else {
                        $auth->logActivity('updated', 'settings', null, null, [
                            'setting' => 'login_background',
                            'filename' => basename($targetFile)
                        ]);
                        $_SESSION['success'] = 'Background login berhasil diperbarui.';
                    }
                }
            }
        }
    }

    unset($_SESSION['login_background_csrf']);
    header('Location: login_background.php');
    exit;
}

$currentBackgroundUrl = null;
if ($currentBackgroundFile) {
    $currentBackgroundUrl = 'includes/' . rawurlencode(basename($currentBackgroundFile));
    $modifiedAt = @filemtime($currentBackgroundFile);
    if ($modifiedAt) {
        $currentBackgroundUrl .= '?v=' . $modifiedAt;
    }
}

require_once 'includes/header.php';
?>

<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-800">Background Login</h2>
    <p class="text-gray-600">Ganti gambar latar belakang halaman login.</p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <section class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Upload Background Baru</h3>
        <p class="text-sm text-gray-500 mb-6">
            Format yang didukung: JPG, PNG, WEBP, atau GIF. Ukuran maksimal
            <?php echo number_format(MAX_FILE_SIZE / 1048576, 0); ?> MB.
        </p>

        <form method="POST" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <label for="background_image" class="block">
                <span class="block text-sm font-medium text-gray-700 mb-2">Pilih gambar</span>
                <input
                    type="file"
                    id="background_image"
                    name="background_image"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    required
                    class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#741f2b]"
                >
            </label>

            <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 text-sm text-gray-600">
                <i class="fas fa-info-circle text-[#741f2b] mr-2"></i>
                Foto akan memenuhi layar login menggunakan mode <strong>cover</strong>.
            </div>

            <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-lg bg-[#741f2b] text-white font-semibold hover:bg-[#591620] transition">
                <i class="fas fa-upload mr-2"></i>
                Simpan Background
            </button>
        </form>
    </section>

    <section class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Background Saat Ini</h3>
            <?php if ($currentBackgroundFile): ?>
                <span class="text-xs px-2.5 py-1 rounded-full bg-green-100 text-green-800">Custom</span>
            <?php else: ?>
                <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">Default</span>
            <?php endif; ?>
        </div>

        <?php if ($currentBackgroundUrl): ?>
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-900">
                <img src="<?php echo htmlspecialchars($currentBackgroundUrl); ?>" alt="Background login saat ini" class="w-full h-72 object-cover">
            </div>
            <p class="mt-3 text-xs text-gray-500 break-all">
                File aktif: <?php echo htmlspecialchars(basename($currentBackgroundFile)); ?>
            </p>
        <?php else: ?>
            <div class="h-72 rounded-xl bg-gradient-to-br from-gray-900 to-[#741f2b] flex items-center justify-center text-white">
                <div class="text-center">
                    <i class="fas fa-image text-4xl mb-3"></i>
                    <p>Belum ada background custom</p>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
