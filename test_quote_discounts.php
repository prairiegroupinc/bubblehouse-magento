<?php

require_once 'vendor/autoload.php';

use BubbleHouse\Integration\Model\Data\QuoteDiscountData;
use BubbleHouse\Integration\Model\Services\QuoteDiscountService;
use Magento\Framework\Serialize\Serializer\Json;

echo "TESTING QUOTE DISCOUNT IMPLEMENTATION\n";

$serializer = new Json();
$service = new QuoteDiscountService($serializer);

$discount1 = new QuoteDiscountData();
$discount1->setAmount('12.0000');

$discount2 = new QuoteDiscountData();
$discount2->setAmount('25.5000');

$discounts = [
    '123' => $discount1,
    '456' => $discount2
];

echo "ORIGINAL DISCOUNTS:\n";
foreach ($discounts as $quoteId => $discount) {
    echo "Quote ID: $quoteId, Amount: " . $discount->getAmount() . "\n";
}

$json = $service->serializeDiscounts($discounts);
echo "\nSERIALIZED JSON:\n$json\n";

$unserialized = $service->unserializeDiscounts($json);
echo "\nUNSERIALIZED DISCOUNTS:\n";
foreach ($unserialized as $quoteId => $discount) {
    echo "Quote ID: $quoteId, Amount: " . $discount->getAmount() . "\n";
}

echo "\nTEST COMPLETED\n";
