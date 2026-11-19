<?php
declare(strict_types=1);

require_once __DIR__ . '/notes.php';

/**
 * Подготовка данных страницы «Заметки». Вызывается ДО подключения шаблона:
 * шаблон получает готовый массив и сам файлы не читает.
 */
function buildNotesPage(): array
{
    try {
        return ['notes' => loadNotes(), 'error' => null];
    } catch (StorageException $ex) {
        return ['notes' => [], 'error' => $ex->getMessage()];
    }
}
