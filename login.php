<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = trim($_POST['employee_id'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($employeeId) || empty($password)) {
        $error = 'Silakan masukkan kode pegawai dan password.';
    } else {
        if ($auth->login($employeeId, $password)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Kode pegawai atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="includes/jawil-favicon.png">
    <link rel="apple-touch-icon" href="includes/jawil-favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --maroon: #741f2b;
            --maroon-dark: #591620;
            --ink: #111111;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: Georgia, "Times New Roman", serif;
            background:
                linear-gradient(115deg, rgba(17, 17, 17, .38), rgba(116, 31, 43, .22)),
                url('includes/jawil.jpg') left bottom / cover no-repeat,
                linear-gradient(135deg, #111111, var(--maroon));
        }

        .login-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.15rem;
            padding: 2rem 1rem 1.5rem;
        }

        .brand-panel {
            width: min(360px, 86vw);
            color: white;
            text-align: center;
        }

        .brand-logo {
            display: block;
            width: min(340px, 86vw);
            height: 132px;
            object-fit: cover;
            object-position: center;
            margin: 0 auto;
            filter: invert(1);
            mix-blend-mode: screen;
            background: transparent;
        }

        .brand-name {
            font-size: clamp(4.5rem, 12vw, 8rem);
            line-height: .75;
            letter-spacing: -.09em;
            margin-right: .7rem;
        }

        .brand-subtitle {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .8rem;
            margin-top: 1.2rem;
            font-size: 1.05rem;
            letter-spacing: .18em;
        }

        .brand-subtitle::before,
        .brand-subtitle::after {
            content: "";
            width: 3.5rem;
            height: 1px;
            background: rgba(255,255,255,.9);
        }

        .brand-caption {
            display: none;
        }

        .login-card {
            width: 100%;
            max-width: 356px;
            padding: 1.25rem 1.35rem 1.05rem;
            border: 1px solid rgba(255,255,255,.7);
            border-radius: 1.35rem;
            background: rgba(224, 224, 221, .62);
            box-shadow: 0 25px 70px rgba(0,0,0,.4);
            backdrop-filter: blur(12px);
        }

        .login-card h1 {
            display: none;
        }

        .login-card .intro {
            display: none;
        }

        .field-label {
            display: none;
        }

        .login-input {
            width: 100%;
            height: 44px;
            border: 1px solid rgba(0,0,0,.12);
            border-radius: .65rem;
            background: rgba(247,247,246,.82);
            padding: .62rem 1rem .62rem 2.45rem;
            color: var(--ink);
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: .82rem;
            transition: .2s ease;
        }

        .login-input::placeholder {
            color: #888;
            opacity: 1;
        }

        .login-input:focus {
            border-color: var(--maroon);
            box-shadow: 0 0 0 3px rgba(116,31,43,.13);
            outline: none;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }

        .password-toggle {
            position: absolute;
            right: .95rem;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #777;
            cursor: pointer;
        }

        .login-button {
            width: 100%;
            border: 0;
            border-radius: .65rem;
            background: #2d2927;
            color: white;
            padding: .78rem 1rem;
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .04em;
            cursor: pointer;
            transition: .2s ease;
        }

        .login-button:hover { background: var(--maroon); transform: translateY(-1px); }

        @media (max-width: 420px) {
            .login-shell { gap: 1rem; padding: 1rem; }
            .brand-name { font-size: 4.2rem; }
            .brand-logo { height: 112px; }
            .login-card { padding: 1.15rem 1.1rem .95rem; }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <section class="brand-panel" aria-label="Jawil Group">
            <?php if (file_exists(__DIR__ . '/includes/logo jawil.jpg')): ?>
                <img src="includes/logo%20jawil.jpg" alt="Jawil Group" class="brand-logo">
            <?php else: ?>
                <div class="brand-name">Jawil</div>
                <div class="brand-subtitle">GROUP</div>
            <?php endif; ?>
            <div class="brand-caption">Inventory Management System</div>
        </section>

        <div class="login-card">
            <div class="mb-3">
                <h1>Login</h1>
                <p class="intro">Masuk ke akun Anda untuk melanjutkan</p>
            </div>

        <?php if ($error): ?>
            <div class="mb-6 rounded-lg border border-[#741f2b] bg-white px-4 py-3 font-sans text-sm text-[#741f2b]">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-3">
            <div>
                <label for="employee_id" class="field-label">Kode Pegawai</label>
                <div class="relative">
                    <i class="fas fa-id-badge input-icon"></i>
                    <input type="text" id="employee_id" name="employee_id" required
                        class="login-input"
                        placeholder="Kode Pegawai"
                        value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>">
                </div>
            </div>

            <div>
                <label for="password" class="field-label">Password</label>
                <div class="relative">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" required
                        class="login-input"
                        style="padding-right: 2.8rem"
                        placeholder="Password">
                    <button type="button" onclick="togglePassword()" class="password-toggle" aria-label="Tampilkan password">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-button">
                LOGIN
            </button>
        </form>

        <div class="mt-2 flex justify-center gap-4 font-sans text-[10px] text-gray-700 underline">
            <a href="#">Forgot Password?</a>
            <a href="#">Need an account?</a>
        </div>
    </div>
        <div class="font-sans text-[10px] tracking-wide text-white/80">powered by <strong class="font-semibold text-white">Minven.Id</strong></div>
    </div>
</div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
