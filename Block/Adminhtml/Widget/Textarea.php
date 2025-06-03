<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Block\Adminhtml\Widget;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Widget\Block\BlockInterface;

class Textarea extends AbstractBlock implements BlockInterface
{
    protected $_template = 'BubbleHouse_Integration::widget/textarea.phtml';
}
