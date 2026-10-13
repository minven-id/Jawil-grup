<?php
require_once 'config.php';
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = trim($_POST['employee_id'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $department = trim($_POST['department'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if ($employeeId === '' || $fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Kode pegawai, nama lengkap, dan email valid wajib diisi.';
    } else {
        $existingUser = $db->fetchOne('SELECT id FROM users WHERE employee_id = ? OR LOWER(email) = ?', [$employeeId, $email]);
        $pendingRequest = $db->fetchOne(
            "SELECT id FROM account_requests WHERE (employee_id = ? OR LOWER(email) = ?) AND status = 'pending'",
            [$employeeId, $email]
        );

        if ($existingUser) {
            $error = 'Kode pegawai atau email tersebut sudah terdaftar. Silakan gunakan menu Lupa Password jika Anda sudah memiliki akun.';
        } elseif ($pendingRequest) {
            $error = 'Permintaan akun dengan kode pegawai atau email tersebut sedang diproses.';
        } else {
            try {
                $db->insert('account_requests', [
                    'employee_id' => $employeeId,
                    'full_name' => $fullName,
                    'email' => $email,
                    'department' => $department !== '' ? $department : null,
                    'reason' => $reason !== '' ? $reason : null,
                    'status' => 'pending'
                ]);
                $success = 'Permintaan akun berhasil dikirim. Admin akan meninjau dan membuatkan akun Anda.';
                $_POST = [];
            } catch (Exception $e) {
                $error = 'Fitur permintaan akun belum aktif. Import file migrate_add_account_recovery.sql melalui phpMyAdmin terlebih dahulu.';
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
    <title>Butuh Akun? - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="includes/jawil-favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --maroon: #741f2b; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: Georgia, "Times New Roman", serif; background: linear-gradient(115deg, rgba(17,17,17,.42), rgba(116,31,43,.24)), url('includes/jawil.jpg') left bottom / cover no-repeat, #111; }
        .shell { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .card { width: 100%; max-width: 460px; padding: 1.4rem; border: 1px solid rgba(255,255,255,.7); border-radius: 1.35rem; background: rgba(224,224,221,.82); box-shadow: 0 25px 70px rgba(0,0,0,.4); backdrop-filter: blur(12px); }
        .logo { display: block; width: min(250px, 72vw); height: 84px; object-fit: cover; margin: 0 auto 1rem; filter: invert(1); mix-blend-mode: screen; }
        .input, .textarea { width: 100%; border: 1px solid rgba(0,0,0,.12); border-radius: .65rem; background: rgba(247,247,246,.9); padding: .65rem .85rem; font-family: ui-sans-serif, system-ui, sans-serif; font-size: .86rem; }
        .input { height: 42px; }
        .textarea { min-height: 72px; resize: vertical; }
        .input:focus, .textarea:focus { outline: none; border-color: var(--maroon); box-shadow: 0 0 0 3px rgba(116,31,43,.13); }
        .button { width: 100%; border: 0; border-radius: .65rem; background: #2d2927; color: white; padding: .78rem 1rem; font-family: ui-sans-serif, system-ui, sans-serif; font-weight: 700; cursor: pointer; }
        .button:hover { background: var(--maroon); }
        label { display: block; margin-bottom: .3rem; font-family: ui-sans-serif, system-ui, sans-serif; font-size: .75rem; font-weight: 600; color: #333; }
    </style>
</head>
<body>
<div class="shell">
    <main class="card">
        <img src="includes/jawil-logo-transparent.png" alt="Jawil Group" class="logo">
        <h1 class="text-center text-xl font-bold text-gray-900">Butuh Akun?</h1>
        <p class="mt-2 mb-5 text-center font-sans text-sm text-gray-700">Ajukan pembuatan akun kepada admin.</p>

        <?php if ($error): ?><div class="mb-4 rounded-lg border border-red-400 bg-red-50 px-3 py-2 font-sans text-sm text-red-700"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="mb-4 rounded-lg border border-green-400 bg-green-50 px-3 py-2 font-sans text-sm text-green-800"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

        <form method="POST" class="space-y-3">
            <div><label for="employee_id">Kode Pegawai *</label><input id="employee_id" class="input" type="text" name="employee_id" maxlength="50" required value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>"></div>
            <div><label for="full_name">Nama Lengkap *</label><input id="full_name" class="input" type="text" name="full_name" maxlength="100" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"></div>
            <div><label for="email">Email *</label><input id="email" class="input" type="email" name="email" maxlength="100" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"></div>
            <div><label for="department">Departemen / Bagian</label><input id="department" class="input" type="text" name="department" maxlength="100" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>"></div>
            <div><label for="reason">Keterangan</label><textarea id="reason" class="textarea" name="reason" maxlength="500" placeholder="Jelaskan kebutuhan akses Anda"><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea></div>
            <button class="button" type="submit">KIRIM PERMINTAAN</button>
        </form>
        <a href="login.php" class="mt-4 block text-center font-sans text-xs font-semibold text-[#741f2b] underline"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Login</a>
    </main>
</div>
</body>
</html>
