<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

$documentRoot = is_file(dirname(__DIR__) . '/public/cms-config/database.php') ? dirname(__DIR__) . '/public' : dirname(__DIR__);
$config = require $documentRoot . '/cms-config/database.php';
if (empty($config['database']) || empty($config['username'])) throw new RuntimeException('CMS not configured');
$db = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']),
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]
);
require_once $documentRoot . '/cms-config/migrate.php';
require_once $documentRoot . '/cms-config/seo-migrate.php';
migrate_cms($db);
migrate_catalog_seo($db);
fwrite(STDOUT, "Migrations complete.\n");
