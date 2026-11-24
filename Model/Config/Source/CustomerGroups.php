<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Config\Source;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class CustomerGroups implements OptionSourceInterface
{
    public function __construct(
        private readonly CollectionFactory $groupCollectionFactory
    ) {
    }

    public function toOptionArray(): array
    {
        return $this->groupCollectionFactory->create()
            ->setOrder('customer_group_code', 'ASC')
            ->toOptionArray();
    }
}
