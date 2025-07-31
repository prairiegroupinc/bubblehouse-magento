<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\ExportData\Order;

class MonetaryMapper
{
    public const PRECISION = 4;

    public static function map(float $price): string
    {
        return number_format($price, self::PRECISION, '.', '');
    }

    public static function unmap(string $monetary): float
    {
        return (float)$monetary;
    }
}
