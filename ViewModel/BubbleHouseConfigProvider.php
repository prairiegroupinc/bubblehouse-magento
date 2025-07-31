<?php
declare(strict_types=1);

namespace BubbleHouse\Integration\ViewModel;

use BubbleHouse\Integration\Model\ConfigProvider;
use BubbleHouse\Integration\Model\Services\Auth\TokenAuthCreate;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

class BubbleHouseConfigProvider implements ArgumentInterface
{
    /** @var Session */
    private ?SessionManagerInterface $customerSession = null;

    public function __construct(
        SessionManagerInterface $customerSession,
        private readonly ConfigProvider $configProvider,
        private readonly StoreManagerInterface $storeManager,
        private readonly TokenAuthCreate $tokenAuthCreate
    ){
        $this->customerSession = $customerSession;
    }

    public function isEnabled(): bool
    {
        $store = $this->storeManager->getStore();

        return $this->configProvider->isEnabled(
            $store->getId()
        );
    }

    public function isCustomerLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    public function getShopSlug(): string
    {
        $store = $this->storeManager->getStore();

        return $this->configProvider->getShopSlug(
            $store->getId()
        );
    }

    public function getCustomerToken(): string
    {
        $store = $this->storeManager->getStore();
        $customerId = $this->customerSession->isLoggedIn() ? $this->customerSession->getCustomerId() : null;

        return $this->tokenAuthCreate->createShopToken(
            $store->getId(),
            $customerId
        );
    }

    public function getIframeHeight(): int
    {
        $store = $this->storeManager->getStore();

        return $this->configProvider->getIframeHeight(
            $store->getId()
        );
    }

    public function getCustomStyles(): string
    {
        $store = $this->storeManager->getStore();

        return $this->configProvider->getIframeStyles($store->getId());
    }

    public function getApiHost(): string
    {
        $store = $this->storeManager->getStore();

        return $this->configProvider->getApiHost($store->getId());
    }
}
