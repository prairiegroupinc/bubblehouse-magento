<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\ExportData\Customer;

use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class ExportScope
{
    public const TYPE_GLOBAL = 'global';
    public const TYPE_WEBSITE = 'website';
    public const TYPE_STORE = 'store';

    public const KEY_TYPE = 'type';
    public const KEY_STORE_ID = 'store_id';
    public const KEY_WEBSITE_ID = 'website_id';

    public const GLOBAL_SCOPE = [
        self::KEY_TYPE => self::TYPE_GLOBAL,
        self::KEY_STORE_ID => null,
        self::KEY_WEBSITE_ID => null,
    ];

    public function __construct(
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function resolveFromRequest(RequestInterface $request): array
    {
        $store = $request->getParam('store');
        if ($store !== null && $store !== '') {
            return $this->resolveStore($store);
        }

        $website = $request->getParam('website');
        if ($website !== null && $website !== '') {
            return $this->resolveWebsite($website);
        }

        return self::GLOBAL_SCOPE;
    }

    public function getStoreScopes(?int $websiteId = null): array
    {
        $scopes = [];

        foreach ($this->storeManager->getStores() as $store) {
            if (method_exists($store, 'isActive') && !$store->isActive()) {
                continue;
            }

            if ($websiteId !== null && (int)$store->getWebsiteId() !== $websiteId) {
                continue;
            }

            $scopes[] = $this->resolveStore((int)$store->getId());
        }

        return $scopes;
    }

    public function getStoreScopesForWebsite(int $websiteId): array
    {
        return $this->getStoreScopes($websiteId);
    }

    public function expandToStoreScopes(?array $scope): array
    {
        $scope = $this->normalizeScope($scope);

        if ($this->isStoreScope($scope)) {
            return [$scope];
        }

        if ($this->isWebsiteScope($scope)) {
            return $this->getStoreScopesForWebsite((int)$scope[self::KEY_WEBSITE_ID]);
        }

        return $this->getStoreScopes();
    }

    public function resolveStore(string|int $store): array
    {
        try {
            $storeModel = $this->storeManager->getStore($store);
        } catch (NoSuchEntityException) {
            throw new LocalizedException(__('Invalid store scope for customer export.'));
        }

        return [
            self::KEY_TYPE => self::TYPE_STORE,
            self::KEY_STORE_ID => (int)$storeModel->getId(),
            self::KEY_WEBSITE_ID => (int)$storeModel->getWebsiteId(),
        ];
    }

    public function resolveWebsite(string|int $website): array
    {
        try {
            $websiteModel = $this->storeManager->getWebsite($website);
        } catch (NoSuchEntityException) {
            throw new LocalizedException(__('Invalid website scope for customer export.'));
        }

        return [
            self::KEY_TYPE => self::TYPE_WEBSITE,
            self::KEY_STORE_ID => null,
            self::KEY_WEBSITE_ID => (int)$websiteModel->getId(),
        ];
    }

    public function applyToCollection(Collection $collection, ?array $scope): void
    {
        $scope = $this->normalizeScope($scope);

        if ($this->isStoreScope($scope)) {
            $collection->addFieldToFilter(self::KEY_STORE_ID, $scope[self::KEY_STORE_ID]);
            return;
        }

        if ($this->isWebsiteScope($scope)) {
            $collection->addFieldToFilter(self::KEY_WEBSITE_ID, $scope[self::KEY_WEBSITE_ID]);
        }
    }

    public function getConfigScopeCode(?array $scope): ?int
    {
        $scope = $this->normalizeScope($scope);

        if ($this->isStoreScope($scope)) {
            return (int)$scope[self::KEY_STORE_ID];
        }

        if ($this->isWebsiteScope($scope)) {
            return $this->getWebsiteDefaultStoreId((int)$scope[self::KEY_WEBSITE_ID]);
        }

        return null;
    }

    public function normalizeScope(?array $scope): array
    {
        if (($scope[self::KEY_TYPE] ?? null) === self::TYPE_GLOBAL) {
            return self::GLOBAL_SCOPE;
        }

        return $scope ?? self::GLOBAL_SCOPE;
    }

    private function isStoreScope(?array $scope): bool
    {
        return ($scope[self::KEY_TYPE] ?? null) === self::TYPE_STORE
            && ($scope[self::KEY_STORE_ID] ?? null) !== null;
    }

    private function isWebsiteScope(?array $scope): bool
    {
        return ($scope[self::KEY_TYPE] ?? null) === self::TYPE_WEBSITE
            && ($scope[self::KEY_WEBSITE_ID] ?? null) !== null;
    }

    private function getWebsiteDefaultStoreId(int $websiteId): ?int
    {
        try {
            $defaultStore = $this->storeManager->getWebsite($websiteId)->getDefaultStore();
        } catch (NoSuchEntityException) {
            return null;
        }

        return $defaultStore ? (int)$defaultStore->getId() : null;
    }
}
