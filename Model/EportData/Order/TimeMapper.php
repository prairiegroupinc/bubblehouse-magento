<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\EportData\Order;

class TimeMapper
{
    public function map(string $date): string
    {
        return date("c", strtotime($date));
    }
}
