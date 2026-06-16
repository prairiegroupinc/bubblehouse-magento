<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\ExportData\Customer;

use BubbleHouse\Integration\Model\ConfigProvider;
use BubbleHouse\Integration\Model\Services\Connector\BubbleHouseRequest;
use BubbleHouse\Integration\Setup\Patch\Data\CreateCustomerExportAttribute;
use Magento\Eav\Model\Config;
use Magento\Framework\DB\Adapter\AdapterInterface;

class InitialExport
{
    public function __construct(
        private readonly CustomerExportCollection $customerExportCollection,
        private readonly CustomerExtractor $customerExtractor,
        private readonly BubbleHouseRequest $request,
        private readonly Config $eavConfig,
        private readonly ExportScope $exportScopeResolver,
        private readonly ConfigProvider $configProvider
    ) {
    }

    public function execute(bool $force, ?array $scope = null): int
    {
        $count = 0;

        foreach ($this->exportScopeResolver->expandToStoreScopes($scope) as $storeScope) {
            $count += $this->executeStoreScope($force, $storeScope);
        }

        return $count;
    }

    private function executeStoreScope(bool $force, array $scope): int
    {
        $pageSize = 100;
        $page = 1;
        $count = 0;
        $scopeCode = $this->exportScopeResolver->getConfigScopeCode($scope);

        if ($scopeCode === null || $scopeCode <= 0 || !$this->configProvider->canExportCustomers($scopeCode)) {
            return 0;
        }

        do {
            $collection = $this->customerExportCollection->create($force, $scope, true);
            $connection = $collection->getConnection();
            $collection->setPageSize($pageSize);
            $collection->setCurPage($page);
            $collection->load();

            if (!$collection->count()) {
                break;
            }

            $exportData = [];
            /** @var \Magento\Customer\Model\Customer $customer */
            foreach ($collection as $customer) {
                $exportData[] = $this->customerExtractor::extract($customer->getDataModel());
            }

            $result = $this->request->exportData(
                BubbleHouseRequest::CUSTOMER_EXPORT_TYPE,
                $exportData,
                $scopeCode,
                true
            );

            if (!$result) {
                break;
            }

            $customerIds = [];
            foreach ($exportData as $customer) {
                $customerIds[] = $customer['id'];
            }

            $this->updateExportAttribute($customerIds, $connection);
            $count += count($customerIds);

            if ($force) {
                $page++;
            }
        } while ($collection->count() >= $pageSize);

        return $count;
    }

    private function updateExportAttribute(array $customerIds, AdapterInterface $connection): void
    {
        if (!$customerIds) {
            return;
        }

        $bhExportedAttribute = $this->eavConfig->getAttribute(
            'customer',
            CreateCustomerExportAttribute::ATTRIBUTE_CODE
        );
        $attributeId = (int)$bhExportedAttribute->getId();
        $tableName = $connection->getTableName('customer_entity_int');
        $rows = [];

        foreach ($customerIds as $customerId) {
            $rows[] = [
                'attribute_id' => $attributeId,
                'entity_id' => (int)$customerId,
                'value' => 1,
            ];
        }

        $connection->insertOnDuplicate($tableName, $rows, ['value']);
    }
}
