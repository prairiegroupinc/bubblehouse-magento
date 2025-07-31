<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\ExportData\Order;

use BubbleHouse\Integration\Model\ConfigProvider;
use BubbleHouse\Integration\Model\ExportData\Customer\CustomerExtractor;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class OrderExtractor
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly ConfigProvider $configProvider,
        private readonly ProductsMapper $productsMapper,
        protected LoggerInterface $logger
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
        $amountFull = (float) $order->getSubtotal()
            + (float) $order->getShippingAmount() + (float) $order->getTaxAmount();
        $amountSpent = (float) ($order->getTotalInvoiced() ?? $order->getGrandTotal());

        if ($this->configProvider->isCustomerBalanceEnabled($store->getId())) {
            $customerBalanceAmount = (float) $order->getData('customer_balance_amount');
            $amountSpent += $customerBalanceAmount;
        }

        $extractedData['amount_full'] = MonetaryMapper::map($amountFull);
        $extractedData['amount_spent'] = MonetaryMapper::map($amountSpent);
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
