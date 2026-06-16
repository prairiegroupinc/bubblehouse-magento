<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Controller\Adminhtml\Customer;

use BubbleHouse\Integration\Model\ExportData\Customer\CustomerExportCollection;
use BubbleHouse\Integration\Model\ExportData\Customer\ExportScope;
use BubbleHouse\Integration\Model\ExportData\Customer\InitialExport;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

class Export extends Action
{
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly InitialExport $initialExport,
        private readonly ExportScope $exportScopeResolver,
        private readonly CustomerExportCollection $customerExportCollection,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $exported = 0;

        $force = filter_var($this->getRequest()->getParam('force'), FILTER_VALIDATE_BOOLEAN);

        try {
            $scope = $this->exportScopeResolver->resolveFromRequest($this->getRequest());

            if (filter_var($this->getRequest()->getParam('count_only'), FILTER_VALIDATE_BOOLEAN)) {
                return $result->setData([
                    'count' => $force
                        ? $this->customerExportCollection->getAllCount($scope)
                        : $this->customerExportCollection->getPendingCount($scope),
                ]);
            }

            $exported = $this->initialExport->execute(
                $force,
                $scope
            );
        } catch (\Exception $exception) {
            $this->logger->critical('Bubblehouse: could not export BH customers: ' . $exception->getMessage());
            return $result->setData([
                'message ' => 'could not export BH customers: ' . $exception->getMessage()
            ]);
        }

        return $result->setData([
            'message' => $exported . ' customers exported.'
        ]);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('BubbleHouse_Integration::customer');
    }
}
