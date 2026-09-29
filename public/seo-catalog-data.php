<?php
declare(strict_types=1);

require_once __DIR__ . '/api/_bootstrap.php';

function seo_catalog_categories(?PDO $db = null): array {
    static $cached = null;
    if ($cached !== null) return $cached;
    $db ??= rb_pdo();
    $cached = rb_public_cache('catalog:categories', static function () use ($db): array {
    $categories = [];
    foreach ($db->query("SELECT id,slug,name_es,name_en,seo_slug_es,seo_slug_en FROM product_categories WHERE is_active=1 ORDER BY sort_order,id") as $row) {
        $key = (string) $row['slug'];
        $esName = trim((string) $row['name_es']);
        $enName = trim((string) $row['name_en']);
        $categories[$key] = [
            'id' => (int) $row['id'],
            'es' => ['slug' => (string) ($row['seo_slug_es'] ?: $key), 'name' => $esName, 'title' => $esName . ' para exportación | Royal Beans Perú', 'description' => 'Conozca los productos de ' . $esName . ' publicados por Royal Beans Perú y consulte sus características y presentaciones disponibles.'],
            'en' => ['slug' => (string) ($row['seo_slug_en'] ?: $key), 'name' => $enName, 'title' => $enName . ' from Peru | Royal Beans Perú', 'description' => 'Explore ' . $enName . ' published by Royal Beans Perú and enquire about specifications and available packaging.'],
        ];
    }
    return $categories;
    });
    return $cached;
}

function seo_catalog_category_key(string $databaseSlug): string {
    return isset(seo_catalog_categories()[$databaseSlug]) ? $databaseSlug : '';
}

function seo_catalog_category_path(string $key, string $lang): string {
    $category = seo_catalog_categories()[$key][$lang] ?? null;
    return $category ? ($lang === 'en' ? '/en/products/' : '/productos/') . $category['slug'] . '/' : '';
}

function seo_catalog_products(PDO $db): array {
    $sql = "SELECT p.id,p.category_id,p.featured_home,p.image_path,p.updated_at,p.sort_order,l.slug AS line_slug,l.name_es AS line_name_es,l.name_en AS line_name_en,c.slug AS category_slug,
        CASE WHEN l.slug='retail' THEN COALESCE((SELECT pp.image_path FROM product_packages pp WHERE pp.product_id=p.id AND pp.is_available=1 AND pp.image_path<>'' ORDER BY pp.sort_order,pp.id LIMIT 1),p.image_path) ELSE p.image_path END AS display_image,
        es.name AS name_es,es.slug AS slug_es,es.short_description AS short_es,es.description AS description_es,es.seo_title AS title_es,es.seo_description AS meta_es,
        en.name AS name_en,en.slug AS slug_en,en.short_description AS short_en,en.description AS description_en,en.seo_title AS title_en,en.seo_description AS meta_en,
        COALESCE((SELECT g.alt_es FROM product_gallery g WHERE g.product_id=p.id AND g.media_type='image' AND g.alt_es<>'' ORDER BY (g.media_path=p.image_path) DESC,g.sort_order,g.id LIMIT 1),'') AS alt_es,
        COALESCE((SELECT g.alt_en FROM product_gallery g WHERE g.product_id=p.id AND g.media_type='image' AND g.alt_en<>'' ORDER BY (g.media_path=p.image_path) DESC,g.sort_order,g.id LIMIT 1),'') AS alt_en,
        COALESCE(s.scientific_name,'') AS scientific_name,COALESCE(s.tariff_code,'') AS tariff_code,COALESCE(s.caliber_es,'') AS caliber_es,COALESCE(s.caliber_en,'') AS caliber_en,
        COALESCE(s.destinations_es,'') AS destinations_es,COALESCE(s.destinations_en,'') AS destinations_en,COALESCE(s.technical_sheet_path,'') AS technical_sheet_path
        FROM products p JOIN product_lines l ON l.id=p.line_id JOIN product_categories c ON c.id=p.category_id
        LEFT JOIN product_translations es ON es.product_id=p.id AND es.locale='es'
        LEFT JOIN product_translations en ON en.product_id=p.id AND en.locale='en'
        LEFT JOIN product_specs s ON s.product_id=p.id
        WHERE p.is_active=1 AND p.deleted_at IS NULL AND l.is_active=1 AND c.is_active=1
        ORDER BY l.sort_order,p.sort_order,p.id";
    return rb_public_cache('catalog:cards', static fn(): array => $db->query($sql)->fetchAll());
}

function seo_catalog_card_products(PDO $db): array {
    return rb_public_cache('catalog:card-only', static function () use ($db): array {
        $rows = $db->query("SELECT p.id,p.category_id,p.featured_home,p.image_path,p.updated_at,p.sort_order,l.slug AS line_slug,c.slug AS category_slug,
          es.name AS name_es,es.slug AS slug_es,es.short_description AS short_es,
          en.name AS name_en,en.slug AS slug_en,en.short_description AS short_en
          FROM products p JOIN product_lines l ON l.id=p.line_id AND l.is_active=1
          JOIN product_categories c ON c.id=p.category_id AND c.is_active=1
          LEFT JOIN product_translations es ON es.product_id=p.id AND es.locale='es'
          LEFT JOIN product_translations en ON en.product_id=p.id AND en.locale='en'
          WHERE p.is_active=1 AND p.deleted_at IS NULL ORDER BY l.sort_order,p.sort_order,p.id")->fetchAll();
        $byId = [];
        foreach ($rows as $index=>$row) { $byId[(int)$row['id']]=$index; $rows[$index]['display_image']=$row['image_path']; $rows[$index]['alt_es']=''; $rows[$index]['alt_en']=''; }
        if (!$rows) return $rows;
        $selectedPackage=[];
        foreach ($db->query("SELECT pp.product_id,pp.image_path FROM product_packages pp JOIN products p ON p.id=pp.product_id JOIN product_lines l ON l.id=p.line_id WHERE p.is_active=1 AND p.deleted_at IS NULL AND l.slug='retail' AND pp.is_available=1 AND pp.image_path<>'' ORDER BY pp.product_id,pp.sort_order,pp.id") as $package) {
            $index = $byId[(int)$package['product_id']] ?? null;
            if ($index !== null && !isset($selectedPackage[$index])) { $rows[$index]['display_image']=$package['image_path'];$selectedPackage[$index]=true; }
        }
        foreach ($db->query("SELECT g.product_id,g.alt_es,g.alt_en FROM product_gallery g JOIN products p ON p.id=g.product_id WHERE p.is_active=1 AND p.deleted_at IS NULL AND g.media_type='image' ORDER BY g.product_id,g.sort_order,g.id") as $gallery) {
            $index = $byId[(int)$gallery['product_id']] ?? null;
            if ($index === null) continue;
            foreach (['es','en'] as $locale) if ($rows[$index]['alt_'.$locale] === '' && trim((string)$gallery['alt_'.$locale]) !== '') $rows[$index]['alt_'.$locale]=$gallery['alt_'.$locale];
        }
        return $rows;
    });
}

function seo_catalog_full_product(PDO $db,array $card): array {
    $statement = $db->prepare("SELECT es.description AS description_es,es.seo_title AS title_es,es.seo_description AS meta_es,
      en.description AS description_en,en.seo_title AS title_en,en.seo_description AS meta_en,
      COALESCE(s.scientific_name,'') AS scientific_name,COALESCE(s.tariff_code,'') AS tariff_code,
      COALESCE(s.caliber_es,'') AS caliber_es,COALESCE(s.caliber_en,'') AS caliber_en,
      COALESCE(s.destinations_es,'') AS destinations_es,COALESCE(s.destinations_en,'') AS destinations_en,
      COALESCE(s.technical_sheet_path,'') AS technical_sheet_path
      FROM products p LEFT JOIN product_translations es ON es.product_id=p.id AND es.locale='es'
      LEFT JOIN product_translations en ON en.product_id=p.id AND en.locale='en'
      LEFT JOIN product_specs s ON s.product_id=p.id WHERE p.id=? AND p.is_active=1 AND p.deleted_at IS NULL LIMIT 1");
    $statement->execute([(int)$card['id']]);
    $details = $statement->fetch();
    if (!$details) throw new RuntimeException('Selected product is unavailable');
    return array_merge($card,$details);
}

function seo_catalog_slug(array $product, string $lang): string {
    $stored = trim((string) ($product['slug_' . $lang] ?? ''));
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $stored) ? $stored : '';
}

function seo_catalog_product_path(array $product, string $lang): string {
    $slug = seo_catalog_slug($product, $lang);
    if ($slug === '') return '';
    $retail = ($product['line_slug'] ?? '') === 'retail';
    $linePath = $lang === 'en'
        ? ($retail ? '/en/products/retail/' : '/en/products/bulk-line/')
        : ($retail ? '/productos/linea-retail/' : '/productos/linea-a-granel/');
    return $linePath . '?producto=' . rawurlencode($slug);
}

function seo_catalog_public_image(PDO $db, array $product): string {
    $path = (string) ($product['display_image'] ?? $product['image_path'] ?? '');
    if (!array_key_exists('display_image', $product) && $product['line_slug'] === 'retail') {
        $statement = $db->prepare("SELECT image_path FROM product_packages WHERE product_id=? AND is_available=1 AND image_path<>'' ORDER BY sort_order,id LIMIT 1");
        $statement->execute([(int) $product['id']]);
        $path = (string) ($statement->fetchColumn() ?: $path);
    }
    return $path !== '' ? rb_media_path($db, $path) : '';
}

function seo_catalog_product_alt(array $product, string $lang): string {
    $stored = trim((string) ($product['alt_' . $lang] ?? ''));
    return $stored !== '' ? $stored : trim((string) ($product['name_' . $lang] ?? '')) . ' - Royal Beans Perú';
}

function seo_catalog_product_detail(PDO $db, array $product, string $lang, array $allProducts): array {
    $id = (int) $product['id'];
    $original = seo_catalog_public_image($db, $product);
    $image = rb_image_variant($original, '1200');
    $gallery = [['media_type'=>'image', 'media_path'=>$image, 'thumbnail_path'=>rb_image_variant($original, '320'), 'original_path'=>$original, 'alt_text'=>seo_catalog_product_alt($product, $lang)]];
    $galleryRows = [];
    try {
        $statement = $db->prepare('SELECT media_type,media_path,alt_es,alt_en FROM product_gallery WHERE product_id=? ORDER BY sort_order,id');
        $statement->execute([$id]);
        $galleryRows = $statement->fetchAll();
    } catch (Throwable $error) {
        error_log('[Royal Beans product detail] Gallery unavailable: ' . $error->getMessage());
    }
    foreach ($galleryRows as $row) {
        $galleryOriginal = rb_media_path($db, (string) $row['media_path']);
        $gallery[] = [
            'media_type'=>(string) $row['media_type'],
            'media_path'=>rb_image_variant($galleryOriginal, '1200'),
            'thumbnail_path'=>rb_image_variant($galleryOriginal, '320'),
            'original_path'=>$galleryOriginal,
            'alt_text'=>trim((string) ($row['alt_' . $lang] ?? '')) ?: seo_catalog_product_alt($product, $lang),
        ];
    }
    $materialColumn = $lang === 'en' ? 'material_en' : 'material_es';
    $packages = [];
    try {
        $statement = $db->prepare("SELECT weight_primary,weight_secondary,$materialColumn AS material,package_type,image_path FROM product_packages WHERE product_id=? AND is_available=1 ORDER BY sort_order,id");
        $statement->execute([$id]);
        $packages = $statement->fetchAll();
    } catch (Throwable $error) {
        error_log('[Royal Beans product detail] Packages unavailable: ' . $error->getMessage());
    }
    foreach ($packages as &$package) { $package['original_path'] = rb_media_path($db, (string) $package['image_path']); $package['image_path'] = rb_image_variant($package['original_path'], '1200'); }
    unset($package);
    $harvest = [];
    try {
        $statement = $db->prepare('SELECT month_number,availability FROM product_harvest WHERE product_id=? ORDER BY month_number');
        $statement->execute([$id]);
        $harvest = array_column($statement->fetchAll(), 'availability', 'month_number');
    } catch (Throwable $error) {
        error_log('[Royal Beans product detail] Harvest unavailable: ' . $error->getMessage());
    }
    $certifications = [];
    try {
        $statement = $db->prepare('SELECT name FROM product_certifications WHERE product_id=? ORDER BY sort_order,id');
        $statement->execute([$id]);
        $certifications = $statement->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $error) {
        error_log('[Royal Beans product detail] Certifications unavailable: ' . $error->getMessage());
    }
    $relatedIds = [];
    try {
        $statement = $db->prepare('SELECT related_product_id FROM product_relations WHERE product_id=? ORDER BY sort_order,related_product_id');
        $statement->execute([$id]);
        $relatedIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $error) {
        error_log('[Royal Beans product detail] Related products unavailable: ' . $error->getMessage());
    }
    $related = [];
    foreach ($relatedIds as $relatedId) foreach ($allProducts as $candidate) {
        if ((int) $candidate['id'] === $relatedId) {
            $related[] = ['product_id'=>$relatedId, 'name'=>(string) $candidate['name_' . $lang], 'url'=>seo_catalog_product_path($candidate, $lang)];
            break;
        }
    }
    $channel = $db->query("SELECT link_url,value_text FROM contact_channels WHERE channel_type='whatsapp' AND is_active=1 ORDER BY sort_order,id LIMIT 1")->fetch() ?: [];
    $whatsappUrl = trim((string) ($channel['link_url'] ?? ''));
    if ($whatsappUrl === '') {
        $number = preg_replace('/\D/', '', (string) ($channel['value_text'] ?? ''));
        $whatsappUrl = $number !== '' ? 'https://wa.me/' . $number : 'https://wa.me/51961804500';
    }
    return [
        'product'=>[
            'id'=>$id, 'name'=>(string) $product['name_' . $lang], 'image'=>$image, 'image_original'=>$original,
            'line'=>(string) $product['line_slug'], 'category'=>(string) $product['category_slug'],
            'short_description'=>(string) ($product['short_' . $lang] ?? ''),
            'description'=>(string) ($product['description_' . $lang] ?? ''),
            'seo_title'=>(string) ($product['title_' . $lang] ?? ''),
            'seo_description'=>(string) ($product['meta_' . $lang] ?? ''),
            'scientific_name'=>(string) $product['scientific_name'],
            'tariff_code'=>(string) $product['tariff_code'],
            'caliber'=>(string) $product['caliber_' . $lang],
            'destinations'=>(string) $product['destinations_' . $lang],
            'technical_sheet_path'=>rb_media_path($db, (string) $product['technical_sheet_path']),
        ],
        'gallery'=>$gallery, 'packages'=>$packages, 'harvest'=>$harvest,
        'certifications'=>$certifications, 'related'=>$related, 'whatsapp_url'=>$whatsappUrl,
    ];
}

function seo_catalog_origin(): string {
    $host = strtolower(trim(explode(':', (string) ($_SERVER['HTTP_HOST'] ?? 'royalbeansperu.com'))[0]));
    if ($host === 'royalbeansperu.com' || $host === 'www.royalbeansperu.com') return 'https://royalbeansperu.com';
    if (!preg_match('/^[a-z0-9.-]+$/', $host)) return 'https://royalbeansperu.com';
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $port = isset($_SERVER['SERVER_PORT']) && !in_array((string) $_SERVER['SERVER_PORT'], ['80', '443'], true) ? ':' . (int) $_SERVER['SERVER_PORT'] : '';
    return ($https ? 'https' : 'http') . '://' . $host . $port;
}

function seo_catalog_escape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function seo_catalog_not_found(string $lang): never {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    $page = __DIR__ . '/404.html';
    if (is_file($page)) readfile($page);
    else echo '<!doctype html><html lang="' . $lang . '"><meta charset="utf-8"><title>404 | Royal Beans Perú</title><body><h1>404</h1></body></html>';
    exit;
}

function seo_catalog_brand(string $value): string {
    return (string) preg_replace('/Royal Beans Peru\b/u', 'Royal Beans Perú', $value);
}

function seo_catalog_excerpt(string ...$values): string {
    foreach ($values as $value) {
        $clean = trim((string) preg_replace('/\s+/u', ' ', strip_tags($value)));
        if ($clean !== '') return $clean;
    }
    return '';
}
