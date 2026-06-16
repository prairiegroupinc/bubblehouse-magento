<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\ExportData\Customer;

use BubbleHouse\Integration\Model\ConfigProvider;
use BubbleHouse\Integration\Setup\Patch\Data\CreateCustomerExportAttribute;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;

class CustomerExportCollection
{
    public function __construct(
        private readonly CustomerCollectionFactory $customerCollectionFactory,
        private readonly ExportScope $exportScopeResolver,
        private readonly ConfigProvider $configProvider
    ) {
    }

    public function create(bool $includeExported, ?array $scope = null, bool $selectAll = false): Collection
    {
        $collection = $this->customerCollectionFactory->create();

        if ($selectAll) {
            $collection->addAttributeToSelect('*');
        }

        $this->exportScopeResolver->applyToCollection($collection, $scope);

        if (!$includeExported) {
            $this->addPendingExportFilter($collection);
        }

        return $collection;
    }

    public function getAllCount(?array $scope = null): int
    {
        return $this->getCount(true, $scope);
    }

    public function getPendingCount(?array $scope = null): int
    {
        return $this->getCount(false, $scope);
    }

    private function getCount(bool $includeExported, ?array $scope): int
    {
        $count = 0;

        foreach ($this->exportScopeResolver->expandToStoreScopes($scope) as $storeScope) {
            $scopeCode = $this->exportScopeResolver->getConfigScopeCode($storeScope);
            if ($scopeCode === null || $scopeCode <= 0 || !$this->configProvider->canExportCustomers($scopeCode)) {
                continue;
            }

            $count += (int)$this->create($includeExported, $storeScope)->getSize();
        }

        return $count;
    }

    private function addPendingExportFilter(Collection $collection): void
    {
        $collection->addAttributeToFilter(
            [
                ['attribute' => CreateCustomerExportAttribute::ATTRIBUTE_CODE, 'eq' => 0],
                ['attribute' => CreateCustomerExportAttribute::ATTRIBUTE_CODE, 'null' => true],
            ],
            null,
            'left'
        );
    }
}
