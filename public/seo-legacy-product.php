<?php
declare(strict_types=1);
require_once __DIR__ . '/seo-catalog-data.php';

$lang = ($_GET['lang'] ?? 'es') === 'en' ? 'en' : 'es';
$requestedLine = (string) ($_GET['line'] ?? '');
$line = in_array($requestedLine, ['linea-retail', 'retail-line', 'retail'], true) ? 'retail'
    : (in_array($requestedLine, ['linea-convencional', 'linea-a-granel', 'conventional-line', 'bulk-line', 'conventional'], true) ? 'conventional' : '');
$requested = trim(rawurldecode((string) ($_GET['slug'] ?? $_GET['producto'] ?? $_GET['product'] ?? '')), '/');
$requestedId = (int) ($_GET['id'] ?? 0);

try {
    $db = rb_pdo();
    $products = seo_catalog_products($db);
    $found = null;

    // A legacy route is redirect-only; the current slug is always taken from BD.
    foreach ($products as $product) {
        if ($line !== '' && $product['line_slug'] !== $line) continue;
        if ($requestedId > 0 && (int) $product['id'] === $requestedId) { $found = $product; break; }
        if ($requested === '') continue;
        if ($requested === seo_catalog_slug($product, $lang)) { $found = $product; break; }
    }

    if ($found === null && $requested !== '') {
        $history = $db->prepare('SELECT product_id FROM product_slug_history WHERE locale=? AND old_slug=? LIMIT 1');
        $history->execute([$lang, $requested]);
        $historyId = (int) ($history->fetchColumn() ?: 0);
        foreach ($products as $product) {
            if ($line !== '' && $product['line_slug'] !== $line) continue;
            if ((int) $product['id'] === $historyId) { $found = $product; break; }
        }
    }

    // These variants were used by earlier manually generated product sheets.
    if ($found === null && $requested !== '') foreach ($products as $product) {
        if ($line !== '' && $product['line_slug'] !== $line) continue;
        $stored = seo_catalog_slug($product, $lang);
        $name = (string) ($product['name_' . $lang] ?? '');
        if (in_array($requested, array_filter([rb_slug($name), preg_replace('/-\d+$/', '', $stored)]), true)) {
            $found = $product;
            break;
        }
    }

    if ($found !== null) {
        $destination = seo_catalog_product_path($found, $lang);
        if ($destination !== '') {
            header('Location: ' . $destination, true, 301);
            exit;
        }
    }
} catch (Throwable $error) {
    error_log('[Royal Beans legacy product route] ' . $error->getMessage());
    http_response_code(503);
    header('Retry-After: 60');
    exit;
}
seo_catalog_not_found($lang);
