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

       /** @var QueueLog $queueLog */
        foreach ($collection as $queueLog) {
            try {
                $type = $queueLog->getData('message_type') === 'customer'
                    ? BubbleHouseRequest::CUSTOMER_EXPORT_TYPE : BubbleHouseRequest::ORDER_EXPORT_TYPE;
                $data = $this->serializer->unserialize($queueLog->getData('message_body'));
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
                $this->logger->critical(__('Failed Resend BubbleHouse Data: ' . $exception->getMessage()));
            }
       }
    }
}
