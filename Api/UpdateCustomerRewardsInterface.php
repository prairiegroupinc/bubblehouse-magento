<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Api;

use BubbleHouse\Integration\Api\Data\UpdateCustomerRewardsResultInterface;

interface UpdateCustomerRewardsInterface
{
    /**
     * Update a customer's Bubblehouse rewards attributes.
     *
     * @param string $customerEmail
     * @param int $websiteId
     * @param string $bhTier
     * @param string $bhPointsBalance
     * @return UpdateCustomerRewardsResultInterface
     */
    public function execute(
        string $customerEmail,
        int $websiteId,
        string $bhTier,
        string $bhPointsBalance
    ): UpdateCustomerRewardsResultInterface;
}
