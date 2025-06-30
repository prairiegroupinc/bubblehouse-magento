<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\EportData\Order;

class MonetaryMapper
{
    public const MAP_PRECISION = 6;
    public const UNMAP_PRECISION = 4;

    public static function map(float $price): string
    {
        if (str_contains(',', (string) $price)) {
            $price = str_replace(',', '', (string) $price);
        }

        return number_format($price, self::MAP_PRECISION, '.');
    }

    public static function unmap(string $monetary): float
    {
        return (float)number_format((float)$monetary, self::UNMAP_PRECISION, '.');
    }
}
