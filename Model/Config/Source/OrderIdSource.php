<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class OrderIdSource implements OptionSourceInterface
{
    public const ENTITY_ID = 'entity_id';
    public const QUOTE_ID = 'quote_id';
    public const EXT_ORDER_ID = 'ext_order_id';
    public const INCREMENT_ID = 'increment_id';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::ENTITY_ID, 'label' => __('Entity ID (getEntityId)')],
            ['value' => self::QUOTE_ID, 'label' => __('Quote ID (getQuoteId)')],
            ['value' => self::EXT_ORDER_ID, 'label' => __('External Order ID (getExtOrderId)')],
            ['value' => self::INCREMENT_ID, 'label' => __('Increment ID (getIncrementId)')],
        ];
    }
}
