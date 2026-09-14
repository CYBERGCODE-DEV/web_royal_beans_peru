<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') rb_json(['error' => 'Method not allowed'], 405);
$data = rb_body();
if (!empty($data['website'])) rb_json(['ok' => true]);
$name = trim((string) ($data['name'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$message = trim((string) ($data['message'] ?? ''));
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') rb_json(['error' => 'Invalid fields'], 422);
if (mb_strlen($name) > 140 || mb_strlen($message) > 4000) rb_json(['error' => 'Content too long'], 422);
try {
    $statement = rb_pdo()->prepare('INSERT INTO inquiries (name,company,email,country,product_name,message,locale,ip_hash) VALUES (:name,:company,:email,:country,:product,:message,:locale,:ip)');
    $statement->execute([
        'name' => $name, 'company' => mb_substr(trim((string) ($data['company'] ?? '')), 0, 180), 'email' => $email,
        'country' => mb_substr(trim((string) ($data['country'] ?? '')), 0, 120), 'product' => mb_substr(trim((string) ($data['product'] ?? '')), 0, 190),
        'message' => $message, 'locale' => ($data['locale'] ?? 'es') === 'en' ? 'en' : 'es',
        'ip' => hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . date('Y-m')),
    ]);
    rb_json(['ok' => true], 201);
} catch (Throwable $error) {
    rb_json(['error' => 'Unable to save enquiry'], 503);
}
