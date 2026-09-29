<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') rb_json(['error' => 'Method not allowed'], 405);
$locale = ($_GET['lang'] ?? 'es') === 'en' ? 'en' : 'es';
try {
    $column = $locale === 'en' ? 'alt_en' : 'alt_es';
    $statement = rb_pdo()->query("SELECT id,image_path,$column AS alt_text FROM home_announcements WHERE is_active=1 AND image_path<>'' AND (is_permanent=1 OR expires_at>UTC_TIMESTAMP()) ORDER BY sort_order,id");
    rb_json(['items' => $statement->fetchAll()]);
} catch (Throwable) {
    rb_json(['error' => 'Announcements unavailable'], 503);
}
