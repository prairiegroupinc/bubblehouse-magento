<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Queue;

use BubbleHouse\Integration\Model\ConfigProvider;
use BubbleHouse\Integration\Model\ExportData\Order\OrderExtractor;
use BubbleHouse\Integration\Model\QueueLogFactory;
use BubbleHouse\Integration\Model\ResourceModel\QueueLog as QueueLogResource;
use BubbleHouse\Integration\Model\Services\Connector\BubbleHouseRequest;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class OrderExportHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly BubbleHouseRequest $bubbleHouseRequest,
        private readonly OrderExtractor $orderExtractor,
        private readonly QueueLogResource $resource,
        private readonly QueueLogFactory $queueLogFactory,
        private readonly SerializerInterface $serializer,
        private readonly StoreManagerInterface $storeManager,
        private readonly ConfigProvider $configProvider
    ) {
    }

    public function process(int $orderId): void
    {
        try {
            $order = $this->orderRepository->get($orderId);
            $storeId = (int)$order->getStoreId();

            if ($storeId <= 0) {
                $this->logger->warning('Bubblehouse: Skipped BubbleHouse order export without store scope: ' . $orderId);
                return;
            }

            if (!$this->configProvider->canExportOrders($storeId)) {
                $this->logger->warning(
                    'Bubblehouse: Skipped BubbleHouse order export for disabled or incomplete store scope: ' . (string)$storeId
                );
                return;
            }

            $extractedData = $this->orderExtractor->extract($order, (bool)$order->getData('is_deleted'));
            $data = $this->serializer->serialize($extractedData);
            $queueLog = $this->queueLogFactory->create();
            $queueLog->setData(
                [
                    'message_type' => 'order',
                    'message_body' => $data,
                    'store_id' => $storeId,
                    'website_id' => (int)$this->storeManager->getStore($storeId)->getWebsiteId(),
                    'status' => 0
                ]
            );
            $this->resource->save($queueLog);

            $response = $this->bubbleHouseRequest->exportData(
                BubbleHouseRequest::ORDER_EXPORT_TYPE,
                $extractedData,
                $storeId
            );

            if (!$response) {
                throw new LocalizedException(__('Failed to export order: ' . $orderId));
            }

            $queueLog->setStatus(1);
            $this->resource->save($queueLog);

        } catch (Exception $e) {
            $this->logger->error("Bubblehouse: Order Export Failed: " . $e->getMessage());
            throw new LocalizedException(__('Bubblehouse export failed'));
        }
    }
}
