<?php
declare(strict_types=1);

function rb_json(array $payload, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function rb_public_cache(string $key, callable $loader, int $seconds = 300): mixed {
    $revisionFile = dirname(__DIR__) . '/cms-config/content-version.json';
    if (!is_file($revisionFile)) return $loader();
    $revision = json_decode((string)file_get_contents($revisionFile), true);
    $stamp = is_array($revision) ? (string)($revision['revision'] ?? '') : '';
    if ($stamp === '') return $loader();
    $apcu = function_exists('apcu_fetch') && function_exists('apcu_store') && (!function_exists('apcu_enabled') || apcu_enabled());
    $cacheKey = 'royalbeans:public:' . hash('sha256', $stamp . '|' . $key);
    if ($apcu) { $cached = apcu_fetch($cacheKey, $found); if ($found) return $cached; }
    $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'royalbeans-public-cache';
    $file = $directory . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
    if (is_file($file)) {
        $stored = json_decode((string)@file_get_contents($file), true);
        if (is_array($stored) && ($stored['revision'] ?? '') === $stamp && (int)($stored['expires'] ?? 0) > time() && array_key_exists('value', $stored)) {
            if ($apcu) apcu_store($cacheKey, $stored['value'], $seconds);
            return $stored['value'];
        }
    }
    $value = $loader();
    if ($apcu) apcu_store($cacheKey, $value, $seconds);
    if (is_dir($directory) || @mkdir($directory, 0700, true)) {
        $payload = json_encode(['revision'=>$stamp,'expires'=>time()+$seconds,'value'=>$value], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if ($payload !== false) { $temporary = $file . '.' . bin2hex(random_bytes(4)) . '.tmp'; if (@file_put_contents($temporary, $payload, LOCK_EX) !== false) @rename($temporary, $file); else @unlink($temporary); }
    }
    return $value;
}

function rb_pdo(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $config = require dirname(__DIR__) . '/cms-config/database.php';
    if (empty($config['database']) || empty($config['username'])) throw new RuntimeException('CMS not installed');
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']);
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function rb_body(): array {
    $type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($type, 'application/json')) {
        $data = json_decode((string) file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }
    return $_POST;
}

function rb_slug(string $value): string {
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($value)) ?: $value;
    $value = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value));
    return trim($value, '-') ?: 'producto';
}

function rb_image_variant(string $path, string $size): string {
    if (!in_array($size, ['320','640','1200','original'], true)) return $path;
    if (!preg_match('~^(https?://[^?#]+/royalbeans/products/by-hash/[a-f0-9]{64}/)original\.(?:png|jpe?g|webp)$~i', $path, $match)) return $path;
    return $size === 'original' ? $path : $match[1] . $size . '.webp';
}

function rb_media_path(PDO $db, string $path, string $fallbackLocal = ''): string {
    $path = trim($path);
    if (preg_match('#^https?://#i', $path)) return $path;
    $candidates = array_values(array_unique(array_filter([$path, trim($fallbackLocal)], static fn(string $candidate): bool => str_starts_with($candidate, '/'))));
    if (!$candidates) return $path;
    $placeholders = implode(',', array_fill(0, count($candidates), '?'));
    $statement = $db->prepare("SELECT remote_path FROM media_aliases WHERE local_path IN ($placeholders) AND remote_path REGEXP '^https?://' ORDER BY FIELD(local_path,$placeholders) LIMIT 1");
    $statement->execute(array_merge($candidates, $candidates));
    $resolved = (string) ($statement->fetchColumn() ?: '');
    if ($resolved !== '') return $resolved;
    $catalogPath = dirname(__DIR__) . '/cms-config/media-aliases.json';
    if (is_file($catalogPath)) {
        $catalog = json_decode((string) file_get_contents($catalogPath), true);
        if (is_array($catalog)) foreach ($candidates as $candidate) {
            $known = $catalog[$candidate] ?? '';
            if (is_string($known) && preg_match('#^https?://#i', $known)) return $known;
        }
    }
    $names = array_values(array_unique(array_map(static fn(string $candidate): string => basename($candidate), $candidates)));
    if ($names) {
        $namePlaceholders = implode(',', array_fill(0, count($names), '?'));
        $media = $db->prepare("SELECT path FROM media WHERE LOWER(original_name) IN ($namePlaceholders) AND path REGEXP '^https?://' ORDER BY id DESC LIMIT 1");
        $media->execute(array_map('strtolower', $names));
        $resolved = (string) ($media->fetchColumn() ?: '');
        if ($resolved !== '') return $resolved;
    }
    return $path;
}
