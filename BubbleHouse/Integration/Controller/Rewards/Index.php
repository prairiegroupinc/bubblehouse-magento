<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Controller\Rewards;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Index implements ActionInterface
{

    public function __construct(
        private readonly PageFactory $pageFactory
    ) {
    }

    public function execute(): ResultInterface
    {
        return $this->pageFactory->create();
    }
}
