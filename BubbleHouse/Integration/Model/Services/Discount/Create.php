<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Services\Discount;

use BubbleHouse\Integration\Api\CreateDiscount4Interface;
use BubbleHouse\Integration\Api\Data\DiscountDataInterface;
use BubbleHouse\Integration\Model\EportData\Order\MonetaryMapper;
use BubbleHouse\Integration\Model\EportData\Order\TimeMapper;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroupCollection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Framework\App\State;
use Magento\SalesRule\Api\Data\ConditionInterfaceFactory;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\Data\RuleInterfaceFactory;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Model\Rule\Condition\Combine;
use Magento\SalesRule\Model\Rule\Condition\CombineFactory;
use Magento\SalesRule\Model\Rule\Condition\Product;
use Magento\SalesRule\Model\Rule\Condition\Product\Found;
use Magento\SalesRule\Model\Rule\Condition\Product\FoundFactory;
use Magento\SalesRule\Model\Rule\Condition\ProductFactory;
use Magento\Store\Model\StoreManagerInterface;

class Create implements CreateDiscount4Interface
{
    public function __construct(
        private readonly State $appState,
        private readonly RuleRepositoryInterface $ruleRepository,
        private readonly RuleInterfaceFactory $cartPriceRuleFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly CustomerGroupCollectionFactory $customerGroupCollectionFactory,
        private readonly FoundFactory $foundFactory,
        private readonly CombineFactory $combineFactory,
        private readonly ProductFactory $productFactory,
        private readonly ConditionInterfaceFactory $conditionInterfaceFactory
    ) {
    }

    public function createDiscount(DiscountDataInterface $discountData): void
    {
        /** @var RuleInterface $cartPriceRule */
        $cartPriceRule = $this->cartPriceRuleFactory->create();
        $availableWebsites = $this->getAvailableWebsiteIds();
        $customerGroupIds = $this->getAvailableCustomerGroupIds((int)$discountData->getCustomerId());
        $cartPriceRule->setName($discountData->getTitle());
        $cartPriceRule->setIsActive(true);
        $cartPriceRule->setCouponType(RuleInterface::COUPON_TYPE_SPECIFIC_COUPON);
        $cartPriceRule->setCustomerGroupIds($customerGroupIds);
        $cartPriceRule->setWebsiteIds($availableWebsites);
        $cartPriceRule->setFromDate(date(TimeMapper::TIME_FORMAT, time()));
        $cartPriceRule->setToDate(TimeMapper::unmap($discountData->getEndTime()));
        // Set the usage limit per customer.
        $cartPriceRule->setUsesPerCoupon($discountData->getMaxUses());
        $cartPriceRule->setUsesPerCustomer(1);
        $cartPriceRule->setData('coupon_code', $discountData->getCode());
        $cartPriceRule->setUseAutoGeneration(false);
        $cartPriceRule->setSimpleFreeShipping('0')
            ->setApplyToShipping('0');
        $cartPriceRule->setDescription(
            implode(' ,', $discountData->getSubjectSlugs())
        );

        // amount or percentage
        if ($discountData->getAmount()) {
            $cartPriceRule->setSimpleAction(RuleInterface::DISCOUNT_ACTION_FIXED_AMOUNT);
            $cartPriceRule->setDiscountAmount(MonetaryMapper::unmap($discountData->getAmount()));
        } else if ($discountData->getPercentage()) {
            $cartPriceRule->setSimpleAction(RuleInterface::DISCOUNT_ACTION_BY_PERCENT);
            $cartPriceRule->setDiscountAmount((int)$discountData->getPercentage()/100);
        }

        // set conditions and actions
        if (!empty($discountData->getProductIds())) {
            if ($discountData->getIsPerProduct()) {
                $cartPriceRule->setCondition(
                    $this->conditionInterfaceFactory->create()->setConditionType(
                        Found::class
                    )->setAggregatorType(
                        'all'
                    )->setValue(
                        1
                    )->setConditions(
                        [
                            0 => $this->conditionInterfaceFactory->create()->setConditionType(
                                Product::class
                            )->setAttributeName(
                                'sku'
                            )->setOperator(
                                '()'
                            )->setValue($discountData->getProductIds())
                        ]
                    )
                );
            } else {
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
                                Product::class
                            )->setAttributeName(
                                'sku'
                            )->setOperator(
                                '()'
                            )->setValue($discountData->getProductIds())
                        ]
                    )
                );
            }
        }

        if (!empty($discountData->getCollectionIds())) {
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
                            Product::class
                        )->setAttributeName(
                            'category_ids'
                        )->setOperator(
                            '()'
                        )->setValue($discountData->getProductIds())
                    ]
                )
            );
        }

        // save
        $savedCartPriceRule = $this->appState->emulateAreaCode(
            FrontNameResolver::AREA_CODE,
            [$this->ruleRepository, 'save'],
            [$cartPriceRule]
        );

        $ruleId = (int) $savedCartPriceRule->getRuleId();
    }

    private function getAvailableCustomerGroupIds(int $customerId): array
    {
        /** @var CustomerGroupCollection $collection */
        $collection = $this->customerGroupCollectionFactory->create();
        $collection->addFieldToSelect('customer_group_id');
        $collection->join(
            $collection->getTable('customer_entity'),
            'customer_entity.group_id=customer_group.customer_group_id'
        )->addFieldToFilter('entity_id', ['eq' => $customerId]);

        return $collection->getAllIds();
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
}
