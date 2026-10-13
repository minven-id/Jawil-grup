<?php
require_once 'config.php';
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = trim($_POST['employee_id'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';

    if ($employeeId === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Silakan isi kode pegawai dan email yang valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($password !== $passwordConfirmation) {
        $error = 'Konfirmasi password baru tidak sama.';
    } else {
        $account = $db->fetchOne(
            "SELECT id, full_name FROM users WHERE employee_id = ? AND LOWER(email) = ? AND is_active = 1",
            [$employeeId, $email]
        );

        if (!$account) {
            // Jangan membocorkan apakah kode pegawai dan email terdaftar.
            $message = 'Jika data tersebut terdaftar, permintaan reset password akan dikirim ke admin.';
        } else {
            try {
                $pendingRequest = $db->fetchOne(
                    "SELECT id FROM password_reset_requests WHERE user_id = ? AND status = 'pending'",
                    [$account['id']]
                );

                if ($pendingRequest) {
                    $error = 'Permintaan reset password Anda masih menunggu persetujuan admin.';
                } else {
                    $db->insert('password_reset_requests', [
                        'user_id' => $account['id'],
                        'requested_password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'status' => 'pending'
                    ]);
                    $message = 'Permintaan reset password berhasil dikirim ke admin. Password baru aktif setelah disetujui.';
                    $_POST = [];
                }
            } catch (Exception $e) {
                $error = 'Fitur permintaan reset password belum aktif. Import file migrate_add_account_recovery.sql melalui phpMyAdmin terlebih dahulu.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Reset Password - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="includes/jawil-favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --maroon: #741f2b; --ink: #111111; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: Georgia, "Times New Roman", serif; background: linear-gradient(115deg, rgba(17,17,17,.42), rgba(116,31,43,.24)), url('includes/jawil.jpg') left bottom / cover no-repeat, #111; }
        .shell { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .card { width: 100%; max-width: 390px; padding: 1.4rem; border: 1px solid rgba(255,255,255,.7); border-radius: 1.35rem; background: rgba(224,224,221,.78); box-shadow: 0 25px 70px rgba(0,0,0,.4); backdrop-filter: blur(12px); }
        .logo { display: block; width: min(260px, 75vw); height: 92px; object-fit: cover; margin: 0 auto 1rem; filter: invert(1); mix-blend-mode: screen; }
        .input { width: 100%; height: 44px; border: 1px solid rgba(0,0,0,.12); border-radius: .65rem; background: rgba(247,247,246,.9); padding: .62rem 1rem .62rem 2.45rem; font-family: ui-sans-serif, system-ui, sans-serif; font-size: .86rem; }
        .input:focus { outline: none; border-color: var(--maroon); box-shadow: 0 0 0 3px rgba(116,31,43,.13); }
        .icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #777; }
        .button { width: 100%; border: 0; border-radius: .65rem; background: #2d2927; color: white; padding: .78rem 1rem; font-family: ui-sans-serif, system-ui, sans-serif; font-weight: 700; cursor: pointer; }
        .button:hover { background: var(--maroon); }
    </style>
</head>
<body>
<div class="shell">
    <main class="card">
        <img src="includes/jawil-logo-transparent.png" alt="Jawil Group" class="logo">
        <h1 class="text-center text-xl font-bold text-gray-900">Lupa Password?</h1>
        <p class="mt-2 mb-5 text-center font-sans text-sm text-gray-700">Isi data akun dan password baru. Permintaan akan ditinjau admin.</p>

        <?php if ($error): ?>
            <div class="mb-4 rounded-lg border border-red-400 bg-red-50 px-3 py-2 font-sans text-sm text-red-700"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="mb-4 rounded-lg border border-green-400 bg-green-50 px-3 py-2 font-sans text-sm text-green-800"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="POST" class="space-y-3">
            <div class="relative"><i class="fas fa-id-badge icon"></i><input class="input" type="text" name="employee_id" placeholder="Kode Pegawai" required value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>"></div>
            <div class="relative"><i class="fas fa-envelope icon"></i><input class="input" type="email" name="email" placeholder="Email terdaftar" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"></div>
            <div class="relative"><i class="fas fa-lock icon"></i><input class="input" type="password" name="password" placeholder="Password baru (minimal 6 karakter)" minlength="6" required></div>
            <div class="relative"><i class="fas fa-check icon"></i><input class="input" type="password" name="password_confirmation" placeholder="Konfirmasi password baru" minlength="6" required></div>
            <button class="button" type="submit">KIRIM PERMINTAAN KE ADMIN</button>
        </form>
        <a href="login.php" class="mt-4 block text-center font-sans text-xs font-semibold text-[#741f2b] underline"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Login</a>
    </main>
</div>
</body>
</html>
