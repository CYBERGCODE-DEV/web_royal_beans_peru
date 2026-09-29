<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
$user = admin_user();
if (!$user) {
    session_write_close();
    http_response_code(401);
    echo '{"ok":false}';
    exit;
}

session_write_close();
echo '{"ok":true}';
