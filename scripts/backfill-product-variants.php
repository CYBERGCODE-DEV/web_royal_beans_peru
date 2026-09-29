<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$documentRoot = is_file(dirname(__DIR__) . '/public/admin/_bootstrap.php') ? dirname(__DIR__) . '/public' : dirname(__DIR__);
require $documentRoot . '/admin/_bootstrap.php';

$apply = in_array('--apply', $argv, true);
$db = admin_db();
$paths = $db->query("SELECT DISTINCT path FROM (
  SELECT p.image_path AS path FROM products p WHERE p.is_active=1 AND p.deleted_at IS NULL
  UNION ALL SELECT pp.image_path FROM product_packages pp JOIN products p ON p.id=pp.product_id WHERE p.is_active=1 AND p.deleted_at IS NULL
  UNION ALL SELECT g.media_path FROM product_gallery g JOIN products p ON p.id=g.product_id WHERE p.is_active=1 AND p.deleted_at IS NULL AND g.media_type='image'
) assets WHERE path<>'' ORDER BY path")->fetchAll(PDO::FETCH_COLUMN);
$settings = r2_settings($db);
$prefix = $settings['r2_public_url'] . '/';
$pending = array_values(array_filter($paths, static fn(string $path): bool => str_starts_with($path, $prefix) && !str_contains($path, '/royalbeans/products/by-hash/')));
fwrite(STDOUT, count($pending) . " legacy product images need variants.\n");
if (!$apply) { fwrite(STDOUT, "Dry run only. Pass --apply after taking a database backup.\n"); exit; }

$updated = 0;
$errors = [];
foreach ($pending as $oldPath) {
    $temporary = tempnam(sys_get_temp_dir(), 'rb-image-');
    if ($temporary === false) throw new RuntimeException('Cannot create temporary file');
    $creation = '';
    $newPath = '';
    try {
        $stream = @fopen($oldPath, 'rb', false, stream_context_create(['http'=>['timeout'=>25,'follow_location'=>0]]));
        if ($stream === false) throw new RuntimeException('Cannot read old image from R2');
        $target = fopen($temporary, 'wb');
        if ($target === false) { fclose($stream); throw new RuntimeException('Cannot open temporary file'); }
        $bytes = stream_copy_to_stream($stream, $target, 16 * 1024 * 1024 + 1);
        fclose($stream); fclose($target);
        if ($bytes === false || $bytes < 1 || $bytes > 16 * 1024 * 1024) throw new RuntimeException('Old image exceeds migration size limit');
        $newPath = store_managed_image_source($db, $temporary, basename((string)parse_url($oldPath, PHP_URL_PATH)), 0, $creation);
        $db->beginTransaction();
        foreach (media_reference_columns() as [$table, $column]) {
            $statement = $db->prepare("UPDATE $table SET $column=? WHERE $column=?");
            $statement->execute([$newPath, $oldPath]);
        }
        $audit = $db->prepare('INSERT INTO audit_log(user_id,action,entity_type,entity_id,details_json) VALUES(NULL,?,?,?,?)');
        $audit->execute(['media-variant-backfill','media',$newPath,json_encode(['old_path'=>$oldPath,'new_path'=>$newPath], JSON_UNESCAPED_SLASHES)]);
        $db->commit();
        $cleanup = release_replaced_media($db, [$oldPath], [$newPath], 0, 'media-backfill', $newPath);
        if ($cleanup['errors']) error_log('[Royal Beans variant backfill] ' . implode(' ', $cleanup['errors']));
        $updated++;
        fwrite(STDOUT, "Migrated " . $updated . '/' . count($pending) . "\n");
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        if ($newPath !== '' && $creation !== '') cleanup_failed_uploads($db, [['path'=>$newPath,'creation'=>$creation]]);
        $errors[] = basename((string)parse_url($oldPath, PHP_URL_PATH)) . ': ' . $error->getMessage();
    } finally { @unlink($temporary); }
}
if ($updated > 0) mark_public_content_changed('products');
fwrite(STDOUT, "Updated: $updated. Errors: " . count($errors) . "\n");
foreach ($errors as $error) fwrite(STDERR, $error . "\n");
if ($errors) exit(1);
