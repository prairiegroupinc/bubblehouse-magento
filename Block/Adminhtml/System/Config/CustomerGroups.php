<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class CustomerGroups extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $selected = $element->getValue();
        $selected = is_array($selected) ? $selected : explode(',', (string)$selected);
        $selected = array_map('strval', $selected);
        $name = $element->getName();
        $baseName = str_ends_with($name, '[]') ? substr($name, 0, -2) : $name;
        $disabled = $element->getDisabled() ? ' disabled="disabled"' : '';
        $html = '<input type="hidden" name="' . $this->escapeHtmlAttr($baseName)
            . '" value=""' . $disabled . ' />';
        $html .= '<div id="' . $this->escapeHtmlAttr($element->getHtmlId()) . '">';

        foreach ($element->getValues() as $option) {
            $value = (string)$option['value'];
            $id = $element->getHtmlId() . '_' . $value;
            $checked = in_array($value, $selected, true) ? ' checked="checked"' : '';
            $html .= '<div class="admin__field admin__field-option">'
                . '<input type="checkbox" class="admin__control-checkbox" id="' . $this->escapeHtmlAttr($id)
                . '" name="' . $this->escapeHtmlAttr($baseName . '[]')
                . '" value="' . $this->escapeHtmlAttr($value) . '"' . $checked . $disabled . ' />'
                . '<label class="admin__field-label" for="' . $this->escapeHtmlAttr($id) . '">'
                . $this->escapeHtml($option['label']) . '</label></div>';
        }

        return $html . '</div>';
    }
}
