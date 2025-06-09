<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\EportData\Customer;

use BubbleHouse\Integration\Model\Services\Connector\BubbleHouseRequest;
use BubbleHouse\Integration\Setup\Patch\Data\CreateCustomerExportAttribute;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class InitialExport
{
    public function __construct(
        private readonly CustomerCollectionFactory $customerCollectionFactory,
        private readonly CustomerExtractor $customerExtractor,
        private readonly BubbleHouseRequest $request,
        private readonly CustomerRepositoryInterface $customerRepository
    ) {
    }
 
    public function execute(): int
    {
        $exported = [];
        $pageSize = 100;
        $page = 1;

        do {
            $collection = $this->customerCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addAttributeToFilter(CreateCustomerExportAttribute::ATTRIBUTE_CODE, ['eq' => 0]);
            $collection->setPageSize($pageSize);
            $collection->setCurPage($page);

            if (!$collection->getSize()) {
                break;
            }

            /** @var \Magento\Customer\Model\Customer $customer */
            foreach ($collection as $customer) {
                $exportData = $this->customerExtractor::extract($customer->getDataModel());
                $result = $this->request->exportData(
                    BubbleHouseRequest::CUSTOMER_EXPORT_TYPE,
                    $exportData,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    $customer->getStoreId()
                );

                if ($result) {
                    $this->customerRepository->save(
                        $customer->setData(CreateCustomerExportAttribute::ATTRIBUTE_CODE, 1)->getDataModel()
                    );
                    $exported[] = $exportData;
                }
            }

            $page++;
        } while ($collection->count() >= $pageSize);

        return count($exported);
    }
}
