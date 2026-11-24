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
    public const CUSTOMER_GROUPS_PATH = 'bubblehouse/general/customer_groups';
    public const CUSTOMER_TAG_PATH = 'bubblehouse/general/customer_tag';
    public const TOKEN_EXPIRATION_TIME_PATH = 'bubblehouse/general/token_expiration_time';
    public const SHARED_SECRET_PATH = 'bubblehouse/general/shared_secret';
    public const API_HOST_PATH = 'bubblehouse/general/api_host';

    public const IFRAME_HEIGHT_PATH = 'bubblehouse/general/iframe_height';
    public const CUSTOMER_BALANCE_AMOUNT_PATH = 'bubblehouse/general/enable_customer_balance_amount';
    public const IFRAME_STYLES_PATH = 'bubblehouse/general/iframe_styles';
    public const WIDGET_ID_PATH = 'bubblehouse/general/widget_id';
    public const DEV_MODE_PATH = 'bubblehouse/general/dev_mode';
    public const ORDER_ID_SOURCE_PATH = 'bubblehouse/general/order_id_source';

    public const DEFAULT_API_HOST = 'app.bubblehouse.com';

    public const META_TITLE = 'bubblehouse/seo/meta_title';
    public const META_DESCRIPTION = 'bubblehouse/seo/meta_description';

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

    public function getMetaTitle(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): string {
        return $this->scopeConfig->getValue(self::META_TITLE, $scopeType, $scopeCode) ?? '';
    }

    public function getMetaDescription(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): string {
        return $this->scopeConfig->getValue(self::META_DESCRIPTION, $scopeType, $scopeCode) ?? '';
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

    public function canExportOrders(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): bool {
        return $this->isOrderExportEnabled($scopeCode, $scopeType);
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

    public function canExportCustomers(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): bool {
        return $this->isCustomerExportEnabled($scopeCode, $scopeType);
    }

    /**
     * Return the configured customer group IDs, including the guest group (0).
     *
     * @return int[]
     */
    public function getCustomerGroupIds(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): array {
        $value = $this->scopeConfig->getValue(self::CUSTOMER_GROUPS_PATH, $scopeType, $scopeCode);
        $values = is_array($value) ? $value : explode(',', (string)$value);
        $groupIds = [];

        foreach ($values as $id) {
            $id = trim((string)$id);
            if ($id !== '' && ctype_digit($id)) {
                $groupIds[] = (int)$id;
            }
        }

        return array_values(array_unique($groupIds));
    }

    public function isCustomerGroupSelected(
        ?int $groupId,
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): bool {
        return $groupId !== null
            && in_array($groupId, $this->getCustomerGroupIds($scopeCode, $scopeType), true);
    }

    public function getCustomerTag(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): string {
        return trim((string)$this->scopeConfig->getValue(self::CUSTOMER_TAG_PATH, $scopeType, $scopeCode));
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

    public function getWidgetId(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): string {
        return $this->scopeConfig->getValue(
            self::WIDGET_ID_PATH,
            $scopeType,
            $scopeCode
        ) ?? 'checkout-widget-custom';
    }

    public function isDevMode(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::DEV_MODE_PATH,
            $scopeType,
            $scopeCode
        );
    }

    public function getOrderIdSource(
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): string {
        return $this->scopeConfig->getValue(
            self::ORDER_ID_SOURCE_PATH,
            $scopeType,
            $scopeCode
        ) ?? 'entity_id';
    }
}
