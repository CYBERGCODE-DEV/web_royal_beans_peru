<?php
declare(strict_types=1);

$config = [
    'host' => getenv('RB_DB_HOST') ?: 'localhost',
    'port' => getenv('RB_DB_PORT') ?: '3306',
    'database' => getenv('RB_DB_NAME') ?: '',
    'username' => getenv('RB_DB_USER') ?: '',
    'password' => getenv('RB_DB_PASSWORD') ?: '',
];
$local = __DIR__ . '/database.local.php';
if (is_file($local)) {
    $saved = require $local;
    if (is_array($saved)) $config = array_merge($config, $saved);
}
return $config;
