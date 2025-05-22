<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model;

use Magento\Framework\Model\AbstractModel;
use BubbleHouse\Integration\Model\ResourceModel\QueueLog as QueueLogResource;

class QueueLog extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(QueueLogResource::class);
    }
}
