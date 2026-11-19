<?php
declare(strict_types=1);

// Проверка сервиса на dataset: php tests/service_check.php
require_once __DIR__ . '/../src/Service/CatalogService.php';

$service = new CatalogService(__DIR__ . '/../data/products.json', __DIR__ . '/../data/categories.json');
$failed = 0;
$check = static function (string $title, bool $ok) use (&$failed): void {
    printf("[%s] %s\n", $ok ? 'OK  ' : 'FAIL', $title);
    if (!$ok) {
        $failed++;
    }
};

$all = $service->products();
$check('из 14 строк products.json создано 12 корректных товаров', count($all) === 12);
$check('две некорректные строки попали в loadErrors()', count($service->loadErrors()) === 2);
$check('categories.json: 5 категорий', count($service->categories()) === 5);
$check('фильтр по категории 1 (клавиатуры) = 2 товара', count($service->filterByCategory(1)) === 2);
$check('без фильтра — все товары', count($service->filterByCategory(null)) === 12);
$check('только в наличии — меньше, чем всего', count($service->filterByCategory(null, true)) < 12);
$check('пустой набор: averagePrice() = 0', $service->averagePrice([]) === 0.0);

$webcam = array_values(array_filter($all, fn(Product $p) => $p->id() === 11))[0];
$check('товар с неизвестной категорией: categoryOf() = null', $service->categoryOf($webcam) === null);

exit($failed === 0 ? 0 : 1);
