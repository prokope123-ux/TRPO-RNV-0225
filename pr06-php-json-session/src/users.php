<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';

/**
 * Пользователи из users.json.
 *
 * @return array<int, array{id:int, login:string, display_name:string, role:string, theme:string}>
 * @throws StorageException
 */
function loadUsers(): array
{
    $users = [];
    foreach (loadJson(dataPath('users.json')) as $row) {
        if (!is_array($row) || !isset($row['id'], $row['display_name']) || !is_numeric($row['id'])) {
            continue; // повреждённую запись пропускаем
        }
        $users[(int) $row['id']] = [
            'id'           => (int) $row['id'],
            'login'        => (string) ($row['login'] ?? ''),
            'display_name' => (string) $row['display_name'],
            'role'         => (string) ($row['role'] ?? 'student'),
            'theme'        => in_array($row['theme'] ?? '', ['light', 'dark'], true) ? $row['theme'] : 'light',
        ];
    }
    return $users;
}
