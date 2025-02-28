<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\EportData\Order;

use BubbleHouse\Integration\Model\EportData\Customer\CustomerExtractor;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;

class OrderExtractor
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductsMapper $productsMapper
    ) {
    }

    public function extract(OrderInterface $order, bool $deleted = false): array
    {
        $store = $this->storeManager->getStore($order->getStoreId());
        $extractedData = [];
        $extractedData['id'] = $order->getEntityId();
        $extractedData['order_time'] = TimeMapper::map($order->getCreatedAt());
        $extractedData['update_time'] = TimeMapper::map($order->getUpdatedAt());
        $extractedData['status'] = OrderStatusMapper::mapStatus($order->getStatus());
        $extractedData['customer'] = $this->getCustomerData(
            $order->getCustomerEmail(),
            (int)$order->getStoreId()
        );
        $extractedData['store_location'] = 'Website ID: ' . $store->getWebsiteId()
            . ' -> Store ID: ' . $store->getName();
        $extractedData['amount_full'] = MonetaryMapper::map((float)$order->getBaseGrandTotal());
        $extractedData['amount_spent'] = MonetaryMapper::map(
            (float)$order->getBaseGrandTotal() - abs((float)$order->getDiscountAmount())
        );
        $extractedData['items'] = $this->getOrderLines($order);

        if ($deleted) {
            $extractedData['deleted'] = true;
        }

        return $extractedData;
    }

    private function getCustomerData(string $customerEmail, int $storeId): array
    {
        $store = $this->storeManager->getStore($storeId);
        $customer = $this->customerRepository->get($customerEmail, $store->getWebsiteId());

        return CustomerExtractor::extract($customer);
    }

    private function getOrderLines(OrderInterface $order): array
    {
        return $this->productsMapper->mapOrderItems($order);
    }
}
