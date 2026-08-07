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
}
