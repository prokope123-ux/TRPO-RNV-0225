<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_ENV') === 'prod' ? '0' : '1');

// Классы подключаются вручную через require_once (см. README: почему это неудобно при росте проекта).
require_once __DIR__ . '/../src/Model/Category.php';
require_once __DIR__ . '/../src/Model/Product.php';
require_once __DIR__ . '/../src/Service/CatalogService.php';
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
  <nav><a href="#filter">Фильтр</a><a href="#catalog">Каталог</a></nav>
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
</main>
</body>
</html>
