<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\ExportData\Order;

class TimeMapper
{
    public const TIME_FORMAT = 'c';

    public static function map(string $date): string
    {
        return date(self::TIME_FORMAT, strtotime($date));
    }

    public static function unmap(string $date): ?string
    {
        if ($date === "0001-01-01T00:00:00Z") {
            return null;
        }

        return date(self::TIME_FORMAT, strtotime($date));
    }
}
