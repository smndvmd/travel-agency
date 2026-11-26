<?php $input = $input ?? []; ?>
<div class="card narrow"><h1>Сабти ном</h1>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<form method="post" action="<?= e(url('register')) ?>"><?= csrf_field() ?>
  <div class="row"><label for="full_name">Ному насаб</label><input id="full_name" name="full_name" required value="<?= e($input['full_name'] ?? '') ?>"></div>
  <div class="row"><label for="email">Email</label><input id="email" type="email" name="email" required value="<?= e($input['email'] ?? '') ?>"></div>
  <div class="row"><label for="phone">Телефон</label><input id="phone" name="phone" value="<?= e($input['phone'] ?? '') ?>"></div>
  <div class="row"><label for="password">Парол</label><input id="password" type="password" name="password" required minlength="6" autocomplete="new-password"></div>
  <button>Сабти ном</button></form></div>
