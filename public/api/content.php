<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') rb_json(['error' => 'Method not allowed'], 405);
$page = preg_replace('/[^a-z0-9_-]/i', '', $_GET['page'] ?? 'inicio');
$locale = ($_GET['lang'] ?? 'es') === 'en' ? 'en' : 'es';
try {
    $column = $locale === 'en' ? 'value_en' : 'value_es';
    $statement = rb_pdo()->prepare("SELECT section_key,field_key,field_type,$column AS value FROM content_fields WHERE page_key=:page ORDER BY section_key,field_key");
    $statement->execute(['page' => $page]);
    $fields = [];
    foreach ($statement as $row) $fields[$row['section_key'] . '.' . $row['field_key']] = ['type' => $row['field_type'], 'value' => $row['value']];
    rb_json(['page' => $page, 'lang' => $locale, 'fields' => $fields]);
} catch (Throwable $error) {
    rb_json(['error' => 'Content unavailable'], 503);
}
