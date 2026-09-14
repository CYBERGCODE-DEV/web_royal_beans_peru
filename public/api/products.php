<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') rb_json(['error' => 'Method not allowed'], 405);
$line = in_array($_GET['line'] ?? '', ['conventional', 'retail'], true) ? $_GET['line'] : 'conventional';
$featured = isset($_GET['featured']) && $_GET['featured'] === '1';
try {
    $sql = "SELECT p.id, p.image_path AS image, l.slug AS line, c.slug AS category,
      MAX(CASE WHEN t.locale='es' THEN t.name END) AS es,
      MAX(CASE WHEN t.locale='en' THEN t.name END) AS en,
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
    rb_json(['products' => $statement->fetchAll(), 'line' => $line]);
} catch (Throwable $error) {
    rb_json(['error' => 'Catalogue unavailable'], 503);
}
