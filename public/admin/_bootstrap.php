<?php
declare(strict_types=1);

session_name('ROYALBEANS_ADMIN');
session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']), 'samesite' => 'Lax', 'path' => '/admin']);
session_start();
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function admin_configured(): bool { return is_file(dirname(__DIR__) . '/cms-config/database.local.php') || (bool) getenv('RB_DB_NAME'); }
function admin_db(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $config = require dirname(__DIR__) . '/cms-config/database.php';
    if (empty($config['database']) || empty($config['username'])) throw new RuntimeException('CMS no instalado');
    $pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']), $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    return $pdo;
}
function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(24)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) throw new RuntimeException('La sesión del formulario expiró. Recarga la página.'); }
function admin_user(): ?array { return isset($_SESSION['admin']) && is_array($_SESSION['admin']) ? $_SESSION['admin'] : null; }
function require_admin(): array { $user = admin_user(); if (!$user) { header('Location: /admin/?view=login'); exit; } return $user; }
function admin_slug(string $value): string { $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($value)) ?: $value; return trim(strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii)), '-') ?: 'producto'; }
function flash(string $type, string $message): void { $_SESSION['flash'] = [$type, $message]; }
function pull_flash(): ?array { $value = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $value; }
function redirect_admin(string $view): never { header('Location: /admin/?view=' . rawurlencode($view)); exit; }
function audit(PDO $db, int $userId, string $action, string $entity, string|int $entityId = '', array $details = []): void {
    $s = $db->prepare('INSERT INTO audit_log (user_id,action,entity_type,entity_id,details_json) VALUES (?,?,?,?,?)');
    $s->execute([$userId,$action,$entity,(string) $entityId,$details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null]);
}
function save_upload(PDO $db, array $file, int $userId): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 8 * 1024 * 1024) throw new RuntimeException('La imagen no es válida o supera 8 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/avif' => 'avif'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Formato permitido: JPG, PNG, WebP o AVIF.');
    $folder = '/uploads/' . date('Y/m');
    $absolute = dirname(__DIR__) . $folder;
    if (!is_dir($absolute) && !mkdir($absolute, 0755, true) && !is_dir($absolute)) throw new RuntimeException('No se pudo crear la carpeta de imágenes.');
    $name = bin2hex(random_bytes(14)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $absolute . '/' . $name)) throw new RuntimeException('No se pudo guardar la imagen.');
    $path = $folder . '/' . $name;
    $s = $db->prepare('INSERT INTO media (path,original_name,mime_type,size_bytes,uploaded_by) VALUES (?,?,?,?,?)');
    $s->execute([$path,mb_substr((string) $file['name'],0,255),$mime,(int) $file['size'],$userId]);
    return $path;
}
