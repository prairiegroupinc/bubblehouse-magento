<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Controller\Adminhtml\Customer;

use BubbleHouse\Integration\Model\EportData\Customer\InitialExport;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Export extends Action
{
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly InitialExport $initialExport
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        return $result->setData([
            'message' => $this->initialExport->execute() . ' customers exported.'
        ]);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('BubbleHouse_Integration::customer');
    }
}
