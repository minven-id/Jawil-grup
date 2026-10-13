<?php
require_once 'config.php';
require_once 'includes/auth.php';

$auth->requireLogin();
if (!$auth->canViewDocuments()) {
    $_SESSION['error'] = 'Anda tidak memiliki izin untuk mengunduh dokumen.';
    header('Location: dashboard.php');
    exit;
}

$documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$documentId || $documentId < 1) {
    $_SESSION['error'] = 'Dokumen tidak ditemukan.';
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance();
$document = $db->fetchOne('SELECT * FROM documents WHERE id = ?', [$documentId]);
if (!$document) {
    $_SESSION['error'] = 'Dokumen tidak ditemukan.';
    header('Location: dashboard.php');
    exit;
}

$storedFilename = basename($document['stored_filename']);
$filePath = __DIR__ . '/uploads/documents/' . $storedFilename;
if (!is_file($filePath) || !is_readable($filePath)) {
    $_SESSION['error'] = 'File dokumen tidak tersedia.';
    header('Location: dashboard.php');
    exit;
}

$auth->logActivity('downloaded', 'documents', $documentId, null, [
    'title' => $document['title'],
    'original_filename' => $document['original_filename']
]);

$downloadName = basename($document['original_filename']);
$downloadName = preg_replace('/[\\r\\n"]/', '_', $downloadName) ?: 'document';
header('Content-Description: File Transfer');
header('Content-Type: ' . ($document['mime_type'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . addslashes($downloadName) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
readfile($filePath);
exit;
