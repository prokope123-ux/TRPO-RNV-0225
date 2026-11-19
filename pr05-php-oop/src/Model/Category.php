<?php
declare(strict_types=1);

/**
 * Категория товаров. Объект неизменяемый: состояние задаётся только конструктором.
 */
final class Category
{
    public function __construct(
        private int $id,
        private string $name,
        private string $slug
    ) {
        if ($id <= 0) {
            throw new InvalidArgumentException('Идентификатор категории должен быть положительным');
        }
        if (trim($name) === '') {
            throw new InvalidArgumentException('Название категории не может быть пустым');
        }
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new InvalidArgumentException('slug категории может содержать только a-z, 0-9 и дефис');
        }
    }

    /** Создаёт категорию из строки categories.json. */
    public static function fromArray(array $row): self
    {
        foreach (['id', 'name', 'slug'] as $key) {
            if (!array_key_exists($key, $row)) {
                throw new InvalidArgumentException("В категории нет обязательного поля «{$key}»");
            }
        }
        return new self((int) $row['id'], (string) $row['name'], (string) $row['slug']);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    /** Название для интерфейса. */
    public function label(): string
    {
        return trim($this->name);
    }
}
