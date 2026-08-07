<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Test\Unit\Model\Services\Inbound;

use BubbleHouse\Integration\Api\Data\UpdateCustomerRewardsResultInterface;
use BubbleHouse\Integration\Model\Services\CustomerExportSuppressor;
use BubbleHouse\Integration\Model\Services\Inbound\UpdateCustomerRewards;
use BubbleHouse\Integration\Setup\Patch\Data\CreateCustomerRewardsAttributes;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\InputException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UpdateCustomerRewardsTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $logger;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private CustomerRepositoryInterface|MockObject $customerRepository;

    /**
     * @var UpdateCustomerRewards
     */
    private UpdateCustomerRewards $updateCustomerRewards;

    /**
     * @var CustomerExportSuppressor
     */
    private CustomerExportSuppressor $customerExportSuppressor;

    /**
     * @var UpdateCustomerRewardsResultInterface|MockObject
     */
    private UpdateCustomerRewardsResultInterface|MockObject $result;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->customerExportSuppressor = new CustomerExportSuppressor();
        $this->result = $this->createMock(UpdateCustomerRewardsResultInterface::class);
        $this->updateCustomerRewards = new UpdateCustomerRewards(
            $this->customerRepository,
            $this->logger,
            $this->customerExportSuppressor,
            $this->result
        );
    }

    public function testExecuteSavesRewardsValuesAsCustomerAttributes(): void
    {
        $customerEmail = 'customer@example.com';
        $websiteId = 2;
        $customer = $this->createMock(CustomerInterface::class);

        $this->customerRepository->expects($this->once())
            ->method('get')
            ->with($customerEmail, $websiteId)
            ->willReturn($customer);

        $customer->expects($this->exactly(2))
            ->method('setCustomAttribute')
            ->willReturnCallback(
                static function (string $attributeCode, mixed $value) use ($customer): CustomerInterface {
                    static $expectedAttributes = [
                        [CreateCustomerRewardsAttributes::TIER_ATTRIBUTE_CODE, 'Gold'],
                        [CreateCustomerRewardsAttributes::POINTS_BALANCE_ATTRIBUTE_CODE, '125.5'],
                    ];

                    self::assertSame(array_shift($expectedAttributes), [$attributeCode, $value]);

                    return $customer;
                }
            );

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($customer)
            ->willReturnCallback(function () use ($customer): CustomerInterface {
                self::assertTrue($this->customerExportSuppressor->isSuppressed());

                return $customer;
            });

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Bubblehouse: Customer rewards attributes updated for customer@example.com on website 2: '
                . 'tier=Gold, points_balance=125.5.'
            );

        $this->result->expects($this->once())
            ->method('setStatus')
            ->with('ok')
            ->willReturnSelf();

        self::assertSame(
            $this->result,
            $this->updateCustomerRewards->execute($customerEmail, $websiteId, 'Gold', '125.5')
        );
        self::assertFalse($this->customerExportSuppressor->isSuppressed());
    }

    public function testExecuteRejectsTierThatExceedsStorageLimit(): void
    {
        $tier = str_repeat('a', 256);

        $this->customerRepository->expects(self::never())->method('get');
        $this->customerRepository->expects(self::never())->method('save');
        $this->logger->expects(self::never())->method('info');

        $this->expectException(InputException::class);
        $this->expectExceptionMessage(
            sprintf('Invalid value of "%s" provided for the bh_tier field.', $tier)
        );

        $this->updateCustomerRewards->execute('customer@example.com', 2, $tier, '125.5');
    }

    /**
     * @dataProvider invalidPointsBalanceProvider
     */
    public function testExecuteRejectsInvalidPointsBalance(string $pointsBalance): void
    {
        $this->customerRepository->expects(self::never())->method('get');
        $this->customerRepository->expects(self::never())->method('save');
        $this->logger->expects(self::never())->method('info');

        $this->expectException(InputException::class);
        $this->expectExceptionMessage(
            sprintf('Invalid value of "%s" provided for the bh_points_balance field.', $pointsBalance)
        );

        $this->updateCustomerRewards->execute('customer@example.com', 2, 'Gold', $pointsBalance);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPointsBalanceProvider(): array
    {
        return [
            'non-numeric' => ['invalid'],
            'outside decimal range' => ['100000000'],
            'too many decimal places' => ['0.00001'],
        ];
    }
}
