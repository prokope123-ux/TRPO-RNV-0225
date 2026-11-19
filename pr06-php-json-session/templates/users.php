<?php
/** @var array $users @var ?string $error @var string $csrf @var ?int $currentUserId */
?>
<section class="card" id="users">
  <h2>Пользователи</h2>
  <p class="muted">Данные из <code>users.json</code>. «Войти» сохраняет имя и режим интерфейса пользователя в <code>$_SESSION</code> — сам файл не меняется.</p>
  <?php if ($error !== null): ?>
    <div class="alert error"><strong>Не удалось загрузить пользователей.</strong> <?= e($error) ?> Проверьте файл <code>data/users.json</code>.</div>
  <?php elseif ($users === []): ?>
    <p class="empty">В users.json нет ни одного пользователя.</p>
  <?php else: ?>
    <table>
      <thead><tr><th>ID</th><th>Логин</th><th>Имя</th><th>Роль</th><th>Режим</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= $user['id'] ?></td>
          <td><?= e($user['login']) ?></td>
          <td><?= e($user['display_name']) ?></td>
          <td><?= e($user['role']) ?></td>
          <td><?= $user['theme'] === 'dark' ? 'тёмный' : 'светлый' ?></td>
          <td>
            <?php if ($currentUserId === $user['id']): ?>
              <span class="pass">текущий</span>
            <?php else: ?>
              <form method="post" action="<?= e(url('users')) ?>">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="return" value="users">
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                <button type="submit" name="action" value="login">Войти</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
