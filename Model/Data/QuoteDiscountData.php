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

    public function getCode(): string
    {
        return (string) $this->getData(self::CODE);
    }

    public function setCode(string $code): self
    {
        return $this->setData(self::CODE, $code);
    }

    public function getDescription(): string
    {
        return (string) $this->getData(self::DESCRIPTION);
    }

    public function setDescription(string $description): self
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    public function getQuoteHash(): string
    {
        return (string) $this->getData(self::QUOTE_HASH);
    }

    public function setQuoteHash(string $quoteHash): self
    {
        return $this->setData(self::QUOTE_HASH, $quoteHash);
    }

    public static function calculateQuoteHash($quote): string
    {
        $items = $quote->getAllVisibleItems();
        $data = [];
        foreach ($items as $item) {
            $price = number_format((float)$item->getPrice(), 4, '.', '');
            $data[] = $item->getProductId() . ',' . $item->getQty() . ',' . $price;
        }
        $concatenated = implode('|', $data);
        return hash('sha256', $concatenated);
    }

    public function bindToQuote($quote): self
    {
        $hash = self::calculateQuoteHash($quote);
        $this->setQuoteHash($hash);
        return $this;
    }
}
