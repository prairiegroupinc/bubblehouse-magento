<?php

namespace BubbleHouse\Integration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider
{
    public const ENABLED_PATH = 'bubblehouse/general/enabled';
    public const DEBUG_PATH = 'bubblehouse/general/debug';
    public const KID_PATH = 'bubblehouse/general/api_key';
    public const SHOP_SLUG_PATH = 'bubblehouse/general/shop_slug';
    public const ORDER_EXPORT_PATH = 'bubblehouse/general/order_export_enabled';
    public const CUSTOMER_EXPORT_PATH = 'bubblehouse/general/customer_export_enabled';
    public const TOKEN_EXPIRATION_TIME_PATH = 'bubblehouse/general/token_expiration_time';
    public const SHARED_SECRET_PATH = 'bubblehouse/general/shared_secret';
    public const API_HOST_PATH = 'bubblehouse/general/api_host';

    public const IFRAME_HEIGHT_PATH = 'bubblehouse/general/iframe_height';
    public const CUSTOMER_BALANCE_AMOUNT_PATH = 'bubblehouse/general/enable_customer_balance_amount';
    public const IFRAME_STYLES_PATH = 'bubblehouse/general/iframe_styles';

    public const DEFAULT_API_HOST = 'app.bubblehouse.com';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::ENABLED_PATH,
            $scopeType,
            $scopeCode
        );
    }

    public function isDebug(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::DEBUG_PATH,
            $scopeType,
            $scopeCode
        );
    }

    public function getKid(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): string {
        return $this->scopeConfig->getValue(self::KID_PATH, $scopeType, $scopeCode) ?? '';
    }

    public function getSharedSecret(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): string {
        return $this->scopeConfig->getValue(self::SHARED_SECRET_PATH, $scopeType, $scopeCode) ?? '';
    }

    public function getApiHost(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): string {
        return $this->scopeConfig->getValue(self::API_HOST_PATH, $scopeType, $scopeCode) ?? self::DEFAULT_API_HOST;
    }

    public function getShopSlug(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): string {
        return $this->scopeConfig->getValue(self::SHOP_SLUG_PATH, $scopeType, $scopeCode) ?? '';
    }

    public function getTokenExpirationTime(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): string {
        return $this->scopeConfig->getValue(self::TOKEN_EXPIRATION_TIME_PATH, $scopeType, $scopeCode) ?? '';
    }

    public function isOrderExportEnabled(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): bool {
        if (!$this->isEnabled($scopeCode, $scopeType)) {
            return false;
        }

        return $this->scopeConfig->isSetFlag(
            self::ORDER_EXPORT_PATH,
            $scopeType,
            $scopeCode
        );
    }

    public function isCustomerExportEnabled(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): bool {
        if (!$this->isEnabled($scopeCode, $scopeType)) {
            return false;
        }

        return $this->scopeConfig->isSetFlag(
            self::CUSTOMER_EXPORT_PATH,
            $scopeType,
            $scopeCode
        );
    }

    public function isCustomerBalanceEnabled(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CUSTOMER_BALANCE_AMOUNT_PATH,
            $scopeType,
            $scopeCode
        );
    }

    public function getIframeHeight(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): int {
        return $this->scopeConfig->getValue(
            self::IFRAME_HEIGHT_PATH,
            $scopeType,
            $scopeCode
        ) ?? 1500;
    }

    public function getIframeStyles(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): string {
        return $this->scopeConfig->getValue(
            self::IFRAME_STYLES_PATH,
            $scopeType,
            $scopeCode
        ) ?? '';
    }
}
