<?php
declare(strict_types=1);

require_once __DIR__ . '/seo-catalog-data.php';

// The former hub, category and standalone product views are redirect-only.
// Product slugs (including history) are resolved by the same DB-backed legacy handler.
if (($_GET['view'] ?? '') === 'product') {
    require __DIR__ . '/seo-legacy-product.php';
    exit;
}
$redirectLang = ($_GET['lang'] ?? 'es') === 'en' ? 'en' : 'es';
$redirectLine = ($_GET['view'] ?? '') === 'retail' ? 'retail' : 'conventional';
$redirectPath = $redirectLang === 'en'
    ? ($redirectLine === 'retail' ? '/en/products/retail/' : '/en/products/bulk-line/')
    : ($redirectLine === 'retail' ? '/productos/linea-retail/' : '/productos/linea-a-granel/');
header('Location: ' . $redirectPath, true, 301);
exit;

$lang = ($_GET['lang'] ?? 'es') === 'en' ? 'en' : 'es';
$view = in_array($_GET['view'] ?? '', ['hub', 'category', 'product', 'retail'], true) ? (string) $_GET['view'] : 'hub';
$requestedSlug = trim(rawurldecode((string) ($_GET['slug'] ?? '')), '/');
$origin = seo_catalog_origin();
$categories = [];
$hubPath = $lang === 'en' ? '/en/products/' : '/productos/';
$homePath = $lang === 'en' ? '/en/' : '/';
$e = static fn($value): string => seo_catalog_escape((string) $value);

try {
    $db = rb_pdo();
    $categories = seo_catalog_categories($db);
    $products = seo_catalog_products($db);
} catch (Throwable $error) {
    error_log('[Royal Beans SEO catalogue] ' . $error->getMessage());
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    header('Retry-After: 60');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!doctype html><html lang="' . $lang . '"><meta charset="utf-8"><title>Royal Beans Perú</title><body><h1>Royal Beans Perú</h1><p>' . ($lang === 'en' ? 'The catalogue is temporarily unavailable.' : 'El catálogo no está disponible temporalmente.') . '</p></body></html>';
    exit;
}

$categoryKey = '';
$product = null;
if ($view === 'product') {
    foreach ($categories as $key => $details) {
        if ($requestedSlug === $details[$lang]['slug']) { $view = 'category'; break; }
    }
}
if ($view === 'category') {
    foreach ($categories as $key => $details) {
        if ($requestedSlug === $details[$lang]['slug']) { $categoryKey = $key; break; }
    }
    if ($categoryKey === '') seo_catalog_not_found($lang);
    $products = array_values(array_filter($products, static fn(array $row): bool => $row['line_slug'] === 'conventional' && (int) $row['category_id'] === (int) $categories[$categoryKey]['id']));
} elseif ($view === 'product') {
    foreach ($products as $row) {
        if (seo_catalog_slug($row, $lang) === $requestedSlug) { $product = $row; break; }
    }
    if ($product === null) {
        $history = $db->prepare("SELECT h.product_id FROM product_slug_history h JOIN products p ON p.id=h.product_id WHERE h.locale=? AND h.old_slug=? AND p.is_active=1 AND p.deleted_at IS NULL LIMIT 1");
        $history->execute([$lang, $requestedSlug]);
        $previousId = (int) ($history->fetchColumn() ?: 0);
        foreach ($products as $row) if ((int) $row['id'] === $previousId) {
            $destination = seo_catalog_product_path($row, $lang);
            if ($destination !== '') { header('Location: ' . $destination, true, 301); exit; }
        }
        seo_catalog_not_found($lang);
    }
    $categoryKey = seo_catalog_category_key((string) $product['category_slug']);
} elseif ($view === 'retail') {
    $products = array_values(array_filter($products, static fn(array $row): bool => $row['line_slug'] === 'retail'));
}

$currentPath = $view === 'hub' ? $hubPath : ($view === 'retail' ? ($lang === 'en' ? '/en/products/retail/' : '/productos/linea-retail/') : ($view === 'category' ? seo_catalog_category_path($categoryKey, $lang) : seo_catalog_product_path($product, $lang)));
$incomingPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
// The PHP renderer is not an alternate public URL for a product or listing.
if ($incomingPath === '/seo-catalog.php') {
    header('Location: ' . $currentPath, true, 301);
    exit;
}
if ($incomingPath !== '' && $incomingPath !== $currentPath && rtrim($incomingPath, '/') === rtrim($currentPath, '/')) {
    header('Location: ' . $currentPath, true, 301);
    exit;
}
$otherLang = $lang === 'es' ? 'en' : 'es';
$alternatePath = $view === 'hub' ? ($lang === 'es' ? '/en/products/' : '/productos/')
    : ($view === 'retail' ? ($lang === 'en' ? '/productos/linea-retail/' : '/en/products/retail/') : ($view === 'category' ? seo_catalog_category_path($categoryKey, $otherLang) : seo_catalog_product_path($product, $otherLang)));
$esPath = $lang === 'es' ? $currentPath : $alternatePath;
$enPath = $lang === 'en' ? $currentPath : $alternatePath;
$canonical = $origin . $currentPath;
$category = $categoryKey !== '' ? $categories[$categoryKey][$lang] : null;
$name = $product !== null ? (string) ($product['name_' . $lang] ?: $product['name_es']) : '';
$productTitleBase = $product !== null ? trim((string) ($product['title_' . $lang] ?? '')) : '';
if ($product !== null && $productTitleBase === '') $productTitleBase = $name . ($lang === 'en' ? ' Supplier from Peru' : ' para Exportación') . ' | Royal Beans Perú';
$title = match ($view) {
    'hub' => $lang === 'en' ? 'Peruvian Agricultural Products for Export | Royal Beans Perú' : 'Productos agrícolas peruanos para exportación | Royal Beans Perú',
    'retail' => $lang === 'en' ? 'Peruvian Retail Products | Royal Beans Perú' : 'Productos Retail Peruanos | Royal Beans Perú',
    'category' => $category['title'],
    default => seo_catalog_brand($productTitleBase),
};
$description = match ($view) {
    'hub' => $lang === 'en'
        ? 'Royal Beans Perú supplies Peruvian pulses, Andean grains and spices for distributors, importers and international buyers.'
        : 'Royal Beans Perú comercializa y exporta legumbres, granos andinos y especias seleccionadas de origen peruano para compradores internacionales.',
    'retail' => $lang === 'en' ? 'Explore the retail presentations published by Royal Beans Perú and enquire about product and packaging options.' : 'Explore las presentaciones retail publicadas por Royal Beans Perú y consulte productos y formatos disponibles.',
    'category' => $category['description'],
    default => seo_catalog_excerpt((string) ($product['meta_' . $lang] ?? ''), (string) ($product['short_' . $lang] ?? ''), (string) ($product['description_' . $lang] ?? '')),
};
if ($description === '' && $product !== null) $description = $lang === 'en' ? "Explore {$name} from Royal Beans Perú and request commercial details." : "Conozca {$name} de Royal Beans Perú y consulte los detalles comerciales.";
$description = seo_catalog_brand($description);
$image = $product !== null ? seo_catalog_public_image($db, $product) : '';
$absoluteImage = $image === '' ? '' : (preg_match('#^https?://#i', $image) ? $image : $origin . '/' . ltrim($image, '/'));
$indexable = !(in_array($view, ['category', 'retail'], true) && count($products) === 0) && in_array(strtolower((string) ($_SERVER['HTTP_HOST'] ?? '')), ['royalbeansperu.com', 'www.royalbeansperu.com'], true);

$breadcrumbs = [[$lang === 'en' ? 'Home' : 'Inicio', $homePath], [$lang === 'en' ? 'Products' : 'Productos', $lang === 'en' ? '/en/products/' : '/productos/']];
if ($category !== null) $breadcrumbs[] = [$category['name'], seo_catalog_category_path($categoryKey, $lang)];
if ($view === 'retail') $breadcrumbs[] = [$lang === 'en' ? 'Retail' : 'Retail', $currentPath];
if ($product !== null) $breadcrumbs[] = [$name, $currentPath];
$breadcrumbSchema = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => []];
foreach ($breadcrumbs as $index => [$label, $path]) $breadcrumbSchema['itemListElement'][] = ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $label, 'item' => $origin . $path];
$schemas = [
    ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => 'Royal Beans Perú', 'url' => $origin . '/', 'logo' => $origin . '/images/logo.webp'],
    ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'Royal Beans Perú', 'url' => $origin . '/'],
    $breadcrumbSchema,
];
if ($product !== null) {
    $productSchema = ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $name, 'description' => $description, 'url' => $canonical, 'brand' => ['@type' => 'Brand', 'name' => 'Royal Beans Perú']];
    if ($absoluteImage !== '') $productSchema['image'] = [$absoluteImage];
    if ($category !== null) $productSchema['category'] = $category['name'];
    $schemas[] = $productSchema;
}

$productPackages = $gallery = $certifications = $related = $harvest = [];
if ($product !== null) {
    $statement = $db->prepare('SELECT weight_primary,weight_secondary,material_es,material_en,image_path FROM product_packages WHERE product_id=? AND is_available=1 ORDER BY sort_order,id');
    $statement->execute([(int) $product['id']]);
    $productPackages = $statement->fetchAll();
    $statement = $db->prepare('SELECT media_path,alt_es,alt_en FROM product_gallery WHERE product_id=? AND media_type=\'image\' ORDER BY sort_order,id');
    $statement->execute([(int) $product['id']]);
    $gallery = $statement->fetchAll();
    try {
        $statement = $db->prepare('SELECT name FROM product_certifications WHERE product_id=? ORDER BY sort_order,id');
        $statement->execute([(int) $product['id']]);
        $certifications = $statement->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $error) { error_log('[Royal Beans SEO certifications] ' . $error->getMessage()); }
    $statement = $db->prepare('SELECT month_number,availability FROM product_harvest WHERE product_id=? AND availability<>\'none\' ORDER BY month_number');
    $statement->execute([(int) $product['id']]);
    $harvest = $statement->fetchAll();
    $statement = $db->prepare('SELECT related_product_id FROM product_relations WHERE product_id=? ORDER BY sort_order,related_product_id');
    $statement->execute([(int) $product['id']]);
    $relatedIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    $allProducts = seo_catalog_products($db);
    foreach ($relatedIds as $relatedId) foreach ($allProducts as $candidate) if ((int) $candidate['id'] === $relatedId) $related[] = $candidate;
    if (!$related) $related = array_values(array_filter($allProducts, static fn(array $row): bool => (int) $row['id'] !== (int) $product['id'] && (int) $row['category_id'] === (int) $product['category_id']));
    $related = array_slice($related, 0, 4);
}
$whatsappNumber = '51961804500';
try {
    $channel = $db->query("SELECT link_url,value_text FROM contact_channels WHERE channel_type='whatsapp' AND is_active=1 ORDER BY sort_order,id LIMIT 1")->fetch();
    if ($channel) {
        $raw = (string) ($channel['link_url'] ?: $channel['value_text']);
        if (preg_match('/(?:phone=|wa\.me\/)([0-9]+)/', $raw, $match)) $whatsappNumber = $match[1];
        elseif (preg_match('/^\+?[0-9\s()-]+$/', $raw)) $whatsappNumber = (string) preg_replace('/\D/', '', $raw);
    }
} catch (Throwable $error) { error_log('[Royal Beans SEO WhatsApp] ' . $error->getMessage()); }
$whatsappText = $product !== null
    ? ($lang === 'en' ? "Hello, I would like a quote for {$name} from Royal Beans Perú. {$canonical}" : "Hola, estoy interesado en cotizar {$name} de Royal Beans Perú. {$canonical}")
    : ($lang === 'en' ? 'Hello, I would like to enquire about Royal Beans Perú products.' : 'Hola, deseo consultar por productos de Royal Beans Perú.');
$whatsappUrl = 'https://wa.me/' . $whatsappNumber . '?text=' . rawurlencode($whatsappText);

// Use the Products-active header/footer rendered by the same public layout.
$homeHtml = @file_get_contents(__DIR__ . ($lang === 'en' ? '/en/products/bulk-line/index.html' : '/productos/linea-a-granel/index.html')) ?: '';
$sharedStyles = $sharedHeader = $sharedFooter = '';
if ($homeHtml !== '') {
    preg_match_all('~<link[^>]+rel="stylesheet"[^>]*>~', $homeHtml, $styleMatches);
    $sharedStyles = implode("\n", $styleMatches[0] ?? []);
    if (preg_match('~<header\b[^>]*class="[^"]*shared-site-header[^"]*"[^>]*>.*?</header>~s', $homeHtml, $match)) $sharedHeader = $match[0];
    if (preg_match('~<footer\b[^>]*class="[^"]*shared-site-footer[^"]*"[^>]*>.*?</footer>~s', $homeHtml, $match)) $sharedFooter = $match[0];
}
if ($sharedHeader === '' || $sharedFooter === '' || $sharedStyles === '') {
    error_log('[Royal Beans SEO catalogue] Public header/footer template unavailable');
    http_response_code(503);
    header('Retry-After: 60');
    header('X-Robots-Tag: noindex, nofollow');
    exit($lang === 'en' ? 'The catalogue is temporarily unavailable.' : 'El catálogo no está disponible temporalmente.');
}
$sharedHeader = preg_replace_callback('~<a\b[^>]*\blang="(es|en)"[^>]*>~', static function (array $match) use ($esPath, $enPath, $e): string {
    $destination = $match[1] === 'en' ? $enPath : $esPath;
    return (string) preg_replace('~\bhref="[^"]*"~', 'href="' . $e($destination) . '"', $match[0], 1);
}, $sharedHeader);

header('Content-Type: text/html; charset=UTF-8');
header('Content-Language: ' . $lang);
header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: ' . ($indexable ? 'index, follow' : 'noindex, follow'));
?><!doctype html>
<html lang="<?= $e($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $e($title) ?></title>
  <meta name="description" content="<?= $e($description) ?>">
  <meta name="robots" content="<?= $indexable ? 'index, follow, max-image-preview:large' : 'noindex, follow' ?>">
  <link rel="canonical" href="<?= $e($canonical) ?>">
  <?php if ($esPath !== '' && $enPath !== ''): ?>
  <link rel="alternate" hreflang="es-PE" href="<?= $e($origin . $esPath) ?>">
  <link rel="alternate" hreflang="en" href="<?= $e($origin . $enPath) ?>">
  <link rel="alternate" hreflang="x-default" href="<?= $e($origin . $esPath) ?>">
  <?php endif; ?>
  <meta property="og:type" content="<?= $product !== null ? 'product' : 'website' ?>">
  <meta property="og:site_name" content="Royal Beans Perú">
  <meta property="og:title" content="<?= $e($title) ?>">
  <meta property="og:description" content="<?= $e($description) ?>">
  <meta property="og:url" content="<?= $e($canonical) ?>">
  <meta property="og:locale" content="<?= $lang === 'en' ? 'en_US' : 'es_PE' ?>">
  <?php if ($absoluteImage !== ''): ?><meta property="og:image" content="<?= $e($absoluteImage) ?>"><?php endif; ?>
  <meta name="twitter:card" content="<?= $absoluteImage !== '' ? 'summary_large_image' : 'summary' ?>">
  <meta name="twitter:title" content="<?= $e($title) ?>">
  <meta name="twitter:description" content="<?= $e($description) ?>">
  <?php if ($absoluteImage !== ''): ?><meta name="twitter:image" content="<?= $e($absoluteImage) ?>"><?php endif; ?>
  <link rel="icon" href="/favicon-64.png" sizes="64x64" type="image/png">
  <?= $sharedStyles ?>
  <link rel="stylesheet" href="/seo-catalog.css?v=20260925-1">
  <?php foreach ($schemas as $schema): ?><script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script><?php endforeach; ?>
</head>
<body>
<a class="skip" href="#main"><?= $lang === 'en' ? 'Skip to content' : 'Ir al contenido' ?></a>
<?= $sharedHeader ?>
<main id="main" class="wrap">
  <nav class="breadcrumbs" aria-label="Breadcrumb"><ol><?php foreach ($breadcrumbs as [$label, $path]): ?><li><a href="<?= $e($path) ?>"><?= $e($label) ?></a></li><?php endforeach; ?></ol></nav>
  <?php if ($view === 'hub'): ?>
    <div class="intro"><p class="eyebrow"><?= $lang === 'en' ? 'Royal Beans Perú portfolio' : 'Portafolio Royal Beans Perú' ?></p><h1><?= $lang === 'en' ? 'Peruvian agricultural products for international markets' : 'Productos agrícolas peruanos para exportación' ?></h1><p><?= $e($description) ?></p></div>
    <section aria-labelledby="categories-title"><h2 id="categories-title"><?= $lang === 'en' ? 'Explore by category' : 'Explore por categoría' ?></h2><div class="cards category-cards">
      <?php foreach ($categories as $key => $details): $categoryProducts = array_values(array_filter($products, static fn(array $row): bool => $row['line_slug'] === 'conventional' && seo_catalog_category_key((string) $row['category_slug']) === $key)); if (!$categoryProducts) continue; $firstImage = seo_catalog_public_image($db, $categoryProducts[0]); ?>
      <article class="card"><a href="<?= $e(seo_catalog_category_path($key, $lang)) ?>"><?php if ($firstImage !== ''): ?><img src="<?= $e($firstImage) ?>" alt="<?= $e($categoryProducts[0]['name_' . $lang] ?: $categoryProducts[0]['name_es']) ?>" width="480" height="320" loading="lazy"><?php endif; ?><h3><?= $e($details[$lang]['name']) ?></h3><p><?= $e($details[$lang]['description']) ?></p><span class="more"><?= $lang === 'en' ? 'View products →' : 'Ver productos →' ?></span></a></article>
      <?php endforeach; ?>
      <article class="card retail-card"><a href="<?= $lang === 'en' ? '/en/products/retail/' : '/productos/linea-retail/' ?>"><h3><?= $lang === 'en' ? 'Retail line' : 'Línea Retail' ?></h3><p><?= $lang === 'en' ? 'Explore our retail presentations and contact our team about available formats.' : 'Explore nuestras presentaciones retail y consulte los formatos disponibles con nuestro equipo.' ?></p><span class="more"><?= $lang === 'en' ? 'Explore retail →' : 'Explorar retail →' ?></span></a></article>
    </div></section>
    <section aria-labelledby="selected-title"><h2 id="selected-title"><?= $lang === 'en' ? 'Products in our portfolio' : 'Productos del portafolio' ?></h2><div class="cards product-cards"><?php foreach (array_slice(array_values(array_filter($products, static fn(array $row): bool => $row['line_slug'] === 'conventional')), 0, 8) as $row) { require __DIR__ . '/seo-product-card.php'; } ?></div></section>
  <?php elseif ($view === 'category' || $view === 'retail'): ?>
    <div class="intro"><p class="eyebrow"><?= $lang === 'en' ? 'Product category' : 'Categoría de productos' ?></p><h1><?= $view === 'retail' ? ($lang === 'en' ? 'Peruvian products for retail' : 'Productos peruanos de línea Retail') : $e($category['title']) ?></h1><p><?= $e($description) ?></p></div>
    <?php if ($products): ?><section aria-labelledby="category-products"><h2 id="category-products"><?= $lang === 'en' ? 'Available products' : 'Productos disponibles' ?></h2><div class="cards product-cards"><?php foreach ($products as $row) { require __DIR__ . '/seo-product-card.php'; } ?></div></section><?php else: ?><p><?= $lang === 'en' ? 'Ask our team about upcoming availability in this category.' : 'Consulte con nuestro equipo la próxima disponibilidad de esta categoría.' ?></p><?php endif; ?>
  <?php else: ?>
    <article class="product-detail"><div class="product-images"><?php if ($image !== ''): ?><img class="main-image" src="<?= $e($image) ?>" alt="<?= $e($name . ' - Royal Beans Perú') ?>" width="900" height="900" fetchpriority="high"><?php endif; ?><?php foreach ($gallery as $index => $photo): $photoPath = rb_media_path($db, (string) $photo['media_path']); ?><img src="<?= $e($photoPath) ?>" alt="<?= $e((string) ($photo['alt_' . $lang] ?: $name . ' - Royal Beans Perú')) ?>" width="600" height="600" loading="lazy"><?php endforeach; ?></div><div class="product-copy"><p class="eyebrow"><?= $e($product['line_name_' . $lang]) ?></p><h1><?= $e($name) ?></h1><?php if ($product['short_' . $lang]): ?><p class="lead"><?= $e($product['short_' . $lang]) ?></p><?php endif; ?><?php if ($product['description_' . $lang]): ?><section><h2><?= $lang === 'en' ? 'Product details' : 'Características del producto' ?></h2><p class="preserve-lines"><?= $e($product['description_' . $lang]) ?></p></section><?php endif; ?>
      <?php $specs = [[$lang === 'en' ? 'Scientific name' : 'Nombre científico', $product['scientific_name']], [$lang === 'en' ? 'Tariff code' : 'Partida arancelaria', $product['tariff_code']], [$lang === 'en' ? 'Caliber' : 'Calibre', $product['caliber_' . $lang]], [$lang === 'en' ? 'Markets' : 'Mercados', $product['destinations_' . $lang]]]; $shownSpecs = array_values(array_filter($specs, static fn(array $spec): bool => trim((string) $spec[1]) !== '')); if ($shownSpecs): ?><section><h2><?= $lang === 'en' ? 'Specifications' : 'Especificaciones' ?></h2><dl><?php foreach ($shownSpecs as [$label, $value]): ?><div><dt><?= $e($label) ?></dt><dd><?= $e($value) ?></dd></div><?php endforeach; ?></dl></section><?php endif; ?>
      <?php if ($harvest): ?><section><h2><?= $lang === 'en' ? 'Agricultural calendar' : 'Calendario agrícola' ?></h2><ul class="packages"><?php foreach ($harvest as $period): ?><li><?= $e((string) $period['month_number']) ?> · <?= $e($period['availability'] === 'harvest' ? ($lang === 'en' ? 'Harvest' : 'Cosecha') : ($lang === 'en' ? 'Planting' : 'Siembra')) ?></li><?php endforeach; ?></ul></section><?php endif; ?>
      <?php if ($productPackages): ?><section><h2><?= $lang === 'en' ? 'Presentations' : 'Presentaciones' ?></h2><ul class="packages"><?php foreach ($productPackages as $package): ?><li><?= $e($package['weight_primary']) ?><?= $package['weight_secondary'] ? ' · ' . $e($package['weight_secondary']) : '' ?><?= $package['material_' . $lang] ? ' · ' . $e($package['material_' . $lang]) : '' ?></li><?php endforeach; ?></ul></section><?php endif; ?>
      <?php if ($certifications): ?><section><h2><?= $lang === 'en' ? 'Certifications' : 'Certificaciones' ?></h2><ul><?php foreach ($certifications as $certification): ?><li><?= $e($certification) ?></li><?php endforeach; ?></ul></section><?php endif; ?>
      <div class="actions"><a class="button" href="<?= $e($whatsappUrl) ?>" target="_blank" rel="noopener noreferrer"><?= $lang === 'en' ? 'Request a quote via WhatsApp' : 'Solicitar cotización por WhatsApp' ?></a><a class="button secondary" href="<?= $lang === 'en' ? '/en/contact/' : '/contacto/' ?>"><?= $lang === 'en' ? 'Check availability' : 'Consultar disponibilidad' ?></a><?php if ($product['technical_sheet_path']): ?><a class="button secondary" href="<?= $e(rb_media_path($db, (string) $product['technical_sheet_path'])) ?>"><?= $lang === 'en' ? 'Technical sheet' : 'Ficha técnica' ?></a><?php endif; ?></div>
    </div></article>
    <?php if ($related): ?><section aria-labelledby="related-title"><h2 id="related-title"><?= $lang === 'en' ? 'Related products' : 'Productos relacionados' ?></h2><div class="cards product-cards"><?php foreach ($related as $row) { require __DIR__ . '/seo-product-card.php'; } ?></div></section><?php endif; ?>
  <?php endif; ?>
  <aside class="closing-cta"><h2><?= $lang === 'en' ? 'Discuss your requirements with us' : 'Conversemos sobre su requerimiento' ?></h2><a class="button" href="<?= $e($whatsappUrl) ?>" target="_blank" rel="noopener noreferrer"><?= $lang === 'en' ? 'Contact via WhatsApp' : 'Contactar por WhatsApp' ?></a></aside>
</main>
<?= $sharedFooter ?>
<script>window.rbSeoLanguagePaths=<?= json_encode(['es' => $esPath, 'en' => $enPath], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;</script>
<script src="/seo-catalog-chrome.js" defer></script>
</body></html>
