<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Plugin\Magento\Customer\Model\ResourceModel\Customer;

use BubbleHouse\Integration\Model\ConfigProvider;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

class CustomerChangePlugin
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PublisherInterface $publisher,
        private readonly ConfigProvider $configProvider
    ) {
    }

    public function afterSave(
        Customer $subject,
        $result,
        $object
    ) {
        /** @var \Magento\Customer\Model\Customer $object */
        if (!$this->configProvider->isCustomerExportEnabled($object->getStoreId())) {
            return $result;
        }

        $changed = $object->isDeleted() || $object->getOrigData("updated_at") !== $object->getData("updated_at");

        if ($changed) {
            $this->publisher->publish('bubblehouse.integration.customer.export', (int) $object->getEntityId());
        }

        return $result;
    }
}
