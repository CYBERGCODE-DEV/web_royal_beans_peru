<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') rb_json(['error' => 'Method not allowed'], 405);
header('CDN-Cache-Control: no-store');
header('Cloudflare-CDN-Cache-Control: no-store');
header('Surrogate-Control: no-store');
$page = preg_replace('/[^a-z0-9_-]/i', '', $_GET['page'] ?? 'inicio');
$locale = ($_GET['lang'] ?? 'es') === 'en' ? 'en' : 'es';
try {
    $payload = rb_public_cache('content:' . $page . ':' . $locale, static function () use ($page,$locale): array {
      $column = $locale === 'en' ? 'value_en' : 'value_es';
      $statement = rb_pdo()->prepare("SELECT section_key,field_key,field_type,$column AS value,style_json FROM content_fields WHERE page_key=:page ORDER BY section_key,field_key");
      $statement->execute(['page' => $page]);
      $fields = [];
      foreach ($statement as $row) $fields[$row['section_key'] . '.' . $row['field_key']] = ['type' => $row['field_type'], 'value' => $row['value'], 'style' => json_decode((string)($row['style_json'] ?? ''), true) ?: []];
    if ($page === 'inicio' && isset($fields['home-products.a-text-link-1'])) {
        $old = $locale === 'en' ? 'View all' : 'Ver todos';
        if ($fields['home-products.a-text-link-1']['value'] === $old) {
            $fields['home-products.a-text-link-1']['value'] = $locale === 'en' ? 'View Bulk Line' : 'Ver Línea a Granel';
        }
    }
    if (isset($fields['hero.image'])) $fields['hero.image']['style']['heroShowText'] = true;
    $theme = [];
    $settings = rb_pdo()->query("SELECT setting_key,value_text FROM settings WHERE is_public=1 AND setting_key IN ('theme_palette','theme_font','theme_background')");
    foreach ($settings as $setting) $theme[$setting['setting_key']] = $setting['value_text'];
      return ['page' => $page, 'lang' => $locale, 'fields' => $fields, 'theme' => $theme];
    });
    rb_json($payload);
} catch (Throwable $error) {
    rb_json(['error' => 'Content unavailable'], 503);
}
