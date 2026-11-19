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

const NOTE_TITLE_MIN = 3;
const NOTE_TITLE_MAX = 100;
const NOTE_BODY_MIN  = 5;
const NOTE_BODY_MAX  = 1000;
const NOTE_TAGS_MAX  = 5;

/**
 * Проверка данных формы новой заметки.
 * Возвращает ['values' => введённые строки, 'tags' => массив тегов, 'errors' => [поле => сообщение]].
 */
function validateNote(array $input): array
{
    $values = [];
    foreach (['title', 'body', 'tags'] as $field) {
        $raw = $input[$field] ?? '';
        $values[$field] = is_string($raw) ? trim($raw) : '';
    }
    $errors = [];

    $titleLength = mb_strlen($values['title'], 'UTF-8');
    if ($values['title'] === '') {
        $errors['title'] = 'Введите заголовок';
    } elseif ($titleLength < NOTE_TITLE_MIN || $titleLength > NOTE_TITLE_MAX) {
        $errors['title'] = sprintf('Заголовок должен содержать от %d до %d символов', NOTE_TITLE_MIN, NOTE_TITLE_MAX);
    }

    $bodyLength = mb_strlen($values['body'], 'UTF-8');
    if ($values['body'] === '') {
        $errors['body'] = 'Введите текст заметки';
    } elseif ($bodyLength < NOTE_BODY_MIN || $bodyLength > NOTE_BODY_MAX) {
        $errors['body'] = sprintf('Текст должен содержать от %d до %d символов', NOTE_BODY_MIN, NOTE_BODY_MAX);
    }

    // Теги необязательны: «php, сессии , json» → ['php', 'сессии', 'json']
    $tags = [];
    if ($values['tags'] !== '') {
        $tags = array_values(array_unique(array_filter(
            array_map(static fn(string $t): string => mb_strtolower(trim($t), 'UTF-8'), explode(',', $values['tags'])),
            static fn(string $t): bool => $t !== ''
        )));
        if (count($tags) > NOTE_TAGS_MAX) {
            $errors['tags'] = 'Не больше ' . NOTE_TAGS_MAX . ' тегов';
        } else {
            foreach ($tags as $tag) {
                if (mb_strlen($tag, 'UTF-8') > 20) {
                    $errors['tags'] = 'Тег не длиннее 20 символов';
                    break;
                }
            }
        }
    }

    return ['values' => $values, 'tags' => $tags, 'errors' => $errors];
}

/**
 * Добавляет заметку в notes.json и возвращает её id.
 * Идентификатор уникален: max(id) + 1, вычисляется под блокировкой файла.
 *
 * @throws StorageException
 */
function addNote(string $title, string $body, array $tags, string $author): int
{
    $path = dataPath('notes.json');
    return withFileLock($path, static function () use ($path, $title, $body, $tags, $author): int {
        $rows = loadJson($path);
        $ids = array_map(static fn($row): int => is_array($row) ? (int) ($row['id'] ?? 0) : 0, $rows);
        $id = ($ids === [] ? 0 : max($ids)) + 1;
        $rows[] = [
            'id'         => $id,
            'title'      => $title,
            'body'       => $body,
            'author'     => $author,
            'created_at' => date('Y-m-d H:i:s'),
            'tags'       => $tags,
        ];
        saveJson($path, array_values($rows));
        return $id;
    });
}
