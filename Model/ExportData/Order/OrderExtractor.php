<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\ExportData\Order;

use BubbleHouse\Integration\Model\ConfigProvider;
use BubbleHouse\Integration\Model\ExportData\Customer\CustomerExtractor;
use BubbleHouse\Integration\Model\Services\QuoteDiscountService;
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
        private readonly QuoteDiscountService $quoteDiscountService,
        protected LoggerInterface $logger
    ) {
    }

    public function extract(OrderInterface $order, bool $deleted = false): array
    {
        $store = $this->storeManager->getStore($order->getStoreId());
        $customer = $this->customerRepository->get($order->getCustomerEmail(), $store->getWebsiteId());

        $extractedData = [];
        $extractedData['id'] = $order->getEntityId();
        $extractedData['order_time'] = TimeMapper::map($order->getCreatedAt());
        $extractedData['update_time'] = TimeMapper::map($order->getUpdatedAt());
        $extractedData['status'] = OrderStatusMapper::mapStatus($order->getStatus());
        $extractedData['customer'] = CustomerExtractor::extract($customer);
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

        $bhQuoteDiscountsAttribute = $customer->getCustomAttribute('bh_quote_discounts');

        if ($bhQuoteDiscountsAttribute) {
            $discountsJson = $bhQuoteDiscountsAttribute->getValue();
            $discounts = $this->quoteDiscountService->unserializeDiscounts($discountsJson);
            if (isset($discounts[$order->getQuoteId()])) {
                $discount = $discounts[$order->getQuoteId()];
                $extractedData['discount_codes'] = [$discount->getCode()];
            }
        }

        if ($deleted) {
            $extractedData['deleted'] = true;
        }

        return $extractedData;
    }

    private function getOrderLines(OrderInterface $order): array
    {
        return $this->productsMapper->mapOrderItems($order);
    }
}
