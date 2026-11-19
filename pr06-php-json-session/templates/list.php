<?php
/** @var array $notes @var ?string $error */
?>
<section class="card" id="notes">
  <h2>Заметки</h2>
  <?php if ($error !== null): ?>
    <div class="alert error"><strong>Не удалось загрузить заметки.</strong> <?= e($error) ?> Проверьте файл <code>data/notes.json</code>.</div>
  <?php elseif ($notes === []): ?>
    <p class="empty">Пока нет ни одной заметки.</p>
  <?php else: ?>
    <?php foreach ($notes as $note): ?>
      <article class="note">
        <h3><?= e($note['title']) ?></h3>
        <div class="meta"><?= e($note['author']) ?> · <?= e($note['created_at']) ?></div>
        <p><?= nl2br(e($note['body'])) ?></p>
        <div>
          <?php foreach ($note['tags'] as $tag): ?><span class="tag"><?= e($tag) ?></span><?php endforeach; ?>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
