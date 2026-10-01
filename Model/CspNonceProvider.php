<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model;

use Magento\Framework\ObjectManagerInterface;

class CspNonceProvider
{
    public function __construct(
        private readonly ObjectManagerInterface $objectManager
    ) {
    }

    public function generateNonce(): ?string
    {
        // Resolve the optional helper only on Magento versions that provide it.
        $providerClass = 'Magento\\Csp\\Helper\\CspNonceProvider';
        if (!class_exists($providerClass)) {
            return null;
        }

        return $this->objectManager->get($providerClass)->generateNonce();
    }
}
