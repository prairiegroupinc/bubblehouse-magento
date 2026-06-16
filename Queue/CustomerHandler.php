<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Queue;

use BubbleHouse\Integration\Model\ConfigProvider;
use BubbleHouse\Integration\Model\ExportData\Customer\CustomerExtractor;
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
        private readonly SerializerInterface $serializer,
        private readonly ConfigProvider $configProvider
    ) {
    }

    public function process(int $customerId): void
    {
        try {
            /** @var Customer $customerModel */
            $customerModel = $this->customerFactory->create();
            $this->customerRepository->load($customerModel, $customerId);
            $storeId = (int)$customerModel->getStoreId();

            if (!$customerModel->getId()) {
                $this->logger->warning('Bubblehouse: Skipped BubbleHouse customer export for missing customer: ' . $customerId);
                return;
            }

            if ($storeId <= 0) {
                $this->logger->warning('Bubblehouse: Skipped BubbleHouse customer export without store scope: ' . $customerId);
                return;
            }

            if (!$this->configProvider->canExportCustomers($storeId)) {
                $this->logger->warning(
                    'Bubblehouse: Skipped BubbleHouse customer export for disabled or incomplete store scope: ' . (string)$storeId
                );
                return;
            }

            $extractedData = CustomerExtractor::extract($customerModel->getDataModel());
            $data = $this->serializer->serialize($extractedData);
            $queueLog = $this->queueLogFactory->create();
            $queueLog->setData(
                [
                    'message_type' => 'customer',
                    'message_body' => $data,
                    'store_id' => $storeId,
                    'website_id' => $customerModel->getWebsiteId() ? (int)$customerModel->getWebsiteId() : null,
                    'status' => 0
                ]
            );
            $this->resource->save($queueLog);

            $response = $this->bubbleHouseRequest->exportData(
                BubbleHouseRequest::CUSTOMER_EXPORT_TYPE,
                $extractedData,
                $storeId
            );

            if (!$response) {
                throw new LocalizedException(__('Failed to export customer: ' . $customerId));
            }

            $queueLog->setStatus(1);
            $this->resource->save($queueLog);
        } catch (Exception $e) {
            $this->logger->error("Bubblehouse: Customer Export Failed: " . $e->getMessage());
            throw new LocalizedException(__('Bubblehouse export failed'));
        }
    }
}
