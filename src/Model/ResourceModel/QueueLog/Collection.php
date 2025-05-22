<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\ResourceModel\QueueLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use BubbleHouse\Integration\Model\QueueLog as QueueLogModel;
use BubbleHouse\Integration\Model\ResourceModel\QueueLog as QueueLogResource;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(QueueLogModel::class, QueueLogResource::class);
    }
}
