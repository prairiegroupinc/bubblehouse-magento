<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Plugin\Magento\Sales\Model\ResourceModel\Order;

use BubbleHouse\Integration\Model\ConfigProvider;
use BubbleHouse\Integration\Model\EportData\Order\OrderExtractor;
use BubbleHouse\Integration\Model\Services\Connector\BubbleHouseRequest;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Model\ResourceModel\Order;

class StatusChangePlugin
{
    public function __construct(
        private readonly BubbleHouseRequest $bubbleHouseRequest,
        private readonly OrderExtractor $orderExtractor,
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
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $object->getStoreId()
            ) && $this->configProvider->isOrderExportEnabled(
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $object->getStoreId()
            )
        ) {
            return $result;
        }

        if ($object->isDeleted() || $object->getOrigData('status') !== $object->getData('status')) {
            $extractedData = $this->orderExtractor->extract($object, $result->isDeleted());
            $this->bubbleHouseRequest->exportData(
                BubbleHouseRequest::ORDER_EXPORT_TYPE,
                $extractedData,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $object->getStoreId()
            );
        }

        return $result;
    }
}
