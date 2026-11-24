<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Test\Unit\Plugin\Magento\Customer\Model\ResourceModel\Customer;

use BubbleHouse\Integration\Model\ConfigProvider;
use BubbleHouse\Integration\Model\Services\CustomerExportSuppressor;
use BubbleHouse\Integration\Plugin\Magento\Customer\Model\ResourceModel\Customer\CustomerChangePlugin;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use stdClass;

class CustomerChangePluginTest extends TestCase
{
    public function testAfterSaveDoesNotPublishWhenExportIsSuppressed(): void
    {
        $publisher = $this->createMock(PublisherInterface::class);
        $configProvider = $this->createMock(ConfigProvider::class);
        $suppressor = new CustomerExportSuppressor();
        $plugin = new CustomerChangePlugin(
            $this->createMock(LoggerInterface::class),
            $publisher,
            $configProvider,
            $suppressor
        );
        $result = new stdClass();

        $publisher->expects(self::never())->method('publish');
        $configProvider->expects(self::never())->method('canExportCustomers');

        $actualResult = $suppressor->suppress(
            fn () => $plugin->afterSave(
                $this->createMock(Customer::class),
                $result,
                new stdClass()
            )
        );

        self::assertSame($result, $actualResult);
    }

    public static function groupChangeCases(): array
    {
        return [
            'joins group' => [1, 4, true],
            'leaves group' => [4, 1, true],
            'same group' => [4, 4, false],
            'same ID with different types' => [4, '4', false],
            'leaves group zero' => ['0', 1, true],
            'new group zero' => [null, 0, true],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('groupChangeCases')]
    public function testGroupChangesWithUnchangedTimestamp($oldGroup, $newGroup, bool $shouldPublish): void
    {
        $publisher = $this->createMock(PublisherInterface::class);
        $publisher->expects($shouldPublish ? self::once() : self::never())
            ->method('publish')->with('bubblehouse.integration.customer.export', 123);
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->method('canExportCustomers')->with(2)->willReturn(true);
        $plugin = new CustomerChangePlugin(
            $this->createMock(LoggerInterface::class),
            $publisher,
            $configProvider,
            new CustomerExportSuppressor()
        );
        $customer = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMock();
        $customer->setData([
            'entity_id' => 123,
            'store_id' => 2,
            'group_id' => $newGroup,
            'updated_at' => '2026-11-24 00:00:00',
        ]);
        $customer->setOrigData('group_id', $oldGroup);
        $customer->setOrigData('updated_at', '2026-11-24 00:00:00');
        $result = new stdClass();
        self::assertSame($result, $plugin->afterSave($this->createMock(Customer::class), $result, $customer));
    }
}
