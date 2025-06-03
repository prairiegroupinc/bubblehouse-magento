<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Block\Adminhtml\Widget;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Element\AbstractBlock;

class Textarea extends AbstractBlock
{
    /**
     * Prepares HTML for the textarea element.
     */
    public function prepareElementHtml(AbstractElement $element)
    {
        $html = '<textarea '
            . 'name="' . $element->getName() . '" '
            . 'id="' . $element->getHtmlId() . '" '
            . 'title="' . $element->getLabel() . '" '
            . 'rows="10" cols="100" '
            . 'class="textarea widget-option input-text">'
            . $element->getEscapedValue()
            . '</textarea>';

        $element->setAfterElementHtml($html);
        return $element;
    }
}
