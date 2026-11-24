<?php
declare(strict_types=1);

namespace BubbleHouse\Integration\Test\Unit\Model\ExportData\Customer;

use ArrayIterator;

use BubbleHouse\Integration\Cron\RetryFailedMessages;
use BubbleHouse\Integration\Model\ConfigProvider;
use BubbleHouse\Integration\Model\ExportData\Customer\CustomerExtractor;
use BubbleHouse\Integration\Model\ExportData\Customer\CustomerExportCollection;
use BubbleHouse\Integration\Model\ExportData\Customer\ExportScope;
use BubbleHouse\Integration\Model\ExportData\Customer\InitialExport;
use BubbleHouse\Integration\Model\QueueLog;
use BubbleHouse\Integration\Model\ResourceModel\QueueLog as LogResource;
use BubbleHouse\Integration\Model\ResourceModel\QueueLog\Collection as LogCollection;
use BubbleHouse\Integration\Model\ResourceModel\QueueLog\CollectionFactory as LogFactory;
use BubbleHouse\Integration\Model\Services\Connector\BubbleHouseRequest;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Eav\Model\Config;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ExportPathsTest extends TestCase
{
    private function config(string $tag = 'magento-member'): ConfigProvider
    {
        $config = $this->createMock(ConfigProvider::class);
        $config->method('canExportCustomers')->with(2)->willReturn(true);
        $config->method('canExportOrders')->with(2)->willReturn(true);
        $config->method('getCustomerTag')->with(2)->willReturn($tag);
        $config->method('isCustomerGroupSelected')->willReturnCallback(function ($group, $store) {
            self::assertSame(2, $store);
            return $group === 4;
        });
        return $config;
    }

    private function customer(int $id, int $group): CustomerInterface
    {
        $customer = $this->createStub(CustomerInterface::class);
        $customer->method('getId')->willReturn($id);
        $customer->method('getEmail')->willReturn('current@example.test');
        $customer->method('getFirstname')->willReturn('Jane');
        $customer->method('getLastname')->willReturn('Doe');
        $customer->method('getGroupId')->willReturn((string)$group);
        $customer->method('getCustomAttributes')->willReturn([]);
        return $customer;
    }

    public static function retryCases(): array
    {
        return [
            'left group' => [1, 'magento-member', 'remove_tags', false, false],
            'joined group' => [4, 'magento-member', 'add_bh_tags', false, false],
            'blank tag' => [4, '', null, false, false],
            'deleted customer' => [4, 'magento-member', null, true, false],
            'order unchanged' => [4, 'magento-member', null, false, true],
        ];
    }

    #[DataProvider('retryCases')]
    public function testRetry(int $group, string $tag, ?string $operation, bool $deleted, bool $order): void
    {
        $data = $order ? ['id' => 999, 'items' => []] : ['id' => 123, 'email' => 'old@example.test', 'add_bh_tags' => ['magento-member'], 'remove_tags' => ['magento-member']];
        $log = $this->getMockBuilder(QueueLog::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $log->setData(['id' => 5, 'message_type' => $order ? 'order' : 'customer', 'message_body' => json_encode($data), 'store_id' => 2, 'status' => 0]);
        $collection = $this->createMock(LogCollection::class);
        foreach (['addFieldToFilter', 'setOrder', 'setPageSize', 'load'] as $method) {
            $collection->method($method)->willReturnSelf();
        }
        $collection->method('getFirstItem')->willReturn($log);
        $collection->method('getIterator')->willReturn(new ArrayIterator([$log]));
        $factory = $this->createMock(LogFactory::class);
        $factory->expects(self::exactly(2))->method('create')->willReturn($collection);
        $repository = $this->createMock(CustomerRepositoryInterface::class);
        if ($order) {
            $repository->expects(self::never())->method('getById');
        } elseif ($deleted) {
            $repository->expects(self::once())->method('getById')->with(123)->willThrowException(new NoSuchEntityException(new Phrase('Missing')));
        } else {
            $repository->expects(self::once())->method('getById')->with(123)->willReturn($this->customer(123, $group));
        }
        $request = $this->createMock(BubbleHouseRequest::class);
        if ($deleted) {
            $request->expects(self::never())->method('exportData');
        } else {
            $request->expects(self::once())->method('exportData')->willReturnCallback(function ($type, $payload, $store) use ($operation, $order, $data) {
                self::assertSame(2, $store);
                if ($order) {
                    self::assertSame(BubbleHouseRequest::ORDER_EXPORT_TYPE, $type);
                    self::assertSame($data, $payload);
                } else {
                    self::assertSame(BubbleHouseRequest::CUSTOMER_EXPORT_TYPE, $type);
                    self::assertSame('current@example.test', $payload['email']);
                    foreach (['add_bh_tags', 'remove_tags'] as $key) {
                        if ($key === $operation) {
                            self::assertSame(['magento-member'], $payload[$key]);
                        } else {
                            self::assertArrayNotHasKey($key, $payload);
                        }
                    }
                }
                return true;
            });
        }
        $resource = $this->createMock(LogResource::class);
        $resource->expects(self::once())->method('save')->with($log)->willReturnSelf();
        $state = $this->createMock(State::class);
        $state->method('emulateAreaCode')->willReturnCallback(fn ($area, $callback, $args) => $callback(...$args));
        $config = $this->config($tag);
        $retry = new RetryFailedMessages($request, $factory, new Json(), $resource, new NullLogger(), $state, $config, $repository, new CustomerExtractor($config));
        $retry->execute();
        self::assertSame($deleted ? 2 : 1, $log->getData('status'));
    }

    public static function bulkCases(): array
    {
        return [[false], [true]];
    }

    #[DataProvider('bulkCases')]
    public function testBulkAndForcedExport(bool $force): void
    {
        $scope = ['type' => 'store', 'store_id' => 2, 'website_id' => 1];
        $resolver = $this->createMock(ExportScope::class);
        $resolver->method('expandToStoreScopes')->willReturn([$scope]);
        $resolver->method('getConfigScopeCode')->with($scope)->willReturn(2);
        $items = [];
        foreach ([123 => 4, 456 => 1] as $id => $group) {
            $model = $this->createMock(Customer::class);
            $model->method('getDataModel')->willReturn($this->customer($id, $group));
            $items[] = $model;
        }
        $db = $this->createMock(AdapterInterface::class);
        $db->method('getTableName')->willReturn('customer_entity_int');
        $db->expects(self::once())->method('insertOnDuplicate')->with('customer_entity_int', self::callback(fn ($rows) => array_column($rows, 'entity_id') === [123, 456]), ['value']);
        $collection = $this->createMock(Collection::class);
        foreach (['setPageSize', 'setCurPage', 'load'] as $method) {
            $collection->method($method)->willReturnSelf();
        }
        $collection->method('getConnection')->willReturn($db);
        $collection->method('getIterator')->willReturn(new ArrayIterator($items));
        $collection->method('count')->willReturn(2);
        $collections = $this->createMock(CustomerExportCollection::class);
        $collections->expects(self::once())->method('create')->with($force, $scope, true)->willReturn($collection);
        $eav = $this->createMock(Config::class);
        $eav->method('getAttribute')->willReturn(new DataObject(['attribute_id' => 7]));
        $request = $this->createMock(BubbleHouseRequest::class);
        $request->expects(self::once())->method('exportData')->willReturnCallback(function ($type, $payload, $store, $multiple) {
            self::assertSame(BubbleHouseRequest::CUSTOMER_EXPORT_TYPE, $type);
            self::assertSame(2, $store);
            self::assertTrue($multiple);
            self::assertSame(['magento-member'], $payload[0]['add_bh_tags']);
            self::assertSame(['magento-member'], $payload[1]['remove_tags']);
            return true;
        });
        $config = $this->config();
        $export = new InitialExport($collections, new CustomerExtractor($config), $request, $eav, $resolver, $config);
        self::assertSame(2, $export->execute($force, $scope));
    }

    public static function automaticCases(): array
    {
        return [
            'matching group' => [4, 'magento-member', 'add_bh_tags', false],
            'nonmatching group' => [1, 'magento-member', 'remove_tags', false],
            'blank tag' => [4, '', null, false],
            'HTTP failure logged for retry' => [4, 'magento-member', 'add_bh_tags', true],
        ];
    }

    #[DataProvider('automaticCases')]
    public function testAutomaticUpdateHttpPayload(int $group, string $tag, ?string $operation, bool $fail): void
    {
        $config = $this->config($tag);
        $config->method('getApiHost')->willReturn('example.test');
        $config->method('getShopSlug')->willReturn('test-shop');
        $auth = $this->createMock(\BubbleHouse\Integration\Model\Services\Auth\TokenAuthCreate::class);
        $auth->method('createShopToken')->with(2)->willReturn('test-token');
        $history = [];
        $mock = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response($fail ? 503 : 200, [], '{"ok":true}')
        ]);
        $stack = \GuzzleHttp\HandlerStack::create($mock);
        $stack->push(\GuzzleHttp\Middleware::history($history));
        $request = new BubbleHouseRequest($config, $auth, new Json(), new \GuzzleHttp\Client(['handler' => $stack]), new NullLogger());
        $model = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->onlyMethods(['getDataModel'])->getMock();
        $model->setData(['id' => 123, 'entity_id' => 123, 'store_id' => 2, 'website_id' => 1]);
        $model->method('getDataModel')->willReturn($this->customer(123, $group));
        $customers = $this->createMock(\Magento\Customer\Model\CustomerFactory::class);
        $customers->method('create')->willReturn($model);
        $customerResource = $this->createMock(\Magento\Customer\Model\ResourceModel\Customer::class);
        $customerResource->expects(self::once())->method('load')->with($model, 123)->willReturnSelf();
        $log = $this->getMockBuilder(QueueLog::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $logs = $this->createMock(\BubbleHouse\Integration\Model\QueueLogFactory::class);
        $logs->method('create')->willReturn($log);
        $resource = $this->createMock(LogResource::class);
        $resource->expects(self::exactly($fail ? 1 : 2))->method('save')->with($log)->willReturnSelf();
        $handler = new \BubbleHouse\Integration\Queue\CustomerHandler(
            new NullLogger(), $customerResource, $customers, $request, $resource, $logs,
            new Json(), $config, new CustomerExtractor($config)
        );
        $failed = false;
        try {
            $handler->process(123);
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            $failed = true;
        }
        self::assertSame($fail, $failed);
        self::assertCount(1, $history);
        $http = $history[0]['request'];
        self::assertSame('/api/v2023061/test-shop/UpdateCustomers3', $http->getUri()->getPath());
        self::assertSame('Bearer test-token', $http->getHeaderLine('Authorization'));
        $payload = json_decode((string)$http->getBody(), true)['customers'][0];
        self::assertSame(123, $payload['id']);
        self::assertSame('current@example.test', $payload['email']);
        foreach (['add_bh_tags', 'remove_tags'] as $key) {
            if ($key === $operation) {
                self::assertSame(['magento-member'], $payload[$key]);
            } else {
                self::assertArrayNotHasKey($key, $payload);
            }
        }
        self::assertSame($payload, json_decode($log->getData('message_body'), true));
        self::assertSame($fail ? 0 : 1, $log->getData('status'));
    }
}
