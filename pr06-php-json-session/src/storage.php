<?php
declare(strict_types=1);

/** Ошибка файлового хранилища: файл отсутствует, повреждён или недоступен для записи. */
final class StorageException extends RuntimeException
{
}

/**
 * Путь к файлу данных. Каталог можно переопределить переменной окружения APP_DATA_DIR
 * (используется автотестами, чтобы не менять файлы dataset).
 */
function dataPath(string $file): string
{
    $dir = getenv('APP_DATA_DIR') ?: __DIR__ . '/../data';
    return rtrim($dir, '/\\') . '/' . $file;
}

/**
 * Загрузка JSON-файла в массив. Единственное место чтения JSON в проекте.
 *
 * @throws StorageException если файла нет, он повреждён или в нём не список/объект
 */
function loadJson(string $path): array
{
    $name = basename($path);
    if (!is_file($path) || !is_readable($path)) {
        throw new StorageException("Файл «{$name}» не найден или недоступен для чтения.");
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new StorageException("Не удалось прочитать файл «{$name}».");
    }
    try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $ex) {
        throw new StorageException("Файл «{$name}» повреждён: " . $ex->getMessage() . '.');
    }
    if (!is_array($data)) {
        throw new StorageException("Файл «{$name}» имеет неверную структуру.");
    }
    return $data;
}
