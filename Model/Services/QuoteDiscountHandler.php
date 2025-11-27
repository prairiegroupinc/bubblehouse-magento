<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Services;

use BubbleHouse\Integration\Model\Services\QuoteDiscountService;
use BubbleHouse\Integration\Model\Data\QuoteDiscountData;
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

        if (!count($shippingAssignment->getItems())) {
            return $this;
        }

        try {
            $customer = $this->customerRepository->getById((int)$customerId);
            $bhQuoteDiscountsAttribute = $customer->getCustomAttribute('bh_quote_discounts');

            if (!$bhQuoteDiscountsAttribute) {
                $this->logger->warning(
                    "[BH] {QuoteDiscountHandler/collect} no 'bh_quote_discounts' attr on customer -> skipping " .
                    "customed.id=" . $customerId
                );
                return $this;
            }

            $discountsJson = $bhQuoteDiscountsAttribute->getValue();
            $discounts = $this->quoteDiscountService->unserializeDiscounts($discountsJson);

            $quoteId = (string)$quote->getId();
            if (!isset($discounts[$quoteId])) {
                return $this;
            }

            $discount = $discounts[$quoteId];

            $currentHash = QuoteDiscountData::calculateQuoteHash($quote);
            $storedHash = $discount->getQuoteHash();
            // In absence of better way of catching cart updates we just check hashed cart contents vs. what discount was issued for.
            if ($storedHash !== $currentHash) {
                $this->logger->warning(
                    "[BH] {QuoteDiscountHandler/collect} hash mismatch (cart contents changed?) -> dropping discount " .
                    "quote.id=" . $quote->getId() . " " .
                    "stored_hash=" . $storedHash . " " .
                    "current_hash=" . $currentHash
                );
                unset($discounts[$quoteId]);
                $updatedJson = $this->quoteDiscountService->serializeDiscounts($discounts);
                $this->quoteDiscountService->set($customerId, $updatedJson);
                return $this;
            }

            $discountAmount = $this->priceCurrency->convert((float)$discount->getAmount());
            $discountLabel = $discount->getDescription();

            // Combine with existing discounts.
            $discountAmountTotal = $discountAmount;
            if ($total->getDiscountAmount() < 0) {
                $discountAmountTotal = -$total->getDiscountAmount() + $discountAmount;
                $discountLabel       = $total->getDiscountDescription().', '.$discountLabel;
            }

            if ($discountAmount <= 0) {
                $this->logger->warning(
                    "[BH] {QuoteDiscountHandler/collect} negative or zero discount detected -> skipping " .
                    "quote.id=" . $quote->getId() . " " .
                    "quote.subtotal=" . $total->getSubtotal() . " " .
                    "discount.amount=" . (string)$discountAmount . " " .
                    "discount.amount_total=" . (string)$discountAmountTotal . " " .
                    "discount.code=" . (string)$discount->getCode()
                );

                return $this;
            }

            $subtotal = $total->getSubtotal() - $discountAmountTotal;
            $baseSubtotal = $total->getBaseSubtotal() - $discountAmountTotal;

            if ($subtotal < 0 || $baseSubtotal < 0) {
                $this->logger->warning(
                    "[BH] {QuoteDiscountHandler/collect} negative subtotal detected -> rounding to zero"
                );

                $subtotal     = max($subtotal, 0);
                $baseSubtotal = max($baseSubtotal, 0);
            }

            $this->logger->info(
                "[BH] {QuoteDiscountHandler/collect} applying discount " .
                "quote.id=" . $quote->getId() . " " .
                "quote.subtotal=" . $total->getSubtotal() . " " .
                "discount.amount=" . (string)$discountAmount . " " .
                "discount.amount_total=" . (string)$discountAmountTotal . " " .
                "discount.code=" . (string)$discount->getCode() . " " .
                "discount.label='" .$discountLabel . "' " .
                "quote_hash=" . $currentHash
            );

            $total->addTotalAmount($this->getCode(), -$discountAmount);
            $total->addBaseTotalAmount($this->getCode(), -$discountAmount);
            $total->setSubtotalWithDiscount($subtotal);
            $total->setBaseSubtotalWithDiscount($baseSubtotal);
            $total->setDiscountAmount(-$discountAmountTotal);
            $total->setBaseDiscountAmount(-$discountAmountTotal);
            $total->setDiscountDescription($discountLabel);
        } catch (\Exception $e) {
            $this->logger->alert($e->getMessage());
            throw new LocalizedException($e->getMessage());
        }

        return $this;
    }
}
