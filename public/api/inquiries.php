<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') rb_json(['error' => 'Method not allowed'], 405);
$data = rb_body();
if (!empty($data['website'])) rb_json(['error' => 'Invalid submission'], 422);

$text = static fn(string $key): string => trim((string) ($data[$key] ?? ''));
$name = $text('name');
$company = $text('company');
$position = $text('position');
$country = $text('country');
$email = $text('email');
$phone = $text('phone');
$volume = $text('volume');
$presentation = $text('presentation');
$destination = $text('destination');
$message = $text('message');
$participant = $text('participant_type');
$line = $text('product_line');
$allowedParticipants = ['importer', 'distributor', 'public_institution', 'other'];
$products = array_values(array_unique(array_filter(array_map(static fn($value): string => mb_substr(trim((string) $value), 0, 190), is_array($data['products'] ?? null) ? $data['products'] : []))));

if ($name === '' || $company === '' || $country === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || $phone === '' || $volume === '' || $destination === '' || $message === '' || !$products
    || !in_array($participant, $allowedParticipants, true) || !in_array($line, ['conventional', 'retail'], true)) {
    rb_json(['error' => 'Invalid fields'], 422);
}
if (strlen((string) preg_replace('/\D/', '', $phone)) < 7 || count($products) > 20
    || mb_strlen($name) > 140 || mb_strlen($company) > 180 || mb_strlen($position) > 120
    || mb_strlen($country) > 120 || mb_strlen($email) > 190 || mb_strlen($phone) > 80
    || mb_strlen($volume) > 120 || mb_strlen($presentation) > 120 || mb_strlen($destination) > 180
    || mb_strlen($message) > 2000) rb_json(['error' => 'Invalid field length'], 422);

$origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
if ($origin !== '' && strtolower((string) parse_url($origin, PHP_URL_HOST)) !== explode(':', $host)[0]) rb_json(['error' => 'Invalid origin'], 403);

$locale = ($data['locale'] ?? 'es') === 'en' ? 'en' : 'es';
$details = $locale === 'es'
    ? ['Cargo' => $position, 'Volumen aproximado' => $volume, 'Presentación' => $presentation, 'País o puerto destino' => $destination]
    : ['Position' => $position, 'Approximate volume' => $volume, 'Presentation' => $presentation, 'Destination country or port' => $destination];
$fullMessage = implode("\n", array_map(static fn(string $label, string $value): string => $label . ': ' . ($value !== '' ? $value : '—'), array_keys($details), array_values($details))) . "\n\n" . $message;
$ipHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . date('Y-m'));

try {
    $db = rb_pdo();
    $rate = $db->prepare('SELECT COUNT(*) FROM inquiries WHERE ip_hash=? AND created_at >= NOW() - INTERVAL 10 MINUTE');
    $rate->execute([$ipHash]);
    if ((int) $rate->fetchColumn() >= 5) rb_json(['error' => 'Too many requests'], 429);

    $duplicate = $db->prepare('SELECT id FROM inquiries WHERE ip_hash=? AND email=? AND message=? AND created_at >= NOW() - INTERVAL 5 MINUTE LIMIT 1');
    $duplicate->execute([$ipHash, $email, $fullMessage]);
    if ($duplicate->fetchColumn()) rb_json(['ok' => true, 'duplicate' => true]);

    $statement = $db->prepare('INSERT INTO inquiries (participant_type,name,company,email,phone,country,product_line,product_name,product_names,message,locale,ip_hash) VALUES (:participant,:name,:company,:email,:phone,:country,:line,:product,:products,:message,:locale,:ip)');
    $statement->execute([
        'participant' => $participant, 'name' => $name, 'company' => $company, 'email' => $email, 'phone' => $phone,
        'country' => $country, 'line' => $line, 'product' => $products[0], 'products' => implode(', ', $products),
        'message' => $fullMessage, 'locale' => $locale, 'ip' => $ipHash,
    ]);
    rb_json(['ok' => true], 201);
} catch (Throwable $error) {
    error_log('[Royal Beans inquiry] ' . $error->getMessage());
    rb_json(['error' => 'Unable to save enquiry'], 503);
}
