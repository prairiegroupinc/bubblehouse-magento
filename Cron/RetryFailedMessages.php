<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Cron;

use BubbleHouse\Integration\Model\ConfigProvider;
use BubbleHouse\Integration\Model\QueueLog;
use BubbleHouse\Integration\Model\ResourceModel\QueueLog as QueueLogResource;
use BubbleHouse\Integration\Model\ResourceModel\QueueLog\Collection;
use BubbleHouse\Integration\Model\ResourceModel\QueueLog\CollectionFactory;
use BubbleHouse\Integration\Model\Services\Connector\BubbleHouseRequest;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\State;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class RetryFailedMessages
{
    private const STATUS_SUCCESS = 1;
    private const STATUS_SKIPPED = 2;

    public function __construct(
        private readonly BubbleHouseRequest $request,
        private readonly CollectionFactory $collectionFactory,
        private readonly SerializerInterface $serializer,
        private readonly QueueLogResource $resource,
        private readonly LoggerInterface $logger,
        private readonly State $appState,
        private readonly ConfigProvider $configProvider
    ) {
    }

    public function execute(): void
    {
        $lastProcessedId = 0;
        $maxQueueLogId = $this->getMaxFailedQueueLogId();
        $pageSize = 100;

        if ($maxQueueLogId <= 0) {
            return;
        }

        do {
            $processedCount = 0;
            $previousLastProcessedId = $lastProcessedId;

            /** @var Collection $collection */
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('status', 0);
            $collection->addFieldToFilter('id', ['gt' => $lastProcessedId]);
            $collection->addFieldToFilter('id', ['lteq' => $maxQueueLogId]);
            $collection->setOrder('id', 'ASC');
            $collection->setPageSize($pageSize);
            $collection->setCurPage(1);
            $collection->load();

            /** @var QueueLog $queueLog */
            foreach ($collection as $queueLog) {
                $queueLogId = (int)$queueLog->getId();

                if ($queueLogId <= 0) {
                    $this->logger->warning('Bubblehouse: Skipped BubbleHouse queue retry without queue log id.');
                    continue;
                }

                $processedCount++;
                $lastProcessedId = max($lastProcessedId, $queueLogId);

                try {
                    $type = $queueLog->getData('message_type') === 'customer'
                        ? BubbleHouseRequest::CUSTOMER_EXPORT_TYPE : BubbleHouseRequest::ORDER_EXPORT_TYPE;
                    $data = $this->serializer->unserialize($queueLog->getData('message_body'));

                    if ($type === BubbleHouseRequest::ORDER_EXPORT_TYPE) {
                        $data = $this->convertMonetary($data);
                    }

                    $scopeCode = $this->getScopeCode($queueLog);

                    if ($scopeCode === null) {
                        $this->markSkipped($queueLog, 'missing store scope');
                        continue;
                    }

                    if (!$this->isExportEnabled($type, $scopeCode)) {
                        $this->logger->warning(
                            'Bubblehouse: Delayed BubbleHouse queue retry #'
                            . (string)$queueLogId
                            . ': export is disabled for store scope '
                            . (string)$scopeCode
                        );
                        continue;
                    }

                    $response = $this->request->exportData(
                        $type,
                        $data,
                        $scopeCode
                    );

                    if ($response) {
                        $queueLog->setData('status', self::STATUS_SUCCESS);
                        $this->saveQueueLog($queueLog);
                    }
                } catch (\Exception $exception) {
                    $this->logger->critical(
                        __('Bubblehouse: Failed Resend Bubblehouse Data: ' . $exception->getMessage())
                    );
                }
            }

            if ($lastProcessedId <= $previousLastProcessedId) {
                break;
            }
        } while ($processedCount >= $pageSize);
    }

    private function getMaxFailedQueueLogId(): int
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', 0);
        $collection->setOrder('id', 'DESC');
        $collection->setPageSize(1);
        $collection->setCurPage(1);
        $collection->load();

        return (int)$collection->getFirstItem()->getId();
    }

    private function getScopeCode(QueueLog $queueLog): ?int
    {
        $storeId = $queueLog->getData('store_id');

        if ($storeId !== null && $storeId !== '' && (int)$storeId > 0) {
            return (int)$storeId;
        }

        return null;
    }

    private function isExportEnabled(int $type, int $scopeCode): bool
    {
        if ($type === BubbleHouseRequest::CUSTOMER_EXPORT_TYPE) {
            return $this->configProvider->canExportCustomers($scopeCode);
        }

        return $this->configProvider->canExportOrders($scopeCode);
    }

    private function markSkipped(QueueLog $queueLog, string $reason): void
    {
        $queueLog->setData('status', self::STATUS_SKIPPED);
        $this->saveQueueLog($queueLog);
        $this->logger->warning(
            'Bubblehouse: Skipped BubbleHouse queue retry #' . (string)$queueLog->getId() . ': ' . $reason
        );
    }

    private function saveQueueLog(QueueLog $queueLog): void
    {
        $this->appState->emulateAreaCode(
            FrontNameResolver::AREA_CODE,
            [$this->resource, 'save'],
            [$queueLog]
        );
    }

    private function convertMonetary(array $orderData): array
    {
        if (isset($orderData['amount_full']) && str_contains($orderData['amount_full'], ',')) {
            $orderData['amount_full'] = str_replace(',', '', $orderData['amount_full']);
        }

        if (isset($orderData['amount_spent']) && str_contains($orderData['amount_spent'], ',')) {
            $orderData['amount_spent'] = str_replace(',', '', $orderData['amount_spent']);
        }

        foreach ($orderData['items'] as $itemIndex => $itemData) {
            if (str_contains($itemData['amount_full'], ',')) {
                $orderData['items'][$itemIndex]['amount_full'] = str_replace(
                    ',',
                    '',
                    $itemData['amount_full']
                );
            }

            if (str_contains($itemData['amount_spent'], ',')) {
                $orderData['items'][$itemIndex]['amount_spent'] = str_replace(
                    ',',
                    '',
                    $itemData['amount_spent']
                );
            }
        }

        return $orderData;
    }
}
