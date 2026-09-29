<?php
declare(strict_types=1);

$path = rawurldecode((string) ($_GET['path'] ?? ''));
if ($path === '' || !str_starts_with($path, '/') || str_contains($path, '..')) {
    http_response_code(404);
    exit;
}

/** @return array<string,string> */
function read_media_alias_cache(string $cacheFile): array {
    if (!is_file($cacheFile)) return [];
    $aliases = json_decode((string) @file_get_contents($cacheFile), true);
    if (!is_array($aliases)) return [];
    return array_filter($aliases, static fn($remote, $local): bool => is_string($local) && str_starts_with($local, '/') && is_string($remote) && preg_match('#^https://#i', $remote) === 1, ARRAY_FILTER_USE_BOTH);
}

function redirect_to_remote_media(string $remotePath): never {
    header('Cache-Control: public, max-age=86400, stale-while-revalidate=604800');
    header('CDN-Cache-Control: public, max-age=86400, stale-while-revalidate=604800');
    header('Location: ' . $remotePath, true, 302);
    exit;
}

$cacheFile = __DIR__ . '/cms-config/media-aliases.json';
$aliases = read_media_alias_cache($cacheFile);
if (isset($aliases[$path])) redirect_to_remote_media($aliases[$path]);

$lastError = null;
for ($attempt = 0; $attempt < 2; $attempt++) {
    try {
        $config = require __DIR__ . '/cms-config/database.php';
        $db = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']),
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 4]
        );
        $statement = $db->prepare("SELECT remote_path FROM media_aliases WHERE local_path=? AND remote_path REGEXP '^https?://' LIMIT 1");
        $statement->execute([$path]);
        $remotePath = (string) ($statement->fetchColumn() ?: '');
        if ($remotePath === '') {
            http_response_code(404);
            header('Cache-Control: no-store');
            exit;
        }
        $aliases[$path] = $remotePath;
        $temporary = $cacheFile . '.tmp-' . bin2hex(random_bytes(4));
        if (@file_put_contents($temporary, json_encode($aliases, JSON_UNESCAPED_SLASHES), LOCK_EX) !== false) @rename($temporary, $cacheFile);
        else @unlink($temporary);
        redirect_to_remote_media($remotePath);
    } catch (Throwable $error) {
        $lastError = $error;
        if ($attempt === 0) usleep(150000);
    }
}

error_log('[media-redirect] Alias lookup unavailable for ' . $path . ': ' . ($lastError?->getMessage() ?? 'unknown error'));
http_response_code(503);
header('Cache-Control: no-store');
header('Retry-After: 3');
