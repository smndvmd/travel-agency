<?php
declare(strict_types=1);
$requestPath = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$scriptDirectory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($scriptDirectory !== '' && ($requestPath === $scriptDirectory || str_starts_with($requestPath, $scriptDirectory . '/'))) {
    $requestPath = substr($requestPath, strlen($scriptDirectory));
}
$requestPath = trim($requestPath, '/');
if ($requestPath === 'index.php') {
    $requestPath = '';
    $_SERVER['REQUEST_URI'] = ($scriptDirectory ?: '') . '/';
}
$legacyPattern = '~^(?:tour|login|register|logout|profile|book|review_add)\.php$|^(?:manager|admin)/[A-Za-z0-9_-]+\.php$~';
if (preg_match($legacyPattern, $requestPath)) {
    $legacyFile = dirname(__DIR__) . '/' . $requestPath;
    if (is_file($legacyFile)) { require $legacyFile; return; }
}
require dirname(__DIR__) . '/index.php';
