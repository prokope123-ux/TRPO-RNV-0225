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

// ---------------------------------------------------------------------------
// Форма заявки сотрудника: нормализация и серверная валидация
// ---------------------------------------------------------------------------

const REQUEST_TYPES   = ['Отпуск', 'Командировка', 'Оборудование', 'Обучение'];
const NAME_MIN_LENGTH = 2;
const NAME_MAX_LENGTH = 80;
const COMMENT_MAX     = 200;
const DAYS_MIN        = 1;
const DAYS_MAX        = 30;
const FORM_FIELDS     = ['full_name', 'email', 'department', 'request_type', 'days', 'comment'];

/**
 * Берёт из «сырых» входных данных только известные поля и приводит их к строке с trim().
 * Если значение пришло не строкой (например full_name[]=x), оно считается пустым.
 */
function normalizeInput(array $source): array
{
    $values = [];
    foreach (FORM_FIELDS as $field) {
        $raw = $source[$field] ?? '';
        $values[$field] = is_string($raw) ? trim($raw) : '';
    }
    return $values;
}

/**
 * Проверяет значения формы. Возвращает ['values' => нормализованные, 'errors' => [поле => сообщение]].
 * Пустой массив errors означает, что данные корректны.
 */
function validateInput(array $source, array $departments): array
{
    $values = normalizeInput($source);
    $errors = [];

    // ФИО: обязательность и длина
    $nameLength = mb_strlen($values['full_name'], 'UTF-8');
    if ($values['full_name'] === '') {
        $errors['full_name'] = 'Укажите ФИО';
    } elseif ($nameLength < NAME_MIN_LENGTH || $nameLength > NAME_MAX_LENGTH) {
        $errors['full_name'] = sprintf('ФИО должно содержать от %d до %d символов', NAME_MIN_LENGTH, NAME_MAX_LENGTH);
    }

    // Email
    if ($values['email'] === '') {
        $errors['email'] = 'Укажите email';
    } elseif (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Некорректный email';
    }

    // Отдел — только из списка, полученного из employees.csv
    if ($values['department'] === '') {
        $errors['department'] = 'Выберите отдел';
    } elseif (!in_array($values['department'], $departments, true)) {
        $errors['department'] = 'Такого отдела нет в списке';
    }

    // Тип заявки — только из допустимых значений
    if ($values['request_type'] === '') {
        $errors['request_type'] = 'Выберите тип заявки';
    } elseif (!in_array($values['request_type'], REQUEST_TYPES, true)) {
        $errors['request_type'] = 'Недопустимый тип заявки';
    }

    // Количество дней: целое число в диапазоне
    if ($values['days'] === '') {
        $errors['days'] = 'Укажите количество дней';
    } elseif (filter_var($values['days'], FILTER_VALIDATE_INT) === false) {
        $errors['days'] = 'Количество дней должно быть целым числом';
    } else {
        $days = (int) $values['days'];
        if ($days < DAYS_MIN || $days > DAYS_MAX) {
            $errors['days'] = sprintf('Количество дней должно быть от %d до %d', DAYS_MIN, DAYS_MAX);
        }
    }

    // Комментарий необязателен, но ограничен по длине
    if (mb_strlen($values['comment'], 'UTF-8') > COMMENT_MAX) {
        $errors['comment'] = sprintf('Комментарий не длиннее %d символов', COMMENT_MAX);
    }

    return ['values' => $values, 'errors' => $errors];
}

// ---------------------------------------------------------------------------
// Фильтрация таблицы и проверка сценариев из form_cases.json
// ---------------------------------------------------------------------------

/**
 * Фильтр по отделу. Пустое значение (параметра нет) — вернуть всех.
 */
function filterEmployees(array $employees, string $department): array
{
    if ($department === '') {
        return $employees;
    }
    return array_values(array_filter(
        $employees,
        static fn(array $emp): bool => $emp['department'] === $department
    ));
}

/**
 * Читает form_cases.json. Бросает RuntimeException при отсутствии файла или неверном JSON.
 */
function loadFormCases(string $path): array
{
    if (!is_readable($path)) {
        throw new RuntimeException('Файл сценариев недоступен: ' . basename($path));
    }
    try {
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $ex) {
        throw new RuntimeException('form_cases.json повреждён: ' . $ex->getMessage());
    }
    return is_array($data['cases'] ?? null) ? $data['cases'] : [];
}

/**
 * Прогоняет один сценарий через validateInput и сравнивает с ожиданием.
 * Возвращает ['passed' => bool, 'actual_fields' => [...], 'expected_fields' => [...]].
 */
function runCase(array $case, array $departments): array
{
    $result = validateInput($case['input'] ?? [], $departments);
    $actual = array_keys($result['errors']);
    $expected = $case['expect']['error_fields'] ?? [];
    $expectedValid = (bool) ($case['expect']['valid'] ?? false);

    sort($actual);
    sort($expected);
    $passed = ($actual === $expected) && ($expectedValid === ($actual === []));

    // Сценарий про экранирование: опасная строка не должна попасть в HTML как есть.
    if ($passed && !empty($case['expect']['escaped'])) {
        $comment = $result['values']['comment'];
        $passed = $comment !== e($comment) && !str_contains(e($comment), '<script');
    }

    return ['passed' => $passed, 'actual_fields' => $actual, 'expected_fields' => $expected];
}
