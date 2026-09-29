<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') rb_json(['error' => 'Method not allowed'], 405);
$file = dirname(__DIR__) . '/cms-config/content-version.json';
$payload = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
rb_json(is_array($payload) ? $payload : ['revision' => 'initial', 'scope' => 'all']);
