# Практическая работа №1. HTML и базовая CSS-разметка

Дисциплина «Технологии разработки программного обеспечения», 3 семестр, 2026–2027 гг.
Преподаватель: Макиевский С. Е.

## Что получилось

Многораздельная адаптивная информационная страница учебного курса **ТРПО** на чистых HTML и CSS, без JavaScript и фреймворков. На странице: шапка с навигацией, первый экран с обложкой, шесть карточек тем курса, таблица с итогом, форма регистрации с встроенной валидацией и подвал.

## Запуск

Сборка не нужна.

```bash
git clone https://github.com/prokope123-ux/TRPO-RNV-0225.git
cd TRPO-RNV-0225
# откройте index.html двойным кликом или в браузере:
xdg-open index.html        # Linux
open index.html            # macOS
start index.html           # Windows
```

Все пути относительные, проект работает из любой папки.

## Структура проекта

```
index.html          разметка страницы
css/styles.css      все стили
images/             logo.svg, cover.svg (из dataset/assets)
data/               content.csv, navigation.json (из dataset)
docs/checklist.md   результаты проверок
README.md
.gitignore
```

## Использование dataset

Файлы dataset лежат в репозитории без изменений.

| Файл | Где используется |
|---|---|
| `data/navigation.json` | пункты `<nav>` в шапке: `label` → текст ссылки, `target` → якорь `#target`. Каждый `target` (`about`, `tracks`, `schedule`, `signup`) совпадает с `id` секции страницы |
| `data/content.csv` | разделитель `;`, поля `id;title;category;description;value`. Шесть строк → шесть одинаковых карточек `article.card` и шесть строк таблицы `#schedule`; `value` — числовой столбец таблицы, шкала `meter` в карточке и сумма 48 в `tfoot`; `title` → варианты списка «Направление» в форме |
| `images/logo.svg` | логотип в шапке, `alt="Логотип курса TRPO Course"` |
| `images/cover.svg` | обложка первого экрана с описательным `alt` |

Значения из CSV перенесены в разметку вручную (допускается заданием на первом этапе).

## Структура HTML

- `header.site-header` → логотип-ссылка и `nav[aria-label]` со списком ссылок;
- `main#main` → четыре `section` с `id` из `navigation.json` (`about`, `tracks`, `schedule`, `signup`), у каждой `aria-labelledby` на свой заголовок;
- `article.card` — карточка; все шесть используют один набор классов;
- `table` с `caption`, `thead`, `tbody`, `tfoot`, у ячеек `scope`;
- `form` с `label` у каждого поля, `fieldset/legend` для радиокнопок;
- `footer`;
- иерархия заголовков: один `h1`, далее `h2` секций и `h3` карточек.

## Применённые приёмы CSS

- `box-sizing: border-box`, CSS-переменные в `:root`, базовая типографика;
- ограничивающий контейнер `.container` (`max-width` + `margin-inline: auto`);
- **Flexbox**: шапка, меню, первый экран, сетка карточек (`flex-wrap` + `flex: 1 1 17rem`), сетка формы;
- медиазапрос `@media (max-width: 720px)`: шапка в колонку, таблица превращается в блоки с подписями из `data-label`, кнопки формы на всю ширину;
- состояния ссылок и `:focus-visible`, ссылка «Перейти к основному содержимому» для клавиатуры;
- состояния формы: `:user-invalid` показывает сообщение об ошибке, `:target` показывает сообщение об успешной отправке;
- пустое состояние: если в `.cards` нет карточек, выводится сообщение (`.cards:not(:has(.card))::before`);
- `prefers-reduced-motion` отключает плавную прокрутку.

## Проверка

Результаты — в [docs/checklist.md](docs/checklist.md).
