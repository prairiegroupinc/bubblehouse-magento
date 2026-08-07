<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Plugin\Magento\Customer\Model\ResourceModel\Customer;

use BubbleHouse\Integration\Model\ConfigProvider;
use BubbleHouse\Integration\Model\Services\CustomerExportSuppressor;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

class CustomerChangePlugin
{
    /**
     * @param LoggerInterface $logger
     * @param PublisherInterface $publisher
     * @param ConfigProvider $configProvider
     * @param CustomerExportSuppressor $customerExportSuppressor
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PublisherInterface $publisher,
        private readonly ConfigProvider $configProvider,
        private readonly CustomerExportSuppressor $customerExportSuppressor
    ) {
    }

    /**
     * Publish changed customers unless the save originated from an inbound update.
     *
     * @param Customer $subject
     * @param mixed $result
     * @param \Magento\Customer\Model\Customer $object
     * @return mixed
     */
    public function afterSave(
        Customer $subject,
        $result,
        $object
    ) {
        if ($this->customerExportSuppressor->isSuppressed()) {
            return $result;
        }

        /** @var \Magento\Customer\Model\Customer $object */
        $storeId = (int)$object->getStoreId();

        if ($storeId <= 0 || !$this->configProvider->canExportCustomers($storeId)) {
            return $result;
        }

        $changed = $object->isDeleted() || $object->getOrigData("updated_at") !== $object->getData("updated_at");

        if ($changed) {
            $this->publisher->publish('bubblehouse.integration.customer.export', (int) $object->getEntityId());
        }

        return $result;
    }
}
