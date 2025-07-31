<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Plugin\Magento\Sales\Model\ResourceModel\Order;

use BubbleHouse\Integration\Model\ConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Model\ResourceModel\Order;
use Psr\Log\LoggerInterface;

class OrderChangePlugin
{
    public function __construct(
        private readonly PublisherInterface $publisher,
        private readonly ConfigProvider $configProvider,
        protected LoggerInterface $logger
    ) {
    }

    public function afterSave(
        Order $subject,
        $result,
        $object
    ) {
        /** @var \Magento\Sales\Model\Order $object */
        if (!$this->configProvider->isOrderExportEnabled($object->getStoreId())) {
            return $result;
        }

        $changed = $object->isDeleted() || $object->getOrigData("updated_at") !== $object->getData("updated_at");

        if ($changed) {
            $this->publisher->publish('bubblehouse.integration.order.export', (int) $object->getEntityId());
        }

        return $result;
    }
}
