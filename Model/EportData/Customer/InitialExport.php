<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\EportData\Customer;

use BubbleHouse\Integration\Model\Services\Connector\BubbleHouseRequest;
use BubbleHouse\Integration\Setup\Patch\Data\CreateCustomerExportAttribute;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;

class InitialExport
{
    public function __construct(
        private readonly CustomerCollectionFactory $customerCollectionFactory,
        private readonly CustomerExtractor $customerExtractor,
        private readonly BubbleHouseRequest $request,
        private readonly Config $eavConfig
    ) {
    }

    public function execute(): int
    {
        $exported = [];
        $pageSize = 100;
        $page = 1;

        do {
            $collection = $this->customerCollectionFactory->create();
            $connection = $collection->getConnection();
            $collection->addAttributeToSelect('*');
            $collection->addAttributeToFilter(CreateCustomerExportAttribute::ATTRIBUTE_CODE, ['eq' => 0]);
            $collection->setPageSize($pageSize);
            $collection->setCurPage($page);

            if (!$collection->getSize()) {
                break;
            }

            /** @var \Magento\Customer\Model\Customer $customer */
            foreach ($collection as $customer) {
                $exportData[] = $this->customerExtractor::extract($customer->getDataModel());
            }

            $result = $this->request->exportData(
                BubbleHouseRequest::CUSTOMER_EXPORT_TYPE,
                $exportData,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $customer->getStoreId(),
                true
            );

            if ($result) {
                $customerIds = [];

                foreach ($exportData as $customer) {
                    $customerIds[] = $customer['id'];
                }

                $this->updateExportAttribute($customerIds, $connection);
            }

            $page++;
        } while ($collection->count() >= $pageSize);

        return count($customerIds);
    }

    private function updateExportAttribute(
        array $customerIds,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection
    ): void {
        $bhExportedAttribute = $this->eavConfig->getAttribute(
            'customer',
            CreateCustomerExportAttribute::ATTRIBUTE_CODE
        );
        $attributeId = (int)$bhExportedAttribute->getId();
        $tableName = $connection->getTableName('customer_entity_int');
        $placeholders = rtrim(str_repeat('?,', count($customerIds)), ',');
        $sql = "UPDATE $tableName SET value = ? WHERE attribute_id = ? AND value = ? AND entity_id IN ($placeholders)";
        $params = array_merge([1, $attributeId, 0], $customerIds);
        $connection->query($sql, $params);
    }
}
