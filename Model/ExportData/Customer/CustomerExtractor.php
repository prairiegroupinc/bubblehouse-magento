<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\ExportData\Customer;

use Magento\Customer\Api\Data\CustomerInterface;

class CustomerExtractor
{
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
