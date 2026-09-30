<?php
declare(strict_types=1);

require dirname(__DIR__) . '/public/admin/_bootstrap.php';
require_once dirname(__DIR__) . '/public/cms-config/migrate.php';

if (!function_exists('restore_known_media_aliases') || !function_exists('repair_known_media_references')) {
    throw new RuntimeException('Las funciones del escaneo no están disponibles.');
}

$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$columns = [];
foreach (media_reference_columns() as [$table, $column]) $columns[$table][] = $column;
foreach ($columns as $table => $tableColumns) {
    $db->exec('CREATE TABLE ' . $table . ' (' . implode(',', array_map(static fn(string $column): string => $column . ' TEXT', $tableColumns)) . ')');
    foreach ($tableColumns as $column) {
        $insert = $db->prepare('INSERT INTO ' . $table . ' (' . $column . ') VALUES (?)');
        $insert->execute(['https://example.com/old.webp']);
    }
}
$db->exec('CREATE TABLE media (path TEXT)');
$db->exec("INSERT INTO media(path) VALUES ('https://example.com/old.webp')");
$changed = replace_media_references($db, 'https://example.com/old.webp', 'https://example.com/new.webp');
if ($changed !== count(media_reference_columns())) throw new RuntimeException('No se actualizaron todas las referencias.');
foreach (media_reference_columns() as [$table, $column]) {
    $count = (int) $db->query("SELECT COUNT(*) FROM $table WHERE $column='https://example.com/new.webp'")->fetchColumn();
    if ($count !== 1) throw new RuntimeException("Referencia sin actualizar: $table.$column");
}
if ($db->query("SELECT path FROM media LIMIT 1")->fetchColumn() !== 'https://example.com/old.webp') {
    throw new RuntimeException('La imagen anterior no se conservó en la biblioteca.');
}

$logo = dirname(__DIR__) . '/public/images/logo.webp';
$optimized = optimized_webp_payload($logo, 1600, 80);
if (!str_starts_with($optimized, 'RIFF') || substr($optimized, 8, 4) !== 'WEBP' || strlen($optimized) >= filesize($logo)) {
    throw new RuntimeException('La optimización WebP falló.');
}

echo "Escaneo, referencias y compresión WebP: OK\n";
