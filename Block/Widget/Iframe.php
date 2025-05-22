<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Block\Widget;

use BubbleHouse\Integration\ViewModel\BubbleHouseConfigProvider;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;

class Iframe extends Template implements BlockInterface
{
    protected $_template = "widget/iframe.phtml";

    public function __construct(
        private readonly BubbleHouseConfigProvider $configProvider,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getConfigProvider(): BubbleHouseConfigProvider
    {
        return $this->configProvider;
    }
}
