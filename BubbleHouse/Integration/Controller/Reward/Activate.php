<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Controller\Reward;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Activate implements ActionInterface
{

    public function __construct(
        private readonly Context $context,
        private readonly PageFactory $pageFactory
    ) {
    }

    public function execute(): ResultInterface
    {
        return $this->pageFactory->create();
    }
}
