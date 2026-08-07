<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Data;

use BubbleHouse\Integration\Api\Data\UpdateCustomerRewardsResultInterface;
use Magento\Framework\DataObject;

class UpdateCustomerRewardsResult extends DataObject implements UpdateCustomerRewardsResultInterface
{
    /**
     * @inheritDoc
     */
    public function getStatus(): string
    {
        return (string) $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status): self
    {
        return $this->setData(self::STATUS, $status);
    }
}
