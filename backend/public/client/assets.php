<?php
declare(strict_types=1);
$assets = ['portal.css' => ['text/css; charset=utf-8', 'client/assets/portal.css'], 'portal.js' => ['text/javascript; charset=utf-8', 'client/assets/portal.js']];
$name = (string)($_GET['file'] ?? '');
if (!isset($assets[$name])) { http_response_code(404); exit; }
header('Content-Type: ' . $assets[$name][0]);
header('Cache-Control: public, max-age=3600');
readfile(dirname(__DIR__, 3) . '/' . $assets[$name][1]);
