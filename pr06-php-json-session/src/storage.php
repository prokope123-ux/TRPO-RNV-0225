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

/**
 * Сохранение массива в JSON-файл. Запись атомарная: сначала во временный файл
 * в том же каталоге, затем rename — читатель никогда не увидит наполовину записанный файл.
 *
 * @throws StorageException
 */
function saveJson(string $path, array $data): void
{
    $name = basename($path);
    try {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    } catch (JsonException $ex) {
        throw new StorageException("Не удалось закодировать данные для «{$name}»: " . $ex->getMessage());
    }
    $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
        throw new StorageException("Не удалось записать файл «{$name}»: проверьте права на каталог data/.");
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        throw new StorageException("Не удалось обновить файл «{$name}».");
    }
}

/**
 * Выполняет $operation под эксклюзивной блокировкой файла данных, чтобы два одновременных
 * запроса не потеряли записи друг друга (цикл «прочитать → изменить → записать»).
 *
 * @throws StorageException
 */
function withFileLock(string $path, callable $operation): mixed
{
    $lock = @fopen($path . '.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        throw new StorageException('Не удалось заблокировать хранилище для записи: проверьте права на каталог data/.');
    }
    try {
        return $operation();
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
