<?php

namespace BubbleHouse\Integration\Model;

use BubbleHouse\Integration\ViewModel\BubbleHouseConfigProvider;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Cms\Block\Widget\Block;
use Magento\Csp\Helper\CspNonceProvider;

class CheckoutConfigProvider implements ConfigProviderInterface
{
   protected $block;

   public function __construct(
        private readonly BubblehouseConfigProvider $configProvider,
        private readonly CspNonceProvider $cspNonceProvider,
        Block $block, $blockId)
   {
        $block->setData('block_id', $blockId);
        $block->setData('nonce', $this->getNonce());
        $block->setData('config_provider', $configProvider);
        $block->setTemplate('BubbleHouse_Integration::widget/checkout.phtml');
        $this->block = $block;
   }

   public function getConfig()
   {
        return [
            'bh_checkout_widget' => $this->block->toHtml()
        ];
   }

    public function getNonce(): string
    {
        return $this->cspNonceProvider->generateNonce();
    }

    public function getApiHost(): string
    {
        return $this->configProvider->getApiHost();
    }

    public function getShopSlug(): string
    {
        return $this->configProvider->getShopSlug();
    }
}
