<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Queue;

use BubbleHouse\Integration\Model\EportData\Customer\CustomerExtractor;
use BubbleHouse\Integration\Model\ResourceModel\QueueLog as QueueLogResource;
use BubbleHouse\Integration\Model\QueueLogFactory;
use BubbleHouse\Integration\Model\Services\Connector\BubbleHouseRequest;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class CustomerHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly BubbleHouseRequest $bubbleHouseRequest,
        private readonly QueueLogResource $resource,
        private readonly QueueLogFactory $queueLogFactory,
        private readonly SerializerInterface $serializer
    ) {
    }

    public function process(int $customerId): void
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $extractedData = CustomerExtractor::extract($customer);
            $data = $this->serializer->serialize($extractedData);
            $queueLog = $this->queueLogFactory->create();
            $queueLog->setData(
                [
                    'message_type' => 'customer',
                    'message_body' => $data,
                    'status' => 0
                ]
            );
            $this->resource->save($queueLog);

            $response = $this->bubbleHouseRequest->exportData(
                BubbleHouseRequest::CUSTOMER_EXPORT_TYPE,
                $extractedData,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $customer->getStoreId()
            );

            if (!$response) {
                throw new LocalizedException(__('Failed to export customer: ' . $customerId));
            }

            $queueLog->setStatus(1);
            $this->resource->save($queueLog);
        } catch (Exception $e) {
            $this->logger->error("Customer Export Failed: " . $e->getMessage());
            throw new LocalizedException(__('BubbleHouse export failed'));
        }
    }
}
