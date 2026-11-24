<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\ExportData\Customer;

use BubbleHouse\Integration\Model\ConfigProvider;
use Magento\Customer\Api\Data\CustomerInterface;

class CustomerExtractor
{
    public function __construct(
        private readonly ConfigProvider $configProvider
    ) {
    }

    public function extractForExport(CustomerInterface $customer, int $storeId): array
    {
        $data = self::extract($customer);
        $tag = $this->configProvider->getCustomerTag($storeId);
        if ($tag !== '') {
            $groupId = $customer->getGroupId();
            // Magento may hydrate integer attributes as strings.
            $operation = $this->configProvider->isCustomerGroupSelected(
                $groupId === null ? null : (int)$groupId,
                $storeId
            )
                ? 'add_bh_tags'
                : 'remove_tags';
            $data[$operation] = [$tag];
        }

        return $data;
    }

    public static function extract(CustomerInterface $customer): array
    {
        $extractedData = [];
        $extractedData['id'] = $customer->getId();
        $extractedData['email'] = $customer->getEmail();
        $extractedData['first_name'] = $customer->getFirstname();
        $extractedData['last_name'] = $customer->getLastname();
        $extras = (array) $customer->getCustomAttributes();
        $extractedData['extras'] = [];
        foreach ($extras as $key => $value) {
            $extractedData['extras'][$key] = $value->getValue();
        }

        return $extractedData;
    }
}
