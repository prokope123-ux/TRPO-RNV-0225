<?php
/** @var array $results @var ?string $error @var string $sessionId @var array $currentState */
$passedCount = count(array_filter($results, static fn(array $r): bool => $r['run']['passed']));
?>
<section class="card" id="current">
  <h2>Текущее состояние session</h2>
  <p class="muted">
    Это содержимое <code>$_SESSION['state']</code> именно этого браузера. Откройте страницу в обычной вкладке и в приватном окне:
    у них разные cookie <code>PHPSESSID</code>, поэтому и состояния разные.
    Идентификатор сессии: <code><?= e(substr($sessionId, 0, 8)) ?>…</code>
  </p>
  <pre><?= e(json_encode($currentState, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
</section>

<section class="card" id="scenarios">
  <h2>Сценарии из session_scenarios.json</h2>
  <?php if ($error !== null): ?>
    <div class="alert error"><strong>Не удалось загрузить сценарии.</strong> <?= e($error) ?> Проверьте файл <code>data/session_scenarios.json</code>.</div>
  <?php elseif ($results === []): ?>
    <p class="empty">В session_scenarios.json нет сценариев.</p>
  <?php else: ?>
    <p class="muted">Каждый сценарий прогоняется на чистой сессии (как первый визит). Пройдено <?= $passedCount ?> из <?= count($results) ?>.</p>
    <?php foreach ($results as $item): ?>
      <?php $sc = $item['scenario']; $run = $item['run']; ?>
      <article class="note">
        <h3><?= e($sc['title']) ?> <span class="<?= $run['passed'] ? 'pass' : 'fail' ?>"><?= $run['passed'] ? 'OK' : 'FAIL' ?></span></h3>
        <?php foreach ($run['steps'] as $i => $step): ?>
          <div class="step"><?= $i + 1 ?>. <code><?= e($step['label']) ?></code>
            → <?= $step['error'] !== null ? e($step['error']) : 'успешно' ?></div>
        <?php endforeach; ?>
        <div class="step">Итог: <code><?= e(json_encode($run['final'], JSON_UNESCAPED_UNICODE)) ?></code></div>
        <?php foreach ($run['problems'] as $problem): ?><div class="field-error"><?= e($problem) ?></div><?php endforeach; ?>
        <?php if (!empty($sc['manual_check'])): ?><p class="muted"><strong>Проверить вручную:</strong> <?= e((string) $sc['manual_check']) ?></p><?php endif; ?>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
