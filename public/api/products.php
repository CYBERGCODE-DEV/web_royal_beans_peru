<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

header('CDN-Cache-Control: no-store');
header('Cloudflare-CDN-Cache-Control: no-store');
header('Surrogate-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') rb_json(['error' => 'Method not allowed'], 405);
$line = in_array($_GET['line'] ?? '', ['conventional', 'retail'], true) ? $_GET['line'] : 'conventional';
$featured = isset($_GET['featured']) && $_GET['featured'] === '1';
try {
  $id = max(0, (int) ($_GET['id'] ?? 0));
  $lang = ($_GET['lang'] ?? 'es') === 'en' ? 'en' : 'es';
  $requestedSlug = trim((string)($_GET['slug'] ?? ''));
  if (!$id && $requestedSlug !== '') {
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $requestedSlug)) rb_json(['error'=>'Product not found'],404);
    $find = rb_pdo()->prepare('SELECT t.product_id FROM product_translations t JOIN products p ON p.id=t.product_id WHERE t.locale=? AND t.slug=? AND p.is_active=1 AND p.deleted_at IS NULL LIMIT 1');
    $find->execute([$lang,$requestedSlug]);
    $id = (int)($find->fetchColumn() ?: 0);
    if (!$id) rb_json(['error'=>'Product not found'],404);
  }
  if ($id) {
    $db = rb_pdo();
    $caliberColumn = $lang === 'es' ? 's.caliber_es' : 's.caliber_en';
    $destinationsColumn = $lang === 'es' ? 's.destinations_es' : 's.destinations_en';
    $galleryAltColumn = $lang === 'es' ? 'alt_es' : 'alt_en';
    $packageMaterialColumn = $lang === 'es' ? 'material_es' : 'material_en';
    $statement = $db->prepare("SELECT p.id,CASE WHEN l.slug='retail' THEN COALESCE((SELECT pp.image_path FROM product_packages pp WHERE pp.product_id=p.id AND pp.is_available=1 AND pp.image_path<>'' ORDER BY pp.sort_order,pp.id LIMIT 1),'') ELSE p.image_path END AS image,l.slug AS line,c.slug AS category,t.name,t.slug,t.short_description,t.description,t.seo_title,t.seo_description,COALESCE(s.scientific_name,'') AS scientific_name,COALESCE(s.tariff_code,'') AS tariff_code,COALESCE($caliberColumn,'') AS caliber,COALESCE($destinationsColumn,'') AS destinations,COALESCE(s.technical_sheet_path,'') AS technical_sheet_path FROM products p JOIN product_lines l ON l.id=p.line_id JOIN product_categories c ON c.id=p.category_id JOIN product_translations t ON t.product_id=p.id AND t.locale=? LEFT JOIN product_specs s ON s.product_id=p.id WHERE p.id=? AND p.is_active=1 AND p.deleted_at IS NULL LIMIT 1");
    $statement->execute([$lang,$id]);
    $product = $statement->fetch();
    if (!$product) rb_json(['error' => 'Product not found'], 404);
    $product['image_original'] = (string)$product['image'];
    $product['image'] = rb_image_variant((string)$product['image'], '1200');

    $gallery = [];
    try { $statement = $db->prepare("SELECT media_type,media_path,$galleryAltColumn AS alt_text FROM product_gallery WHERE product_id=? ORDER BY sort_order,id"); $statement->execute([$id]); $gallery = $statement->fetchAll(); }
    catch (Throwable $optionalError) { error_log('[Royal Beans products API] Gallery unavailable: ' . $optionalError->getMessage()); }
    $coverAlt = '';
    foreach ($gallery as $photo) {
      if ($photo['media_type'] === 'image' && $photo['media_path'] === $product['image_original']) { $coverAlt = trim((string) $photo['alt_text']); break; }
    }
    if ($coverAlt === '') foreach ($gallery as $photo) {
      if ($photo['media_type'] === 'image' && trim((string) $photo['alt_text']) !== '') { $coverAlt = trim((string) $photo['alt_text']); break; }
    }
    foreach ($gallery as &$photo) if ($photo['media_type'] === 'image') { $photo['original_path'] = $photo['media_path']; $photo['thumbnail_path'] = rb_image_variant((string)$photo['media_path'], '320'); $photo['media_path'] = rb_image_variant((string)$photo['media_path'], '1200'); }
    unset($photo);
    array_unshift($gallery, ['media_type'=>'image','media_path'=>$product['image'],'thumbnail_path'=>rb_image_variant($product['image_original'],'320'),'original_path'=>$product['image_original'],'alt_text'=>$coverAlt ?: $product['name'] . ' - Royal Beans Perú']);

    $packages = [];
    try { $statement = $db->prepare("SELECT weight_primary,weight_secondary,$packageMaterialColumn AS material,package_type,image_path FROM product_packages WHERE product_id=? AND is_available=1 ORDER BY sort_order,id"); $statement->execute([$id]); $packages = $statement->fetchAll(); }
    catch (Throwable $optionalError) { error_log('[Royal Beans products API] Packages unavailable: ' . $optionalError->getMessage()); }
    foreach ($packages as &$package) { $package['original_path'] = $package['image_path']; $package['image_path'] = rb_image_variant((string)$package['image_path'], '1200'); }
    unset($package);

    $harvest = [];
    try { $statement = $db->prepare('SELECT month_number,availability FROM product_harvest WHERE product_id=? ORDER BY month_number'); $statement->execute([$id]); $harvest = array_column($statement->fetchAll(), 'availability', 'month_number'); }
    catch (Throwable $optionalError) { error_log('[Royal Beans products API] Harvest unavailable: ' . $optionalError->getMessage()); }

    $certifications = [];
    try {
      $statement = $db->prepare('SELECT name FROM product_certifications WHERE product_id=? ORDER BY sort_order,id');
      $statement->execute([$id]);
      $certifications = $statement->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $secondaryError) {
      error_log('[Royal Beans products API] Certifications unavailable: ' . $secondaryError->getMessage());
    }
    $whatsapp = $db->query("SELECT link_url,value_text FROM contact_channels WHERE channel_type='whatsapp' AND is_active=1 ORDER BY sort_order,id LIMIT 1")->fetch() ?: [];
    $whatsappUrl = trim((string)($whatsapp['link_url'] ?? ''));
    if ($whatsappUrl === '') {
      $number = preg_replace('/\D/', '', (string)($whatsapp['value_text'] ?? ''));
      $whatsappUrl = $number !== '' ? 'https://wa.me/' . $number : 'https://wa.me/51961804500';
    }
    rb_json(['product'=>$product,'gallery'=>$gallery,'packages'=>$packages,'harvest'=>$harvest,'certifications'=>$certifications,'whatsapp_url'=>$whatsappUrl]);
  }

    $products = rb_public_cache('products:list:' . $line . ':' . (int)$featured, static function () use ($line,$featured): array {
    $sql = "SELECT p.id, CASE WHEN l.slug='retail' THEN COALESCE((SELECT pp.image_path FROM product_packages pp WHERE pp.product_id=p.id AND pp.is_available=1 AND pp.image_path<>'' ORDER BY pp.sort_order,pp.id LIMIT 1),'') ELSE p.image_path END AS image, l.slug AS line, c.slug AS category,c.name_es AS category_name_es,c.name_en AS category_name_en,
      COALESCE((SELECT g.alt_es FROM product_gallery g WHERE g.product_id=p.id AND g.media_type='image' AND g.alt_es<>'' ORDER BY (g.media_path=p.image_path) DESC,g.sort_order,g.id LIMIT 1),'') AS alt_es,
      COALESCE((SELECT g.alt_en FROM product_gallery g WHERE g.product_id=p.id AND g.media_type='image' AND g.alt_en<>'' ORDER BY (g.media_path=p.image_path) DESC,g.sort_order,g.id LIMIT 1),'') AS alt_en,
      MAX(CASE WHEN t.locale='es' THEN t.name END) AS es,
      MAX(CASE WHEN t.locale='en' THEN t.name END) AS en,
      MAX(CASE WHEN t.locale='es' THEN t.slug END) AS slug_es,
      MAX(CASE WHEN t.locale='en' THEN t.slug END) AS slug_en,
      MAX(CASE WHEN t.locale='es' THEN t.short_description END) AS description_es,
      MAX(CASE WHEN t.locale='en' THEN t.short_description END) AS description_en
      FROM products p
      JOIN product_lines l ON l.id=p.line_id
      JOIN product_categories c ON c.id=p.category_id
      JOIN product_translations t ON t.product_id=p.id
      WHERE l.slug=:line AND l.is_active=1 AND p.is_active=1 AND p.deleted_at IS NULL";
    if ($featured) $sql .= " AND p.featured_home=1";
    $sql .= " GROUP BY p.id,p.image_path,l.slug,c.slug,p.sort_order ORDER BY p.sort_order,p.id";
    $statement = rb_pdo()->prepare($sql);
    $statement->execute(['line' => $line]);
    $products = $statement->fetchAll();
    foreach ($products as &$product) {
      $original = (string)$product['image'];
      $product['image'] = rb_image_variant($original, '640');
      $product['image_320'] = rb_image_variant($original, '320');
      $product['image_640'] = $product['image'];
    }
    unset($product);
    return $products;
    });
    rb_json(['products' => $products, 'line' => $line]);
} catch (Throwable $error) {
    error_log('[Royal Beans products API] Catalogue unavailable: ' . $error->getMessage());
    rb_json(['error' => 'Catalogue unavailable'], 503);
}
