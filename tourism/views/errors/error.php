<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= htmlspecialchars((string)$code, ENT_QUOTES, 'UTF-8') ?> — Ошибка</title></head>
<body><main><h1><?= htmlspecialchars((string)($code ?? $status ?? 500), ENT_QUOTES, 'UTF-8') ?></h1><p><?= htmlspecialchars((string)$message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p><a href="<?= htmlspecialchars($this->url('/'), ENT_QUOTES, 'UTF-8') ?>">На главную</a></main></body></html>
