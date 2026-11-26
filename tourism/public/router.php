<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$relative = ltrim(rawurldecode($path), '/');
$file = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
$publicRoot = realpath(__DIR__);
if ($relative !== '' && $file !== false && $publicRoot !== false
    && $file !== realpath(__FILE__)
    && str_starts_with($file, $publicRoot . DIRECTORY_SEPARATOR) && is_file($file)) return false;
require __DIR__ . '/index.php';
