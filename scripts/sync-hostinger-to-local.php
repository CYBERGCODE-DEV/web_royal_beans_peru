<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo puede ejecutarse desde la terminal.\n");
    exit(1);
}

$projectRoot = dirname(__DIR__);
$config = require $projectRoot . '/public/cms-config/database.php';
$allowedHosts = ['localhost', '127.0.0.1', '::1'];

if (!in_array((string) ($config['host'] ?? ''), $allowedHosts, true)) {
    fwrite(STDERR, "Operación cancelada: la configuración no apunta a MySQL local.\n");
    exit(1);
}

$dumpPath = $argv[1] ?? '';
$productionUrl = rtrim($argv[2] ?? 'https://darkseagreen-squirrel-657050.hostingersite.com', '/');
if ($dumpPath === '' || !is_file($dumpPath)) {
    fwrite(STDERR, "Uso: php scripts/sync-hostinger-to-local.php <respaldo.sql> [url-produccion]\n");
    exit(1);
}

$database = (string) ($config['database'] ?? '');
if ($database === '') {
    fwrite(STDERR, "La base de datos local no está configurada.\n");
    exit(1);
}

$mysqlDirectory = 'C:/xampp/mysql/bin';
$mysql = $mysqlDirectory . '/mysql.exe';
$mysqldump = $mysqlDirectory . '/mysqldump.exe';
if (!is_file($mysql) || !is_file($mysqldump)) {
    fwrite(STDERR, "No se encontraron mysql.exe y mysqldump.exe en XAMPP.\n");
    exit(1);
}

function mysqlCommand(string $executable, array $config, string $database): array
{
    return [
        $executable,
        '--host=' . $config['host'],
        '--port=' . $config['port'],
        '--user=' . $config['username'],
        '--default-character-set=utf8mb4',
        $database,
    ];
}

function runProcess(array $command, array $config, mixed $stdin, mixed $stdout): void
{
    $systemEnvironment = getenv();
    $environment = array_merge(is_array($systemEnvironment) ? $systemEnvironment : [], [
        'MYSQL_PWD' => (string) ($config['password'] ?? ''),
    ]);
    $process = proc_open($command, [$stdin, $stdout, ['pipe', 'w']], $pipes, null, $environment);
    if (!is_resource($process)) {
        throw new RuntimeException('No se pudo iniciar una herramienta de MySQL.');
    }
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $status = proc_close($process);
    if ($status !== 0) {
        throw new RuntimeException(trim($errors) ?: 'La herramienta de MySQL terminó con error.');
    }
}

function downloadFile(string $url, string $destination): bool
{
    $context = stream_context_create(['http' => ['timeout' => 30, 'follow_location' => 1]]);
    $payload = @file_get_contents($url, false, $context);
    if ($payload === false || $payload === '') {
        return false;
    }
    $directory = dirname($destination);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        return false;
    }
    return file_put_contents($destination, $payload, LOCK_EX) !== false;
}

$backupDirectory = $projectRoot . '/_local_cache/db-backups';
if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0775, true) && !is_dir($backupDirectory)) {
    throw new RuntimeException('No se pudo crear el directorio de respaldos locales.');
}
$backupPath = $backupDirectory . '/before-hostinger-sync-' . date('Ymd-His') . '.sql';
$backupStream = fopen($backupPath, 'wb');
if ($backupStream === false) {
    throw new RuntimeException('No se pudo crear el respaldo local.');
}

try {
    runProcess(mysqlCommand($mysqldump, $config, $database), $config, ['pipe', 'r'], $backupStream);
} catch (Throwable $exception) {
    fclose($backupStream);
    @unlink($backupPath);
    throw $exception;
}
fclose($backupStream);

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $database),
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_COLUMN);
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($tables as $table) {
    $pdo->exec('DROP TABLE `' . str_replace('`', '``', (string) $table) . '`');
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

$dumpStream = fopen($dumpPath, 'rb');
if ($dumpStream === false) {
    throw new RuntimeException('No se pudo abrir el dump de producción.');
}
try {
    runProcess(mysqlCommand($mysql, $config, $database), $config, $dumpStream, ['pipe', 'w']);
} catch (Throwable $exception) {
    $restoreStream = fopen($backupPath, 'rb');
    if ($restoreStream !== false) {
        runProcess(mysqlCommand($mysql, $config, $database), $config, $restoreStream, ['pipe', 'w']);
        fclose($restoreStream);
    }
    throw $exception;
} finally {
    fclose($dumpStream);
}

$sql = file_get_contents($dumpPath);
preg_match_all("#['\"](/uploads/[^'\"]+)['\"]#", (string) $sql, $matches);
$downloaded = 0;
$existing = 0;
$mirrored = 0;
$failed = [];
foreach (array_unique($matches[1] ?? []) as $relativePath) {
    if (str_contains($relativePath, '..')) {
        continue;
    }
    $suffix = str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $destination = $projectRoot . '/public' . $suffix;
    if (is_file($destination)) {
        $existing++;
    } elseif (downloadFile($productionUrl . $relativePath, $destination)) {
        $downloaded++;
    } else {
        $failed[] = $relativePath;
        continue;
    }
    if (is_dir($projectRoot . '/out')) {
        $outputDestination = $projectRoot . '/out' . $suffix;
        $outputDirectory = dirname($outputDestination);
        if (!is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0775, true);
        }
        if (!is_file($outputDestination) || hash_file('sha256', $outputDestination) !== hash_file('sha256', $destination)) {
            if (copy($destination, $outputDestination)) {
                $mirrored++;
            }
        }
    }
}

$activeProducts = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND deleted_at IS NULL')->fetchColumn();
$mediaRows = (int) $pdo->query('SELECT COUNT(*) FROM media')->fetchColumn();

echo "Sincronización local completada.\n";
echo "Respaldo anterior: {$backupPath}\n";
echo "Productos activos: {$activeProducts}\n";
echo "Medios en BD: {$mediaRows}\n";
echo "Imágenes descargadas: {$downloaded}; existentes: {$existing}; fallidas: " . count($failed) . "\n";
echo "Imágenes reflejadas en out: {$mirrored}\n";
if ($failed !== []) {
    echo "Pendientes:\n- " . implode("\n- ", $failed) . "\n";
}