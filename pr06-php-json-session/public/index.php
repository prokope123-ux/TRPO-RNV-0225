<?php
declare(strict_types=1);

// Единая точка входа приложения. Все переходы: /index.php?page=<имя>.

error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_ENV') === 'prod' ? '0' : '1');

require_once __DIR__ . '/../src/storage.php';
require_once __DIR__ . '/../src/notes.php';
require_once __DIR__ . '/../src/view.php';
require_once __DIR__ . '/../src/pages.php';
require_once __DIR__ . '/../src/session.php';
require_once __DIR__ . '/../src/actions.php';

// Сессия стартует до любого вывода.
startSession();

// Таблица маршрутов: значение параметра page → шаблон, заголовок и функция подготовки данных.
$routes = [
    'notes' => ['template' => 'list', 'title' => 'Заметки', 'handler' => 'buildNotesPage'],
];

// POST: изменить данные и сделать redirect. HTML здесь не выводится.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    handlePostRequest(array_keys($routes));
}

$page = $_GET['page'] ?? 'notes';
$page = is_string($page) && $page !== '' ? $page : 'notes';

if (!isset($routes[$page])) {
    renderPage('404', ['requested' => $page], ['title' => 'Страница не найдена', 'page' => '', 'flash' => pullFlash(), 'csrf' => csrfToken()], 404);
    exit;
}

$route = $routes[$page];
$data = ($route['handler'])();                 // 1) подготовка данных
renderPage($route['template'], $data,          // 2) вывод шаблона
    ['title' => $route['title'], 'page' => $page, 'flash' => pullFlash(), 'csrf' => csrfToken()]);
