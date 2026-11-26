<?php
declare(strict_types=1);
$tourId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$_SERVER['REQUEST_URI'] = $tourId === false || $tourId === null ? '/__not_found__' : '/tour/' . $tourId;
require __DIR__ . '/index.php';
