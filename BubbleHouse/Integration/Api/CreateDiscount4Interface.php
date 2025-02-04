<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Api;

interface CreateDiscount4Interface
{
    /**
     * @param \code\BubbleHouse\Integration\Api\Data\DiscountDataInterface $discountData
     * @return void
     */
    public function createDiscount(\code\BubbleHouse\Integration\Api\Data\DiscountDataInterface $discountData): void;
}
