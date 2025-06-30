<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Cron;

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
    public function __construct(
        private readonly BubbleHouseRequest $request,
        private readonly CollectionFactory $collectionFactory,
        private readonly SerializerInterface $serializer,
        private readonly QueueLogResource $resource,
        private readonly LoggerInterface $logger,
        private readonly State $appState
    ) {
    }

    public function execute(): void
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', 0);
        $collection->setPageSize(100);
        $totalPages = $collection->getLastPageNumber();

        for ($currentPage = 1; $currentPage <= $totalPages; $currentPage++) {
            $collection->clear(); // important: resets previous results
            $collection->setCurPage($currentPage);
            $collection->load();

            /** @var QueueLog $queueLog */
            foreach ($collection as $queueLog) {
                try {
                    $type = $queueLog->getData('message_type') === 'customer'
                        ? BubbleHouseRequest::CUSTOMER_EXPORT_TYPE : BubbleHouseRequest::ORDER_EXPORT_TYPE;
                    $data = $this->serializer->unserialize($queueLog->getData('message_body'));

                    if ($type === BubbleHouseRequest::ORDER_EXPORT_TYPE) {
                        $data = $this->convertMonetary($data);
                    }

                    $response = $this->request->exportData(
                        $type,
                        $data
                    );

                    if ($response) {
                        $queueLog->setData('status', 1);
                        $this->appState->emulateAreaCode(
                            FrontNameResolver::AREA_CODE,
                            [$this->resource, 'save'],
                            [$queueLog]
                        );
                    }
                } catch (\Exception $exception) {
                    $this->logger->critical(__('Failed Resend Bubblehouse Data: ' . $exception->getMessage()));
                }
            }
        }
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
