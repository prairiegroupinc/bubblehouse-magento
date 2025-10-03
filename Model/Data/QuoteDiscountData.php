<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Data;

use BubbleHouse\Integration\Api\Data\QuoteDiscountDataInterface;
use Magento\Framework\DataObject;

class QuoteDiscountData extends DataObject implements QuoteDiscountDataInterface
{
    public function getAmount(): string
    {
        return (string) $this->getData(self::AMOUNT);
    }

    public function setAmount(string $amount): self
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    public function getDescription(): string
    {
        return (string) $this->getData(self::DESCRIPTION);
    }

    public function setDescription(string $description): self
    {
        return $this->setData(self::DESCRIPTION, $description);
    }
}
