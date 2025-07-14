<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\SalesRule\Model\CouponFactory;
use Magento\SalesRule\Model\ResourceModel\Coupon as CouponResource;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Psr\Log\LoggerInterface;

class UpdateCouponBubbleHouseFields implements ObserverInterface
{
    public function __construct(
        private readonly CouponFactory $couponFactory,
        private readonly CouponResource $couponResource,
        private readonly RuleRepositoryInterface $ruleRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $couponCode = $order->getCouponCode();

        if (!$couponCode) {
            return;
        }

        $coupon = $this->couponFactory->create();
        $this->couponResource->load($coupon, $couponCode, 'code');

        if (!$coupon->getId()) {
            return;
        }

        if ($coupon->getData('bubble_house_coupon')) {
            $coupon->setData('bubble_house_usages', (int)$coupon->getData('bubble_house_usages') +1);
            $this->couponResource->save($coupon);

            $ruleId = $coupon->getRuleId();
            $shouldDisable = (int) $coupon->getData('bubble_house_usages')
                >= (int) $coupon->getData('bubble_house_max_usages');

            if ($ruleId && $shouldDisable) {
                try {
                    $rule = $this->ruleRepository->getById($ruleId);
                    if ($rule->getIsActive()) {
                        $rule->setIsActive(false);
                        $this->ruleRepository->save($rule);
                    }
                } catch (\Exception $e) {
                    $this->logger->critical('Could not disable BubbleHouse coupon');
                }
            }
        }
    }
}
