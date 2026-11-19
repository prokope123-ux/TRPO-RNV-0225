<?php /** @var string $requested */ ?>
<section class="card">
  <span class="badge">404</span>
  <h2>Страница не найдена</h2>
  <p>Неизвестное значение параметра <code>page</code>: <code><?= e($requested) ?></code>.</p>
  <p><a class="btn" href="<?= e(url('notes')) ?>">К списку заметок</a></p>
</section>
