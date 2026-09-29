<?php
declare(strict_types=1);

$lang = ($_GET['lang'] ?? 'es') === 'en' ? 'en' : 'es';
$line = in_array($_GET['line'] ?? '', ['retail', 'linea-retail', 'retail-line'], true) ? 'retail' : 'conventional';
if ((int) ($_GET['id'] ?? 0) > 0 || !empty($_GET['slug']) || !empty($_GET['producto']) || !empty($_GET['product'])) {
    require __DIR__ . '/seo-legacy-product.php';
    exit;
}
$destination = $lang === 'en'
    ? '/en/products/' . ($line === 'retail' ? 'retail' : 'bulk-line') . '/'
    : '/productos/' . ($line === 'retail' ? 'linea-retail' : 'linea-a-granel') . '/';

header('Location: ' . $destination, true, 301);
exit;
