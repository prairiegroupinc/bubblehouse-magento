<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\UrlInterface;

class ExportButton extends Field
{
    protected $_template = 'BubbleHouse_Integration::system/config/export_button.phtml';

    public function __construct(
        Context $context,
        private readonly UrlInterface $urlBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getAjaxUrl()
    {
        $params = [];
        $store = $this->getRequest()->getParam('store');
        $website = $this->getRequest()->getParam('website');

        if ($store !== null && $store !== '') {
            $params['store'] = $store;
        }

        if ($website !== null && $website !== '') {
            $params['website'] = $website;
        }

        return $this->urlBuilder->getUrl('bubblehouse/customer/export', $params);
    }

    public function getButtonCaption()
    {
        return __('Export Customers');
    }

    public function getForceCheckboxLabel()
    {
        return __('Force');
    }

    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }
}
