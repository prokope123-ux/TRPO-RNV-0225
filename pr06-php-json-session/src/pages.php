<?php
declare(strict_types=1);

require_once __DIR__ . '/notes.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/state.php';
require_once __DIR__ . '/scenarios.php';

/**
 * Подготовка данных страницы «Заметки». Вызывается ДО подключения шаблона:
 * шаблон получает готовый массив и сам файлы не читает.
 */
function buildNotesPage(): array
{
    $form = pullForm('note'); // значения и ошибки формы после неудачного POST
    $base = [
        'form' => $form['values'], 'formErrors' => $form['errors'], 'csrf' => csrfToken(),
        'favorites' => $_SESSION['state']['favorites'], 'author' => $_SESSION['state']['display_name'],
    ];
    try {
        return ['notes' => loadNotes(), 'error' => null] + $base;
    } catch (StorageException $ex) {
        return ['notes' => [], 'error' => $ex->getMessage()] + $base;
    }
}

/** Страница «Пользователи»: данные из users.json и текущий вход из session. */
function buildUsersPage(): array
{
    $base = ['csrf' => csrfToken(), 'currentUserId' => $_SESSION['state']['user_id']];
    try {
        return ['users' => loadUsers(), 'error' => null] + $base;
    } catch (StorageException $ex) {
        return ['users' => [], 'error' => $ex->getMessage()] + $base;
    }
}

/**
 * Страница «Сценарии session»: сценарии из session_scenarios.json, их прогон на чистой сессии
 * и текущее содержимое реальной $_SESSION (для сравнения в обычной вкладке и приватном окне).
 */
function buildScenariosPage(): array
{
    $base = [
        'sessionId' => session_id(),
        'currentState' => $_SESSION['state'],
    ];
    try {
        $results = [];
        foreach (loadScenarios() as $scenario) {
            $results[] = ['scenario' => $scenario, 'run' => runScenario($scenario, stateContext())];
        }
        return ['results' => $results, 'error' => null] + $base;
    } catch (StorageException $ex) {
        return ['results' => [], 'error' => $ex->getMessage()] + $base;
    }
}
