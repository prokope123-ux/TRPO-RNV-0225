<?php
declare(strict_types=1);

require_once __DIR__ . '/users.php';
require_once __DIR__ . '/notes.php';

const THEMES = ['light', 'dark'];
const GUEST_NAME = 'Гость';

/** Состояние пользователя по умолчанию (то, что видит новый посетитель). */
function defaultState(): array
{
    return ['display_name' => GUEST_NAME, 'user_id' => null, 'theme' => 'light', 'favorites' => []];
}

/** Создаёт недостающие ключи состояния в $_SESSION (после session_start). */
function initSessionState(): void
{
    $_SESSION['state'] = (is_array($_SESSION['state'] ?? null) ? $_SESSION['state'] : []) + defaultState();
}

/**
 * Единственная функция, которая изменяет состояние пользователя.
 * Работает с массивом по ссылке, поэтому её можно применить и к $_SESSION['state'],
 * и к отдельному массиву — так сценарии из session_scenarios.json проверяются без браузера.
 *
 * $context — загрузчики данных: ['users' => callable, 'note_ids' => callable].
 * Возвращает текст ошибки или null при успехе.
 *
 * @throws StorageException если нужный файл данных недоступен
 */
function applyAction(array &$state, string $action, array $params, array $context): ?string
{
    switch ($action) {
        case 'init':
            return null;

        case 'login':
            $userId = filter_var($params['user_id'] ?? null, FILTER_VALIDATE_INT);
            $users = ($context['users'])();
            if ($userId === false || !isset($users[$userId])) {
                return 'Пользователь не найден.';
            }
            $state['user_id'] = $userId;
            $state['display_name'] = $users[$userId]['display_name'];
            $state['theme'] = $users[$userId]['theme'];   // у пользователя в users.json свой режим
            return null;

        case 'clear_name':
            $state['user_id'] = null;
            $state['display_name'] = GUEST_NAME;
            return null;

        case 'set_theme':
            $theme = $params['theme'] ?? '';
            if (!is_string($theme) || !in_array($theme, THEMES, true)) {
                return 'Недопустимый режим интерфейса.';
            }
            $state['theme'] = $theme;
            return null;

        case 'toggle_favorite':
            $noteId = filter_var($params['note_id'] ?? null, FILTER_VALIDATE_INT);
            if ($noteId === false || !in_array($noteId, ($context['note_ids'])(), true)) {
                return 'Такой заметки нет.';
            }
            $favorites = $state['favorites'];
            $state['favorites'] = in_array($noteId, $favorites, true)
                ? array_values(array_diff($favorites, [$noteId]))
                : [...$favorites, $noteId];
            return null;
    }
    return 'Неизвестное действие.';
}

/** Загрузчики данных для applyAction: файлы читаются только тогда, когда они действительно нужны. */
function stateContext(): array
{
    return [
        'users'    => static fn(): array => loadUsers(),
        'note_ids' => static fn(): array => array_column(loadNotes(), 'id'),
    ];
}
