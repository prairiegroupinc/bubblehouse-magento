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

        $metaTitle = $this->configProvider->getMetaTitle();
        if ($metaTitle !== '') {
            $pageConfig->getTitle()->set($metaTitle);
        }

        $metaDescription = $this->configProvider->getMetaDescription();
        if ($metaDescription !== '') {
            $pageConfig->setDescription($metaDescription);
        }

        return $page;
    }
}
