<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Services;

use BubbleHouse\Integration\Model\Services\QuoteDiscountService;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Customer\Model\Session as CustomerSession;
use \Magento\Framework\Pricing\PriceCurrencyInterface;

class QuoteDiscountHandler extends AbstractTotal
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly QuoteDiscountService $quoteDiscountService,
        private readonly CustomerSession $customerSession,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly \Psr\Log\LoggerInterface $logger,
    ) {
        $this->setCode('bh_quote_discount');
    }

    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ): self {
        parent::collect($quote, $shippingAssignment, $total);

        if (!$quote->getId()) {
            return $this;
        }

        $customerId = $quote->getCustomerId();
        if (!$customerId) {
            return $this;
        }

        $this->logger->debug("QuotaDiscountHandler: collect");
        if (!count($shippingAssignment->getItems())) {
            return $this;
        }

        try {
            $customer = $this->customerRepository->getById((int)$customerId);
            $bhQuoteDiscountsAttribute = $customer->getCustomAttribute('bh_quote_discounts');


            if (!$bhQuoteDiscountsAttribute) {
                return $this;
            }

            $discountsJson = $bhQuoteDiscountsAttribute->getValue();
            $discounts = $this->quoteDiscountService->unserializeDiscounts($discountsJson);

            $quoteId = (string)$quote->getId();
            if (!isset($discounts[$quoteId])) {
                return $this;
            }

            $discount = $discounts[$quoteId];
            $discountAmount = $this->priceCurrency->convert((float)$discount->getAmount());
            $discountLabel = $discount->getDescription();

            // Combine with existing discounts.
            $discountAmountTotal = $discountAmount;
            if($total->getDiscountAmount() < 0) {
                $discountAmountTotal = -$total->getDiscountAmount() + $discountAmount;
                $discountLabel       = $total->getDiscountDescription().', '.$discountLabel;
            }

            $this->logger->info(
                "[BH] {QuoteDiscountHandler/collect} " .
                "quote.id=" . $quote->getId() . " " .
                "quote.subtotal=" . $total->getSubtotal() . " " .
                "discount.amount=" . (string)$discountAmount . " " .
                "discount.amount_total=" . (string)$discountAmountTotal . " " .
                "discount.code=" . (string)$discount->getCode()
            );

            if ($discountAmount > 0) {
                $total->addTotalAmount($this->getCode(), -$discountAmount);
                $total->addBaseTotalAmount($this->getCode(), -$discountAmount);
                $total->setSubtotalWithDiscount($total->getSubtotal() - $discountAmountTotal);
                $total->setBaseSubtotalWithDiscount($total->getBaseSubtotal() - $discountAmountTotal);
                $total->setDiscountAmount(-$discountAmountTotal);
                $total->setBaseDiscountAmount(-$discountAmountTotal);
                $total->setDiscountDescription($discountLabel);
            }
        } catch (\Exception $e) {
            $this->logger->alert($e->getMessage());
            throw new LocalizedException($e->getMessage());
        }

        return $this;
    }
}
