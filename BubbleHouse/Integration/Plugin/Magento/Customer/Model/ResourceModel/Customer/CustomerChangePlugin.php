<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Plugin\Magento\Customer\Model\ResourceModel\Customer;

use BubbleHouse\Integration\Model\ConfigProvider;
use BubbleHouse\Integration\Model\EportData\Customer\CustomerExtractor;
use BubbleHouse\Integration\Model\Services\Connector\BubbleHouseRequest;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CustomerChangePlugin
{
    public function __construct(
        private readonly BubbleHouseRequest $bubbleHouseRequest,
        private readonly CustomerExtractor $customerExtractor,
        private readonly ConfigProvider $configProvider
    ) {
    }

    public function afterSave(
        Customer $subject,
        $result,
        $object
    ) {
        /** @var \Magento\Customer\Model\Customer $object */
        if (!$this->configProvider->isEnabled(
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $object->getStoreId()
            ) && $this->configProvider->isCustomerExportEnabled(
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $object->getStoreId()
            )
        ) {
            return $result;
        }

        if ($object->isDeleted() || $this->shouldTrackChanges($object)) {
            $extractedData = $this->customerExtractor->extract($object->getDataModel());
            $this->bubbleHouseRequest->exportData(
                BubbleHouseRequest::CUSTOMER_EXPORT_TYPE,
                $extractedData,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $object->getStoreId()
            );
        }

        return $result;
    }

    private function shouldTrackChanges(\Magento\Customer\Model\Customer $object): bool
    {
        return $object->getData('firstname') !== $object->getOrigData('firstname')
            || $object->getData('lastname') !== $object->getOrigData('lastname')
            || $object->getData('email') !== $object->getOrigData('email');
    }
}
