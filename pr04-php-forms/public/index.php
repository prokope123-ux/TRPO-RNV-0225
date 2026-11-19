<?php
declare(strict_types=1);

// Версия учебного приложения и текущая дата — вычисляются на сервере.
// Если файл открыть как обычный текст, эти значения в браузере не появятся.
const APP_VERSION = '1.0.0';
date_default_timezone_set('Europe/Moscow');

// Переменные разных типов и массив (шаг 4).
$appName   = 'Заявки сотрудников';        // string
$formCount = 6;                            // int
$taxRate   = 0.13;                         // float
$isDemo    = true;                         // bool
$stack     = ['PHP ' . PHP_VERSION, 'HTML5', 'CSS3', 'CSV', 'JSON'];  // array

$today = date('d.m.Y H:i');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site-header">
  <h1>Практическая работа 4</h1>
  <span class="meta">Версия <?= APP_VERSION ?> · <?= $today ?></span>
</header>
<main>
  <section class="card" id="about">
    <span class="badge">PHP на сервере</span>
    <h2>О приложении</h2>
    <p class="muted">Строка версии и дата выше формируются PHP — файл выполняется сервером.</p>
    <table>
      <tr><th>Переменная</th><th>Тип</th><th>Значение</th></tr>
      <tr><td>$appName</td><td>string</td><td><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></td></tr>
      <tr><td>$formCount</td><td>int</td><td><?= $formCount ?> полей формы</td></tr>
      <tr><td>$taxRate</td><td>float</td><td><?= $taxRate * 100 ?>%</td></tr>
      <tr><td>$isDemo</td><td>bool</td><td><?= $isDemo ? 'учебный режим' : 'рабочий режим' ?></td></tr>
      <tr><td>$stack</td><td>array</td><td><?= htmlspecialchars(implode(', ', $stack), ENT_QUOTES, 'UTF-8') ?></td></tr>
    </table>
  </section>
</main>
</body>
</html>
