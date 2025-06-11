<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Controller\Adminhtml\Customer;

use BubbleHouse\Integration\Model\EportData\Customer\InitialExport;
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
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $exported = 0;

        try {
            $exported = $this->initialExport->execute();
        } catch (\Exception $exception) {
            $this->logger->critical('could not export BH customers: ' . $exception->getMessage());
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
