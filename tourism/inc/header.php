<?php
if (!array_key_exists('u', get_defined_vars())) $u = function_exists('me') ? me() : null;
if (!array_key_exists('flash', get_defined_vars())) $flash = function_exists('flash') ? flash() : null;
?>
<!DOCTYPE html>
<html lang="tg"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Тоҷикистон Тур') ?> — Тоҷикистон Тур</title>
<meta name="theme-color" content="#0f8a6d">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap&subset=cyrillic" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/style.css') ?>">
</head><body>
<div class="progress"></div>
<div class="aurora"><i></i><i></i><i></i></div>
<header class="top"><div class="nav">
  <a class="logo" href="<?= url() ?>"><b>⛰</b>Тоҷикистон Тур</a>
  <nav>
    <a href="<?= url() ?>">Турҳо</a>
    <?php if ($u): ?>
      <?php if ($u['role'] === 'tourist'): ?><a href="<?= url('profile.php') ?>">Утоқи ман</a><?php endif; ?>
      <?php if ($u['role'] === 'manager'): ?><a href="<?= url('manager/index.php') ?>">Турҳои ман</a><a href="<?= url('manager/bookings.php') ?>">Бандҳо</a><?php endif; ?>
      <?php if ($u['role'] === 'admin'): ?><a href="<?= url('admin/index.php') ?>">Менеҷерҳо</a><a href="<?= url('admin/reviews.php') ?>">Модератсия</a><?php endif; ?>
      <span class="who"><?= e($u['full_name']) ?></span>
      <a href="<?= url('logout.php') ?>">Баромадан</a>
    <?php else: ?>
      <a href="<?= url('login') ?>">Ворид шудан</a>
      <a class="btn small" href="<?= url('register') ?>">Сабти ном</a>
    <?php endif; ?>
    <button class="theme" id="themeBtn" type="button" aria-label="Мавзӯ">🌙</button>
  </nav>
</div></header>
<main class="wrap">
<?php if ($flash): ?><div class="flash <?= e($flash[1]) ?>"><?= e($flash[0]) ?></div><?php endif; ?>
