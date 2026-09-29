<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$origin = rtrim((string)($argv[1] ?? 'https://royalbeansperu.com'), '/');
if (!filter_var($origin, FILTER_VALIDATE_URL)) throw new InvalidArgumentException('Pass a valid site origin');

function audit_get_json(string $url): array {
    $curl = curl_init($url);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_HTTPHEADER=>['Accept: application/json']]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    $json = is_string($body) ? json_decode($body, true) : null;
    return ['status'=>$status,'json'=>is_array($json)?$json:null,'error'=>$error];
}

fputcsv(STDOUT, ['product_id','slug_es','status_es','slug_en','status_en']);
$failed = 0;
foreach (['conventional','retail'] as $line) {
    $list = audit_get_json($origin . '/api/products.php?line=' . $line);
    if ($list['status'] !== 200 || !isset($list['json']['products']) || !is_array($list['json']['products'])) throw new RuntimeException('Cannot list active ' . $line . ' products');
    foreach ($list['json']['products'] as $product) {
        $statuses = [];
        foreach (['es','en'] as $lang) {
            $slug = (string)($product['slug_' . $lang] ?? '');
            $detail = audit_get_json($origin . '/api/products.php?slug=' . rawurlencode($slug) . '&lang=' . $lang);
            $valid = $detail['status'] === 200 && (int)($detail['json']['product']['id'] ?? 0) === (int)$product['id'];
            $statuses[$lang] = $valid ? '200 OK' : $detail['status'] . ' FAIL';
            if (!$valid) $failed++;
        }
        fputcsv(STDOUT, [(int)$product['id'],(string)($product['slug_es']??''),$statuses['es'],(string)($product['slug_en']??''),$statuses['en']]);
    }
}
if ($failed) exit(1);
