<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') rb_json(['error' => 'Method not allowed'], 405);
$module = in_array($_GET['module'] ?? '', ['presentation', 'impact', 'contacts', 'standards'], true) ? $_GET['module'] : '';
$locale = ($_GET['lang'] ?? 'es') === 'en' ? 'en' : 'es';
if ($module === '') rb_json(['error' => 'Invalid module'], 422);

try {
    $db = rb_pdo();
    if ($module === 'contacts') {
        $labelColumn = $locale === 'en' ? "COALESCE(NULLIF(label_en,''),label_es)" : 'label_es';
        $query = $db->query("SELECT id,channel_type,$labelColumn AS label,value_text,link_url FROM contact_channels WHERE is_active=1 ORDER BY sort_order,id");
        $contacts = $query->fetchAll();
        if ($locale === 'en') foreach ($contacts as &$contact) {
            if ($contact['channel_type'] === 'address') $contact['value_text'] = str_replace('Perú', 'Peru', (string) $contact['value_text']);
        }
        unset($contact);
        rb_json(['module' => $module, 'lang' => $locale, 'contacts' => $contacts]);
    }

    $titleColumn = $locale === 'en' ? "COALESCE(NULLIF(c.title_en,''),c.title_es)" : 'c.title_es';
    $descriptionColumn = $locale === 'en' ? "COALESCE(NULLIF(c.description_en,''),c.description_es)" : 'c.description_es';
    $locationColumn = $locale === 'en' ? "COALESCE(NULLIF(c.location_en,''),c.location_es)" : 'c.location_es';
    $statement = $db->prepare("SELECT c.id,c.entry_type,$titleColumn AS title,$descriptionColumn AS description,c.event_date,$locationColumn AS location,c.logo_path,c.cover_path FROM cms_collections c WHERE c.module_key=? AND c.is_active=1 ORDER BY c.entry_type,c.sort_order,c.event_date DESC,c.id DESC");
    $statement->execute([$module]);
    $collections = $statement->fetchAll();
    if ($locale === 'en') foreach ($collections as &$collection) {
        $collection['location'] = str_replace('Perú', 'Peru', (string) $collection['location']);
    }
    unset($collection);
    $mediaStatement = $db->prepare('SELECT media_path,caption_es,caption_en FROM cms_collection_media WHERE collection_id=? ORDER BY sort_order,id');
    foreach ($collections as &$collection) {
        $standardLegacyPath = '';
        if ($module === 'standards') {
            $standardLegacyPath = match (mb_strtoupper(trim((string) $collection['title']))) {
                'FDA' => '/images/standards/fda.png',
                'SENASA' => '/images/standards/senasa.png',
                'HACCP INTERNO', 'INTERNAL HACCP' => '/images/standards/haccp.png',
                'MARCA PERÚ', 'PERU BRAND' => '/images/standards/marca-peru.png',
                default => '',
            };
        }
        $collection['logo_path'] = rb_media_path($db, (string) $collection['logo_path'], $standardLegacyPath);
        $collection['cover_path'] = rb_media_path($db, (string) $collection['cover_path']);
        $mediaStatement->execute([$collection['id']]);
        $collection['media'] = array_map(static function(array $media) use ($locale, $db):array {
            $caption = $locale === 'en' ? ($media['caption_en'] ?: $media['caption_es']) : $media['caption_es'];
            return ['path' => rb_media_path($db, (string) $media['media_path']), 'caption' => $caption];
        }, $mediaStatement->fetchAll());
    }
    unset($collection);
    rb_json(['module' => $module, 'lang' => $locale, 'collections' => $collections]);
} catch (Throwable $error) {
    rb_json(['error' => 'Section unavailable'], 503);
}
