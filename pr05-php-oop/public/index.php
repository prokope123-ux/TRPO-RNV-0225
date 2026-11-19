<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_ENV') === 'prod' ? '0' : '1');

// Классы подключаются вручную через require_once (см. README: почему это неудобно при росте проекта).
require_once __DIR__ . '/../src/Model/Category.php';
require_once __DIR__ . '/../src/Model/Product.php';
require_once __DIR__ . '/../src/Service/CatalogService.php';
require_once __DIR__ . '/../src/Service/RequirementsChecker.php';
require_once __DIR__ . '/../src/view_helpers.php';

$service = new CatalogService(__DIR__ . '/../data/products.json', __DIR__ . '/../data/categories.json');

// --- Обработка фильтра (GET). Она живёт здесь, а не в классе Product. -------
$selectedCategory = null;
$onlyInStock = isset($_GET['in_stock']) && $_GET['in_stock'] === '1';
$rawCategory = $_GET['category'] ?? '';
if (is_string($rawCategory) && ctype_digit($rawCategory) && $rawCategory !== '') {
    $selectedCategory = (int) $rawCategory;
}

// --- Подготовка данных до вывода --------------------------------------------
$fatalError = null;
$products = [];
$categories = [];
$loadErrors = [];
try {
    $categories = $service->categories();
    if ($selectedCategory !== null && !isset($categories[$selectedCategory])) {
        $selectedCategory = null; // неизвестная категория в URL игнорируется
    }
    $products = $service->filterByCategory($selectedCategory, $onlyInStock);
    $loadErrors = $service->loadErrors();
} catch (RuntimeException $ex) {
    $fatalError = $ex->getMessage();
}

// --- Требования к классам из class_requirements.csv --------------------------
$requirementResults = [];
$requirementsError = null;
try {
    $requirementResults = (new RequirementsChecker(__DIR__ . '/../data/class_requirements.csv'))->check();
} catch (RuntimeException $ex) {
    $requirementsError = $ex->getMessage();
}
$requirementsOk = count(array_filter($requirementResults, static fn(array $r): bool => $r['ok']));

// --- Демонстрация защиты конструктора: некорректные данные не создают объект --
$constructorDemo = [];
$demoCases = [
    'Отрицательная цена'   => fn() => new Product(100, 'Тестовый товар', -10.0, 1, 'T-1', 1),
    'Пустое название'      => fn() => new Product(101, '   ', 10.0, 1, 'T-2', 1),
    'Скидка больше 90%'    => fn() => new Product(102, 'Тестовый товар', 10.0, 1, 'T-3', 1, null, 95),
];
foreach ($demoCases as $title => $factory) {
    try {
        $factory();
        $constructorDemo[$title] = 'объект создан (неожиданно)';
    } catch (InvalidArgumentException $ex) {
        $constructorDemo[$title] = 'InvalidArgumentException: ' . $ex->getMessage();
    }
}
$totalCount = count($products);
$averagePrice = $fatalError === null ? $service->averagePrice($products) : 0.0;
$stockValue = $fatalError === null ? $service->stockValue($products) : 0.0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Каталог товаров — ПР №5</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site-header">
  <h1>Практическая работа 5</h1>
  <nav><a href="#filter">Фильтр</a><a href="#catalog">Каталог</a><a href="#requirements">Требования</a><a href="#constructor">Конструктор</a></nav>
  <span class="meta">PHP ООП · Product → CatalogService → index.php</span>
</header>
<main>
  <?php if ($fatalError !== null): ?>
    <div class="alert error"><strong>Каталог недоступен.</strong> <?= e($fatalError) ?>. Проверьте файлы в папке <code>data/</code>.</div>
  <?php else: ?>

  <section class="card" id="filter">
    <h2>Фильтр каталога</h2>
    <form method="get" action="/" class="inline-form">
      <div class="row">
        <label for="category">Категория</label>
        <select id="category" name="category">
          <option value="">Все категории</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= $category->id() ?>" <?= $selectedCategory === $category->id() ? 'selected' : '' ?>><?= e($category->label()) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="row">
        <label><input type="checkbox" name="in_stock" value="1" <?= $onlyInStock ? 'checked' : '' ?>> Только в наличии</label>
      </div>
      <button type="submit">Применить</button>
      <a class="btn secondary" href="/">Сбросить</a>
    </form>
  </section>

  <?php if ($loadErrors !== []): ?>
    <div class="alert error">
      <strong>Часть строк dataset пропущена (<?= count($loadErrors) ?>):</strong>
      <ul><?php foreach ($loadErrors as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <section class="card">
    <div class="stats">
      <div><strong><?= $totalCount ?></strong>товаров в выборке</div>
      <div><strong><?= formatPrice($averagePrice) ?></strong>средняя итоговая цена</div>
      <div><strong><?= formatPrice($stockValue) ?></strong>стоимость остатков</div>
    </div>
  </section>

  <section id="catalog">
    <?php if ($products === []): ?>
      <p class="card empty">По выбранным условиям товаров нет.</p>
    <?php else: ?>
      <div class="products">
        <?php foreach ($products as $product): ?>
          <?php $category = $service->categoryOf($product); ?>
          <article class="product">
            <span class="cat"><?= $category !== null ? e($category->label()) : 'Без категории' ?></span>
            <h3><?= e($product->name()) ?></h3>
            <div>
              <span class="price"><?= formatPrice($product->finalPrice()) ?></span>
              <?php if ($product->hasDiscount()): ?>
                <span class="old"><?= formatPrice($product->price()) ?></span>
                <span class="sale">−<?= $product->discountPercent() ?>%</span>
              <?php endif; ?>
            </div>
            <p class="muted"><?= e($product->shortDescription(90)) ?></p>
            <div class="muted">Артикул: <?= e($product->sku()) ?></div>
            <?php if (!$product->isInStock()): ?>
              <div class="stock-out">Нет в наличии</div>
            <?php elseif ($product->isLowStock()): ?>
              <div class="stock-low">Осталось мало: <?= $product->stock() ?> шт.</div>
            <?php else: ?>
              <div class="stock-ok">В наличии: <?= $product->stock() ?> шт.</div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <?php endif; ?>

  <section class="card" id="requirements">
    <h2>Требования из class_requirements.csv</h2>
    <?php if ($requirementsError !== null): ?>
      <div class="alert error"><?= e($requirementsError) ?></div>
    <?php else: ?>
      <p class="muted">Выполнено <?= $requirementsOk ?> из <?= count($requirementResults) ?> (проверка через Reflection)</p>
      <table>
        <thead><tr><th>Класс</th><th>Член</th><th>Вид</th><th>Тип</th><th>Обяз.</th><th>Назначение</th><th>Итог</th></tr></thead>
        <tbody>
        <?php foreach ($requirementResults as $r): ?>
          <tr>
            <td><?= e($r['class']) ?></td><td><code><?= e($r['member']) ?></code></td><td><?= e($r['kind']) ?></td>
            <td><code><?= e($r['type']) ?></code></td><td><?= $r['required'] === 'yes' ? 'да' : 'нет' ?></td>
            <td><?= e($r['description']) ?></td>
            <td class="<?= $r['ok'] ? 'pass' : 'fail' ?>"><?= $r['ok'] ? 'OK' : 'FAIL: ' . e($r['note']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <section class="card" id="constructor">
    <h2>Проверка некорректного конструктора</h2>
    <p class="muted">Конструктор <code>Product</code> бросает <code>InvalidArgumentException</code>, поэтому объект с неверным состоянием создать нельзя.</p>
    <table>
      <tr><th>Вход</th><th>Результат</th></tr>
      <?php foreach ($constructorDemo as $title => $result): ?>
        <tr><td><?= e($title) ?></td><td><?= e($result) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </section>
</main>
</body>
</html>
