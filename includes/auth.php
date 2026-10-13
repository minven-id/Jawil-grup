<?php
// Authentication and authorization functions
require_once __DIR__ . '/db.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function login($employeeId, $password) {
        $sql = "SELECT u.*, r.role_name, r.permissions 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.employee_id = ? AND u.is_active = 1";
        
        $user = $this->db->fetchOne($sql, [$employeeId]);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['employee_id'] = $user['employee_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['profile_photo'] = $user['profile_photo'] ?? null;
            $_SESSION['email'] = $user['email'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['permissions'] = json_decode($user['permissions'], true);
            $_SESSION['last_activity'] = time();
            
            // Log the login activity
            $this->logActivity('login', 'users', $user['id']);
            
            return true;
        }
        
        return false;
    }

    public function logout() {
        if ($this->isLoggedIn()) {
            $this->logActivity('logout', 'users', $_SESSION['user_id']);
        }
        
        session_unset();
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['last_activity']);
    }

    public function checkSessionTimeout() {
        if ($this->isLoggedIn()) {
            if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
                $this->logout();
                return false;
            }
            $_SESSION['last_activity'] = time();
        }
        return true;
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            if (!headers_sent()) {
                header('Location: login.php');
                exit;
            } else {
                die('<script>window.location.href="login.php";</script>');
            }
        }
        $this->checkSessionTimeout();
    }

    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $permissions = $_SESSION['permissions'] ?? [];
        return in_array($permission, $permissions) || in_array('*', $permissions);
    }

    public function canViewDocuments() {
        $roleName = $_SESSION['role_name'] ?? '';
        return $this->hasPermission('documents.view') || in_array($roleName, ['Super Admin', 'Admin', 'Staff'], true);
    }

    public function canManageDocuments() {
        $roleName = $_SESSION['role_name'] ?? '';
        return $this->hasPermission('documents.manage') || in_array($roleName, ['Super Admin', 'Admin'], true);
    }

    public function requirePermission($permission, $menuName = null) {
        $this->requireLogin();
        
        // Check permission OR menu access (if menuName provided)
        $hasAccess = $this->hasPermission($permission);
        
        if ($menuName !== null) {
            $hasAccess = $hasAccess || $this->hasMenuAccess($menuName);
        }
        
        if (!$hasAccess) {
            if (!headers_sent()) {
                $_SESSION['error'] = 'You do not have permission to access this page.';
                header('Location: dashboard.php');
                exit;
            } else {
                die('<script>window.location.href="dashboard.php";</script>');
            }
        }
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'employee_id' => $_SESSION['employee_id'] ?? null,
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'profile_photo' => $_SESSION['profile_photo'] ?? null,
            'email' => $_SESSION['email'],
            'role_id' => $_SESSION['role_id'],
            'role_name' => $_SESSION['role_name'],
            'permissions' => $_SESSION['permissions']
        ];
    }

    public function logActivity($action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
        if (!$this->isLoggedIn()) {
            return;
        }

        $data = [
            'user_id' => $_SESSION['user_id'],
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];

        $this->db->insert('activity_logs', $data);
    }

    public function isSuperAdmin() {
        return $_SESSION['role_name'] === 'Super Admin';
    }

    public function hasMenuAccess($menuName) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $roleId = $_SESSION['role_id'];
        $db = Database::getInstance();

        try {
            $sql = "SELECT rma.can_view 
                    FROM role_menu_access rma
                    JOIN menus m ON rma.menu_id = m.id
                    WHERE rma.role_id = ? AND m.menu_name = ? AND rma.can_view = 1";

            $result = $db->fetchOne($sql, [$roleId, $menuName]);

            return $result !== false;
        } catch (Exception $e) {
            // Fallback: if role_menu_access table doesn't exist, allow access for Super Admin
            return $this->isSuperAdmin();
        }
    }

    public function getAccessibleMenus() {
        if (!$this->isLoggedIn()) {
            return [];
        }

        $roleId = $_SESSION['role_id'];
        $db = Database::getInstance();

        try {
            $sql = "SELECT m.*
                    FROM menus m
                    JOIN role_menu_access rma ON m.id = rma.menu_id
                    WHERE rma.role_id = ? AND rma.can_view = 1 AND m.is_active = 1
                    ORDER BY m.menu_order";

            $menus = $db->fetchAll($sql, [$roleId]);
            
            // If no menus found and user is Super Admin, return all active menus
            if (empty($menus) && $this->isSuperAdmin()) {
                return $db->fetchAll("SELECT * FROM menus WHERE is_active = 1 ORDER BY menu_order");
            }
            
            return $menus;
        } catch (Exception $e) {
            // Fallback: if tables don't exist yet, return all menus for Super Admin
            if ($this->isSuperAdmin()) {
                try {
                    return $db->fetchAll("SELECT * FROM menus WHERE is_active = 1 ORDER BY menu_order");
                } catch (Exception $e2) {
                    // Final fallback to hardcoded menus
                    return [
                        ['menu_name' => 'dashboard', 'menu_label' => 'Dashboard', 'menu_icon' => 'fa-home', 'menu_url' => 'dashboard.php'],
                        ['menu_name' => 'items', 'menu_label' => 'Barang', 'menu_icon' => 'fa-box', 'menu_url' => 'items.php'],
                        ['menu_name' => 'categories', 'menu_label' => 'Kategori', 'menu_icon' => 'fa-tags', 'menu_url' => 'categories.php'],
                        ['menu_name' => 'locations', 'menu_label' => 'Lokasi', 'menu_icon' => 'fa-map-marker-alt', 'menu_url' => 'locations.php'],
                        ['menu_name' => 'roles', 'menu_label' => 'Role', 'menu_icon' => 'fa-user-shield', 'menu_url' => 'roles.php'],
                        ['menu_name' => 'users', 'menu_label' => 'Pengguna', 'menu_icon' => 'fa-users', 'menu_url' => 'users.php'],
                        ['menu_name' => 'logs', 'menu_label' => 'Aktivitas', 'menu_icon' => 'fa-history', 'menu_url' => 'logs.php']
                    ];
                }
            }
            
            // For non-super admins, return empty array if tables don't exist
            return [];
        }
    }
}

// Initialize Auth instance
$auth = new Auth();
?>
