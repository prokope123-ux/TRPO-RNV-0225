<?php
/** @var string $title @var string $page @var string $content @var ?array $flash */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> — ПР №6</title>
  <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="site-header">
  <h1>Практическая работа 6</h1>
  <nav>
    <a href="<?= e(url('notes')) ?>" class="<?= $page === 'notes' ? 'active' : '' ?>">Заметки</a>
  </nav>
</header>
<main>
<?php if ($flash !== null): ?>
  <div class="alert <?= $flash['type'] === 'ok' ? 'ok' : 'error' ?>" role="status"><?= e($flash['text']) ?></div>
<?php endif; ?>
<?= $content ?>
</main>
</body>
</html>
