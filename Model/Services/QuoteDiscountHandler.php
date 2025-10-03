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

            $appliedCartDiscount = 0;
            if($total->getDiscountDescription()) {
                $appliedCartDiscount = $total->getDiscountAmount();
                $discountAmount      = $total->getDiscountAmount() + $discountAmount;
                $discountLabel       = $total->getDiscountDescription().', '.$discountLabel;
            }

            if ($discountAmount > 0) {
                $total->addTotalAmount($this->getCode(), -$discountAmount);
                $total->addBaseTotalAmount($this->getCode(), -$discountAmount);
                $total->setSubtotalWithDiscount($total->getSubtotal() - $discountAmount);
                $total->setBaseSubtotalWithDiscount($total->getBaseSubtotal() - $discountAmount);
                $total->setDiscountAmount(-$discountAmount);
                $total->setBaseDiscountAmount(-$discountAmount);
                $total->setDiscountDescription($discountLabel);
                // TODO (@seletskiy): Ideally we want to clean no longer relevant data.
                /* unset($discounts[$quoteId]); */
                /* $updatedJson = $this->quoteDiscountService->serializeDiscounts($discounts); */
                /* $this->quoteDiscountService->set($customerId, $updatedJson); */
            }

        } catch (\Exception $e) {
            $this->logger->alert($e->getMessage());
            throw new LocalizedException($e->getMessage());
        }

        return $this;
    }

    public function fetch(Quote $quote, Total $total): ?array
    {
        $amount = $total->getTotalAmount($this->getCode());
        if ($amount != 0) {
            return [
                'code' => $this->getCode(),
                'title' => 'test',
                'value' => $amount
            ];
        }
        return null;
    }
}
