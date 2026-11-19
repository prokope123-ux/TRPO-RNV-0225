<?php
declare(strict_types=1);

require_once __DIR__ . '/notes.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/state.php';

/** Безопасно читает строковое поле POST (массивы и отсутствующие поля дают пустую строку). */
function postString(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Redirect после POST (шаблон Post/Redirect/Get): обновление страницы не повторяет отправку формы. */
function redirectTo(string $page, string $anchor = ''): never
{
    header('Location: ' . url($page) . $anchor, true, 303);
    exit;
}

/**
 * Обработка всех POST-запросов. Здесь изменяются данные и session;
 * шаблоны ничего не изменяют, а только показывают результат.
 */
function handlePostRequest(array $allowedPages): never
{
    $return = postString('return');
    $return = in_array($return, $allowedPages, true) ? $return : 'notes';

    if (!verifyCsrf(postString('csrf'))) {
        flash('error', 'Форма устарела. Обновите страницу и повторите действие.');
        redirectTo($return);
    }

    switch (postString('action')) {
        case 'add_note':
            $result = validateNote($_POST);
            if ($result['errors'] !== []) {
                rememberForm('note', $result['values'], $result['errors']);
                flash('error', 'Заметка не сохранена. Исправьте ошибки в форме.');
                redirectTo('notes', '#add');
            }
            try {
                $id = addNote($result['values']['title'], $result['values']['body'], $result['tags'], $_SESSION['state']['display_name']);
                flash('ok', "Заметка №{$id} добавлена.");
            } catch (StorageException $ex) {
                rememberForm('note', $result['values'], []);
                flash('error', $ex->getMessage());
            }
            redirectTo('notes', '#notes');

        case 'login':
        case 'clear_name':
        case 'set_theme':
        case 'toggle_favorite':
            $action = postString('action');
            $params = ['user_id' => postString('user_id'), 'theme' => postString('theme'), 'note_id' => postString('note_id')];
            try {
                $error = applyAction($_SESSION['state'], $action, $params, stateContext());
            } catch (StorageException $ex) {
                $error = $ex->getMessage();
            }
            if ($error !== null) {
                flash('error', $error);
            } else {
                if ($action === 'login') {
                    session_regenerate_id(true); // после входа выдаём новый идентификатор сессии
                }
                flash('ok', [
                    'login'           => 'Вы вошли как ' . $_SESSION['state']['display_name'] . '.',
                    'clear_name'      => 'Имя очищено, вы снова «Гость».',
                    'set_theme'       => 'Режим интерфейса: ' . ($_SESSION['state']['theme'] === 'dark' ? 'тёмный' : 'светлый') . '.',
                    'toggle_favorite' => 'Избранное обновлено.',
                ][$action]);
            }
            redirectTo($return);

        default:
            flash('error', 'Неизвестное действие.');
            redirectTo($return);
    }
}
