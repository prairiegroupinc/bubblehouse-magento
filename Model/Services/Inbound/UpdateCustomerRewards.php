<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Services\Inbound;

use BubbleHouse\Integration\Api\Data\UpdateCustomerRewardsResultInterface;
use BubbleHouse\Integration\Api\UpdateCustomerRewardsInterface;
use BubbleHouse\Integration\Model\Services\CustomerExportSuppressor;
use BubbleHouse\Integration\Setup\Patch\Data\CreateCustomerRewardsAttributes;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\InputException;
use Psr\Log\LoggerInterface;

class UpdateCustomerRewards implements UpdateCustomerRewardsInterface
{
    private const STATUS_OK = 'ok';
    private const POINTS_BALANCE_INTEGER_DIGITS = 8;
    private const POINTS_BALANCE_SCALE = 4;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     * @param CustomerExportSuppressor $customerExportSuppressor
     * @param UpdateCustomerRewardsResultInterface $result
     */
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly LoggerInterface $logger,
        private readonly CustomerExportSuppressor $customerExportSuppressor,
        private readonly UpdateCustomerRewardsResultInterface $result
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(
        string $customerEmail,
        int $websiteId,
        string $bhTier,
        string $bhPointsBalance
    ): UpdateCustomerRewardsResultInterface
    {
        $this->validateTier($bhTier);
        $this->validatePointsBalance($bhPointsBalance);

        $customer = $this->customerRepository->get($customerEmail, $websiteId);
        $customer->setCustomAttribute(CreateCustomerRewardsAttributes::TIER_ATTRIBUTE_CODE, $bhTier);
        $customer->setCustomAttribute(
            CreateCustomerRewardsAttributes::POINTS_BALANCE_ATTRIBUTE_CODE,
            $bhPointsBalance
        );
        $this->customerExportSuppressor->suppress(
            fn () => $this->customerRepository->save($customer)
        );

        $this->logger->info(
            sprintf(
                'Bubblehouse: Customer rewards attributes updated for %s on website %d: tier=%s, points_balance=%s.',
                $customerEmail,
                $websiteId,
                $bhTier,
                $bhPointsBalance
            )
        );

        return $this->result->setStatus(self::STATUS_OK);
    }

    /**
     * Validate against the customer_entity_varchar VARCHAR(255) storage type.
     *
     * @param string $tier
     * @throws InputException
     */
    private function validateTier(string $tier): void
    {
        if (mb_strlen($tier, 'UTF-8') > CreateCustomerRewardsAttributes::TIER_MAX_LENGTH) {
            throw InputException::invalidFieldValue('bh_tier', $tier);
        }
    }

    /**
     * Validate against the customer_entity_decimal DECIMAL(12,4) storage type.
     *
     * @param string $pointsBalance
     * @throws InputException
     */
    private function validatePointsBalance(string $pointsBalance): void
    {
        if (!preg_match('/^[+-]?\d+(?:\.\d+)?$/', $pointsBalance)) {
            throw InputException::invalidFieldValue('bh_points_balance', $pointsBalance);
        }

        $unsignedValue = ltrim($pointsBalance, '+-');
        [$integerPart, $fractionalPart] = array_pad(explode('.', $unsignedValue, 2), 2, '');
        $integerPart = ltrim($integerPart, '0');
        $integerDigits = strlen($integerPart === '' ? '0' : $integerPart);
        $exceedsStorage = $integerDigits > self::POINTS_BALANCE_INTEGER_DIGITS
            || strlen($fractionalPart) > self::POINTS_BALANCE_SCALE;

        if ($exceedsStorage) {
            throw InputException::invalidFieldValue('bh_points_balance', $pointsBalance);
        }
    }
}
