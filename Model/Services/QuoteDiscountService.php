<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Services;

use BubbleHouse\Integration\Api\Data\QuoteDiscountDataInterface;
use BubbleHouse\Integration\Model\Data\QuoteDiscountData;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Eav\Model\Config;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use BubbleHouse\Integration\Setup\Patch\Data\CreateQuoteDiscountsAttribute;

class QuoteDiscountService
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly Config $eavConfig,
        private readonly CustomerCollectionFactory $customerCollectionFactory,
        private readonly \Psr\Log\LoggerInterface $logger
    ) {
    }

    public function serializeDiscounts(array $discounts): string
    {
        $serializedData = [];
        foreach ($discounts as $quoteId => $discount) {
            if ($discount instanceof QuoteDiscountDataInterface) {
                $serializedData[$quoteId] = [
                    QuoteDiscountDataInterface::AMOUNT => $discount->getAmount(),
                    QuoteDiscountDataInterface::DESCRIPTION => $discount->getDescription(),
                    QuoteDiscountDataInterface::CODE => $discount->getCode()
                ];
            }
        }
        return $this->serializer->serialize($serializedData);
    }

    public function unserializeDiscounts(string $json): array
    {
        if (empty($json)) {
            return [];
        }

        $data = $this->serializer->unserialize($json);
        if (!is_array($data)) {
            return [];
        }

        $discounts = [];
        foreach ($data as $quoteId => $discountData) {
            if (is_array($discountData) && isset($discountData[QuoteDiscountDataInterface::AMOUNT])) {
                $discount = new QuoteDiscountData();
                $discount->setAmount((string) $discountData[QuoteDiscountDataInterface::AMOUNT]);
                $discount->setDescription((string) $discountData[QuoteDiscountDataInterface::DESCRIPTION]);
                $discount->setCode((string) ($discountData[QuoteDiscountDataInterface::CODE] ?? ""));
                $discounts[$quoteId] = $discount;
            }
        }

        return $discounts;
    }

    public function set($customerId, $data): void {
        try {
            $collection = $this->customerCollectionFactory->create();
            $connection = $collection->getConnection();
            $attribute = $this->eavConfig->getAttribute(
                'customer',
                CreateQuoteDiscountsAttribute::ATTRIBUTE_CODE
            );
            $attributeId = (int)$attribute->getId();
            $tableName = $connection->getTableName('customer_entity_text');
            $sql = "UPDATE $tableName SET value = ? WHERE attribute_id = ? AND entity_id = ?";
            $connection->query($sql, [$data, $attributeId, $customerId]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
