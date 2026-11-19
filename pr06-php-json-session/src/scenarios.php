<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/state.php';

/**
 * Сценарии из session_scenarios.json.
 *
 * @throws StorageException
 */
function loadScenarios(): array
{
    $list = loadJson(dataPath('session_scenarios.json'));
    return array_values(array_filter($list, static fn($s): bool => is_array($s) && isset($s['id'], $s['title'], $s['steps']) && is_array($s['steps'])));
}

/**
 * Моделирует несколько последовательных запросов: берёт «чистую» сессию,
 * по очереди применяет шаги сценария и сравнивает итоговое состояние с ожидаемым.
 * Использует ту же applyAction(), что и реальные обработчики POST.
 *
 * @return array{passed: bool, steps: array<int, array{label: string, error: ?string, ok: bool}>, final: array, problems: string[]}
 */
function runScenario(array $scenario, array $context): array
{
    $state = defaultState();
    $stepResults = [];
    $problems = [];

    foreach ($scenario['steps'] as $number => $step) {
        $action = (string) ($step['action'] ?? '');
        $params = is_array($step['params'] ?? null) ? $step['params'] : [];
        $expectError = (bool) ($step['expect_error'] ?? false);

        try {
            $error = applyAction($state, $action, $params, $context);
        } catch (StorageException $ex) {
            $error = $ex->getMessage();
        }

        $ok = $expectError ? $error !== null : $error === null;
        if (!$ok) {
            $problems[] = 'Шаг ' . ($number + 1) . ($expectError ? ': ожидалась ошибка, но её не было' : ': ' . $error);
        }
        $label = $action . ($params !== [] ? ' ' . json_encode($params, JSON_UNESCAPED_UNICODE) : '');
        $stepResults[] = ['label' => $label, 'error' => $error, 'ok' => $ok];
    }

    foreach ($scenario['expect'] ?? [] as $key => $expected) {
        if (!array_key_exists($key, $state) || $state[$key] !== $expected) {
            $problems[] = sprintf(
                'Поле «%s»: ожидалось %s, получено %s',
                $key,
                json_encode($expected, JSON_UNESCAPED_UNICODE),
                json_encode($state[$key] ?? null, JSON_UNESCAPED_UNICODE)
            );
        }
    }

    return ['passed' => $problems === [], 'steps' => $stepResults, 'final' => $state, 'problems' => $problems];
}
