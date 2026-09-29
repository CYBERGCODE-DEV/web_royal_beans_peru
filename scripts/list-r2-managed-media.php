<?php
declare(strict_types=1);

$config = require dirname(__DIR__) . '/public/cms-config/database.php';

try {
    $db = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']),
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );
    $paths = $db->query("SELECT DISTINCT local_path FROM media_aliases WHERE local_path LIKE '/%' AND remote_path REGEXP '^https?://' ORDER BY local_path")->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(array_values($paths), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    fwrite(STDERR, 'No se pudo obtener el inventario R2: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
