<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Api;

use BubbleHouse\Integration\Api\Data\DiscountDataInterface;

interface CreateDiscount4Interface
{
    /**
     * @param DiscountDataInterface $CreateDiscount4
     * @return void
     */
    public function execute(DiscountDataInterface $CreateDiscount4): void;
}
