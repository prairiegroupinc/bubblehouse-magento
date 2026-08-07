<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Api\Data;

interface UpdateCustomerRewardsResultInterface
{
    public const STATUS = 'status';

    /**
     * Get the update status.
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Set the update status.
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;
}
