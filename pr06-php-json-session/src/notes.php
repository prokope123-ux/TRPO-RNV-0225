<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';

/**
 * Приводит запись notes.json к ожидаемой структуре.
 * Возвращает null для некорректной записи (её просто пропускаем).
 */
function normalizeNote(mixed $row): ?array
{
    if (!is_array($row) || !isset($row['id'], $row['title'], $row['body']) || !is_numeric($row['id'])) {
        return null;
    }
    return [
        'id'         => (int) $row['id'],
        'title'      => (string) $row['title'],
        'body'       => (string) $row['body'],
        'author'     => isset($row['author']) && $row['author'] !== '' ? (string) $row['author'] : 'Гость',
        'created_at' => (string) ($row['created_at'] ?? ''),
        'tags'       => is_array($row['tags'] ?? null) ? array_values(array_map('strval', $row['tags'])) : [],
    ];
}

/**
 * Все заметки из notes.json, новые — первыми.
 *
 * @throws StorageException
 */
function loadNotes(): array
{
    $notes = [];
    foreach (loadJson(dataPath('notes.json')) as $row) {
        $note = normalizeNote($row);
        if ($note !== null) {
            $notes[] = $note;
        }
    }
    usort($notes, static fn(array $a, array $b): int => [$b['created_at'], $b['id']] <=> [$a['created_at'], $a['id']]);
    return $notes;
}
