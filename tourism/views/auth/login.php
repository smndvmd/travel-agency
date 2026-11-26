<div class="card narrow"><h1>Воридшавӣ</h1>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<form method="post" action="<?= e(url('login')) ?>"><?= csrf_field() ?>
  <div class="row"><label for="email">Email</label><input id="email" type="email" name="email" required autocomplete="email"></div>
  <div class="row"><label for="password">Парол</label><input id="password" type="password" name="password" required autocomplete="current-password"></div>
  <button>Ворид шудан</button> <a href="<?= e(url('register')) ?>">Сабти ном</a></form></div>
