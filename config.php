<?php
// Configuration file for jawil_grup application

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jawil_grup');

// Application configuration
define('APP_NAME', 'Jawil Grup');
define('APP_URL', 'http://localhost/jawil_grup');
define('SESSION_NAME', 'jawil_grup_session');

// Password recovery email settings. Configure SMTP/PHP mail on production.
define('PASSWORD_RESET_FROM', 'no-reply@jawilgrup.com');
define('PASSWORD_RESET_EXPIRY', 3600); // 1 hour
// Keep true only for local XAMPP development without an SMTP service.
define('SHOW_RESET_LINK_ON_MAIL_FAILURE', true);

// Security
define('HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_LIFETIME', 3600); // 1 hour

// File upload settings
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
define('DOCUMENT_MAX_FILE_SIZE', 10485760); // 10MB in bytes
define('DOCUMENT_ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'jpg', 'jpeg', 'png']);

// Pagination
define('ITEMS_PER_PAGE', 10);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Jakarta');
?>
