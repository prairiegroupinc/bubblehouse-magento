<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Services\Discount;

use BubbleHouse\Integration\Api\CreateDiscount4Interface;
use BubbleHouse\Integration\Api\Data\DiscountDataInterface;
use BubbleHouse\Integration\Api\Data\QuoteDiscountDataInterface;
use BubbleHouse\Integration\Model\Data\QuoteDiscountData;
use BubbleHouse\Integration\Model\ExportData\Order\MonetaryMapper;
use BubbleHouse\Integration\Model\ExportData\Order\TimeMapper;
use BubbleHouse\Integration\Model\Services\QuoteDiscountService;
use Exception;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroupCollection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Framework\App\State;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\SalesRule\Api\Data\ConditionInterfaceFactory;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\Data\CouponInterfaceFactory;
use Magento\SalesRule\Api\Data\RuleInterfaceFactory;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Model\ResourceModel\Coupon as CouponResource;
use Magento\SalesRule\Model\Rule\Condition\Address;
use Magento\SalesRule\Model\Rule\Condition\Combine;
use Magento\SalesRule\Model\Rule\Condition\Product;
use Magento\SalesRule\Model\Rule\Condition\Product\Found;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Create implements CreateDiscount4Interface
{
    public function __construct(
        private readonly State $appState,
        private readonly RuleRepositoryInterface $ruleRepository,
        private readonly RuleInterfaceFactory $cartPriceRuleFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly CustomerGroupCollectionFactory $customerGroupCollectionFactory,
        private readonly ConditionInterfaceFactory $conditionInterfaceFactory,
        private readonly CouponInterfaceFactory $couponInterfaceFactory,
        private readonly CouponResource $couponResource,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly QuoteDiscountService $quoteDiscountService,
        private readonly \Psr\Log\LoggerInterface $logger,
    ) {
    }

    /**
     * @param DiscountDataInterface $CreateDiscount4
     * @return void
     * @throws AlreadyExistsException|Exception
     */
    public function execute(DiscountDataInterface $CreateDiscount4): void
    {
        $extras = $CreateDiscount4->getExtras();
        if ($extras && isset($extras['checkout_quote_id'])) {
            $this->updateCustomerQuoteDiscounts($CreateDiscount4, $extras['checkout_quote_id']);
            return;
        }

        /** @var RuleInterface $cartPriceRule */
        $cartPriceRule = $this->cartPriceRuleFactory->create();
        $availableWebsites = $this->getAvailableWebsiteIds();
        $customerGroupIds = $this->getAvailableCustomerGroupIds((int)$CreateDiscount4->getCustomerId());
        $cartPriceRule->setName($CreateDiscount4->getTitle());
        $cartPriceRule->setIsActive(true);
        $cartPriceRule->setCouponType(RuleInterface::COUPON_TYPE_SPECIFIC_COUPON);
        $cartPriceRule->setCustomerGroupIds($customerGroupIds);
        $cartPriceRule->setWebsiteIds($availableWebsites);
        $cartPriceRule->setFromDate(date(TimeMapper::TIME_FORMAT, time()));

        if ($CreateDiscount4->getEndTime() && TimeMapper::unmap($CreateDiscount4->getEndTime())) {
            $cartPriceRule->setToDate(TimeMapper::unmap($CreateDiscount4->getEndTime()));
        }

        // Set the usage limit per customer.
        $cartPriceRule->setUsesPerCoupon($CreateDiscount4->getMaxUses());
        $cartPriceRule->setUsesPerCustomer(1);
        $cartPriceRule->setData('coupon_code', $CreateDiscount4->getCode());
        $cartPriceRule->setUseAutoGeneration(false);
        $cartPriceRule->setSimpleFreeShipping('0')
            ->setApplyToShipping('0');
        $cartPriceRule->setDescription(
            implode(' ,', $CreateDiscount4->getSubjectSlugs())
        );

        // amount or percentage
        if ((int)$CreateDiscount4->getAmount() > 0
            && (int)$CreateDiscount4->getPercentage() === 0
            && !$CreateDiscount4->getIsPerProduct()
        ) {
            $cartPriceRule->setSimpleAction(RuleInterface::DISCOUNT_ACTION_FIXED_AMOUNT_FOR_CART);
            $cartPriceRule->setDiscountAmount(MonetaryMapper::unmap($CreateDiscount4->getAmount()));
        } else if ((int)$CreateDiscount4->getPercentage() > 0) {
            $cartPriceRule->setSimpleAction(RuleInterface::DISCOUNT_ACTION_BY_PERCENT);
            $cartPriceRule->setDiscountAmount((int)$CreateDiscount4->getPercentage()/100);
        } else if ((int)$CreateDiscount4->getAmount() > 0
            && (int)$CreateDiscount4->getPercentage() === 0
            && $CreateDiscount4->getIsPerProduct()
        ) {
            $cartPriceRule->setSimpleAction(RuleInterface::DISCOUNT_ACTION_FIXED_AMOUNT);
            $cartPriceRule->setDiscountAmount(MonetaryMapper::unmap($CreateDiscount4->getAmount()));
        }

        if (!empty($CreateDiscount4->getMinOrderAmount())) {
            $cartPriceRule->setCondition(
                $this->conditionInterfaceFactory->create()->setConditionType(
                    Combine::class
                )->setAggregatorType(
                    'all'
                )->setValue(
                    1
                )->setConditions(
                    [
                        0 => $this->conditionInterfaceFactory->create()->setConditionType(
                            Address::class
                        )->setAttributeName(
                            'base_subtotal'
                        )->setOperator(
                            '>='
                        )->setValue($CreateDiscount4->getMinOrderAmount())
                    ]
                )
            );
        }

        // set conditions and actions
        if (!empty($CreateDiscount4->getProductIds())) {
            $skus = $CreateDiscount4->getProductIds(); // Example: ['SKU1', 'SKU2']

            // Create the product condition: SKU is one of the given SKUs
            $productCondition = $this->conditionInterfaceFactory->create()
                ->setConditionType(\Magento\SalesRule\Model\Rule\Condition\Product::class)
                ->setAttributeName('sku')
                ->setOperator('()') // 'is one of'
                ->setValue($skus);

            // Create the "Found" condition: if an item is FOUND in the cart matching product condition
            $foundCondition = $this->conditionInterfaceFactory->create()
                ->setConditionType(\Magento\SalesRule\Model\Rule\Condition\Product\Found::class)
                ->setAggregatorType('all') // all subconditions must match
                ->setValue(1) // TRUE
                ->setConditions([$productCondition]);

            // Wrap the Found condition inside a Combine root condition (this is important)
            $combineCondition = $this->conditionInterfaceFactory->create()
                ->setConditionType(\Magento\SalesRule\Model\Rule\Condition\Combine::class)
                ->setAggregatorType('all')
                ->setValue(1)
                ->setConditions([$foundCondition]);

            // Set it to the rule
            $cartPriceRule->setCondition($combineCondition);

            // Optional: also restrict which items get discounted (target the same SKUs)
            $cartPriceRule->setActionCondition(
                $this->conditionInterfaceFactory->create()
                    ->setConditionType(\Magento\SalesRule\Model\Rule\Condition\Combine::class)
                    ->setAggregatorType('all')
                    ->setValue(1)
                    ->setConditions([$productCondition])
            );
        }

        // save
        $savedCartPriceRule = $this->appState->emulateAreaCode(
            FrontNameResolver::AREA_CODE,
            [$this->ruleRepository, 'save'],
            [$cartPriceRule]
        );

        // create coupon
        $coupon = $this->couponInterfaceFactory->create();
        $coupon->setRuleId($savedCartPriceRule->getRuleId());
        $coupon->setCode($CreateDiscount4->getCode());
        $coupon->setIsPrimary(true);
        $coupon->setUsageLimit($CreateDiscount4->getMaxUses());
        $coupon->setData('bubble_house_coupon', 1);
        $coupon->setData('bubble_house_max_usages', $CreateDiscount4->getMaxUses());
        $coupon->setData('bubble_house_usages', 0);
        $this->couponResource->save($coupon);
    }

    private function getAvailableCustomerGroupIds(?int $customerId): array
    {
        /** @var CustomerGroupCollection $collection */
        $collection = $this->customerGroupCollectionFactory->create();
        $collection->addFieldToSelect('customer_group_id');

        if ($customerId) {
            $collection->join(
                $collection->getTable('customer_entity'),
                'customer_entity.group_id=main_table.customer_group_id'
            )->addFieldToFilter('entity_id', ['eq' => $customerId]);
        }

        $ids = $collection->getAllIds();

        // Check if result is empty or only contains [0]
        if (empty($ids) || (count($ids) === 1 && (int)$ids[0] === 0)) {
            /** @var CustomerGroupCollection $collectionWithoutFilters */
            $collectionWithoutFilters = $this->customerGroupCollectionFactory->create();
            $ids = $collectionWithoutFilters->getAllIds();
        }

        return $ids;
    }

    protected function getAvailableWebsiteIds(): array
    {
        $websiteIds = [];
        $websites = $this->storeManager->getWebsites();

        foreach ($websites as $website) {
            $websiteIds[] = $website->getId();
        }

        return $websiteIds;
    }

    private function updateCustomerQuoteDiscounts(DiscountDataInterface $CreateDiscount4, string $quoteId): void
    {
        $customerId = $CreateDiscount4->getCustomerId();
        if (!$customerId) {
            return;
        }

        try {
            $customer = $this->customerRepository->getById((int)$customerId);

            $currentDiscountsJson = '';
            $quoteDiscountAttribute = $customer->getCustomAttribute('bh_quote_discounts');
            if ($quoteDiscountAttribute) {
                $currentDiscountsJson = $quoteDiscountAttribute->getValue();
            }

            $currentDiscounts = $this->quoteDiscountService->unserializeDiscounts($currentDiscountsJson);

            $newDiscount = new QuoteDiscountData();
            $newDiscount->setAmount($CreateDiscount4->getAmount() ?? '0.0000');
            $newDiscount->setDescription($CreateDiscount4->getTitle());
            $newDiscount->setCode($CreateDiscount4->getCode());

            $currentDiscounts[$quoteId] = $newDiscount;

            $updatedJson = $this->quoteDiscountService->serializeDiscounts($currentDiscounts);
            $this->quoteDiscountService->set($customerId, $updatedJson);

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new \Exception('Failed to update customer quote discounts: ' . $e->getMessage());
        }
    }
}
