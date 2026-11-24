<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Test\Unit\Model\ExportData\Customer;

use BubbleHouse\Integration\Model\ConfigProvider;
use BubbleHouse\Integration\Model\ExportData\Customer\CustomerExtractor;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CustomerTagConfigTest extends TestCase
{
    public static function payloadCases(): array
    {
        return [
            'selected integer ID' => ['4', 'member', 4, [4], 'add_bh_tags'],
            'selected string ID' => ['4', 'member', '4', [4], 'add_bh_tags'],
            'different group' => ['4', 'member', '1', [4], 'remove_tags'],
            'group zero' => ['0, 4,4,,invalid', ' member ', '0', [0, 4], 'add_bh_tags'],
            'empty selection' => ['', 'member', '4', [], 'remove_tags'],
            'missing selection' => [null, 'member', '4', [], 'remove_tags'],
            'array selection' => [[0, '4'], 'member', '4', [0, 4], 'add_bh_tags'],
            'blank tag' => ['4', '   ', '4', [4], null],
            'missing tag' => ['4', null, '4', [4], null],
            'missing customer group' => ['0,4', 'member', null, [0, 4], 'remove_tags'],
        ];
    }

    #[DataProvider('payloadCases')]
    public function testScopedPayload($groups, $tag, $group, array $expectedIds, ?string $operation): void
    {
        $scope = $this->createMock(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturnCallback(function ($path, $type, $code) use ($groups, $tag) {
            self::assertSame('store', $type);
            self::assertSame(2, $code);
            return match ($path) {
                ConfigProvider::CUSTOMER_GROUPS_PATH => $groups,
                ConfigProvider::CUSTOMER_TAG_PATH => $tag,
                default => null,
            };
        });
        $config = new ConfigProvider($scope);
        self::assertSame($expectedIds, $config->getCustomerGroupIds(2));
        $customer = $this->createStub(CustomerInterface::class);
        $customer->method('getId')->willReturn('123');
        $customer->method('getEmail')->willReturn('customer@example.test');
        $customer->method('getFirstname')->willReturn('Jane');
        $customer->method('getLastname')->willReturn('Doe');
        $customer->method('getGroupId')->willReturn($group);
        $attribute = $this->createStub(AttributeInterface::class);
        $attribute->method('getValue')->willReturn('preserved');
        $customer->method('getCustomAttributes')->willReturn(['custom' => $attribute]);
        $base = [
            'id' => '123', 'email' => 'customer@example.test',
            'first_name' => 'Jane', 'last_name' => 'Doe', 'extras' => ['custom' => 'preserved'],
        ];
        $expected = $operation === null ? $base : $base + [$operation => [trim((string)$tag)]];
        self::assertSame($expected, (new CustomerExtractor($config))->extractForExport($customer, 2));
        self::assertSame($base, CustomerExtractor::extract($customer), 'Order-embedded customer data must not gain tag operations.');
    }

    public function testDifferentStoreSettingsAndExplicitWebsiteScope(): void
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturnCallback(function ($path, $type, $code) {
            return match ([$type, $code, $path]) {
                ['store', 1, ConfigProvider::CUSTOMER_GROUPS_PATH] => '4',
                ['store', 2, ConfigProvider::CUSTOMER_GROUPS_PATH] => '1',
                ['store', 1, ConfigProvider::CUSTOMER_TAG_PATH] => 'store-one',
                ['store', 2, ConfigProvider::CUSTOMER_TAG_PATH] => 'store-two',
                ['website', 3, ConfigProvider::CUSTOMER_GROUPS_PATH] => '4,5',
                ['website', 3, ConfigProvider::CUSTOMER_TAG_PATH] => 'website-three',
                default => null,
            };
        });
        $config = new ConfigProvider($scope);
        self::assertTrue($config->isCustomerGroupSelected(4, 1));
        self::assertFalse($config->isCustomerGroupSelected(4, 2));
        self::assertSame('store-one', $config->getCustomerTag(1));
        self::assertSame('store-two', $config->getCustomerTag(2));
        self::assertSame([4, 5], $config->getCustomerGroupIds(3, 'website'));
        self::assertSame('website-three', $config->getCustomerTag(3, 'website'));
    }
}
