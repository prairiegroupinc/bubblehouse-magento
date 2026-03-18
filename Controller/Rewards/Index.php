<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Controller\Rewards;

use BubbleHouse\Integration\ViewModel\BubbleHouseConfigProvider;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Index implements ActionInterface
{

    public function __construct(
        private readonly BubbleHouseConfigProvider $configProvider,
        private readonly PageFactory $pageFactory
    ) {
    }

    public function execute(): ResultInterface
    {
        $page = $this->pageFactory->create();
        $pageConfig = $page->getConfig();
        $pageConfig->getTitle()->set($this->configProvider->getMetaTitle());
        $pageConfig->setDescription($this->configProvider->getMetaDescription());

        return $page;
    }
}
