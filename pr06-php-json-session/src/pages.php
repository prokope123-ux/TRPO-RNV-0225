<?php
declare(strict_types=1);

require_once __DIR__ . '/notes.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/state.php';

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
