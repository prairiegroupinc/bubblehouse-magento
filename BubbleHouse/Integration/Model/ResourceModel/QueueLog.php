<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class QueueLog extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('bubblehouse_queue_log', 'id');
    }
}
