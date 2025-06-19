<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Queue;

use BubbleHouse\Integration\Model\EportData\Customer\CustomerExtractor;
use BubbleHouse\Integration\Model\ResourceModel\QueueLog as QueueLogResource;
use BubbleHouse\Integration\Model\QueueLogFactory;
use BubbleHouse\Integration\Model\Services\Connector\BubbleHouseRequest;
use Exception;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class CustomerHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CustomerResource $customerRepository,
        private readonly CustomerFactory $customerFactory,
        private readonly BubbleHouseRequest $bubbleHouseRequest,
        private readonly QueueLogResource $resource,
        private readonly QueueLogFactory $queueLogFactory,
        private readonly SerializerInterface $serializer
    ) {
    }

    public function process(int $customerId): void
    {
        try {
            /** @var Customer $customerModel */
            $customerModel = $this->customerFactory->create();
            $this->customerRepository->load($customerModel, $customerId);
            $extractedData = CustomerExtractor::extract($customerModel->getDataModel());
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
                $customerModel->getStoreId()
            );

            if (!$response) {
                throw new LocalizedException(__('Failed to export customer: ' . $customerId));
            }

            $queueLog->setStatus(1);
            $this->resource->save($queueLog);
        } catch (Exception $e) {
            $this->logger->error("Customer Export Failed: " . $e->getMessage());
            throw new LocalizedException(__('Bubblehouse export failed'));
        }
    }
}
