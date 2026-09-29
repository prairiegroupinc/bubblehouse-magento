<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Services\Auth;

use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Authorization\PolicyInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Integration\Api\Exception\UserTokenException;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\UserTokenReaderInterface;
use Magento\Integration\Api\UserTokenValidatorInterface;
use Magento\Integration\Model\Integration;

class AuthorizeDiscount
{
    public const RESOURCE = 'BubbleHouse_Integration::sales_rules';

    public function __construct(
        private readonly UserTokenReaderInterface $tokenReader,
        private readonly UserTokenValidatorInterface $tokenValidator,
        private readonly CollectionFactory $roleCollectionFactory,
        private readonly PolicyInterface $aclPolicy,
        private readonly IntegrationServiceInterface $integrationService
    ) {
    }

    public function execute(string $authorizationHeader): void
    {
        if (!preg_match('/\ABearer[ \t]+([^\s,]+)\z/i', $authorizationHeader, $matches)) {
            throw new AuthenticationException(__('A valid bearer token is required.'));
        }

        try {
            $token = $this->tokenReader->read($matches[1]);
            $this->tokenValidator->validate($token);
        } catch (UserTokenException | AuthorizationException $exception) {
            throw new AuthenticationException(__('A valid bearer token is required.'));
        }

        $context = $token->getUserContext();
        if (!$context->getUserId() || !in_array($context->getUserType(), [
            UserContextInterface::USER_TYPE_ADMIN,
            UserContextInterface::USER_TYPE_INTEGRATION,
        ], true)) {
            throw new AuthorizationException(__('Coupon creation is not permitted.'));
        }

        if ($context->getUserType() === UserContextInterface::USER_TYPE_INTEGRATION) {
            $integration = $this->integrationService->get($context->getUserId());
            if ((int)$integration->getStatus() !== Integration::STATUS_ACTIVE) {
                throw new AuthorizationException(__('Coupon creation is not permitted.'));
            }
        }

        // Resolve the role from the validated token, never from a storefront session.
        $role = $this->roleCollectionFactory->create()
            ->setUserFilter($context->getUserId(), $context->getUserType())
            ->getFirstItem();
        if (!$role->getId() || !$this->aclPolicy->isAllowed($role->getId(), self::RESOURCE)) {
            throw new AuthorizationException(__('Coupon creation is not permitted.'));
        }
    }
}
