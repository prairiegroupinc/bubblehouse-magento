<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\EportData\Customer;

use Magento\Customer\Api\Data\CustomerInterface;

class CustomerExtractor
{
    public function extract(CustomerInterface $customer): array
    {
        $extractedData = [];
        $extractedData['id'] = $customer->getId();
        $extractedData['email'] = $customer->getEmail();
        $extractedData['first_name'] = $customer->getFirstname();
        $extractedData['last_name'] = $customer->getLastname();
        $extractedData['full_name'] = $customer->getFirstname() . ' ' . $customer->getLastname();

        return $extractedData;
    }
}
