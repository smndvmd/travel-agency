<?php
// Временный JSON API: учебные данные, не реальные предложения.
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$tours = [
  ['id'=>1, 'title'=>'Фанские горы', 'category'=>'mountains', 'category_label'=>'Горы', 'days'=>4, 'price'=>1250, 'emoji'=>'🏔️', 'style'=>'tour-mountains', 'description'=>'Горные тропы и панорамы'],
  ['id'=>2, 'title'=>'Семь озёр', 'category'=>'lakes', 'category_label'=>'Озёра', 'days'=>2, 'price'=>650, 'emoji'=>'🏞️', 'style'=>'tour-lakes', 'description'=>'Природные озёра и живописные долины'],
  ['id'=>3, 'title'=>'Памирский тракт', 'category'=>'mountains', 'category_label'=>'Приключения', 'days'=>7, 'price'=>4900, 'emoji'=>'🗻', 'style'=>'tour-pamir', 'description'=>'Путешествие по высокогорным дорогам'],
  ['id'=>4, 'title'=>'Озеро Искандеркуль', 'category'=>'lakes', 'category_label'=>'Озёра', 'days'=>1, 'price'=>350, 'emoji'=>'💧', 'style'=>'tour-lakes', 'description'=>'Озеро, водопад и прогулки'],
  ['id'=>5, 'title'=>'Худжанд и Истаравшан', 'category'=>'culture', 'category_label'=>'Культура', 'days'=>2, 'price'=>780, 'emoji'=>'🏛️', 'style'=>'tour-city', 'description'=>'Города, история и культура'],
  ['id'=>6, 'title'=>'Душанбе и Гиссар', 'category'=>'culture', 'category_label'=>'Культура', 'days'=>1, 'price'=>320, 'emoji'=>'🏛️', 'style'=>'tour-city', 'description'=>'Прогулка по столице и исторической крепости'],
  // Безопасная проверка: HTML в заголовке должен показываться текстом, а не как разметка.
  ['id'=>7, 'title'=>'<b>Тестовый тур</b>', 'category'=>'culture', 'category_label'=>'Проверка XSS', 'days'=>1, 'price'=>0, 'emoji'=>'🔎', 'style'=>'tour-city', 'description'=>'Тестовая запись для проверки textContent'],
];

// Запасной вариант для учебного PHP без расширения mbstring.
function lowerCyrillic(string $text): string {
  if (function_exists('mb_strtolower')) return mb_strtolower($text, 'UTF-8');
  return strtr($text, array_combine(
    preg_split('//u', 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯҒҚҶҲӢӮ', -1, PREG_SPLIT_NO_EMPTY),
    preg_split('//u', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяғқҷҳӣӯ', -1, PREG_SPLIT_NO_EMPTY)
  ));
}
$q = lowerCyrillic(trim((string) ($_GET['q'] ?? '')));
$category = trim((string) ($_GET['type'] ?? ''));
if ($q !== '') {
  $tours = array_values(array_filter($tours, static fn(array $t): bool =>
    str_contains(lowerCyrillic($t['title']), $q) ||
    str_contains(lowerCyrillic($t['description']), $q)
  ));
}
if (in_array($category, ['mountains','lakes','culture'], true)) {
  $tours = array_values(array_filter($tours, static fn(array $t): bool => $t['category'] === $category));
}
echo json_encode($tours, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
