<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo puede ejecutarse desde la terminal.\n");
    exit(1);
}

$projectRoot = dirname(__DIR__);
$outputRoot = $projectRoot . '/out';
$config = require $projectRoot . '/public/cms-config/database.php';
if (!in_array((string) ($config['host'] ?? ''), ['localhost', '127.0.0.1', '::1'], true)) {
    fwrite(STDERR, "Operación cancelada: la configuración no apunta a MySQL local.\n");
    exit(1);
}

$pages = [
    ['inicio', 'es', 'index.html'],
    ['inicio', 'en', 'en/index.html'],
    ['nosotros', 'es', 'nosotros/index.html'],
    ['nosotros', 'en', 'en/about-us/index.html'],
    ['productos-conventional', 'es', 'productos/linea-a-granel/index.html'],
    ['productos-conventional', 'en', 'en/products/bulk-line/index.html'],
    ['productos-retail', 'es', 'productos/linea-retail/index.html'],
    ['productos-retail', 'en', 'en/products/retail-line/index.html'],
    ['participacion', 'es', 'participacion/index.html'],
    ['participacion', 'en', 'en/events/index.html'],
    ['impacto', 'es', 'impacto/index.html'],
    ['impacto', 'en', 'en/impact/index.html'],
    ['contacto', 'es', 'contacto/index.html'],
    ['contacto', 'en', 'en/contact/index.html'],
    ['privacidad', 'es', 'politica-de-privacidad/index.html'],
    ['privacidad', 'en', 'en/privacy-policy/index.html'],
    ['terminos', 'es', 'terminos-y-condiciones/index.html'],
    ['terminos', 'en', 'en/terms-and-conditions/index.html'],
];

function safeKey(string $value): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($value)) ?: $value;
    $value = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value));
    return substr(trim($value, '-'), 0, 46);
}

function hasManagedAncestor(DOMElement $element, DOMElement $root): bool
{
    for ($node = $element; $node instanceof DOMElement; $node = $node->parentNode) {
        if ($node->hasAttribute('data-cms-managed') || $node->hasAttribute('data-product-id')) return true;
        if ($node === $root) break;
    }
    return false;
}

function directText(DOMElement $element): string
{
    $parts = [];
    foreach ($element->childNodes as $child) {
        if ($child instanceof DOMText && trim($child->nodeValue) !== '') $parts[] = trim($child->nodeValue);
    }
    return trim(implode(' ', $parts));
}

function elementValue(DOMElement $element): string
{
    return $element->tagName === 'img' ? trim($element->getAttribute('src')) : trim($element->textContent);
}

$rows = [];
$allowedTags = array_flip(['h1','h2','h3','h4','h5','h6','p','li','blockquote','figcaption','dt','dd','a','button','span','strong','small','label','img']);

foreach ($pages as [$pageKey, $locale, $relativePath]) {
    $path = $outputRoot . '/' . $relativePath;
    if (!is_file($path)) throw new RuntimeException("No existe el HTML exportado: {$relativePath}");
    $document = new DOMDocument();
    libxml_use_internal_errors(true);
    $document->loadHTML((string) file_get_contents($path), LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    $xpath = new DOMXPath($document);
    $main = $xpath->query('//main')->item(0);
    if (!$main instanceof DOMElement) throw new RuntimeException("La página no contiene <main>: {$relativePath}");

    $used = [];
    foreach ($xpath->query('//*[@data-cms]') as $existing) {
        if ($existing instanceof DOMElement) $used[$existing->getAttribute('data-cms')] = true;
    }
    $sections = [];
    foreach ($xpath->query('./section', $main) as $section) {
        if ($section instanceof DOMElement) $sections[] = $section;
    }
    if ($sections === []) $sections[] = $main;

    foreach ($sections as $sectionIndex => $section) {
        if ($section->hasAttribute('data-cms-managed')) continue;
        $sectionSource = $section->getAttribute('id');
        if ($sectionSource === '') {
            foreach (preg_split('/\s+/', trim($section->getAttribute('class'))) ?: [] as $className) {
                if ($className !== '' && !in_array($className, ['section-pad', 'container'], true)) {
                    $sectionSource = $className;
                    break;
                }
            }
        }
        $sectionName = safeKey($sectionSource ?: "main-0-{$sectionIndex}") ?: "section-{$sectionIndex}";
        $counters = [];
        foreach ($section->getElementsByTagName('*') as $element) {
            if (!$element instanceof DOMElement || !isset($allowedTags[$element->tagName]) || hasManagedAncestor($element, $section)) continue;
            $isImage = $element->tagName === 'img';
            if (!$isImage && directText($element) === '') continue;
            if (!$element->hasAttribute('data-cms')) {
                $kind = $isImage ? 'image' : safeKey($element->tagName . '-' . ($element->getAttribute('class') ?: 'text'));
                $counters[$kind] = ($counters[$kind] ?? 0) + 1;
                $key = $sectionName . '.' . $kind . '-' . $counters[$kind];
                while (isset($used[$key])) {
                    $counters[$kind]++;
                    $key = $sectionName . '.' . $kind . '-' . $counters[$kind];
                }
                $element->setAttribute('data-cms', $key);
                $used[$key] = true;
            }
            $key = $element->getAttribute('data-cms');
            [$sectionKey, $fieldKey] = array_pad(explode('.', $key, 2), 2, '');
            if ($sectionKey === '' || $fieldKey === '') continue;
            $value = elementValue($element);
            if ($isImage && $value === '') continue;
            $rowKey = $pageKey . '|' . $sectionKey . '|' . $fieldKey;
            $rows[$rowKey] ??= [
                'page_key' => $pageKey,
                'section_key' => $sectionKey,
                'field_key' => $fieldKey,
                'field_type' => $isImage ? 'image' : (mb_strlen($value) > 90 ? 'textarea' : 'text'),
                'label' => mb_substr($isImage ? ($element->getAttribute('alt') ?: 'Imagen') : preg_replace('/\s+/', ' ', $value), 0, 58),
                'value_es' => null,
                'value_en' => null,
            ];
            $rows[$rowKey]['value_' . $locale] = $value;
        }
    }
}

$seedPath = $projectRoot . '/public/cms-config/content.seed.json';
file_put_contents($seedPath, json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", LOCK_EX);

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']),
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$statement = $pdo->prepare("INSERT INTO content_fields (page_key,section_key,field_key,field_type,label,value_es,value_en) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE field_type=VALUES(field_type),label=VALUES(label),value_es=IF(value_es IS NULL OR value_es='',VALUES(value_es),value_es),value_en=IF(value_en IS NULL OR value_en='',VALUES(value_en),value_en)");
$pdo->beginTransaction();
foreach ($rows as $row) {
    $statement->execute([$row['page_key'],$row['section_key'],$row['field_key'],$row['field_type'],$row['label'],$row['value_es'],$row['value_en']]);
}
$pdo->commit();

echo 'Campos inventariados: ' . count($rows) . PHP_EOL;
echo 'Semilla: ' . $seedPath . PHP_EOL;
echo 'Filas actuales en MySQL local: ' . $pdo->query('SELECT COUNT(*) FROM content_fields')->fetchColumn() . PHP_EOL;
