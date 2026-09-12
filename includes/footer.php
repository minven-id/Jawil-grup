            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden" onclick="toggleSidebar()"></div>
    
    <!-- Mobile Sidebar -->
    <div id="mobileSidebar" class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg z-50 transform -translate-x-full transition-transform md:hidden">
        <div class="p-6 border-b flex items-center justify-between">
            <div class="flex items-center space-x-3" title="Jawil Group">
                <img src="includes/logo%20jawil.jpg" alt="Jawil Group" class="sidebar-brand-logo">
            </div>
            <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <nav class="p-4 space-y-2">
            <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-home w-5"></i>
                <span>Dashboard</span>
            </a>
            
            <?php if ($auth->hasPermission('items.view') || $auth->hasPermission('items.manage')): ?>
            <a href="items.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-box w-5"></i>
                <span>Barang</span>
            </a>
            <?php endif; ?>
            
            <?php if ($auth->hasPermission('categories.view') || $auth->hasPermission('categories.manage')): ?>
            <a href="categories.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-tags w-5"></i>
                <span>Kategori</span>
            </a>
            <?php endif; ?>
            
            <?php if ($auth->hasPermission('locations.view') || $auth->hasPermission('locations.manage')): ?>
            <a href="locations.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-map-marker-alt w-5"></i>
                <span>Lokasi</span>
            </a>
            <?php endif; ?>
            
            <?php if ($auth->hasPermission('roles.manage')): ?>
            <a href="roles.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-user-shield w-5"></i>
                <span>Role</span>
            </a>
            <?php endif; ?>
            
            <?php if ($auth->hasPermission('users.manage')): ?>
            <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-users w-5"></i>
                <span>Pengguna</span>
            </a>
            <?php endif; ?>
            
            <?php if ($auth->hasPermission('logs.view')): ?>
            <a href="logs.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-history w-5"></i>
                <span>Aktivitas</span>
            </a>
            <?php endif; ?>
        </nav>
        
        <div class="absolute bottom-0 w-64 p-4 border-t bg-white">
            <a href="logout.php" class="flex items-center justify-center space-x-2 w-full px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        function toggleDesktopSidebar() {
            const sidebar = document.getElementById('desktopSidebar');
            if (!sidebar) return;

            sidebar.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('sidebar-collapsed') ? '1' : '0');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('desktopSidebar');
            if (sidebar && localStorage.getItem('sidebarCollapsed') === '1') {
                sidebar.classList.add('sidebar-collapsed');
            }
        });
    </script>
</body>
</html>
