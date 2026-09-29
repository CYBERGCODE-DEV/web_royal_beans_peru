<?php
declare(strict_types=1);

/**
 * Read-only matrix check: php scripts/verify-cms-html.php https://royalbeansperu.com
 * Optional RB_TEST_DB_HOST/PORT/NAME/USER/PASSWORD select an isolated DB dump.
 */
require_once dirname(__DIR__) . '/public/cms-server-render.php';

$base = rtrim((string) ($argv[1] ?? ''), '/');
if (!preg_match('#^https?://[^/]+$#i', $base)) {
    fwrite(STDERR, "Usage: php scripts/verify-cms-html.php https://host\n");
    exit(2);
}
$saved = require dirname(__DIR__) . '/public/cms-config/database.php';
$config = [
    'host' => getenv('RB_TEST_DB_HOST') ?: $saved['host'],
    'port' => getenv('RB_TEST_DB_PORT') ?: $saved['port'],
    'database' => getenv('RB_TEST_DB_NAME') ?: $saved['database'],
    'username' => getenv('RB_TEST_DB_USER') ?: $saved['username'],
    'password' => getenv('RB_TEST_DB_PASSWORD') !== false ? getenv('RB_TEST_DB_PASSWORD') : $saved['password'],
];
try {
    $db = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']), $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (Throwable $error) {
    fwrite(STDERR, 'Database connection failed: ' . $error->getMessage() . "\n");
    exit(2);
}

$routes = [
    ['/', 'inicio', 'es'], ['/nosotros/', 'nosotros', 'es'], ['/participacion/', 'participacion', 'es'],
    ['/impacto/', 'impacto', 'es'], ['/contacto/', 'contacto', 'es'],
    ['/productos/linea-a-granel/', 'productos-conventional', 'es'], ['/productos/linea-retail/', 'productos-retail', 'es'],
    ['/en/', 'inicio', 'en'], ['/en/about-us/', 'nosotros', 'en'], ['/en/events/', 'participacion', 'en'],
    ['/en/impact/', 'impacto', 'en'], ['/en/contact/', 'contacto', 'en'],
    ['/en/products/bulk-line/', 'productos-conventional', 'en'], ['/en/products/retail/', 'productos-retail', 'en'],
];
$normalize = static fn(string $value): string => trim((string) preg_replace('/\s+/u', ' ', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
$errors = [];
$fieldsQuery = $db->prepare('SELECT section_key,field_key,field_type,value_es,value_en,style_json FROM content_fields WHERE page_key=?');
foreach ($routes as [$path, $page, $lang]) {
    $url = $base . $path;
    $handle = curl_init($url);
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 25, CURLOPT_HTTPHEADER => ['Cache-Control: no-cache', 'Pragma: no-cache'], CURLOPT_USERAGENT => 'RoyalBeans-CMS-HTML-check/1.0']);
    $html = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($handle);
    curl_close($handle);
    if ($status !== 200 || !is_string($html)) {
        $errors[] = "$path HTTP $status" . ($curlError !== '' ? ": $curlError" : '');
        continue;
    }
    if (preg_match('/Cargando contenido|Loading content/i', $html)) $errors[] = "$path contains a content loader";
    if (preg_match('/<\s*(?:img|source)\b[^>]*\/\s+(?:src|srcSet|style|hidden)\b/i', $html)) $errors[] = "$path contains malformed image attributes";
    if (str_contains($html, 'Todos los productos')) $errors[] = "$path contains the legacy product menu";
    if (!str_contains($html, '__ROYALBEANS_SERVER_CMS_PATH__')) $errors[] = "$path lacks the server-CMS hydration marker";
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $xpath = new DOMXPath($document);
    $root = $xpath->query('/html')->item(0);
    $main = $xpath->query('//main')->item(0);
    $header = $xpath->query('//header')->item(0);
    $footer = $xpath->query('//footer[contains(concat(" ", normalize-space(@class), " "), " site-footer ")]')->item(0);
    if (!$root || $root->attributes?->getNamedItem('lang')?->nodeValue !== $lang) $errors[] = "$path wrong html language";
    if (!$main || !$header || !$footer) { $errors[] = "$path missing main/header/footer"; continue; }
    $headerText = $normalize($header->textContent);
    $footerText = $normalize($footer->textContent);
    $expectedMenu = $lang === 'es' ? ['Productos', 'Línea a Granel', 'Línea Retail'] : ['Products', 'Bulk Line', 'Retail Line'];
    foreach ($expectedMenu as $label) if (!str_contains($headerText, $label)) $errors[] = "$path missing menu label: $label";
    $expectedFooter = $lang === 'es' ? 'Diseñado y desarrollado por' : 'Designed and developed by';
    if (!str_contains($footerText, $expectedFooter)) $errors[] = "$path missing localized footer credit";
    if ($lang === 'en' && (str_contains($html, 'Contenido protegido') || str_contains($html, 'Del Perú para el mundo'))) $errors[] = "$path contains Spanish global text";
    if ($lang === 'es' && $page === 'productos-retail' && preg_match('/\b(?:pulses|grains)\b/i', $normalize($main->textContent))) $errors[] = "$path displays English category labels";

    $fieldsQuery->execute([$page]);
    $fields = $fieldsQuery->fetchAll();
    $source = dirname(__DIR__) . '/out/' . cms_server_static_path($page, $lang) . '/index.html';
    $visibleKeys = [];
    if (is_file($source)) foreach (cms_server_nodes((string) file_get_contents($source)) as $node) if ($node['key'] !== '') $visibleKeys[$node['key']] = true;
    foreach ($fields as $field) {
        $key = $field['section_key'] . '.' . $field['field_key'];
        if ($visibleKeys && !isset($visibleKeys[$key])) continue;
        $value = (string) $field['value_' . $lang];
        if ($value === '') continue;
        $encodedKey = htmlspecialchars($key, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $element = $xpath->query('//*[@data-cms="' . $encodedKey . '"]')->item(0);
        if ($element && $field['field_type'] === 'image') {
            if ($element->attributes?->getNamedItem('src')?->nodeValue !== $value) $errors[] = "$path image $key differs from BD";
        }
        if ($element) {
            if ($field['field_type'] !== 'image' && $normalize($element->textContent) !== $normalize($value)) $errors[] = "$path text $key differs from BD";
            $style = json_decode((string) ($field['style_json'] ?? ''), true) ?: [];
            $inlineStyle = (string) ($element->attributes?->getNamedItem('style')?->nodeValue ?? '');
            if (preg_match('/^#[0-9a-f]{6}$/i', (string) ($style['color'] ?? '')) && !str_contains($inlineStyle, 'color:' . $style['color'])) $errors[] = "$path style $key color differs from BD";
            if (isset($style['size']) && (int) $style['size'] >= 10 && (int) $style['size'] <= 120 && !str_contains($inlineStyle, 'font-size:' . (int) $style['size'] . 'px')) $errors[] = "$path style $key size differs from BD";
            if ($key === 'hero.image' && ($hero = $xpath->query('ancestor::*[@data-hero][1]', $element)->item(0))) {
                $heroStyle = (string) ($hero->attributes?->getNamedItem('style')?->nodeValue ?? '');
                if (isset($style['heroHeight']) && !str_contains($heroStyle, '--hero-height:' . (int) $style['heroHeight'] . 'px')) $errors[] = "$path hero height differs from BD";
                if (isset($style['heroOverlay']) && !str_contains($heroStyle, '--hero-overlay:' . max(0, min(90, (float) $style['heroOverlay'])) / 100)) $errors[] = "$path hero overlay differs from BD";
                if (($style['heroShowText'] ?? true) === false && !$hero->hasAttribute('data-hero-text-hidden')) $errors[] = "$path hero text visibility differs from BD";
            }
            $runs = $style['richText'][$lang] ?? null;
            if (is_array($runs) && implode('', array_column($runs, 'text')) === $value) foreach ($runs as $run) {
                $color = $run['color'] ?? '';
                if (!preg_match('/^#[0-9a-f]{6}$/i', (string) $color)) continue;
                $found = false;
                foreach ($xpath->query('.//span[@data-cms-run="color"]', $element) as $span) {
                    if ($span->textContent === $run['text'] && str_contains((string) $span->attributes?->getNamedItem('style')?->nodeValue, 'color:' . $color)) { $found = true; break; }
                }
                if (!$found) $errors[] = "$path richText $key color $color differs from BD";
            }
        } elseif ($field['field_type'] !== 'image' && mb_strlen($value) >= 20 && !str_contains($normalize($main->textContent), $normalize($value))) {
            $errors[] = "$path section $key differs from BD";
        }
    }
    echo "OK $path HTTP 200; CMS, locale, header/footer and visible BD fields checked\n";
}
if ($errors) {
    fwrite(STDERR, "\n" . count($errors) . " failures:\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}
echo "\nAll 14 public routes match the supplied/current BD fields checked.\n";
