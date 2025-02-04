<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Api;

interface CreateDiscount4Interface
{
    /**
     * @param array $discountData
     * @return void
     */
    public function createDiscount(array $discountData): void;
}
