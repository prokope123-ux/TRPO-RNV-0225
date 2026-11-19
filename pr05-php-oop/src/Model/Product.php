<?php
declare(strict_types=1);

/**
 * Товар каталога. Модель описывает данные и поведение и ничего не знает о HTML.
 *
 * Обязательные значения передаются в конструктор; конструктор проверяет их
 * и бросает InvalidArgumentException, поэтому невалидный объект создать нельзя.
 */
final class Product
{
    public const LOW_STOCK_THRESHOLD = 5;
    public const MAX_DISCOUNT = 90;

    public function __construct(
        private int $id,
        private string $name,
        private float $price,
        private int $categoryId,
        private string $sku,
        private int $stock,
        private ?string $description = null,
        private ?int $discountPercent = null
    ) {
        if ($id <= 0) {
            throw new InvalidArgumentException("Товар #{$id}: идентификатор должен быть положительным");
        }
        if (trim($name) === '') {
            throw new InvalidArgumentException("Товар #{$id}: название не может быть пустым");
        }
        if (!is_finite($price) || $price < 0) {
            throw new InvalidArgumentException("Товар #{$id}: цена должна быть числом не меньше нуля");
        }
        if ($categoryId <= 0) {
            throw new InvalidArgumentException("Товар #{$id}: не указана категория");
        }
        if (trim($sku) === '') {
            throw new InvalidArgumentException("Товар #{$id}: артикул не может быть пустым");
        }
        if ($stock < 0) {
            throw new InvalidArgumentException("Товар #{$id}: остаток не может быть отрицательным");
        }
        if ($discountPercent !== null && ($discountPercent < 0 || $discountPercent > self::MAX_DISCOUNT)) {
            throw new InvalidArgumentException(
                "Товар #{$id}: скидка должна быть в диапазоне 0–" . self::MAX_DISCOUNT . '%'
            );
        }
    }

    /**
     * Создаёт товар из строки products.json (ассоциативный массив).
     * Пустые необязательные значения ("" и null) превращаются в null.
     */
    public static function fromArray(array $row): self
    {
        foreach (['id', 'name', 'price', 'category_id', 'sku', 'stock'] as $key) {
            if (!array_key_exists($key, $row)) {
                throw new InvalidArgumentException("В товаре нет обязательного поля «{$key}»");
            }
        }
        if (!is_numeric($row['price'])) {
            throw new InvalidArgumentException('Товар #' . (int) $row['id'] . ': цена не является числом');
        }

        $description = isset($row['description']) ? trim((string) $row['description']) : '';
        $discount = $row['discount_percent'] ?? null;

        return new self(
            (int) $row['id'],
            (string) $row['name'],
            (float) $row['price'],
            (int) $row['category_id'],
            (string) $row['sku'],
            (int) $row['stock'],
            $description !== '' ? $description : null,
            $discount !== null && $discount !== '' ? (int) $discount : null
        );
    }

    // --- чтение состояния -------------------------------------------------

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return trim($this->name);
    }

    public function price(): float
    {
        return $this->price;
    }

    public function categoryId(): int
    {
        return $this->categoryId;
    }

    public function sku(): string
    {
        return $this->sku;
    }

    public function stock(): int
    {
        return $this->stock;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function discountPercent(): ?int
    {
        return $this->discountPercent;
    }

    // --- поведение предметной области -------------------------------------

    /** Есть ли действующая скидка (0% скидкой не считается). */
    public function hasDiscount(): bool
    {
        return $this->discountPercent !== null && $this->discountPercent > 0;
    }

    /** Итоговая цена с учётом скидки, округлённая до копеек. */
    public function finalPrice(): float
    {
        if (!$this->hasDiscount()) {
            return $this->price;
        }
        return round($this->price * (100 - $this->discountPercent) / 100, 2);
    }

    /** Сколько рублей экономит покупатель. */
    public function discountAmount(): float
    {
        return round($this->price - $this->finalPrice(), 2);
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /** Товар есть, но остаток меньше порога. */
    public function isLowStock(): bool
    {
        return $this->isInStock() && $this->stock < self::LOW_STOCK_THRESHOLD;
    }

    /** Краткое описание не длиннее $limit символов; для пустого описания — запасной текст. */
    public function shortDescription(int $limit = 80): string
    {
        if ($this->description === null) {
            return 'Описание отсутствует';
        }
        if (mb_strlen($this->description, 'UTF-8') <= $limit) {
            return $this->description;
        }
        return rtrim(mb_substr($this->description, 0, $limit - 1, 'UTF-8')) . '…';
    }

    /** Принадлежит ли товар категории с указанным идентификатором. */
    public function belongsTo(int $categoryId): bool
    {
        return $this->categoryId === $categoryId;
    }
}
