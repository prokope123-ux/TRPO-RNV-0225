<?php
declare(strict_types=1);

/** Экранирование значения перед выводом в HTML. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Форматирование цены: 1 990,50 ₽ (копейки показываются только если они есть). */
function formatPrice(float $price): string
{
    $decimals = fmod($price, 1.0) === 0.0 ? 0 : 2;
    return number_format($price, $decimals, ',', ' ') . ' ₽';
}
