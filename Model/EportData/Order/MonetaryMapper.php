<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\EportData\Order;

class MonetaryMapper
{
    public const PRECISION = 6;

    public function map(float $price): string
    {
        return number_format($price, self::PRECISION, '.');
    }
}
