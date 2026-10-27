<?php
require __DIR__ . '/inc/lib.php';
$f = basename((string)($_GET['f'] ?? ''));
if (!preg_match('/^[\w-]+\.jpg$/', $f)) { http_response_code(404); exit; }
$path = data_dir() . '/uploads/' . $f;
if (!is_file($path)) { http_response_code(404); exit; }
header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=2592000');
header('X-Content-Type-Options: nosniff');
readfile($path);
