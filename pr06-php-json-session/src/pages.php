<?php
declare(strict_types=1);

require_once __DIR__ . '/notes.php';
require_once __DIR__ . '/session.php';

/**
 * Подготовка данных страницы «Заметки». Вызывается ДО подключения шаблона:
 * шаблон получает готовый массив и сам файлы не читает.
 */
function buildNotesPage(): array
{
    $form = pullForm('note'); // значения и ошибки формы после неудачного POST
    $base = ['form' => $form['values'], 'formErrors' => $form['errors'], 'csrf' => csrfToken()];
    try {
        return ['notes' => loadNotes(), 'error' => null] + $base;
    } catch (StorageException $ex) {
        return ['notes' => [], 'error' => $ex->getMessage()] + $base;
    }
}
