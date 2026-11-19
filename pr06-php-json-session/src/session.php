<?php
declare(strict_types=1);

/**
 * Запуск сессии. Вызывается в самом начале index.php — до любого вывода HTML,
 * иначе PHP не сможет отправить заголовок Set-Cookie.
 */
function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
        session_start();
    }
}

/** Одноразовое сообщение для пользователя: показывается на следующей странице и удаляется. */
function flash(string $type, string $text): void
{
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
}

function pullFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

/** Состояние формы (введённые значения и ошибки), сохранённое между POST и redirect. */
function rememberForm(string $name, array $values, array $errors): void
{
    $_SESSION['form'][$name] = ['values' => $values, 'errors' => $errors];
}

function pullForm(string $name): array
{
    $state = $_SESSION['form'][$name] ?? ['values' => [], 'errors' => []];
    unset($_SESSION['form'][$name]);
    return $state;
}

/** CSRF-токен: защищает POST-формы от отправки с чужого сайта. */
function csrfToken(): string
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function verifyCsrf(string $token): bool
{
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}
