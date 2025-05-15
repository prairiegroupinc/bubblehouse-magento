<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\EportData\Order;

class TimeMapper
{
    public const TIME_FORMAT = 'Y-m-d H:i:s';

    public static function map(string $date): string
    {
        return date("c", strtotime($date));
    }

    public static function unmap(string $date): ?string
    {
        if ($date === "0001-01-01T00:00:00Z") {
            return null;
        }

        return date(self::TIME_FORMAT, strtotime($date));
    }
}
