<?php
// ===== Global Config =====
$_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Derive base path from this file's location relative to document root
// __DIR__ = .../htdocs/prototype/includes  → project root = .../htdocs/prototype
$_docRoot  = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '/');
$_projRoot = rtrim(str_replace('\\', '/', realpath(dirname(__DIR__))), '/');
$_basePath = str_ireplace($_docRoot, '', $_projRoot);

define('BASE_URL', $_protocol . '://' . $_host . $_basePath);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');
define('ALLOWED_TYPES', ['pdf','docx','xlsx','jpg','jpeg','png','mp4']);
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // 20 MB

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
