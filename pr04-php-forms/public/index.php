<?php
declare(strict_types=1);

// Версия учебного приложения и текущая дата — вычисляются на сервере.
// Если файл открыть как обычный текст, эти значения в браузере не появятся.
const APP_VERSION = '1.1.0';
date_default_timezone_set('Europe/Moscow');

// Ошибки показываем только при локальной разработке (шаг 15).
error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_ENV') === 'prod' ? '0' : '1');

require_once __DIR__ . '/../src/functions.php';

$dataDir = __DIR__ . '/../data';

// Загрузка сотрудников из employees.csv. Ошибку превращаем в понятное сообщение.
$employees = [];
$skippedRows = 0;
$loadError = null;
try {
    $loaded = loadEmployees($dataDir . '/employees.csv');
    $employees = $loaded['employees'];
    $skippedRows = $loaded['skipped'];
} catch (RuntimeException $ex) {
    $loadError = $ex->getMessage();
}

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
  <nav><a href="#about">О приложении</a><a href="#employees">Сотрудники</a></nav>
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

  <section class="card" id="employees">
    <h2>Сотрудники</h2>
    <?php if ($loadError !== null): ?>
      <div class="alert error"><?= e($loadError) ?>. Проверьте файл <code>data/employees.csv</code>.</div>
    <?php elseif ($employees === []): ?>
      <p class="empty">В employees.csv нет ни одной корректной записи.</p>
    <?php else: ?>
      <?php if ($skippedRows > 0): ?>
        <div class="alert error">Пропущено повреждённых строк: <?= $skippedRows ?></div>
      <?php endif; ?>
      <table>
        <thead><tr><th>ID</th><th>ФИО</th><th>Email</th><th>Отдел</th><th>Должность</th><th>Оклад</th><th>Принят</th></tr></thead>
        <tbody>
        <?php foreach ($employees as $emp): ?>
          <tr>
            <td><?= $emp['id'] ?></td>
            <td><?= e($emp['full_name']) ?></td>
            <td><?= e($emp['email']) ?></td>
            <td><?= e($emp['department']) ?></td>
            <td><?= $emp['position'] !== null ? e($emp['position']) : '<span class="muted">не указана</span>' ?></td>
            <td><?= formatMoney($emp['salary']) ?></td>
            <td><?= e($emp['hired_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
