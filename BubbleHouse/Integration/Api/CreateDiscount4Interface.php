<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Api;

interface CreateDiscount4Interface
{
    /**
     * @param \BubbleHouse\Integration\Api\Data\DiscountDataInterface $discountData
     * @return void
     */
    public function createDiscount(\BubbleHouse\Integration\Api\Data\DiscountDataInterface $discountData): void;
}
