<?php
declare(strict_types=1);
require_once __DIR__ . '/seo-catalog-data.php';


function sitemap_origin(): string {
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'royalbeansperu.com'));
    if (in_array($host, ['royalbeansperu.com', 'www.royalbeansperu.com'], true)) return 'https://royalbeansperu.com';
    if (!preg_match('/^[a-z0-9.-]+(?::\d+)?$/', $host)) $host = 'royalbeansperu.com';
    $forwarded = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwarded === 'https' || ($_SERVER['REQUEST_SCHEME'] ?? '') === 'https';
    return ($https ? 'https' : 'http') . '://' . $host;
}

function sitemap_xml(string $value): string {
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$origin = sitemap_origin();
$pairs = [
    ['/', '/en/'], ['/nosotros/', '/en/about-us/'],
    ['/productos/linea-a-granel/', '/en/products/bulk-line/'],
    ['/productos/linea-retail/', '/en/products/retail/'],
    ['/participacion/', '/en/events/'], ['/impacto/', '/en/impact/'], ['/contacto/', '/en/contact/'],
    ['/politica-de-privacidad/', '/en/privacy-policy/'], ['/terminos-y-condiciones/', '/en/terms-and-conditions/'],
];
$items = [];
foreach ($pairs as [$es, $en]) {
    $items[] = ['loc' => $origin . $es, 'lastmod' => '', 'es' => $origin . $es, 'en' => $origin . $en];
    $items[] = ['loc' => $origin . $en, 'lastmod' => '', 'es' => $origin . $es, 'en' => $origin . $en];
}
try {
    $db = rb_pdo();
    $products = $db->query("SELECT p.id,p.updated_at,l.slug AS line_slug,es.slug AS slug_es,en.slug AS slug_en
      FROM products p JOIN product_lines l ON l.id=p.line_id AND l.is_active=1
      JOIN product_categories c ON c.id=p.category_id AND c.is_active=1
      JOIN product_translations es ON es.product_id=p.id AND es.locale='es'
      JOIN product_translations en ON en.product_id=p.id AND en.locale='en'
      WHERE p.is_active=1 AND p.deleted_at IS NULL ORDER BY p.id");
    foreach ($products as $product) {
        $es = seo_catalog_product_path($product, 'es');
        $en = seo_catalog_product_path($product, 'en');
        if ($es === '' || $en === '') continue;
        $lastmod = substr((string)($product['updated_at'] ?? ''), 0, 10);
        $items[] = ['loc'=>$origin.$es,'lastmod'=>$lastmod,'es'=>$origin.$es,'en'=>$origin.$en];
        $items[] = ['loc'=>$origin.$en,'lastmod'=>$lastmod,'es'=>$origin.$es,'en'=>$origin.$en];
    }
} catch (Throwable $error) {
    error_log('[Royal Beans sitemap] ' . $error->getMessage());
    http_response_code(503);
    header('Retry-After: 30');
    exit;
}

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
foreach ($items as $item) {
    echo '<url><loc>' . sitemap_xml($item['loc']) . '</loc>';
    if ($item['lastmod'] !== '') echo '<lastmod>' . sitemap_xml($item['lastmod']) . '</lastmod>';
    if ($item['es'] !== '' && $item['en'] !== '') {
        echo '<xhtml:link rel="alternate" hreflang="es-PE" href="' . sitemap_xml($item['es']) . '"/>';
        echo '<xhtml:link rel="alternate" hreflang="en" href="' . sitemap_xml($item['en']) . '"/>';
        echo '<xhtml:link rel="alternate" hreflang="x-default" href="' . sitemap_xml($item['es']) . '"/>';
    }
    echo '</url>';
}
echo '</urlset>';
