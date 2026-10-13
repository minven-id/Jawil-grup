<?php
require_once 'config.php';
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance();
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = '';
$reset = null;

if ($token !== '') {
    $reset = $db->fetchOne(
        "SELECT pr.id, pr.user_id, u.full_name
         FROM password_resets pr
         JOIN users u ON u.id = pr.user_id
         WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at >= NOW() AND u.is_active = 1",
        [hash('sha256', $token)]
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';

    if (!$reset) {
        $error = 'Link reset password tidak valid atau sudah kedaluwarsa.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $passwordConfirmation) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $db->update('users', ['password' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$reset['user_id']]);
        $db->update('password_resets', ['used_at' => date('Y-m-d H:i:s')], 'user_id = ? AND used_at IS NULL', [$reset['user_id']]);
        $success = 'Password berhasil diubah. Silakan login menggunakan password baru.';
        $reset = null;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="includes/jawil-favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --maroon: #741f2b; }
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
        <h1 class="text-center text-xl font-bold text-gray-900">Buat Password Baru</h1>

        <?php if ($error): ?><div class="my-4 rounded-lg border border-red-400 bg-red-50 px-3 py-2 font-sans text-sm text-red-700"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="my-4 rounded-lg border border-green-400 bg-green-50 px-3 py-2 font-sans text-sm text-green-800"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

        <?php if ($reset): ?>
            <p class="mt-2 mb-5 text-center font-sans text-sm text-gray-700">Password untuk akun <?php echo htmlspecialchars($reset['full_name']); ?></p>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="relative"><i class="fas fa-lock icon"></i><input class="input" type="password" name="password" placeholder="Password baru" minlength="6" required></div>
                <div class="relative"><i class="fas fa-check icon"></i><input class="input" type="password" name="password_confirmation" placeholder="Konfirmasi password" minlength="6" required></div>
                <button class="button" type="submit">SIMPAN PASSWORD</button>
            </form>
        <?php elseif (!$success): ?>
            <p class="my-5 text-center font-sans text-sm text-red-700">Link reset password tidak valid atau sudah kedaluwarsa.</p>
        <?php endif; ?>
        <a href="login.php" class="mt-4 block text-center font-sans text-xs font-semibold text-[#741f2b] underline"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Login</a>
    </main>
</div>
</body>
</html>
