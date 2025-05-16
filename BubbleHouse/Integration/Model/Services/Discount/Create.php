<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Services\Discount;

use BubbleHouse\Integration\Api\CreateDiscount4Interface;
use BubbleHouse\Integration\Api\Data\DiscountDataInterface;
use BubbleHouse\Integration\Model\EportData\Order\MonetaryMapper;
use BubbleHouse\Integration\Model\EportData\Order\TimeMapper;
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
        private readonly CouponResource $couponResource
    ) {
    }

    /**
     * @param DiscountDataInterface $CreateDiscount4
     * @return void
     * @throws AlreadyExistsException|Exception
     */
    public function execute(DiscountDataInterface $CreateDiscount4): void
    {
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
        if ($CreateDiscount4->getAmount()) {
            $cartPriceRule->setSimpleAction(RuleInterface::DISCOUNT_ACTION_FIXED_AMOUNT);
            $cartPriceRule->setDiscountAmount(MonetaryMapper::unmap($CreateDiscount4->getAmount()));
        } else if ($CreateDiscount4->getPercentage()) {
            $cartPriceRule->setSimpleAction(RuleInterface::DISCOUNT_ACTION_BY_PERCENT);
            $cartPriceRule->setDiscountAmount((int)$CreateDiscount4->getPercentage()/100);
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
            if (!$CreateDiscount4->getIsPerProduct()) {
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
                            )->setValue($CreateDiscount4->getProductIds())
                        ]
                    )
                );
            } else {
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
                            )->setValue($CreateDiscount4->getProductIds())
                        ]
                    )
                );
                $cartPriceRule->setActionCondition(
                    $this->conditionInterfaceFactory->create()->setConditionType(
                        Combine::class
                    )->setValue(
                        1
                    )->setAggregatorType(
                        'all'
                    )->setConditions(
                        [
                            0 => $this->conditionInterfaceFactory->create()->setConditionType(
                                Product::class
                            )->setAttributeName(
                                'sku'
                            )->setOperator(
                                '()'
                            )->setValue($CreateDiscount4->getProductIds())
                        ]
                    )
                );
            }
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

        if (empty($ids)) {
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
}
