<?php
/** @var array $notes @var ?string $error @var array $form @var array $formErrors @var string $csrf */
?>
<section class="card" id="add">
  <h2>Новая заметка</h2>
  <form method="post" action="<?= e(url('notes')) ?>" novalidate>
    <input type="hidden" name="action" value="add_note">
    <input type="hidden" name="return" value="notes">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <div class="row <?= isset($formErrors['title']) ? 'has-error' : '' ?>">
      <label for="title">Заголовок</label>
      <input type="text" id="title" name="title" value="<?= e($form['title'] ?? '') ?>">
      <?php if (isset($formErrors['title'])): ?><div class="field-error"><?= e($formErrors['title']) ?></div><?php endif; ?>
    </div>
    <div class="row <?= isset($formErrors['body']) ? 'has-error' : '' ?>">
      <label for="body">Текст</label>
      <textarea id="body" name="body"><?= e($form['body'] ?? '') ?></textarea>
      <?php if (isset($formErrors['body'])): ?><div class="field-error"><?= e($formErrors['body']) ?></div><?php endif; ?>
    </div>
    <div class="row <?= isset($formErrors['tags']) ? 'has-error' : '' ?>">
      <label for="tags">Теги через запятую (необязательно)</label>
      <input type="text" id="tags" name="tags" value="<?= e($form['tags'] ?? '') ?>">
      <?php if (isset($formErrors['tags'])): ?><div class="field-error"><?= e($formErrors['tags']) ?></div><?php endif; ?>
    </div>
    <button type="submit">Сохранить</button>
  </form>
</section>

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
