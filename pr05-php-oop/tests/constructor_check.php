<?php
declare(strict_types=1);

// Проверка конструкторов: php tests/constructor_check.php
require_once __DIR__ . '/../src/Model/Category.php';
require_once __DIR__ . '/../src/Model/Product.php';

$failed = 0;
$check = static function (string $title, bool $ok) use (&$failed): void {
    printf("[%s] %s\n", $ok ? 'OK  ' : 'FAIL', $title);
    if (!$ok) {
        $failed++;
    }
};

// Корректные объекты
$p = new Product(10, 'Keyboard', 3500.0, 1, 'KB-1', 7, null, 10);
$check('finalPrice со скидкой 10% = 3150', $p->finalPrice() === 3150.0);
$check('hasDiscount() = true при 10%', $p->hasDiscount());
$check('discountAmount() = 350', $p->discountAmount() === 350.0);
$check('isLowStock() = false при остатке 7', !$p->isLowStock());
$check('shortDescription() для null', $p->shortDescription() === 'Описание отсутствует');
$check('скидка 0% не считается скидкой', !(new Product(1, 'A', 100.0, 1, 'A', 0, null, 0))->hasDiscount());
$check('остаток 0: isInStock() = false', !(new Product(1, 'A', 100.0, 1, 'A', 0))->isInStock());

// Некорректные конструкторы должны бросать InvalidArgumentException
$bad = [
    'отрицательная цена'       => fn() => new Product(1, 'A', -1.0, 1, 'A', 1),
    'пустое название'          => fn() => new Product(1, '   ', 1.0, 1, 'A', 1),
    'отрицательный остаток'    => fn() => new Product(1, 'A', 1.0, 1, 'A', -3),
    'скидка больше 90%'        => fn() => new Product(1, 'A', 1.0, 1, 'A', 1, null, 95),
    'нулевой id'               => fn() => new Product(0, 'A', 1.0, 1, 'A', 1),
    'нет поля в fromArray'     => fn() => Product::fromArray(['id' => 1]),
    'цена-строка в fromArray'  => fn() => Product::fromArray(['id' => 1, 'name' => 'A', 'price' => 'много', 'category_id' => 1, 'sku' => 'A', 'stock' => 1]),
    'плохой slug категории'    => fn() => new Category(1, 'Мыши', 'Mice Big'),
];
foreach ($bad as $title => $factory) {
    try {
        $factory();
        $check("InvalidArgumentException: {$title}", false);
    } catch (InvalidArgumentException) {
        $check("InvalidArgumentException: {$title}", true);
    }
}

exit($failed === 0 ? 0 : 1);
