<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

$auth->requireLogin();
$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="includes/jawil-favicon.png">
    <link rel="apple-touch-icon" href="includes/jawil-favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --maroon: #741f2b;
            --maroon-dark: #591620;
            --ink: #111111;
            --paper: #ffffff;
            --muted-paper: #f5f5f5;
        }

        body {
            background-color: var(--muted-paper);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        aside {
            background-color: var(--ink) !important;
            color: white;
        }

        aside .border-b,
        aside .border-t {
            border-color: #2d2d2d !important;
        }

        aside h1,
        aside p,
        aside .text-gray-500,
        aside .text-gray-700 {
            color: white !important;
        }

        aside > div:last-child {
            background-color: var(--ink) !important;
        }

        aside > div:last-child a {
            background-color: var(--maroon) !important;
            color: white !important;
        }

        aside .bg-gradient-to-r,
        #mobileSidebar .bg-gradient-to-r {
            background-image: linear-gradient(135deg, var(--maroon), var(--maroon-dark)) !important;
        }

        .sidebar-link {
            color: #d4d4d4 !important;
        }

        .sidebar-link.active {
            background: var(--maroon) !important;
            color: white !important;
        }

        .sidebar-link:hover:not(.active) {
            background-color: #292929 !important;
            color: white !important;
        }

        header {
            background-color: var(--paper) !important;
            border-bottom: 1px solid #e5e5e5;
        }

        #mobileSidebar {
            background-color: var(--ink) !important;
            color: white;
        }

        #mobileSidebar .border-b,
        #mobileSidebar .border-t {
            border-color: #2d2d2d !important;
        }

        #mobileSidebar h1,
        #mobileSidebar a,
        #mobileSidebar button,
        #mobileSidebar .text-gray-700,
        #mobileSidebar .text-gray-600,
        #mobileSidebar .text-gray-800 {
            color: white !important;
        }

        #mobileSidebar a:hover {
            background-color: #292929 !important;
        }

        #mobileSidebar > div:last-child {
            background-color: var(--ink) !important;
        }

        /* Shared visual system for every authenticated page. */
        main {
            color: var(--ink);
        }

        main .bg-white {
            border: 1px solid #e8e8e8;
            box-shadow: 0 8px 24px rgba(17, 17, 17, 0.05) !important;
        }

        main h1,
        main h2,
        main h3,
        main h4 {
            letter-spacing: -0.02em;
        }

        main input,
        main select,
        main textarea {
            border-color: #d4d4d4 !important;
            border-radius: 0.55rem;
        }

        main input:focus,
        main select:focus,
        main textarea:focus {
            border-color: var(--maroon) !important;
            box-shadow: 0 0 0 3px rgba(116, 31, 43, 0.12) !important;
            outline: none;
        }

        main button:not([type="reset"]):not([class*="bg-gray"]),
        main a[class*="bg-indigo-"],
        main a[class*="bg-purple-"],
        main a[class*="bg-blue-"],
        main a[class*="bg-green-"],
        main a[class*="bg-red-"] {
            background-color: var(--maroon) !important;
            background-image: none !important;
            border-color: var(--maroon) !important;
            color: white !important;
        }

        main button:not([type="reset"]):not([class*="bg-gray"]):hover,
        main a[class*="bg-indigo-"]:hover,
        main a[class*="bg-purple-"]:hover,
        main a[class*="bg-blue-"]:hover,
        main a[class*="bg-green-"]:hover,
        main a[class*="bg-red-"]:hover {
            background-color: var(--maroon-dark) !important;
        }

        main [class*="from-purple-"],
        main [class*="from-indigo-"],
        main [class*="to-purple-"],
        main [class*="to-indigo-"] {
            background-image: linear-gradient(135deg, var(--maroon), var(--maroon-dark)) !important;
        }

        main [class*="text-purple-"],
        main [class*="text-indigo-"],
        main [class*="text-blue-"],
        main [class*="text-green-"],
        main [class*="text-orange-"],
        main [class*="text-yellow-"],
        main [class*="text-red-"] {
            color: var(--maroon) !important;
        }

        main [class*="bg-purple-100"],
        main [class*="bg-indigo-50"],
        main [class*="bg-indigo-100"],
        main [class*="bg-blue-50"],
        main [class*="bg-blue-100"],
        main [class*="bg-green-50"],
        main [class*="bg-green-100"],
        main [class*="bg-orange-100"],
        main [class*="bg-yellow-100"],
        main [class*="bg-red-50"],
        main [class*="bg-red-100"] {
            background-color: #f1f1f1 !important;
        }

        main [class*="border-purple-"],
        main [class*="border-indigo-"],
        main [class*="border-blue-"],
        main [class*="border-green-"],
        main [class*="border-orange-"],
        main [class*="border-yellow-"],
        main [class*="border-red-"] {
            border-color: var(--maroon) !important;
        }

        main [class*="from-purple-600"],
        main [class*="from-indigo-600"] {
            background-color: var(--maroon) !important;
        }

        /* Maroon is reserved for primary actions; status colors remain meaningful. */
        button[class*="bg-indigo-600"],
        button[class*="bg-purple-600"],
        button[class*="bg-blue-600"],
        a[class*="bg-indigo-600"],
        a[class*="bg-purple-600"],
        a[class*="bg-blue-600"],
        [class*="from-purple-600"] {
            background-color: var(--maroon) !important;
            background-image: none !important;
        }

        button[class*="bg-indigo-600"]:hover,
        button[class*="bg-purple-600"]:hover,
        button[class*="bg-blue-600"]:hover,
        a[class*="bg-indigo-600"]:hover,
        a[class*="bg-purple-600"]:hover,
        a[class*="bg-blue-600"]:hover,
        [class*="from-purple-600"]:hover {
            background-color: var(--maroon-dark) !important;
        }

        #desktopSidebar {
            width: 16rem;
            transition: width 0.25s ease;
        }

        #desktopSidebar.sidebar-collapsed {
            width: 5rem;
        }

        #desktopSidebar.sidebar-collapsed .sidebar-brand-text,
        #desktopSidebar.sidebar-collapsed .sidebar-user-info,
        #desktopSidebar.sidebar-collapsed .sidebar-link span,
        #desktopSidebar.sidebar-collapsed .sidebar-logout span {
            display: none;
        }

        #desktopSidebar.sidebar-collapsed .sidebar-link,
        #desktopSidebar.sidebar-collapsed .sidebar-logout {
            justify-content: center;
        }

        #desktopSidebar.sidebar-collapsed > div:last-child {
            width: 5rem;
        }

        #desktopSidebar.sidebar-collapsed .sidebar-user {
            justify-content: center;
        }

        #desktopSidebar.sidebar-collapsed .sidebar-toggle-icon {
            transform: rotate(180deg);
        }

        .sidebar-brand-logo {
            display: block;
            width: 140px;
            height: 52px;
            object-fit: cover;
            object-position: center;
            filter: invert(1);
            mix-blend-mode: screen;
            background: transparent;
        }

        #desktopSidebar.sidebar-collapsed .sidebar-brand-logo {
            width: 42px;
            height: 42px;
        }

        @media (max-width: 767px) {
            #desktopSidebar {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="desktopSidebar" class="w-64 bg-white shadow-lg flex-shrink-0 hidden md:block">
            <div class="p-6 border-b">
                <div class="flex items-center space-x-3" title="Jawil Group">
                    <img src="includes/logo%20jawil.jpg" alt="Jawil Group" class="sidebar-brand-logo">
                </div>
            </div>

            <nav class="p-4 space-y-2">
                <?php
                $accessibleMenus = $auth->getAccessibleMenus();
                foreach ($accessibleMenus as $menu):
                ?>
                <a href="<?php echo $menu['menu_url']; ?>" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 transition <?php echo basename($_SERVER['PHP_SELF']) == basename($menu['menu_url']) ? 'active' : ''; ?>">
                    <i class="fas <?php echo $menu['menu_icon']; ?> w-5"></i>
                    <span><?php echo $menu['menu_label']; ?></span>
                </a>
                <?php endforeach; ?>
            </nav>
            
            <div class="absolute bottom-0 w-64 p-4 border-t bg-white">
                <div class="sidebar-user flex items-center space-x-3 mb-4">
                    <?php if (!empty($user['profile_photo'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Foto profil" class="w-10 h-10 rounded-full object-cover border-2 border-white">
                    <?php else: ?>
                        <div class="w-10 h-10 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="sidebar-user-info flex-1">
                        <p class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['role_name']); ?></p>
                    </div>
                </div>
                <a href="logout.php" class="sidebar-logout flex items-center justify-center space-x-2 w-full px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <button class="hidden md:inline-flex text-gray-600 hover:text-gray-800 p-2 rounded-lg hover:bg-gray-100" onclick="toggleDesktopSidebar()" title="Toggle sidebar" aria-label="Toggle sidebar">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <button class="md:hidden text-gray-600 hover:text-gray-800" onclick="toggleSidebar()">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
                
                <div class="flex-1"></div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <i class="fas fa-bell text-gray-400 hover:text-gray-600 cursor-pointer"></i>
                        <span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
