<?php
/** @var string $title @var string $page @var string $content @var ?array $flash @var string $csrf @var array $state */
$nextTheme = $state['theme'] === 'dark' ? 'light' : 'dark';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> — ПР №6</title>
  <link rel="stylesheet" href="/style.css">
</head>
<body data-theme="<?= e($state['theme']) ?>">
<header class="site-header">
  <h1>Практическая работа 6</h1>
  <nav>
    <a href="<?= e(url('notes')) ?>" class="<?= $page === 'notes' ? 'active' : '' ?>">Заметки</a>
    <a href="<?= e(url('users')) ?>" class="<?= $page === 'users' ? 'active' : '' ?>">Пользователи</a>
  </nav>
  <div class="who" id="session-state">
    <span>Пользователь: <strong id="who-name"><?= e($state['display_name']) ?></strong> · режим: <?= $state['theme'] === 'dark' ? 'тёмный' : 'светлый' ?></span>
    <form method="post" action="<?= e(url($page === '' ? 'notes' : $page)) ?>">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="return" value="<?= e($page === '' ? 'notes' : $page) ?>">
      <input type="hidden" name="theme" value="<?= e($nextTheme) ?>">
      <button type="submit" name="action" value="set_theme" class="secondary">Режим: <?= $nextTheme === 'dark' ? 'тёмный' : 'светлый' ?></button>
      <?php if ($state['user_id'] !== null): ?>
        <button type="submit" name="action" value="clear_name" class="secondary">Очистить имя</button>
      <?php endif; ?>
    </form>
  </div>
</header>
<main>
<?php if ($flash !== null): ?>
  <div class="alert <?= $flash['type'] === 'ok' ? 'ok' : 'error' ?>" role="status"><?= e($flash['text']) ?></div>
<?php endif; ?>
<?= $content ?>
</main>
</body>
</html>
