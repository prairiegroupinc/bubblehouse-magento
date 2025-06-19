<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Plugin\Magento\Sales\Model\ResourceModel\Order;

use BubbleHouse\Integration\Model\ConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Model\ResourceModel\Order;

class StatusChangePlugin
{
    public function __construct(
        private readonly PublisherInterface $publisher,
        private readonly ConfigProvider $configProvider
    ) {
    }

    public function afterSave(
        Order $subject,
        $result,
        $object
    ) {
        /** @var \Magento\Sales\Model\Order $object */
        if (!$this->configProvider->isEnabled(
                $object->getStoreId()
            ) && $this->configProvider->isOrderExportEnabled(
                $object->getStoreId()
            )
        ) {
            return $result;
        }

        if ($object->isDeleted() || $object->getOrigData('status') !== $object->getData('status')) {
            $this->publisher->publish('bubblehouse.integration.order.export', (int) $object->getEntityId());
        }

        return $result;
    }
}
