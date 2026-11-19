<?php
declare(strict_types=1);

require_once __DIR__ . '/../Model/Category.php';
require_once __DIR__ . '/../Model/Product.php';

/**
 * Загружает products.json и categories.json и формирует объекты Product и Category.
 * Не выводит HTML и не читает $_GET/$_POST — только данные и операции над ними.
 */
final class CatalogService
{
    /** @var Product[]|null */
    private ?array $products = null;
    /** @var array<int, Category>|null */
    private ?array $categories = null;
    /** @var string[] */
    private array $loadErrors = [];

    public function __construct(
        private string $productsPath,
        private string $categoriesPath
    ) {
    }

    /**
     * Все корректные товары. Некорректные строки не роняют приложение:
     * они пропускаются, а причина попадает в loadErrors().
     *
     * @return Product[]
     */
    public function products(): array
    {
        if ($this->products === null) {
            $this->products = [];
            foreach ($this->readJson($this->productsPath, 'products.json') as $index => $row) {
                try {
                    if (!is_array($row)) {
                        throw new InvalidArgumentException('строка не является объектом');
                    }
                    $this->products[] = Product::fromArray($row);
                } catch (InvalidArgumentException $ex) {
                    $this->loadErrors[] = 'products.json, запись №' . ($index + 1) . ': ' . $ex->getMessage();
                }
            }
        }
        return $this->products;
    }

    /**
     * Категории, индексированные по id.
     *
     * @return array<int, Category>
     */
    public function categories(): array
    {
        if ($this->categories === null) {
            $this->categories = [];
            foreach ($this->readJson($this->categoriesPath, 'categories.json') as $index => $row) {
                try {
                    $category = Category::fromArray(is_array($row) ? $row : []);
                    $this->categories[$category->id()] = $category;
                } catch (InvalidArgumentException $ex) {
                    $this->loadErrors[] = 'categories.json, запись №' . ($index + 1) . ': ' . $ex->getMessage();
                }
            }
        }
        return $this->categories;
    }

    /** Категория товара или null, если в categories.json такой нет. */
    public function categoryOf(Product $product): ?Category
    {
        return $this->categories()[$product->categoryId()] ?? null;
    }

    /**
     * Фильтр по категории. null — без фильтра.
     *
     * @return Product[]
     */
    public function filterByCategory(?int $categoryId, bool $onlyInStock = false): array
    {
        return array_values(array_filter(
            $this->products(),
            static fn(Product $p): bool =>
                ($categoryId === null || $p->belongsTo($categoryId)) && (!$onlyInStock || $p->isInStock())
        ));
    }

    /**
     * Средняя итоговая цена набора товаров (0.0 для пустого набора).
     *
     * @param Product[] $products
     */
    public function averagePrice(array $products): float
    {
        if ($products === []) {
            return 0.0;
        }
        $sum = array_reduce($products, static fn(float $acc, Product $p): float => $acc + $p->finalPrice(), 0.0);
        return round($sum / count($products), 2);
    }

    /**
     * Суммарная стоимость остатков набора товаров по итоговым ценам.
     *
     * @param Product[] $products
     */
    public function stockValue(array $products): float
    {
        return round(array_reduce(
            $products,
            static fn(float $acc, Product $p): float => $acc + $p->finalPrice() * $p->stock(),
            0.0
        ), 2);
    }

    /**
     * Сообщения о пропущенных строках. Заполняется после загрузки данных.
     *
     * @return string[]
     */
    public function loadErrors(): array
    {
        $this->products();
        $this->categories();
        return $this->loadErrors;
    }

    /**
     * Читает JSON-массив из файла. Бросает RuntimeException с понятным текстом
     * при отсутствующем файле, неверном JSON или неверной структуре.
     */
    private function readJson(string $path, string $label): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException("Файл {$label} не найден или недоступен");
        }
        try {
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $ex) {
            throw new RuntimeException("Файл {$label} повреждён: " . $ex->getMessage());
        }
        if (!is_array($data) || !array_is_list($data)) {
            throw new RuntimeException("Файл {$label} должен содержать список записей");
        }
        return $data;
    }
}
