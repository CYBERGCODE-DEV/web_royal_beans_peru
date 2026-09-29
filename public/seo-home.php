<?php
declare(strict_types=1);

require_once __DIR__ . '/seo-catalog-data.php';
require_once __DIR__ . '/cms-server-render.php';

$lang = ($_GET['lang'] ?? 'es') === 'en' ? 'en' : 'es';
$rsc = ($_GET['format'] ?? '') === 'rsc' || isset($_GET['_rsc']) || ($_SERVER['HTTP_RSC'] ?? '') === '1';
$source = __DIR__ . ($lang === 'en' ? '/en/index' : '/index') . ($rsc ? '.txt' : '.html');

try {
    $html = file_get_contents($source);
    if (!is_string($html) || $html === '') throw new RuntimeException('Home HTML unavailable');
    $oldCatalogueCta = $lang === 'en' ? 'View all' : 'Ver todos';
    $newCatalogueCta = $lang === 'en' ? 'View Bulk Line' : 'Ver Línea a Granel';
    $db = rb_pdo();
    $categories = seo_catalog_categories($db);
    $products = [];
    foreach (seo_catalog_card_products($db) as $product) {
        if ($product['line_slug'] !== 'conventional' || (int) $product['featured_home'] !== 1) continue;
        $originalImage = seo_catalog_public_image($db, $product);
        $products[] = [
            'id' => (int) $product['id'],
            'image' => rb_image_variant($originalImage, '640'),
            'image_320' => rb_image_variant($originalImage, '320'),
            'image_640' => rb_image_variant($originalImage, '640'),
            'line' => $product['line_slug'],
            'category' => $product['category_slug'],
            'category_name_es' => $categories[$product['category_slug']]['es']['name'] ?? '',
            'category_name_en' => $categories[$product['category_slug']]['en']['name'] ?? '',
            'es' => (string) ($product['name_es'] ?? ''),
            'en' => (string) ($product['name_en'] ?? ''),
            'slug_es' => (string) ($product['slug_es'] ?? ''),
            'slug_en' => (string) ($product['slug_en'] ?? ''),
            'description_es' => (string) ($product['short_es'] ?? ''),
            'description_en' => (string) ($product['short_en'] ?? ''),
            'alt_es' => seo_catalog_product_alt($product, 'es'),
            'alt_en' => seo_catalog_product_alt($product, 'en'),
        ];
    }
    if (!$products) throw new RuntimeException('No featured products are published');

    // Next's static HTML contains the serialized initialProducts prop. Update it
    // together with the visible cards so React hydrates the same BD snapshot.
    $marker = $rsc ? '"initialProducts":[' : '\\"initialProducts\\":[';
    $markerAt = strpos($html, $marker);
    if ($markerAt === false) throw new RuntimeException('Home product payload not found');
    $arrayStart = $markerAt + strlen($marker) - 1;
    $depth = 0;
    $arrayEnd = null;
    for ($i = $arrayStart, $length = strlen($html); $i < $length; $i++) {
        if ($html[$i] === '[') $depth++;
        elseif ($html[$i] === ']' && --$depth === 0) { $arrayEnd = $i; break; }
    }
    if ($arrayEnd === null) throw new RuntimeException('Home product payload is malformed');
    $json = json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR);
    $encoded = str_replace(['\\', '"'], ['\\\\', '\\"'], $json);
    $html = substr_replace($html, $rsc ? $json : $encoded, $arrayStart, $arrayEnd - $arrayStart + 1);

    if ($rsc) {
        header('Content-Type: text/x-component; charset=UTF-8');
        header('Cache-Control: no-store, max-age=0');
        header('CDN-Cache-Control: no-store');
        header('Cloudflare-CDN-Cache-Control: no-store');
        echo str_replace($oldCatalogueCta, $newCatalogueCta, cms_server_render($html, 'inicio', $lang, true, $db));
        exit;
    }

    if (!preg_match('~<section class="home-products"[^>]*>.*?</section>~s', $html, $sectionMatch)) throw new RuntimeException('Home product section not found');
    $section = $sectionMatch[0];
    $count = count($products);
    $cardCount = 0;
    $section = preg_replace_callback('~<article class="home-product-card[^>]*>.*?</article>~s', static function (array $match) use ($products, $lang, $count, &$cardCount): string {
        $offset = $cardCount++;
        $product = $products[($offset - 1 + $count) % $count];
        $name = htmlspecialchars($product[$lang], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $image = htmlspecialchars($product['image'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $smallImage = htmlspecialchars($product['image_320'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $linePath = $lang === 'en' ? '/en/products/bulk-line/' : '/productos/linea-a-granel/';
        $path = htmlspecialchars($linePath . '?producto=' . rawurlencode($product['slug_' . $lang]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $card = preg_replace('~data-product-id="[^"]*"~', 'data-product-id="' . $product['id'] . '"', $match[0], 1);
        $card = preg_replace_callback('~<h3>.*?</h3>~s', static fn (): string => '<h3>' . $name . '</h3>', $card, 1);
        $card = preg_replace_callback('~<a\b[^>]*data-product-preview-link[^>]*>~', static fn (array $linkMatch): string => preg_replace('~\bhref="[^"]*"~', 'href="' . $path . '"', $linkMatch[0], 1), $card);
        $alt = htmlspecialchars($product['alt_' . $lang], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $card = preg_replace_callback('~<img\b[^>]*>~', static function (array $imageMatch) use ($image, $smallImage, $alt): string {
            $tag = preg_replace_callback('~\bsrc="[^"]*"~', static fn (): string => 'src="' . $image . '"', $imageMatch[0], 1);
            $tag = preg_replace('~\bsrcSet="[^"]*"~', 'srcset="' . $smallImage . ' 320w, ' . $image . ' 640w"', $tag, 1) ?? $tag;
            if (!str_contains(strtolower($tag), 'srcset=')) $tag = str_replace('src="' . $image . '"', 'src="' . $image . '" srcset="' . $smallImage . ' 320w, ' . $image . ' 640w" sizes="(max-width: 640px) 85vw, 360px"', $tag);
            return preg_replace_callback('~\balt="[^"]*"~', static fn (): string => 'alt="' . $alt . '"', $tag, 1);
        }, $card, 1);
        return $card;
    }, $section);
    if ($cardCount !== 6) throw new RuntimeException('Home carousel template changed');
    $html = str_replace($sectionMatch[0], $section, $html);

    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, max-age=0');
    header('CDN-Cache-Control: no-store');
    header('Cloudflare-CDN-Cache-Control: no-store');
    echo str_replace($oldCatalogueCta, $newCatalogueCta, cms_server_render($html, 'inicio', $lang, false, $db));
} catch (Throwable $error) {
    error_log('[Royal Beans dynamic home] ' . $error->getMessage());
    http_response_code(503);
    header('Retry-After: 60');
    header('X-Robots-Tag: noindex, nofollow');
    echo $lang === 'en' ? 'The home page is temporarily unavailable.' : 'La página de inicio no está disponible temporalmente.';
}
