<?php
declare(strict_types=1);

/**
 * Экранирование любого пользовательского значения перед выводом в HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Преобразует одну строку CSV (индексный массив) в ассоциативную структуру сотрудника.
 * Возвращает null, если строка повреждена (не хватает колонок или нет id).
 */
function parseEmployeeRow(array $row, array $header): ?array
{
    if (count($row) !== count($header)) {
        return null;
    }
    $data = array_combine($header, array_map('trim', $row));
    if (!ctype_digit($data['id'] ?? '')) {
        return null;
    }
    return [
        'id'         => (int) $data['id'],
        'full_name'  => $data['full_name'],
        'email'      => $data['email'],
        'department' => $data['department'],
        'position'   => $data['position'] !== '' ? $data['position'] : null, // необязательное поле
        'salary'     => (int) $data['salary'],
        'hired_at'   => $data['hired_at'],
    ];
}

/**
 * Читает employees.csv. Возвращает ['employees' => [...], 'skipped' => N].
 * Бросает RuntimeException, если файл недоступен.
 */
function loadEmployees(string $path): array
{
    if (!is_readable($path)) {
        throw new RuntimeException('Файл сотрудников недоступен: ' . basename($path));
    }
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Не удалось открыть файл: ' . basename($path));
    }

    $employees = [];
    $skipped = 0;
    $header = fgetcsv($handle, null, ',', '"', '');
    if (!is_array($header)) {
        fclose($handle);
        throw new RuntimeException('Файл сотрудников пуст');
    }
    $header[0] = ltrim((string) $header[0], "\xEF\xBB\xBF"); // убираем BOM, если он есть
    $header = array_map('trim', $header);

    while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
        if ($row === [null]) {          // пустая строка в файле
            continue;
        }
        $employee = parseEmployeeRow($row, $header);
        if ($employee === null) {
            $skipped++;
            continue;
        }
        $employees[] = $employee;
    }
    fclose($handle);

    return ['employees' => $employees, 'skipped' => $skipped];
}

/**
 * Список уникальных отделов из набора сотрудников (отсортирован).
 */
function departmentsOf(array $employees): array
{
    $list = array_values(array_unique(array_column($employees, 'department')));
    sort($list, SORT_STRING);
    return $list;
}

function formatMoney(int $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' ₽';
}
