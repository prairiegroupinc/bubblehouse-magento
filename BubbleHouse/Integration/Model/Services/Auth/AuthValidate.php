<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Services\Auth;


use Magento\Framework\Oauth\Exception;
use Magento\Framework\Oauth\TokenProviderInterface;

class AuthValidate
{
    public const AUTH = 'Authentication';

    public function __construct(
        protected readonly TokenProviderInterface $tokenProvider
    ) {
    }

    public function validateToken(string $authToken): bool
    {
        try {
            return (bool) $this->tokenProvider->validateAccessToken($authToken);
        } catch (Exception $exception) {
            // token expired
            return false;
        }

        return false;
    }
}
