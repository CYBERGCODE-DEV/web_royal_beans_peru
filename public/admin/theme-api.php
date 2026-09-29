<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
$user = require_permission('settings', ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' ? 'view' : 'edit');
$db = admin_db();
header('Content-Type: application/json; charset=utf-8');
$respond = static function (array $data, int $status = 200): never {
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};
$options = [
    'theme_palette' => ['royal', 'harvest', 'pacific'],
    'theme_font' => ['bricolage-dm', 'editorial', 'modern'],
    'theme_background' => ['paper', 'white', 'mist'],
];
try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        verify_csrf();
        $statement = $db->prepare('INSERT INTO settings (setting_key,value_text,is_public) VALUES (?,?,1) ON DUPLICATE KEY UPDATE value_text=VALUES(value_text),is_public=1');
        foreach ($options as $key => $allowed) {
            $value = (string) ($_POST[$key] ?? '');
            if (!in_array($value, $allowed, true)) throw new RuntimeException('La configuración visual seleccionada no es válida.');
            $statement->execute([$key, $value]);
        }
        audit($db, (int) $user['id'], 'edit', 'theme');
        mark_public_content_changed('settings');
    }
    $theme = [];
    $query = $db->query("SELECT setting_key,value_text FROM settings WHERE setting_key IN ('theme_palette','theme_font','theme_background')");
    foreach ($query as $row) $theme[$row['setting_key']] = $row['value_text'];
    $csrf = csrf_token();
    $respond(['ok' => true, 'theme' => $theme, 'csrf' => $csrf]);
} catch (Throwable $error) { $respond(['error' => $error->getMessage()], 422); }
