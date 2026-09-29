<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/public/seo-catalog-data.php';

$auditDsn = getenv('RB_AUDIT_DSN');
$db = $auditDsn !== false && $auditDsn !== ''
    ? new PDO($auditDsn, getenv('RB_AUDIT_USER') ?: 'root', getenv('RB_AUDIT_PASSWORD') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC])
    : rb_pdo();
$origin = 'https://royalbeansperu.com';
$output = dirname(__DIR__) . '/outputs/inventario-urls-productos.csv';
$directory = dirname($output);
if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
    throw new RuntimeException('Cannot create report directory');
}

$history = [];
foreach ($db->query('SELECT product_id,locale,old_slug FROM product_slug_history ORDER BY locale,old_slug') as $row) {
    $history[(int) $row['product_id']][(string) $row['locale']][] = (string) $row['old_slug'];
}

$rows = $db->query("SELECT p.id,p.is_active,p.deleted_at,l.slug AS line_slug,l.is_active AS line_active,
    c.slug AS category_slug,c.is_active AS category_active,c.seo_slug_es,c.seo_slug_en,
    t.locale,t.name,t.slug
    FROM products p JOIN product_lines l ON l.id=p.line_id
    JOIN product_categories c ON c.id=p.category_id
    JOIN product_translations t ON t.product_id=p.id
    ORDER BY p.id,t.locale")->fetchAll();
$translations = [];
foreach ($rows as $row) {
    $translations[(int) $row['id']][(string) $row['locale']] = (string) $row['slug'];
}
$sitemapPaths = null;
$sitemapUrl = getenv('RB_AUDIT_SITEMAP_URL');
if ($sitemapUrl !== false && $sitemapUrl !== '') {
    $xml = @simplexml_load_file($sitemapUrl);
    if ($xml === false) throw new RuntimeException('Cannot read local sitemap for inventory');
    $sitemapPaths = [];
    foreach ($xml->url as $item) {
        $path = parse_url((string) $item->loc, PHP_URL_PATH);
        if (is_string($path)) $sitemapPaths[$path] = true;
    }
}
$testOrigin = rtrim((string) (getenv('RB_AUDIT_TEST_ORIGIN') ?: ''), '/');
function audit_http_status(string $url): string {
    $handle = curl_init($url);
    if ($handle === false) return 'error';
    curl_setopt_array($handle, [CURLOPT_NOBODY => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 20]);
    curl_exec($handle);
    $status = curl_errno($handle) === 0 ? (string) curl_getinfo($handle, CURLINFO_RESPONSE_CODE) : 'error';
    curl_close($handle);
    return $status;
}

$handle = fopen($output, 'wb');
if ($handle === false) throw new RuntimeException('Cannot write inventory');
fwrite($handle, "\xEF\xBB\xBF");
fputcsv($handle, ['product_id','producto','idioma','URL actual','URL canónica definitiva','acción','URLs antiguas detectadas','destino 301','código HTTP final local','aparece sitemap local','canonical','hreflang es-PE','hreflang en','hreflang x-default'], ';');

$published = $drafts = 0;
foreach ($rows as $row) {
    $id = (int) $row['id'];
    $lang = (string) $row['locale'];
    $base = $lang === 'en' ? '/en/products/' : '/productos/';
    $slug = (string) $row['slug'];
    $valid = (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    $active = (int) $row['is_active'] === 1 && $row['deleted_at'] === null
        && (int) $row['line_active'] === 1 && (int) $row['category_active'] === 1 && $valid;
    $canonical = $valid ? $origin . $base . $slug . '/' : '';
    $lineSegment = $lang === 'en'
        ? ($row['line_slug'] === 'retail' ? 'retail' : 'bulk-line')
        : ($row['line_slug'] === 'retail' ? 'linea-retail' : 'linea-a-granel');
    $categorySegment = (string) $row[$lang === 'en' ? 'seo_slug_en' : 'seo_slug_es'];
    $old = [];
    foreach ($history[$id][$lang] ?? [] as $oldSlug) {
        if ($oldSlug === $slug) continue;
        $old[] = $origin . $base . $oldSlug . '/';
        $old[] = $origin . $base . $lineSegment . '/' . $oldSlug . '/';
        if ($categorySegment !== '') $old[] = $origin . $base . $categorySegment . '/' . $oldSlug . '/';
    }
    if ($valid) {
        $old[] = $origin . $base . $lineSegment . '/' . $slug . '/';
        if ($categorySegment !== '') $old[] = $origin . $base . $categorySegment . '/' . $slug . '/';
    }
    $old[] = $origin . '/product-seo.php?lang=' . $lang . '&line=' . $lineSegment . '&slug=' . rawurlencode($slug);
    $old[] = $origin . '/product.php?id=' . $id . '&lang=' . $lang;
    $old = array_values(array_unique($old));
    $esSlug = $translations[$id]['es'] ?? '';
    $enSlug = $translations[$id]['en'] ?? '';
    $es = $esSlug !== '' ? $origin . '/productos/' . $esSlug . '/' : '';
    $en = $enSlug !== '' ? $origin . '/en/products/' . $enSlug . '/' : '';
    $published += $active ? 1 : 0;
    $drafts += $active ? 0 : 1;
    $httpStatus = $testOrigin !== '' && $active
        ? audit_http_status($testOrigin . $base . $slug . '/')
        : ($active ? 'sin verificar' : 'no publicada');
    fputcsv($handle, [
        $id, (string) $row['name'], $lang, $active ? $canonical : '', $canonical,
        $active ? 'mantener 200; variantes 301' : 'borrador/inactivo: no indexar',
        implode(' | ', $old), $active ? $canonical : '', $httpStatus,
        ($active && ($sitemapPaths === null || isset($sitemapPaths[$base . $slug . '/']))) ? 'sí' : 'no',
        $active ? $canonical : '', $active ? $es : '', $active ? $en : '', $active ? $es : '',
    ], ';');
}
fclose($handle);
echo "report=$output\nactive_locale_rows=$published\ninactive_locale_rows=$drafts\n";
