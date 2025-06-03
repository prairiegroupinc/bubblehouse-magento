<?php

namespace BubbleHouse\Integration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

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

    public const IFRAME_HEIGHT_PATH = 'bubblehouse/general/iframe_height';
    public const CUSTOMER_BALANCE_AMOUNT_PATH = 'bubblehouse/general/enable_customer_balance_amount';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::ENABLED_PATH,
            $scopeType,
            $scopeCode
        );
    }

    public function isDebug(
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::DEBUG_PATH,
            $scopeType,
            $scopeCode
        );
    }

    public function getKid(
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ): string {
        return $this->scopeConfig->getValue(self::KID_PATH, $scopeType, $scopeCode) ?? '';
    }

    public function getSharedSecret(
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ): string {
        return $this->scopeConfig->getValue(self::SHARED_SECRET_PATH, $scopeType, $scopeCode) ?? '';
    }

    public function getShopSlug(
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ): string {
        return $this->scopeConfig->getValue(self::SHOP_SLUG_PATH, $scopeType, $scopeCode) ?? '';
    }

    public function getTokenExpirationTime(
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ): string {
        return $this->scopeConfig->getValue(self::TOKEN_EXPIRATION_TIME_PATH, $scopeType, $scopeCode) ?? '';
    }

    public function isOrderExportEnabled(
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::ORDER_EXPORT_PATH,
            $scopeType,
            $scopeCode
        );
    }

    public function isCustomerExportEnabled(
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CUSTOMER_EXPORT_PATH,
            $scopeType,
            $scopeCode
        );
    }

    public function isCustomerBalanceEnabled(
        $scopeCode = null,
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CUSTOMER_BALANCE_AMOUNT_PATH,
            $scopeType,
            $scopeCode
        );
    }

    public function getIframeHeight(
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ): int {
        return $this->scopeConfig->getValue(
            self::IFRAME_HEIGHT_PATH,
            $scopeType,
            $scopeCode
        ) ?? 1500;
    }
}
