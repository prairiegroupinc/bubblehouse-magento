<?php

namespace BubbleHouse\Integration\Model\EportData\Order;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;

class ProductsMapper
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }
    public function mapOrderItems(Order $order): array
    {
        $mappedItems = [];
        $orderItems = $order->getAllVisibleItems();

        foreach ($orderItems as $orderedItem) {
            $mappedItem = [];
            $mappedItem['quantity'] = (int)$orderedItem->getQtyOrdered();
            $mappedItem['amount_full'] = MonetaryMapper::map($orderedItem->getPriceInclTax());
            $mappedItem['amount_spent'] = MonetaryMapper::map(
                (float)$orderedItem->getPriceInclTax() - (float)$orderedItem->getDiscountAmount()
            );
            $mappedItem['product'] = $this->mapProduct($orderedItem);
            $mappedItems[] = $mappedItem;
        }

        return $mappedItems;
    }

    private function mapProduct(OrderItemInterface $orderItem): array
    {
        $mappedProduct = [];
        $mappedProduct['id'] = $orderItem->getSku();
        $mappedProduct['title'] = $orderItem->getName();
        $mappedProduct['collections'] = $this->mapCategories($orderItem);

        return $mappedProduct;
    }

    private function mapCategories(OrderItemInterface $orderItem): array
    {
        /** @var Product $product */
        $product = $this->productRepository->get(
            $orderItem->getSku(),
            false,
            (int)$orderItem->getStoreId()
        );
        /** @var CategoryInterface[] $categories */
        $categories = $product->getCategoryCollection()->getItems();
        $mappedCategories = [];

        foreach ($categories as $category) {
            $mappedCategory = [];
            $mappedCategory['id'] = $category->getEntityId();
            $mappedCategory['title'] = $category->getName();
            $mappedCategories[] = $mappedCategory;
        }

        return $mappedCategories;
    }
}
