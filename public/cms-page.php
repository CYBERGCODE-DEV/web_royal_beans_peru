<?php
declare(strict_types=1);

require_once __DIR__ . '/cms-server-render.php';
require_once __DIR__ . '/seo-catalog-data.php';

$route = trim((string) ($_GET['path'] ?? ''), '/');
$lang = str_starts_with($route, 'en/') ? 'en' : 'es';
$routes = [
    'nosotros'=>'nosotros', 'participacion'=>'participacion', 'impacto'=>'impacto', 'contacto'=>'contacto',
    'politica-de-privacidad'=>'privacidad', 'terminos-y-condiciones'=>'terminos',
    'productos/linea-a-granel'=>'productos-conventional', 'productos/linea-retail'=>'productos-retail',
    'en/about-us'=>'nosotros', 'en/events'=>'participacion', 'en/impact'=>'impacto', 'en/contact'=>'contacto',
    'en/privacy-policy'=>'privacidad', 'en/terms-and-conditions'=>'terminos',
    'en/products/bulk-line'=>'productos-conventional', 'en/products/retail'=>'productos-retail',
];
$page = $routes[$route] ?? null;
if ($page === null) { http_response_code(404); exit; }
if (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) === '/cms-page.php') {
    header('Location: /' . $route . '/', true, 301);
    exit;
}
$rsc = ($_GET['format'] ?? '') === 'rsc' || isset($_GET['_rsc']) || ($_SERVER['HTTP_RSC'] ?? '') === '1';
$source = __DIR__ . '/' . cms_server_static_path($page, $lang) . '/index.' . ($rsc ? 'txt' : 'html');
header('Cache-Control: no-store, max-age=0');
header('CDN-Cache-Control: no-store');
header('Cloudflare-CDN-Cache-Control: no-store');
header('Content-Type: ' . ($rsc ? 'text/x-component' : 'text/html') . '; charset=UTF-8');
try {
    $html = file_get_contents($source);
    if (!is_string($html) || $html === '') throw new RuntimeException('Missing static shell');
    $html = cms_server_render($html, $page, $lang, $rsc);
    if (!$rsc && str_starts_with($page, 'productos-') && isset($_GET['categoria']) && !isset($_GET['producto'])) {
        $html = preg_replace('~<meta name="robots" content="[^"]*"/>~', '<meta name="robots" content="noindex, follow"/>', $html, 1) ?? $html;
        header('X-Robots-Tag: noindex, follow');
    }
    $allProducts = [];
    if (!$rsc && str_starts_with($page, 'productos-')) {
        $db = rb_pdo();
        $allProducts = seo_catalog_card_products($db);
        $productById = [];
        foreach ($allProducts as $item) $productById[(int) $item['id']] = $item;
        $html = preg_replace_callback('~<article class="product-card" data-product-id="(\d+)"[^>]*>.*?</article>~s', static function (array $match) use ($db, $lang, $productById): string {
            $item = $productById[(int) $match[1]] ?? null;
            if (!$item) return $match[0];
            $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $href = $escape(seo_catalog_product_path($item, $lang));
            $alt = $escape(seo_catalog_product_alt($item, $lang));
            $card = preg_replace_callback('~<a\b[^>]*data-product-preview-link[^>]*>~', static fn (array $link): string => preg_replace_callback('~href="[^"]*"~', static fn (): string => 'href="' . $href . '"', $link[0], 1) ?? $link[0], $match[0]) ?? $match[0];
            $original = seo_catalog_public_image($db, $item);
            $small = $escape(rb_image_variant($original, '320'));
            $medium = $escape(rb_image_variant($original, '640'));
            return preg_replace_callback('~<img\b[^>]*>~', static function (array $image) use ($alt, $small, $medium): string {
                $tag = preg_replace('~\s(?:src|srcSet|sizes|alt)="[^"]*"~', '', $image[0]) ?? $image[0];
                return preg_replace('~\s*/?>$~', ' src="' . $medium . '" srcset="' . $small . ' 320w, ' . $medium . ' 640w" sizes="(max-width: 640px) 45vw, (max-width: 1024px) 30vw, 260px" alt="' . $alt . '"/>', $tag, 1) ?? $tag;
            }, $card, 1) ?? $card;
        }, $html) ?? $html;
    }
    // The same line page remains canonical; the query identifies the selected
    // product for crawlers and supplies its translated data in the first HTML.
    if (!$rsc && str_starts_with($page, 'productos-')) {
        $slug = trim((string) ($_GET['producto'] ?? ''));
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $selectedTranslation = $db->prepare('SELECT product_id FROM product_translations WHERE locale=? AND slug=? LIMIT 1');
            $selectedTranslation->execute([$lang, $slug]);
            $product = $productById[(int) $selectedTranslation->fetchColumn()] ?? null;
            // The catalogue index contains only active, non-deleted products.
            if ($product && ($product['line_slug'] === 'retail') === ($page === 'productos-retail') && seo_catalog_slug($product, $lang) === $slug) {
                $product = seo_catalog_full_product($db, $product);
                $name = trim((string) ($product['name_' . $lang] ?? ''));
                $description = trim((string) ($product['short_' . $lang] ?: $product['description_' . $lang] ?? ''));
                $descriptionEs = trim((string) ($product['short_es'] ?: $product['description_es'] ?? ''));
                $descriptionEn = trim((string) ($product['short_en'] ?: $product['description_en'] ?? ''));
                $imageOriginal = seo_catalog_public_image($db, $product);
                $image = rb_image_variant($imageOriginal, '1200');
                $cardImage = rb_image_variant($imageOriginal, '640');
                $imageAlt = seo_catalog_product_alt($product, $lang);
                $detail = seo_catalog_product_detail($db, $product, $lang, $allProducts);
                $esPath = seo_catalog_product_path($product, 'es');
                $enPath = seo_catalog_product_path($product, 'en');
                $origin = seo_catalog_origin();
                $productUrl = $origin . seo_catalog_product_path($product, $lang);
                $title = trim((string) ($product['title_' . $lang] ?? '')) ?: $name . ' | Royal Beans Perú';
                $metaDescription = trim((string) ($product['meta_' . $lang] ?? '')) ?: $description;
                $absoluteImage = str_starts_with($image, '/') ? $origin . $image : $image;
                $imageObject = ['@type'=>'ImageObject','contentUrl'=>$absoluteImage,'url'=>$absoluteImage,'name'=>$imageAlt,'inLanguage'=>$lang === 'es' ? 'es-PE' : 'en'];
                $schema = ['@context'=>'https://schema.org','@type'=>'Product','name'=>$name,'description'=>$metaDescription,'image'=>[$imageObject],'url'=>$productUrl,'inLanguage'=>$lang === 'es' ? 'es-PE' : 'en','sku'=>(string) $product['id']];
                $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR);
                $escapedTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $escapedDescription = htmlspecialchars($metaDescription, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $escapedImage = htmlspecialchars($absoluteImage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $escapedUrl = htmlspecialchars($productUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $html = preg_replace('~<title>.*?</title>~s', '<title>' . $escapedTitle . '</title>', $html, 1) ?? $html;
                foreach (['description'=>$escapedDescription,'twitter:title'=>$escapedTitle,'twitter:description'=>$escapedDescription] as $key=>$value) {
                    $html = preg_replace('~<meta name="' . preg_quote($key, '~') . '" content="[^"]*"/>~', '<meta name="' . $key . '" content="' . $value . '"/>', $html, 1) ?? $html;
                }
                foreach (['og:title'=>$escapedTitle,'og:description'=>$escapedDescription,'og:url'=>$escapedUrl,'og:type'=>'product'] as $key=>$value) {
                    $html = preg_replace('~<meta property="' . preg_quote($key, '~') . '" content="[^"]*"/>~', '<meta property="' . $key . '" content="' . $value . '"/>', $html, 1) ?? $html;
                }
                $html = preg_replace('~<link rel="canonical" href="[^"]*"/>~', '<link rel="canonical" href="' . $escapedUrl . '"/>', $html, 1) ?? $html;
                $selection = json_encode([
                    'productId'=>(int) $product['id'],
                    'category'=>(string) $product['category_slug'],
                    'languagePaths'=>['es'=>$esPath,'en'=>$enPath],
                    'product'=>[
                        'id'=>(int) $product['id'], 'es'=>(string) $product['name_es'], 'en'=>(string) $product['name_en'],
                        'slug_es'=>(string) $product['slug_es'], 'slug_en'=>(string) $product['slug_en'],
                        'description_es'=>$descriptionEs, 'description_en'=>$descriptionEn,
                        'category'=>(string) $product['category_slug'], 'image'=>$cardImage,
                        'image_320'=>rb_image_variant($imageOriginal, '320'), 'image_640'=>$cardImage,
                        'alt_es'=>seo_catalog_product_alt($product, 'es'), 'alt_en'=>seo_catalog_product_alt($product, 'en'),
                        'line'=>$product['line_slug'] === 'retail' ? 'retail' : 'conventional',
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR);
                $detailJson = json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR);
                $html = str_replace('</head>', '<meta property="og:image" content="' . $escapedImage . '"/><meta name="twitter:image" content="' . $escapedImage . '"/><script type="application/ld+json" id="selected-product-schema">' . $json . '</script><script type="application/json" id="selected-product-data">' . $selection . '</script><script type="application/json" id="selected-product-detail-data">' . $detailJson . '</script><script id="selected-product-modal-open">(()=>{const open=()=>{const dialog=document.querySelector(".product-sheet-dialog");if(!dialog)return;try{if(dialog.open)dialog.close();dialog.showModal()}catch{dialog.setAttribute("open","")}};if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",open,{once:true});else open()})()</script></head>', $html);
                $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $lineName = $product['line_slug'] === 'retail' ? ($lang === 'es' ? 'Línea retail' : 'Retail line') : ($lang === 'es' ? 'Línea a granel' : 'Bulk line');
                $lead = $description !== '' ? $description : ($lang === 'es' ? 'Producto peruano seleccionado por su calidad, trazabilidad y origen.' : 'Peruvian product selected for its quality, traceability and origin.');
                $dialog = '<dialog class="product-sheet-dialog" open data-cms-managed="true" role="dialog" aria-modal="true" aria-labelledby="product-sheet-title">'
                    . '<button class="product-sheet-close" type="button" aria-label="' . ($lang === 'es' ? 'Cerrar ficha' : 'Close product sheet') . '">×</button>'
                    . '<a class="product-sheet-language" href="' . $escape($lang === 'es' ? $enPath : $esPath) . '" lang="' . ($lang === 'es' ? 'en' : 'es') . '" aria-label="' . ($lang === 'es' ? 'Ver este producto en inglés' : 'View this product in Spanish') . '">' . ($lang === 'es' ? 'EN' : 'ES') . '</a>'
                    . '<div class="product-sheet-dialog-inner"><article class="native-product-sheet' . ($product['line_slug'] === 'retail' ? ' retail-product-sheet' : '') . '" aria-busy="true"><section class="native-product-hero">'
                    . '<div class="native-product-gallery"><button class="native-product-zoom" type="button" tabindex="-1"><img src="' . $escape($image) . '" alt="' . $escape((string) $imageAlt) . '" width="1200" height="1200" decoding="async"/></button></div>'
                    . '<div class="native-product-copy"><p class="eyebrow">' . $lineName . '</p><h2 id="product-sheet-title">' . $escape($name) . '</h2><p class="native-product-lead">' . $escape($lead) . '</p></div>'
                    . '</section></article></div></dialog>';
                $html = preg_replace_callback('~<dialog class="product-sheet-dialog"[^>]*></dialog>~', static fn (): string => $dialog, $html, 1, $dialogCount) ?? $html;
                if ($dialogCount !== 1) throw new RuntimeException('Product dialog shell not found');
                if ($esPath !== '' && $enPath !== '') {
                    $html = preg_replace_callback('~<link rel="alternate" hrefLang="(es-PE|en|x-default)" href="[^"]*"/>~i', static function (array $match) use ($origin, $esPath, $enPath): string {
                        $path = strtolower($match[1]) === 'en' ? $enPath : $esPath;
                        return '<link rel="alternate" hrefLang="' . $match[1] . '" href="' . htmlspecialchars($origin . $path, ENT_QUOTES, 'UTF-8') . '"/>';
                    }, $html) ?? $html;
                    $html = preg_replace_callback('~<a href="[^"]*" lang="(es|en)"~', static function (array $match) use ($esPath, $enPath): string {
                        return '<a href="' . htmlspecialchars($match[1] === 'en' ? $enPath : $esPath, ENT_QUOTES, 'UTF-8') . '" lang="' . $match[1] . '"';
                    }, $html) ?? $html;
                }
            }
        }
    }
    echo $html;
} catch (Throwable $error) {
    error_log('[Royal Beans CMS initial HTML] ' . $error->getMessage());
    http_response_code(503);
    header('Retry-After: 30');
    header('X-Robots-Tag: noindex, nofollow');
    echo $rsc ? '' : ($lang === 'en' ? 'Content temporarily unavailable.' : 'Contenido temporalmente no disponible.');
}
