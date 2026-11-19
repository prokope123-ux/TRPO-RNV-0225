<?php
declare(strict_types=1);

/** Экранирование значения перед выводом в HTML. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Единый формат внутренних ссылок: все переходы идут через public/index.php. */
function url(string $page, array $extra = []): string
{
    return '/index.php?' . http_build_query(['page' => $page] + $extra);
}

/**
 * Подключает шаблон и возвращает его вывод строкой.
 * Шаблон получает только переданные ему данные: ключи массива становятся переменными.
 *
 * @param string $template имя файла в каталоге templates без расширения (list, users, 404 ...)
 */
function render(string $template, array $data = []): string
{
    $path = __DIR__ . '/../templates/' . $template . '.php';
    if (!preg_match('/^[a-z0-9_]+$/', $template) || !is_file($path)) {
        throw new InvalidArgumentException("Шаблон «{$template}» не найден");
    }
    extract($data, EXTR_SKIP);
    ob_start();
    require $path;
    return (string) ob_get_clean();
}

/**
 * Отправляет страницу: сначала рендерит шаблон, затем вставляет его в layout.
 */
function renderPage(string $template, array $data, array $layout, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    $content = render($template, $data);
    echo render('layout', $layout + ['content' => $content]);
}
