<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Controller\Hooks\Abstract;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Oauth\Exception;
use Magento\Framework\Oauth\TokenProviderInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

abstract class Index  implements CsrfAwareActionInterface, HttpPostActionInterface
{
    private const AUTH_HEADER = 'authorization';

    public function __construct(
        protected TokenProviderInterface $tokenProvider,
        protected Request $request,
        protected JsonFactory $jsonFactory,
        protected SerializerInterface $serializer,
        protected LoggerInterface $logger
    ) {
    }

    protected function validateToken(): bool
    {
        $authToken = $this->request->getHeaders(self::AUTH_HEADER)->getFieldValue();
        // test purpose
        $this->logger->debug('auth: ' . $authToken);
        $authToken = str_contains($authToken, 'Bearer')
            ? str_replace('Bearer ', '', $authToken)
            : $authToken;

        if ($authToken !== null) {
            try {
                return (bool) $this->tokenProvider->validateAccessToken($authToken);
            } catch (Exception $exception) {
                // token expired or does not exist
                return false;
            }
        }

        return false;
    }

    // Bypass CSRF validation
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null; // No exception, bypass CSRF check
    }

    public function validateForCsrf(RequestInterface $request): bool
    {
        return true; // Always allow request
    }
}
