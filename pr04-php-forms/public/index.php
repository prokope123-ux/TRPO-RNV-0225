<?php
declare(strict_types=1);

// Версия учебного приложения и текущая дата — вычисляются на сервере.
// Если файл открыть как обычный текст, эти значения в браузере не появятся.
const APP_VERSION = '1.2.0';
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

$departments = departmentsOf($employees);

// Обработка POST-формы. Значения по умолчанию для GET-запроса — пустая форма.
$form = ['full_name' => '', 'email' => '', 'department' => '', 'request_type' => '', 'days' => '', 'comment' => ''];
$errors = [];
$accepted = null;   // принятая заявка (после успешной валидации)

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $result = validateInput($_POST, $departments);
    $form = $result['values'];
    $errors = $result['errors'];
    if ($errors === []) {
        $accepted = $form;
    }
}
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
  <nav><a href="#about">О приложении</a><a href="#employees">Сотрудники</a><a href="#request">Заявка</a></nav>
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

  <section class="card" id="request">
    <h2>Заявка сотрудника</h2>
    <?php if ($accepted !== null): ?>
      <div class="alert ok">Заявка принята. Ниже — обработанные значения.</div>
      <table>
        <tr><th>Поле</th><th>Значение</th></tr>
        <tr><td>ФИО</td><td><?= e($accepted['full_name']) ?></td></tr>
        <tr><td>Email</td><td><?= e($accepted['email']) ?></td></tr>
        <tr><td>Отдел</td><td><?= e($accepted['department']) ?></td></tr>
        <tr><td>Тип заявки</td><td><?= e($accepted['request_type']) ?></td></tr>
        <tr><td>Дней</td><td><?= (int) $accepted['days'] ?></td></tr>
        <tr><td>Комментарий</td><td><?= $accepted['comment'] !== '' ? e($accepted['comment']) : '<span class="muted">без комментария</span>' ?></td></tr>
      </table>
      <p><a class="btn secondary" href="/#request">Подать ещё одну</a></p>
    <?php else: ?>
      <?php if ($errors !== []): ?>
        <div class="alert error">Заявка не отправлена. Исправьте ошибки:
          <ul><?php foreach ($errors as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>
      <form method="post" action="/#request" novalidate>
        <div class="cols">
          <div class="row <?= isset($errors['full_name']) ? 'has-error' : '' ?>">
            <label for="full_name">ФИО</label>
            <input type="text" id="full_name" name="full_name" value="<?= e($form['full_name']) ?>">
            <?php if (isset($errors['full_name'])): ?><div class="field-error"><?= e($errors['full_name']) ?></div><?php endif; ?>
          </div>
          <div class="row <?= isset($errors['email']) ? 'has-error' : '' ?>">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($form['email']) ?>">
            <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
          </div>
          <div class="row <?= isset($errors['department']) ? 'has-error' : '' ?>">
            <label for="department">Отдел</label>
            <select id="department" name="department">
              <option value="">— выберите —</option>
              <?php foreach ($departments as $dep): ?>
                <option value="<?= e($dep) ?>" <?= $form['department'] === $dep ? 'selected' : '' ?>><?= e($dep) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['department'])): ?><div class="field-error"><?= e($errors['department']) ?></div><?php endif; ?>
          </div>
          <div class="row <?= isset($errors['request_type']) ? 'has-error' : '' ?>">
            <label for="request_type">Тип заявки</label>
            <select id="request_type" name="request_type">
              <option value="">— выберите —</option>
              <?php foreach (REQUEST_TYPES as $type): ?>
                <option value="<?= e($type) ?>" <?= $form['request_type'] === $type ? 'selected' : '' ?>><?= e($type) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['request_type'])): ?><div class="field-error"><?= e($errors['request_type']) ?></div><?php endif; ?>
          </div>
          <div class="row <?= isset($errors['days']) ? 'has-error' : '' ?>">
            <label for="days">Количество дней (<?= DAYS_MIN ?>–<?= DAYS_MAX ?>)</label>
            <input type="text" inputmode="numeric" id="days" name="days" value="<?= e($form['days']) ?>">
            <?php if (isset($errors['days'])): ?><div class="field-error"><?= e($errors['days']) ?></div><?php endif; ?>
          </div>
          <div class="row <?= isset($errors['comment']) ? 'has-error' : '' ?>">
            <label for="comment">Комментарий (необязательно)</label>
            <textarea id="comment" name="comment"><?= e($form['comment']) ?></textarea>
            <?php if (isset($errors['comment'])): ?><div class="field-error"><?= e($errors['comment']) ?></div><?php endif; ?>
          </div>
        </div>
        <button type="submit">Отправить заявку</button>
      </form>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
