<?php
declare(strict_types=1);

// Uso: php scripts/migrate-heavy-images.php [--apply] [--min-bytes=1048576] [--limit=10]
// La inspección local puede usar --public-url=https://... cuando no hay credenciales R2.
// Por defecto solo inspecciona. No borra originales ni cambia el esquema.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__);
$publicRoot = is_file($root . '/public/admin/_bootstrap.php') ? $root . '/public' : $root;
require_once $publicRoot . '/admin/_bootstrap.php';

$apply = in_array('--apply', $argv, true);
$minimum = 1048576;
$limit = 0;
$publicUrl = '';
foreach ($argv as $argument) {
    if (preg_match('/^--min-bytes=(\d+)$/', $argument, $match)) $minimum = max(1, (int)$match[1]);
    if (preg_match('/^--limit=(\d+)$/', $argument, $match)) $limit = (int)$match[1];
    if (str_starts_with($argument, '--public-url=')) $publicUrl = rtrim(substr($argument, 13), '/');
}

$config = require $publicRoot . '/cms-config/database.php';
$db = new PDO(
    'mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['database'] . ';charset=utf8mb4',
    $config['username'], $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
if ($apply && $publicUrl !== '') throw new RuntimeException('--public-url solo se permite en modo de inspección.');
$settings = $publicUrl === '' ? r2_settings($db) : ['r2_public_url' => $publicUrl];
$prefix = $settings['r2_public_url'] . '/';
$references = media_reference_columns();
$candidates = [];
foreach ($references as [$table, $column]) {
    // Un alias puede apuntar a un objeto ya eliminado; solo migrar imágenes usadas por contenido.
    if ($table === 'media_aliases') continue;
    $condition = $table === 'content_fields' ? " AND field_type='image'" : '';
    $statement = $db->query("SELECT DISTINCT $column AS path FROM $table WHERE $column IS NOT NULL AND $column<>''$condition");
    foreach ($statement as $row) {
        $path = (string)$row['path'];
        if (!str_starts_with($path, $prefix) || !preg_match('/\.(?:png|jpe?g)(?:\?.*)?$/i', $path)) continue;
        $candidates[$path] = true;
    }
}

$download = static function (string $url): string {
    $temporary = tempnam(sys_get_temp_dir(), 'rb-img-');
    if ($temporary === false) throw new RuntimeException('No se pudo crear un archivo temporal.');
    $file = fopen($temporary, 'wb');
    if ($file === false) { @unlink($temporary); throw new RuntimeException('No se pudo abrir el archivo temporal.'); }
    $bytes = 0;
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use ($file, &$bytes): int {
            $bytes += strlen($chunk);
            return $bytes <= 40 * 1024 * 1024 ? (int)fwrite($file, $chunk) : 0;
        },
    ]);
    curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    fclose($file);
    if ($status !== 200 || $error !== '') {
        @unlink($temporary);
        throw new RuntimeException('Descarga fallida (HTTP ' . $status . ')' . ($error !== '' ? ': ' . $error : ''));
    }
    return $temporary;
};

$existing = $db->prepare("SELECT path FROM media WHERE content_hash=? AND mime_type='image/webp' AND path LIKE ? ORDER BY id LIMIT 1");
$oldMedia = $db->prepare('SELECT original_name,alt_es,alt_en,uploaded_by FROM media WHERE path=? LIMIT 1');
$insert = $db->prepare('INSERT INTO media(path,original_name,mime_type,size_bytes,content_hash,alt_es,alt_en,uploaded_by) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE mime_type=VALUES(mime_type),size_bytes=VALUES(size_bytes),content_hash=VALUES(content_hash)');
$updates = [];
foreach ($references as [$table, $column]) $updates[] = $db->prepare("UPDATE $table SET $column=? WHERE $column=?");
$totals = ['found' => count($candidates), 'checked' => 0, 'heavy' => 0, 'migrated' => 0, 'reused' => 0, 'references' => 0, 'before_bytes' => 0, 'after_bytes' => 0, 'errors' => 0];
echo ($apply ? 'APPLY' : 'DRY RUN') . ': ' . count($candidates) . " URL candidatas.\n";
foreach (array_keys($candidates) as $oldPath) {
    if ($limit > 0 && $totals['checked'] >= $limit) break;
    $totals['checked']++;
    $temporary = null;
    try {
        $temporary = $download($oldPath);
        $originalBytes = (int)filesize($temporary);
        if ($originalBytes < $minimum) continue;
        $totals['heavy']++;
        $payload = optimized_webp_payload($temporary);
        $newBytes = strlen($payload);
        $totals['before_bytes'] += $originalBytes;
        $totals['after_bytes'] += $newBytes;
        $hash = hash('sha256', $payload);
        $existing->execute([$hash, $prefix . '%']);
        $newPath = (string)($existing->fetchColumn() ?: '');
        if ($newPath !== '' && !str_starts_with($newPath, $prefix)) $newPath = '';
        if ($apply) {
            if ($newPath !== '') {
                try { r2_request($settings, 'HEAD', r2_key_from_url($db, $newPath)); }
                catch (Throwable) { $newPath = ''; }
            }
            if ($newPath === '') {
                $newPath = r2_store_payload($db, $payload, 'image/webp', 'webp');
            } else {
                $totals['reused']++;
            }
            r2_request($settings, 'HEAD', r2_key_from_url($db, $newPath));
            $oldMedia->execute([$oldPath]);
            $metadata = $oldMedia->fetch() ?: [];
            $db->beginTransaction();
            try {
                $insert->execute([$newPath, $metadata['original_name'] ?? basename((string)parse_url($oldPath, PHP_URL_PATH)), 'image/webp', $newBytes, $hash, $metadata['alt_es'] ?? '', $metadata['alt_en'] ?? '', $metadata['uploaded_by'] ?? null]);
                foreach ($updates as $statement) {
                    $statement->execute([$newPath, $oldPath]);
                    $totals['references'] += $statement->rowCount();
                }
                $db->commit();
                $totals['migrated']++;
            } catch (Throwable $error) {
                if ($db->inTransaction()) $db->rollBack();
                throw $error;
            }
        }
        echo basename((string)parse_url($oldPath, PHP_URL_PATH)) . ': ' . $originalBytes . ' -> ' . $newBytes . ' bytes' . ($apply ? ' [actualizado]' : ' [estimado]') . "\n";
    } catch (Throwable $error) {
        $totals['errors']++;
        fwrite(STDERR, basename((string)parse_url($oldPath, PHP_URL_PATH)) . ': ' . $error->getMessage() . "\n");
    } finally {
        if ($temporary !== null) @unlink($temporary);
    }
}
if ($apply && $totals['migrated'] > 0) mark_public_content_changed('media');
echo json_encode($totals, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit($totals['errors'] > 0 ? 1 : 0);
